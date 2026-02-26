<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use DateTime;
use FastRoute\Dispatcher;
use Hyperf\Context\RequestContext;
use Hyperf\Context\ResponseContext;
use Hyperf\Contract\Arrayable;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\HttpServer\Event\RequestTerminated;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Logger\LoggerFactory;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Swow\Psr7\Message\ResponsePlusInterface;
use Verdient\cli\Console;

use function Hyperf\Config\config;

/**
 * 请求终止监听器
 *
 * @author Verdient。
 */
class RequestTerminatedListener implements ListenerInterface
{
    /**
     * 是否打印访问日志
     *
     * @author Verdient。
     */
    protected bool $printAccessLog = false;

    /**
     * 是否记录访问日志
     *
     * @author Verdient。
     */
    protected bool $logAccessLog = false;

    /**
     * 记录器
     *
     * @author Verdient。
     */
    protected LoggerInterface $logger;

    /**
     * 是否启用
     *
     * @author Verdient。
     */
    protected bool $isEnabled = false;

    /**
     * 隐藏的字段
     *
     * @author Verdient。
     */
    protected array $hidden = [];

    /**
     * 构造函数
     *
     * @author Verdient。
     */
    public function __construct(ContainerInterface $container)
    {
        $this->printAccessLog = config('dev.access.print', false);
        $this->logAccessLog = config('dev.access.log.enable', false);

        if ($this->logAccessLog) {
            $this->logger = $container->get(LoggerFactory::class)->get(RequestTerminatedListener::class, RequestTerminatedListener::class);
            $this->hidden = config('dev.access.log.hidden', []);
        }

        $this->isEnabled = $this->printAccessLog || $this->logAccessLog;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function listen(): array
    {
        return [
            RequestTerminated::class,
        ];
    }

    /**
     * @param RequestTerminated $event
     *
     * @author Verdient。
     */
    #[Override]
    public function process($event): void
    {
        if (!$this->isEnabled) {
            return;
        }

        if (!($event instanceof RequestTerminated)) {
            return;
        }

        $request = RequestContext::get();

        $dispatched = $request->getAttribute(Dispatched::class);

        if (!$dispatched || $dispatched->status !== Dispatcher::FOUND) {
            return;
        }

        $response = ResponseContext::get();

        $serverParams = $request->getServerParams();
        $timeCost = round((microtime(true) - $serverParams['request_time_float']) * 1000, 2);

        if ($this->printAccessLog) {
            $this->print($request, $timeCost);
        }

        if ($this->logAccessLog) {
            $this->log($request, $response, $timeCost);
        }
    }

    /**
     * 打印访问日志
     *
     * @param ServerRequestInterface $request 请求
     * @param float $timeCost 时间消耗
     *
     * @author Verdient。
     */
    protected function print(ServerRequestInterface $request, float $timeCost)
    {
        Console::output(implode(' ', [
            Console::colour((new DateTime('now'))->format('Y-m-d H:i:s'), Console::FG_YELLOW),
            Console::colour('[' . $timeCost . ' ms]', Console::FG_YELLOW, Console::BOLD),
            Console::colour('[' . $request->getMethod() . ']', Console::FG_GREEN),
            (string) $request->getUri()
        ]));

        Console::stdout(PHP_EOL);
    }

    /**
     * 记录访问信息到日志
     *
     * @param ServerRequestInterface $request 请求
     * @param ResponsePlusInterface $response 响应
     * @param float $timeCost 时间消耗
     *
     * @author Verdient。
     */
    protected function log(ServerRequestInterface $request, ResponsePlusInterface $response, float $timeCost)
    {
        $statusCode = $response->getStatusCode();

        if (
            $statusCode < 200
            || $statusCode >= 300
        ) {
            return;
        }

        $path = $request->getUri()->getPath();

        if (empty($path)) {
            $path = '/';
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        $parts = ['[' . $timeCost . ' ms] [' .  $request->getMethod() . '] ' . $path];

        if ($user = $this->user($request)) {
            $parts[] = 'USER ' . $user;
        }

        if ($query = $this->query($request)) {
            $parts[] = 'QUERY ' . $query;
        }

        if ($body = $this->body($request)) {
            $parts[] = 'BODY ' . $body;
        }

        if ($responseContent = $this->response($request, $response)) {
            $parts[] = 'RESPONSE ' . $responseContent;
        }

        $message = implode(PHP_EOL, $parts);

        if ($timeCost > 10000) {
            return $this->logger->warning($message);
        }

        if ($timeCost > 5000) {
            return $this->logger->notice($message);
        }

        $this->logger->info($message);
    }

    /**
     * 用户
     *
     * @param ServerRequestInterface $request 请求对象
     *
     * @author Verdient。
     */
    protected function user(ServerRequestInterface $request): ?string
    {
        if ($credential = $request->getAttribute('Verdient\Hyperf3\AccessControl\Credential')) {
            if ($identity = $credential->identity()) {
                return (string) $identity->getIdentifier();
            }
        }
        return null;
    }

    /**
     * 查询参数
     *
     * @param ServerRequestInterface $request 请求对象
     *
     * @author Verdient。
     */
    protected function query(ServerRequestInterface $request): ?string
    {
        $queryParams = $request->getQueryParams();

        if (empty($queryParams)) {
            return null;
        }

        foreach ($this->hidden as $name) {
            if (isset($queryParams[$name])) {
                $queryParams[$name] = '<hidden>';
            }
        }

        return json_encode($this->hiddenValue($queryParams), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 消息体参数
     *
     * @param ServerRequestInterface $request 请求对象
     *
     * @author Verdient。
     */
    protected function body(ServerRequestInterface $request): ?string
    {
        if ($request->getBody()->getSize() > 1024) {
            return '["<entity too large>"]';
        }

        $bodyParams = $request->getParsedBody();

        if (empty($bodyParams)) {
            return null;
        }

        return json_encode($this->hiddenValue($bodyParams), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 响应
     *
     * @param ServerRequestInterface $request 请求对象
     * @param ResponsePlusInterface $response 响应对象
     *
     * @author Verdient。
     */
    protected function response(ServerRequestInterface $request, ResponsePlusInterface $response): ?string
    {
        if ($response->getBody()->getSize() === 0) {
            return null;
        }

        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return '<ignore>';
        }

        if (strlen((string) $response->getBody()->getSize()) > 1024) {
            return '["<entity too large>"]';
        };

        $data = ResponseDataContext::get();

        if ($data === null) {
            return null;
        }

        if (is_bool($data)) {
            return $data ? '<true>' : '<false>';
        }

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        if (is_array($data)) {
            return json_encode($this->hiddenValue($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $response->getBody();
    }

    /**
     * 隐藏值
     *
     * @param array $data
     * @author Verdient。
     */
    protected function hiddenValue(array $data): array
    {
        foreach ($this->hidden as $name) {
            if (isset($data[$name])) {
                $data[$name] = '<hidden>';
            }
        }

        foreach ($data as $key => $value) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            if (is_array($value)) {
                $data[$key] = $this->hiddenValue($value);
            }
        }

        return $data;
    }
}

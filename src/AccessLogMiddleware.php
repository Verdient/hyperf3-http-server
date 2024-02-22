<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Verdient\cli\Console;

use function Hyperf\Config\config;

/**
 * 访问日志中间件
 * @author Verdient。
 */
class AccessLogMiddleware implements MiddlewareInterface
{
    /**
     * 是否打印访问日志
     * @author Verdient。
     */
    protected bool $printAccessLog;

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function __construct()
    {
        $this->printAccessLog = config('print_access_log');
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } finally {
            $this->log($request);
        }
    }

    /**
     * 记录访问日志
     * @param ServerRequestInterface $request 请求
     * @author Verdient。
     */
    protected function log(ServerRequestInterface $request)
    {
        if ($this->printAccessLog) {
            $serverParams = $request->getServerParams();
            $uri = $request->getUri();
            $timeCost = round((microtime(true) - $serverParams['request_time_float']) * 1000, 2);
            Console::output(implode(' ', [
                date('Y-m-d H:i:s', $serverParams['request_time']),
                Console::colour('[' . $timeCost . ' ms]', Console::FG_YELLOW, Console::BOLD),
                Console::colour($request->getMethod(), Console::FG_GREEN),
                (string) $uri
            ]));
            Console::stdout(PHP_EOL);
        }
    }
}

<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\Context\RequestContext;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\HttpServer\Event\RequestTerminated;
use Override;
use Verdient\Hyperf3\Di\Container;

/**
 * 请求终止监听器
 *
 * @author Verdient。
 */
class RequestTerminatedListener implements ListenerInterface
{
    /**
     * 请求记录器
     *
     * @author Verdient。
     */
    protected ?RequestLoggerInterface $logger = null;

    /**
     * 构造函数
     *
     * @author Verdient。
     */
    public function __construct()
    {
        $this->logger = Container::getOrNull(RequestLoggerInterface::class);
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
        $this->logger?->log(RequestContext::get());
    }
}

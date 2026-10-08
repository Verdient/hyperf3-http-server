<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 请求记录器接口
 *
 * @author Verdient。
 */
interface RequestLoggerInterface
{
    /**
     * @param ServerRequestInterface $request 请求
     *
     * @author Verdient。
     */
    public function log(ServerRequestInterface $request): void;
}

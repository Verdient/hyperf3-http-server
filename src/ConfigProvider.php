<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\HttpServer\Router\DispatcherFactory;
use Verdient\Hyperf3\HttpServer\DispatcherFactory as HttpServerDispatcherFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                DispatcherFactory::class => HttpServerDispatcherFactory::class,
            ]
        ];
    }
}

<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\HttpServer\CoreMiddleware;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Verdient\Hyperf3\HttpServer\CoreMiddleware as HttpServerCoreMiddleware;
use Verdient\Hyperf3\HttpServer\DispatcherFactory as HttpServerDispatcherFactory;
use Verdient\Hyperf3\HttpServer\RequestTerminatedListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                DispatcherFactory::class => HttpServerDispatcherFactory::class,
                CoreMiddleware::class => HttpServerCoreMiddleware::class,
            ],
            'listeners' => [
                RequestTerminatedListener::class
            ],
            'logger' => [
                RequestTerminatedListener::class => [
                    'handler' => [
                        'class' => \Monolog\Handler\RotatingFileHandler::class,
                        'constructor' => [
                            'filename' => BASE_PATH . '/runtime/logs/access/.log',
                            'level' => \Monolog\Level::Info,
                            'filenameFormat' => '{date}'
                        ],
                    ],
                    'formatter' => [
                        'class' => \Monolog\Formatter\LineFormatter::class,
                        'constructor' => [
                            'format' => "[%datetime%] [%level_name%] %message%\n",
                            'dateFormat' => 'Y-m-d H:i:s',
                            'allowInlineLineBreaks' => true,
                        ],
                    ],
                ]
            ]
        ];
    }
}

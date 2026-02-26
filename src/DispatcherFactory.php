<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PatchMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Router\DispatcherFactory as RouterDispatcherFactory;
use Hyperf\Stringable\Str;
use Override;
use ReflectionMethod;

/**
 * @inheritdoc
 *
 * @author Verdient。
 */
class DispatcherFactory extends RouterDispatcherFactory
{
    /**
     * @author Verdient。
     */
    #[Override]
    protected function getPrefix(string $className, string $prefix): string
    {
        if (!$prefix) {
            $classNameParts = explode('\\', $className);
            $className = $classNameParts[count($classNameParts) - 1];
            $handledNamespace = Str::replaceLast('Controller', '', $className);
            $prefix = '/' . Str::snake($handledNamespace, '-');
        } else if ($prefix[0] !== '/') {
            $prefix = '/' . $prefix;
        }
        return $prefix;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function handleController(string $className, Controller $annotation, array $methodMetadata, array $middlewares = []): void
    {
        $mappingAnnotations = [
            RequestMapping::class,
            GetMapping::class,
            PostMapping::class,
            PutMapping::class,
            PatchMapping::class,
            DeleteMapping::class,
        ];
        foreach ($methodMetadata as $methodName => $values) {
            foreach ($mappingAnnotations as $mappingAnnotation) {
                if ($mapping = $values[$mappingAnnotation] ?? null) {
                    if (!isset($mapping->path)) {
                        $mapping->path = Str::snake($methodName, '-');
                    }
                }
            }
        }
        parent::handleController($className, $annotation, $methodMetadata, $middlewares);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function parsePath(string $prefix, ReflectionMethod $method): string
    {
        return $prefix . '/' . Str::snake($method->getName(), '-');
    }
}

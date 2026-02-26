<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\Context\Context;

/**
 * 响应数据上下文
 *
 * @author Verdient。
 */
class ResponseDataContext
{
    /**
     * 获取响应数据
     *
     * @param ?int $coroutineId 协程ID
     *
     * @author Verdient。
     */
    public static function get(?int $coroutineId = null): mixed
    {
        return Context::get(static::class, null, $coroutineId);
    }

    /**
     * 设置响应数据
     *
     * @param mixed $response 响应数据
     * @param ?int $coroutineId 协程ID
     *
     * @author Verdient。
     */
    public static function set(mixed $data, ?int $coroutineId = null): mixed
    {
        return Context::set(static::class, $data, $coroutineId);
    }
}

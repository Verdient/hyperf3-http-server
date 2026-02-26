<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

/**
 * 请求方法
 *
 * @author Verdient。
 */
enum Method
{
    case GET;
    case POST;
    case PUT;
    case PATCH;
    case DELETE;
    case HEAD;
    case OPTIONS;
}

<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\Context\ApplicationContext;
use Hyperf\HttpMessage\Uri\Uri;
use Hyperf\HttpServer\Contract\RequestInterface;
use function Hyperf\Config\config;

/**
 * 路由
 * @author Verdient。
 */
class Router
{
    /**
     * 获取基础URL
     * @param string $server 服务器名称
     * @return string|null
     * @author Verdient。
     */
    public static function baseUrl($endpoint = 'api'): string|null
    {
        $configName = strtolower($endpoint) . '_endpoint';
        if ($baseUrl = config($configName)) {
            return $baseUrl;
        }
        if (ApplicationContext::hasContainer()) {
            /** @var RequestInterface */
            $request = ApplicationContext::getContainer()->get(RequestInterface::class);
            if ($scheme = $request->getHeaderLine('X-Forwarded-Scheme')) {
                if ($host = $request->getHeaderLine('X-Forwarded-Host')) {
                    return $scheme . '://' . $host;
                }
            }
            $uri = $request->getUri();
            return Uri::composeComponents($uri->getScheme(), $uri->getAuthority(), '', '', '');
        }
        return null;
    }

    /**
     * 生成访问地址
     * @param string $path 访问路径
     * @param string $server 服务器名称
     * @return string
     * @author Verdient。
     */
    public static function to(string $path, $endpoint = 'api'): string
    {
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }
        return static::baseUrl($endpoint) . $path;
    }
}

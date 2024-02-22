<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\Context\Context;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Router\Dispatched;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * CORS中间件
 * @author Verdient。
 */
class CorsMiddleware implements MiddlewareInterface
{
    const DEFAULTS = [
        'origin' => '*',
        'headers' => '*',
        'methods' => '*',
        'credentials' => true,
        'exposeHeaders' => '*',
        'maxAge' => 86400
    ];

    /**
     * 配置
     * @author Verdient。
     */
    protected array $configs = [];

    /**
     * @author Verdient。
     */
    public function __construct(protected ConfigInterface $config)
    {
        $this->configs = $this->config->get('cors', []);
    }

    /**
     * 获取配置
     * @param ServerRequestInterface $request 请求
     * @param string $name 配置名称
     * @return string|int|bool|string[]
     * @author Verdient。
     */
    protected function getConfig(ServerRequestInterface $request, string $name): string|int|bool|array
    {
        if ($request->getAttribute('serverName')) {
            $serverName = $request->getAttribute('serverName');
        } else {
            if ($dispatched = $request->getAttribute(Dispatched::class)) {
                $serverName = $dispatched->serverName;
            } else {
                $serverName = null;
            }
        }
        if (
            $serverName
            && array_key_exists($serverName, $this->configs)
            && array_key_exists($name, $this->configs[$serverName])
        ) {
            return $this->configs[$serverName][$name];
        }
        return static::DEFAULTS[$name];
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isCorsRequest($request)) {
            return $handler->handle($request);
        }

        $response = Context::get(ResponseInterface::class);

        $response = $this->allowOrigin($response, $this->getConfig($request, 'origin'), $request);
        $response = $this->allowMethods($response, $this->getConfig($request, 'methods'), $request);
        $response = $this->allowHeaders($response, $this->getConfig($request, 'headers'), $request);
        $response = $this->allowCredentials($response, $this->getConfig($request, 'credentials'));
        $response = $this->allowMaxAge($response, $this->getConfig($request, 'maxAge'));

        if ($this->isPreflight($request)) {
            return $this->allowExposeHeaders($response, $this->getConfig($request, 'exposeHeaders'));
        }

        Context::set(ResponseInterface::class, $response);

        try {
            return $this->allowExposeHeaders($handler->handle($request), $this->getConfig($request, 'exposeHeaders'));;
        } catch (Throwable $e) {
            Context::set(ResponseInterface::class, $this->allowExposeHeaders(Context::get(ResponseInterface::class), $this->getConfig($request, 'exposeHeaders')));
            throw $e;
        }
    }

    /**
     * 是否是CORS请求
     * @param ServerRequestInterface $request 请求对象
     * @return bool
     * @author Verdient。
     */
    protected function isCorsRequest($request): bool
    {
        return $request->hasHeader('Origin');
    }

    /**
     * 是否是预检请求
     * @param ServerRequestInterface $request 请求对象
     * @return bool
     * @author Verdient。
     */
    protected function isPreflight($request): bool
    {
        return $request->getMethod() == 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
    }

    /**
     * 允许域
     * @param ResponseInterface $response 响应对象
     * @param string|string[] $origin 域
     * @return ResponseInterface
     * @author Verdient。
     */
    protected function allowOrigin(ResponseInterface $response, string|array $origin, ServerRequestInterface $request): ResponseInterface
    {
        if (!$response->hasHeader('Access-Control-Allow-Origin')) {
            $requestOrigin = $request->getHeaderLine('Origin');
            if (is_string($origin)) {
                if ($origin === '*') {
                    return $response->withHeader('Access-Control-Allow-Origin', $requestOrigin ?: '*');
                } else if ($origin === $requestOrigin) {
                    return $response->withHeader('Access-Control-Allow-Origin', $requestOrigin);
                }
            }
            if (is_array($origin) && in_array($requestOrigin, $origin)) {
                return $response->withHeader('Access-Control-Allow-Origin', $requestOrigin);
            }
        }
        return $response;
    }

    /**
     * 允许方法
     * @param ResponseInterface $response 响应对象
     * @param string|string[] $methods 请求方法
     * @param ServerRequestInterface $request 请求对象
     * @return ResponseInterface
     * @author Verdient。
     */
    protected function allowMethods(ResponseInterface $response, string|array $methods, ServerRequestInterface $request): ResponseInterface
    {
        if (!$response->hasHeader('Access-Control-Allow-Methods')) {

            if ($this->isPreflight($request)) {
                $requestMethod = strtoupper($request->getHeaderLine('Access-Control-Request-Method'));
            } else {
                $requestMethod = strtoupper($request->getMethod());
            }

            if (is_string($methods)) {
                if ($methods === '*') {
                    return $response->withHeader('Access-Control-Allow-Methods', $requestMethod);
                } else {
                    if ($requestMethod === strtoupper($methods)) {
                        return $response->withHeader('Access-Control-Allow-Methods', $requestMethod);
                    }
                }
            }

            if (is_array($methods)) {
                if (in_array($requestMethod, array_map('strtoupper', $methods))) {
                    return $response->withHeader('Access-Control-Allow-Methods', $requestMethod);
                }
            }
        }
        return $response;
    }

    /**
     * 允许头部
     * @param ResponseInterface $response 响应对象
     * @param string|array $headers 头部
     * @param ServerRequestInterface $request 请求对象
     * @return ResponseInterface
     * @author Verdient。
     */
    protected function allowHeaders(ResponseInterface $response, string|array $headers, ServerRequestInterface $request): ResponseInterface
    {
        if (!$response->hasHeader('Access-Control-Allow-Headers')) {
            $requestHeaders = [];

            if ($this->isPreflight($request)) {
                $requestHeadersLine = $request->getHeaderLine('Access-Control-Request-Headers');
                if (!empty($requestHeadersLine)) {
                    $requestHeaders = array_map('strtolower', explode(',', $requestHeadersLine));
                }
            } else {
                $requestHeaders = array_change_key_case($request->getHeaders(), CASE_LOWER);
                foreach ([
                    'accept', 'accept-language', 'content-language', 'dpr',
                    'downlink', 'save-data', 'viewport-width', 'width', 'host', 'connection',
                    'pragma', 'cache-control', 'sec-ch-ua', 'sec-ch-ua-mobile', 'user-agent', 'origin',
                    'sec-fetch-site', 'sec-fetch-mode', 'sec-fetch-dest', 'accept-encoding'
                ] as $name) {
                    unset($requestHeaders[$name]);
                }
                $requestHeaders = array_keys($requestHeaders);
            }

            $requestHeaders = array_map([$this, 'normalizeHeaderName'], $requestHeaders);

            if (is_string($headers)) {
                if ($headers === '*') {
                    if (!empty($requestHeaders)) {
                        return $response->withHeader('Access-Control-Allow-Headers', implode(',', $requestHeaders));
                    }
                } else {
                    $headers = $this->normalizeHeaderName($headers);
                    if (in_array($headers, $requestHeaders)) {
                        return $response->withHeader('Access-Control-Allow-Headers', $headers);
                    }
                }
            }

            if (is_array($headers)) {
                $headers = array_map([$this, 'normalizeHeaderName'], $headers);
                $allowHeaders = array_intersect($requestHeaders, $headers);
                if (!empty($allowHeaders)) {
                    return $response->withHeader('Access-Control-Allow-Headers', implode(',', $allowHeaders));
                }
            }
        }

        return $response;
    }

    /**
     * 允许携带凭证
     * @param ResponseInterface $response 响应对象
     * @param bool $credentials 是否允许携带凭证
     * @return ResponseInterface
     * @author Verdient。
     */
    protected function allowCredentials($response, bool $credentials): ResponseInterface
    {
        if (!$response->hasHeader('Access-Control-Allow-Credentials')) {
            return $response->withHeader('Access-Control-Allow-Credentials', $credentials ? 'true' : 'false');
        }
        return $response;
    }

    /**
     * 允许暴露额外的头部
     * @param ResponseInterface $response 响应对象
     * @param string|array $exposeHeaders 额外暴露的头部
     * @return ResponseInterface
     * @author Verdient。
     */
    protected function allowExposeHeaders($response, string|array $exposeHeaders): ResponseInterface
    {
        if (!$response->hasHeader('Access-Control-Expose-Headers')) {

            $responseHeaders = array_change_key_case($response->getHeaders(), CASE_LOWER);

            foreach ([
                'access-control-allow-origin', 'access-control-allow-headers', 'access-control-allow-methods',
                'access-control-allow-credentials', 'access-control-max-age', 'content-type', 'server', 'connection',
                'date', 'content-length', 'content-encoding'
            ] as $name) {
                unset($responseHeaders[$name]);
            }

            $responseHeaders = array_map([$this, 'normalizeHeaderName'], array_keys($responseHeaders));

            if (!empty($responseHeaders)) {
                if (is_string($exposeHeaders)) {
                    if ($exposeHeaders === '*') {
                        return $response->withHeader('Access-Control-Expose-Headers', implode(',', $responseHeaders));
                    } else {
                        $exposeHeaders = $this->normalizeHeaderName($exposeHeaders);
                        if (in_array($exposeHeaders, $responseHeaders)) {
                            return $response->withHeader('Access-Control-Expose-Headers', $exposeHeaders);
                        }
                    }
                }
                if (is_array($exposeHeaders)) {
                    $exposeHeaders = array_map([$this, 'normalizeHeaderName'], $exposeHeaders);

                    $allowExposeHeaders = array_intersect($responseHeaders, $exposeHeaders);
                    if (!empty($allowExposeHeaders)) {
                        return $response->withHeader('Access-Control-Expose-Headers', implode(',', $allowExposeHeaders));
                    }
                }
            }
        }

        return $response;
    }

    /**
     * 允许最大缓存时间
     * @param ResponseInterface $response 响应对象
     * @param int $maxAge 最大缓存时间
     * @return ResponseInterface
     * @author Verdient。
     */
    protected function allowMaxAge(ResponseInterface $response, int $maxAge): ResponseInterface
    {
        if (!$response->hasHeader('Access-Control-Max-Age') && $maxAge > 0) {
            return $response->withHeader('Access-Control-Max-Age', $maxAge);
        }
        return $response;
    }

    /**
     * 格式化头部名称
     * @param string $name 头部名称
     * @return string
     * @author Verdient。
     */
    protected function normalizeHeaderName(string $name): string
    {
        return implode('-', array_map(function ($value) {
            return ucfirst(strtolower($value));
        }, explode('-', $name)));
    }
}

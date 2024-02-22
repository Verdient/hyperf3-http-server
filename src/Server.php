<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\HttpServer\Server as HttpServerServer;

/**
 * @inheritdoc
 * @author Verdient。
 */
class Server extends HttpServerServer
{
    /**
     * @inheritdoc
     * @author Verdient。
     */
    protected function initRequestAndResponse($request, $response): array
    {
        if (isset($request->header['host'])) {
            if ($host = $this->normalizeHost($request->header['host'])) {
                $request->header['host'] = $host;
            } else {
                unset($request->header['host']);
            }
        }
        [$request, $response] = parent::initRequestAndResponse($request, $response);
        return [$request, $response];
    }

    /**
     * 格式化主机名
     * @param string $host 主机名
     * @return string|false
     * @author Verdient。
     */
    public function normalizeHost($host)
    {
        if (!$parsedUrl = parse_url($host)) {
            return false;
        }
        if (!isset($parsedUrl['host'])) {
            return false;
        }
        $result = $parsedUrl['host'];
        if (isset($parsedUrl['port']) && ($parsedUrl['port'] > 0 && $parsedUrl['port'] < 65535)) {
            $result .= ':' . $parsedUrl['port'];
        }
        return $result;
    }
}

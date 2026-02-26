<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\Contract\Arrayable;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\CoreMiddleware as HttpServerCoreMiddleware;
use Hyperf\HttpServer\Router\Dispatched;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Verdient\Hyperf3\Di\Container as DiContainer;

/**
 * 核心中间件
 *
 * @author Verdient。
 */
class CoreMiddleware extends HttpServerCoreMiddleware
{
    /**
     * @author Verdient。
     */
    #[Override]
    protected function handleFound(Dispatched $dispatched, ServerRequestInterface $request): mixed
    {
        $result = parent::handleFound($dispatched, $request);

        if ($result instanceof DataBag) {
            $responseData = $result->toArray();
            if ($result->isFailed) {
                $response = DiContainer::get(ResponseInterface::class)
                    ->json($responseData)
                    ->withStatus($result->code);
            } else {
                $response = $responseData;
            }
        } else if ($result instanceof Arrayable) {
            $response = $responseData = $result->toArray();
        } else {
            $response = $responseData = $result;
        }

        ResponseDataContext::set($responseData);

        return $response;
    }
}

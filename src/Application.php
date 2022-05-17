<?php

declare(strict_types=1);

namespace MattHarvey\Auraxx;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Forms the outermost layer of the application, accepting a request and returning a
 * response.
 *
 * The consumer of this class is responsible for constructing that request and emitting
 * that response.
 *
 * Application class arranges for routing, middleware execution, controller instantiation,
 * and passing to the appropriate controller method.
 */
final class Application implements RequestHandlerInterface
{
    /**
     * @param ContainerInterface $container Must be able to provide instances of the following,
     *   e.g. `$container->get(Auraxx\Router::class)` must return an instance of `Auraxx\Router`:
     *   * Auraxx\Router
     *   * Psr\Log\LoggerInterface
     *   * Any middleware referred to in the router setup
     *   * Any controller referred to in the router setup
     * @param string $controllerAttributeKey The key at which, within the request attributes,
     *   the router should store the controller instance.
     * @param string $permittedRolesAttributeKey The key at which, within the request attributes,
     *   the router should store the roles that are permitted to access the resolved route.
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $controllerAttributeKey = 'controller',
        private readonly string $permittedRolesAttributeKey = 'permittedRoles',
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $router = $this->container->get(Router::class);
        $action = $router->createAction($this->container, $request);
        $request = $request
            ->withAttribute($this->controllerAttributeKey, $action->getController())
            ->withAttribute($this->permittedRolesAttributeKey, $action->getPermittedRoles());
        return $action->handle($request);
    }
}

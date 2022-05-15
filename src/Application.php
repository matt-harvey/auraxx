<?php

declare(strict_types=1);

namespace MattHarvey\Auraxx;

use Lib\Http\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Forms the outermost layer of the application.
 *
 * TODO Document this.
 */
final class Application implements RequestHandlerInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $controllerAttributeKey,
        private readonly string $permittedRolesAttributeKey,
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

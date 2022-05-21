<?php

declare(strict_types=1);

namespace TestUtil\Factory;

use MattHarvey\Auraxx\Action;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;

class ActionFactory
{
    /**
     * These constructor parameters determine the constructor parameters that will by default be
     * passed when creating an Action.
     *
     * @param Array<string, string> $routeParameters
     * @param Array<string>|null $permittedRoles
     * @param Array<MiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly mixed $controller,
        private readonly string $controllerMethod,
        private readonly array $routeParameters,
        private readonly ?array $permittedRoles,
        private readonly array $middlewares,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function createAction(array $overrides = []): Action
    {
        return new Action(
            controller: $this->getParam('controller', $overrides),
            controllerMethod: $this->getParam('controllerMethod', $overrides),
            routeParameters: $this->getParam('routeParameters', $overrides),
            permittedRoles: $this->getParam('permittedRoles', $overrides),
            middlewares: $this->getParam('middlewares', $overrides),
            logger: $this->getParam('logger', $overrides),
        );
    }

    private function getParam(string $name, array $overrides): mixed
    {
        if (array_key_exists($name, $overrides)) {
            return $overrides[$name];
        }
        return $this->$name;
    }
}


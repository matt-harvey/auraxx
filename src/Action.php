<?php

declare(strict_types=1);

namespace MattHarvey\Auraxx;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * @internal
 *
 * Represents a middleware chain, a controller, and a controller method.
 * Handles a request after the routing layer has determined which of these
 * apply.
 */
class Action implements RequestHandlerInterface
{
    /** @var Array<MiddlewareInterface> $middlewares we be called in LIFO order */
    private array $middlewares;

    /**
     * @param Array<string, string> $routeParameters route parameters from the matched route
     * @param Array<string>|null $permittedRoles represents the roles that are permitted
     *   to access this action, i.e. a user must have at least one of these roles to access it.
     *   If this is passed null, then there is no role requirement to access the action.
     * @param Array<MiddlewareInterface> $middlewares pass this in the order you want them to be
     *   called
     */
    public function __construct(
        private readonly mixed $controller,
        private readonly string $controllerMethod,
        private readonly array $routeParameters,
        private readonly ?array $permittedRoles,
        array $middlewares,
        private readonly ?LoggerInterface $logger,
    )
    {
        $this->middlewares = array_reverse($middlewares);
    }

    public function getController(): mixed
    {
        return $this->controller;
    }

    /** @return array<string>|null */
    public function getPermittedRoles(): ?array
    {
        return $this->permittedRoles;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->middlewares) == 0) {

            // Last middleware has been called; so pass handling the controller method.
            $controller = $this->controller;
            $controllerMethod = $this->controllerMethod;
            $controllerClass = get_class($controller);
            if ($this->logger !== null) {
                $this->logger->info("Dispatching to {$controllerClass}::{$controllerMethod}");
            }

            // Pass the request, along with matched route parameters to corresponding controller method
            // parameters.
            $controllerMethodReflection = new ReflectionMethod($controllerClass, $controllerMethod);
            $controllerMethodParameters = $controllerMethodReflection->getParameters();
            $controllerMethodArgs = [];
            foreach ($controllerMethodParameters as $controllerMethodParameter) {
                $controllerMethodParameterName = $controllerMethodParameter->getName();
                $controllerMethodParameterType = $controllerMethodParameter->getType();

                if ($controllerMethodParameterType === null) {
                    $controllerMethodArgs[] = ($this->routeParameters[$controllerMethodParameterName] ?? null);
                } elseif (is_a($controllerMethodParameterType, ReflectionNamedType::class)) {
                    $parameterTypeName = $controllerMethodParameterType->getName();
                    switch ($parameterTypeName) {
                    case ServerRequestInterface::class:
                        $controllerMethodArgs[] = $request;
                        break;
                    case 'string':
                        $controllerMethodArgs[] = ($this->routeParameters[$controllerMethodParameterName] ?? null);
                        break;
                    case 'int':
                        $value = filter_var(
                            $this->routeParameters[$controllerMethodParameterName] ?? null,
                            FILTER_VALIDATE_INT,
                        );
                        if ($value === false) {
                            // Throw Exception, as this is not a 422: the router should only have matched
                            // if the route param is parseable as an integer.
                            throw new Exception(
                                "Controller method parameter $controllerMethodParameterName " .
                                "expected an integer, but the received route parameter could not be " .
                                "parsed as such"
                            );
                        }
                        $controllerMethodArgs[] = $value;
                        break;
                    default:
                        throw new Exception("Unexpected controller parameter type $parameterTypeName");
                    }
                } else {
                    throw new Exception('Unexpected controller parameter type');
                }
            }

            return $controller->$controllerMethod(...$controllerMethodArgs);
        }
        $middleware = array_pop($this->middlewares);
        return $middleware->process($request, $this);
    }
}

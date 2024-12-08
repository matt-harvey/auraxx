<?php

declare(strict_types=1);

namespace MattHarvey\Auraxx;

use Aura\Router\Map;
use Aura\Router\RouterContainer;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;

/**
 * Router is a wrapper around the Aura library's RouterContainer. It is designed to be extended
 * by an application-specific router that defines the actual routes, and then used
 * in conjunction with an Application instance for actually handling a request.
 *
 * Router adds certain functionality to the Aura router:
 * * Allows default middleware to be defined globally, by overriding `::getDefaultMiddlewares`,
 *   such that middleware order can be configured independently of per-route middleware
 *   applicability
 * * A `::generateUri` method for generating a `UriInterface` instance including optional
 *   query data and fragment
 * * A convention whereby the route name will, by default, determine the controller and method
 *   that will be called when that route is resolved (after the middleware layer has been
 *   traversed). For example, the following route definition will result in
 *   `App\Controller\Admin\DashboardController::index` method handling the request:
 *
 *   ```
 *   $map->get('admin.dashboard.index', '/admin')
 *   ```
 * * Automatic injection of string or integer route parameters into corresponding controller
 *   method parameters based on matching parameter name and type.
 */
abstract class Router
{
    private readonly RouterContainer $routerContainer;

    /*
     * @param string $fallbackRouteName the name of the route that you want otherwise unmatched
     *   requests to fall back to. (Generally, this should map to a controller method that you have
     *   arranged to respond with a 404.)
     * @param array<string> $controllerNamespace An array of strings that will be joined to
     *   determine the namespace in which controller classes will live when using route names to
     *   determine which controller handles a given route. For example, if passed
     *   `['App', 'Controller']`, then a route name like `admin.userManagement.index` will map
     *   to the controller `App\Controller\Admin\UserManagementController` and the controller
     *   method `::index`.
     */
    public function __construct(
        protected readonly UriFactoryInterface $uriFactory,
        private readonly string $fallbackRouteName = 'error.notFound',
        private readonly array $controllerNamespace = ['App', 'Controller'],
    )
    {
        $routerContainer = new RouterContainer();
        $routerContainer->setRouteFactory(fn () => new Route);
        $map = $routerContainer->getMap();
        $map->permittedRoles(null); // By default, no particular role is required for a user to access routes.
        $this->routerContainer = $routerContainer;
        $this->configureRoutes($map);
    }

    /**
     * @param callable $loggerFactory will be passed to \Aura\Router\RouterContainer::setLoggerFactory.
     *   If set, this will perform *detailed* logging of the router matching as determined by the underlying
     *   `Aura` router. You would normally not set this unless performing diagnostic in your
     *   development environment, as it logs very verbosely.
     */
    public function setLoggerFactory(callable $loggerFactory): void
    {
        $this->routerContainer->setLoggerFactory($loggerFactory);
    }

    /**
     * This method should be defined in the inheriting class to configure the actual routes for the
     * application. See the Route class for additional methods that can be configured per route or
     * route group. Otherwise, $map should be treated like an instance of `\Aura\Router\Map`.
     *
     * For example:
     *
     * ```
     *  protected function configureRoutes(Map $map): void
     *  {
     *      $map->get('home.index', '/');
     *      $map->get('admin.user, '/admin/users')->permittedRoles(['admin']);
     *      $map->get('authentication.signIn', '/sign-in')->middleware([AuthenticationMiddleware::class => false]);
     *      // etc.
     *  }
     * ```
     */
    abstract protected function configureRoutes(Map $map): void;

    /**
     * @return array<string, bool> $defaultMiddlewares
     *
     * This should be defined in the inheriting class to configure the order of middlewares (outside-to-in),
     * and the default applicability of each middleware.
     *
     * For example:
     *
     * ```
     * protected function getDefaultMiddlewares(): array {
     *    return [
     *        ErrorHandlerMiddleware::class => true,
     *        BodyParserMiddleware::class => true,
     *        CsrfMiddleware::class => true,
     *        RateLimiterMiddleware::class => false,
     *        // etc.
     *    ];
     *  }
     * ```
     *
     * The middlewares will _always_ run in the order defined by this method; however, middlewares
     * set to `false` will be skipped. The applicability (`true`/`false`) of each middleware defined
     * here, can be overridden per route or route group. E.g., in this example, to toggle the rate limiter
     * _on_ for a particular route, while skipping the authentication middleware, you might do the following
     * within your `::configureRoutes` method:
     *
     * ```
     * $map->get('authentication.signIn', '/sign-in')->middleware([
     *     RateLimiterMiddleware::class => true,
     *     AuthenticationMiddleware::class => false, // you shouldn't have to be signed in to sign in!
     * ]);
     * ```
     *
     * In this example, all the other middleware marked `true` in the defaults (`ErrorHandlerMiddleware` etc.)
     * will still run on this route, however `RateLimiterMiddleware` will also run (just after
     * `CsrfMiddleware`), while `AuthenticationMiddleware` will be skipped.
     */
    abstract protected function getDefaultMiddlewares(): array;

    public function getRoute(string $routeName): Route
    {
        $route = $this->routerContainer->getMap()->getRoute($routeName);
        if (!is_a($route, Route::class)) {
            throw new Exception('Route is of unexpected type');
        }
        return $route;
    }

    /** @return array<mixed> */
    private function controllerAndMethodForRoute(ContainerInterface $container, Route $route): array
    {
        $handler = $route->handler;
        if (!is_string($handler)) {
            throw new Exception('Unexpected route handler type');
        }
        return $this->handlerToControllerAndMethod($container, $handler);
    }

    /** @return array<MiddlewareInterface> */
    private function middlewaresForRoute(
        ContainerInterface $container,
        Route $route,
    ): array
    {
        $defaultMiddlewares = $this->getDefaultMiddlewares();
        $middlewareClasses = array_merge($defaultMiddlewares, $route->middleware);
        $middlewares = [];
        foreach ($middlewareClasses as $middlewareClass => $applicable) {
            if ($applicable) {
                $middlewares[] = $container->get($middlewareClass);
            }
        }
        return $middlewares;
    }

    /** @return array<mixed> */
    private function handlerToControllerAndMethod(ContainerInterface $container, string $handler): array
    {
        $segments = explode('.', $handler);
        $numSegments = count($segments);
        switch ($numSegments) {
        case 0:
            // fallthrough
        case 1:
            throw new Exception("Route name unexpectedly has $numSegments segments");
        case 2:
            $controllerNameBase = ucfirst($segments[0]);
            if (empty($this->controllerNamespace)) {
                $controllerPrefix = '';
            } else {
                $controllerPrefix = implode('\\', $this->controllerNamespace) . '\\';
            }
            $controllerName = "{$controllerPrefix}{$controllerNameBase}Controller";
            $controllerMethod = $segments[$numSegments - 1];
            break;
        default:
            $controllerNameBase = ucfirst($segments[$numSegments - 2]);
            $namespaceSegments = $this->controllerNamespace;
            for ($i = 0; $i != $numSegments - 2; ++$i) {
                $namespaceSegments[] = ucfirst($segments[$i]);
            }
            $controllerNamePrefix = implode("\\", $namespaceSegments);
            $controllerName = "$controllerNamePrefix\\{$controllerNameBase}Controller";
            $controllerMethod = $segments[$numSegments - 1];
            break;
        }
        $controller = $container->get($controllerName);
        return [$controller, $controllerMethod];
    }

    private function getRouteForRequest(ServerRequestInterface $request): Route|null
    {
        $route = $this->routerContainer->getMatcher()->match($request);
        if (empty($route)) {
            return $this->getRoute($this->fallbackRouteName);
        }
        if (!is_a($route, Route::class)) {
            throw new Exception('Route is of unexpected type');
        }
        return $route;
    }

    /** @param ?array<string, mixed> $params */
    public function generate(string $routeName, ?array $params = null): string
    {
        return $this->routerContainer->getGenerator()->generate($routeName, $params ?? []);
    }

    /**
     * @param ?array<string, mixed> $params
     * @param ?array<string, mixed> $queryData
     */
    public function generateUri(
        string $routeName,
        ?array $params = null,
        ?array $queryData = null,
        ?string $fragment = null,
    ): UriInterface
    {
        $path = $this->generate($routeName, $params);
        $uri = $this->uriFactory->createUri($path)->withFragment($fragment ?? '');
        if (empty($queryData)) {
            return $uri->withQuery('');
        }
        return $uri->withQuery(http_build_query($queryData));
    }

    /**
     * @internal
     *
     * This method is for internal use by auraxx library code only.
     */
    public function createAction(ContainerInterface $container, ServerRequestInterface $request): Action
    {
        $route = $this->getRouteForRequest($request);
        [$controller, $controllerMethod] = $this->controllerAndMethodForRoute($container, $route);

        $middlewares = $this->middlewaresForRoute($container, $route);

        $action = new Action(
            $controller,
            $controllerMethod,
            $route->attributes,
            $route->permittedRoles,
            $middlewares,
            ($container->has(LoggerInterface::class) ? $container->get(LoggerInterface::class) : null),
        );

        return $action;
    }
}

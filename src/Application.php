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

    /**
     * Runs the application, arranging for HTTP method normalization, routing, middleware execution,
     * controller instantiation, and passing to the appropriate controller method.
     *
     * The consumer of this class is responsible for constructing the request instance in the first
     * place, and emitting the response.
     *
     * `Application::handle` will, before it does anything else, normalize the HTTP method of the request, by
     * examining each of the following in turn:
     * * `X-Http-Method-Override` header;
     * * Any field named `_METHOD` or `_method` on the parsed request body; this applies to
     *   `application/x-www-form-urlencoded` or `multipart/form-data` request bodies only (AJAX requests
     *   can set their own method just fine)
     * The HTTP method is then capitalized (`get` becomes `GET` etc.).
     * The thus-normalized method is then used for the rest of the request/response cycle, and
     * will be the return value whenever `$request->getMethod()` is called.
     *
     * Before being passed to middleware pipeline (and thereafter to the controller method), the
     * request will have the controller instance set as a request attribute on the `$controllerAttributeKey`,
     * and the array of permitted roles (either `null` or an `array<string>`) for the given route, set
     * as a request attribute on the `$permittedRolesAttributeKey`. (See the `Application`
     * constructor for the value of these keys.)
     *
     * See `Aurax\Route` for more on how the permitted roles are configured on the router.
     *
     * Auraxx doesn't do anything further with the permitted roles other than recording them on the
     * request. It is up to consumers of the `Auraxx` library to define middleware or other means
     * to actually check a users' roles against these permitted roles and either allow or disallow
     * the request.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = self::normalizeMethod($request);
        $router = $this->container->get(Router::class);
        $action = $router->createAction($this->container, $request);
        $request = $request
            ->withAttribute($this->controllerAttributeKey, $action->getController())
            ->withAttribute($this->permittedRolesAttributeKey, $action->getPermittedRoles());
        return $action->handle($request);
    }

    private static function normalizeMethod(ServerRequestInterface $request): ServerRequestInterface
    {
        $method = $request->getHeaderLine('X-Http-Method-Override');
        if (strlen($method) == 0) {
            $body = $request->getParsedBody();
            $method = $body['_METHOD'] ?? $body['_method'] ?? $request->getMethod();
        }
        return $request->withMethod(strtoupper($method));
    }
}

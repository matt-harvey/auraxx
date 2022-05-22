<?php

declare(strict_types=1);

use Aura\Router\Route as AuraRoute;
use MattHarvey\Auraxx\Route;
use MattHarvey\Auraxx\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use TestUtil\Fixture\AppRouter;
use TestUtil\Fixture\Container;
use TestUtil\Fixture\Controller\DogController;
use TestUtil\Fixture\Middleware\MiddlewareA;
use TestUtil\Fixture\Middleware\MiddlewareB;

final class RouterTest extends TestCase
{
    private function createAppRouter(): AppRouter
    {
        $container = new Container;
        return $container->get(Router::class);
    }

    public function testRouterConstruct(): void
    {
        $container = new Container;
        $uriFactory = $container->get(UriFactoryInterface::class);
        $appRouter = new AppRouter($uriFactory);
        $this->assertInstanceOf(Router::class, $appRouter);
    }

    public function testRouterGetRoute(): void
    {
        $appRouter = $this->createAppRouter();

        $routeA = $appRouter->getRoute('dog.create');
        $this->assertSame('dog.create', $routeA->handler);
        $this->assertSame('/dog/new', $routeA->path);
        $this->assertSame([], $routeA->middleware);
        $this->assertSame(null, $routeA->permittedRoles);
        $this->assertSame(['GET'], $routeA->allows);

        $routeB = $appRouter->getRoute('admin.dashboard.inviteUser');
        $this->assertSame('admin.dashboard.inviteUser', $routeB->handler);
        $this->assertSame('/admin/invite-user', $routeB->path);
        $this->assertSame([], $routeB->middleware);
        $this->assertSame(['admin'], $routeB->permittedRoles);
        $this->assertSame(['POST'], $routeB->allows);

        $routeC = $appRouter->getRoute('authentication.signIn');
        $this->assertSame('authentication.signIn', $routeC->handler);
        $this->assertSame('/sign-in', $routeC->path);
        $this->assertSame([MiddlewareA::class => false, MiddlewareB::class => true], $routeC->middleware);
        $this->assertSame(null, $routeC->permittedRoles);
        $this->assertSame(['POST'], $routeC->allows);
    }

    public function testRouterGenerate(): void
    {
        $appRouter = $this->createAppRouter();

        $urlA = $appRouter->generate('dog.create');
        $this->assertSame('/dog/new', $urlA);

        $urlB = $appRouter->generate('dog.show', ['id' => 50]);
        $this->assertSame('/dog/50', $urlB);
    }

    public function testRouterGenerateUri(): void
    {
        $appRouter = $this->createAppRouter();

        $uriA = $appRouter->generateUri('dog.create');
        $this->assertInstanceOf(UriInterface::class, $uriA);
        $this->assertSame('/dog/new', $uriA->__toString());

        $uriB = $appRouter->generateUri('dog.show', ['id' => 50], ['cool' => 'yes', 'oh' => 5], 'boom');
        $this->assertInstanceOf(UriInterface::class, $uriB);
        $this->assertSame('/dog/50?cool=yes&oh=5#boom', $uriB->__toString());
    }

    public function testRouterCreateAction(): void
    {
        $container = new Container;
        $appRouter = $container->get(Router::class);
        $psr17Factory = $container->get(Psr17Factory::class);
        $request = $psr17Factory->createServerRequest('GET', '/dog/new');
        $action = $appRouter->createAction($container, $request);
        $this->assertInstanceOf(DogController::class, $action->getController());
        $this->assertSame(null, $action->getPermittedRoles());
    }
}

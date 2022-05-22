<?php

declare(strict_types=1);

use MattHarvey\Auraxx\Application;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use TestUtil\Fixture\Container;
use TestUtil\Fixture\Controller\Admin\DashboardController;
use TestUtil\Fixture\Controller\AuthenticationController;
use TestUtil\Fixture\Controller\DogController;

final class ApplicationTest extends TestCase
{
    public function testRouterConstruct(): void
    {
        $container = new Container;
        $application = new Application($container, 'c', 'pr');
        $this->assertInstanceOf(Application::class, $application);
    }

    public function testHandleSimple(): void
    {
        $container = new Container;
        $application = new Application($container);
        $psr17Factory = $container->get(Psr17Factory::class);
        $request = $psr17Factory->createServerRequest('GET', '/dog/30');
        $response = $application->handle($request);
        $this->assertSame('DogController::show called with id 30', (string) $response->getBody());
        $controller = $container->get(DogController::class);
        $this->assertTrue($controller->requestReceived->getAttribute('MiddlewareA called'));
        $this->assertNull($controller->requestReceived->getAttribute('MiddlewareB called'));
        $this->assertTrue($controller->requestReceived->getAttribute('MiddlewareC called'));
    }

    public function testHandleWithRouteMiddlewareOverrides(): void
    {
        $container = new Container;
        $application = new Application($container);
        $psr17Factory = $container->get(Psr17Factory::class);
        $request = $psr17Factory->createServerRequest('POST', '/sign-in');
        $response = $application->handle($request);
        $this->assertSame('AuthenticationController::signIn called', (string) $response->getBody());
        $controller = $container->get(AuthenticationController::class);
        $this->assertNull($controller->requestReceived->getAttribute('MiddlewareA called'));
        $this->assertTrue($controller->requestReceived->getAttribute('MiddlewareB called'));
        $this->assertTrue($controller->requestReceived->getAttribute('MiddlewareC called'));
    }

    public function testHandleWithInnerNamespaceAndRole(): void
    {
        $container = new Container;
        $application = new Application($container);
        $psr17Factory = $container->get(Psr17Factory::class);
        $request = $psr17Factory->createServerRequest('POST', '/admin/invite-user');
        $response = $application->handle($request);
        $this->assertSame('Admin DashboardController::inviteUser called', (string) $response->getBody());
        $controller = $container->get(DashboardController::class);
        $this->assertTrue($controller->requestReceived->getAttribute('MiddlewareA called'));
        $this->assertNull($controller->requestReceived->getAttribute('MiddlewareB called'));
        $this->assertTrue($controller->requestReceived->getAttribute('MiddlewareC called'));
        $this->assertSame(['admin'], $controller->requestReceived->getAttribute('permittedRoles'));
    }
}

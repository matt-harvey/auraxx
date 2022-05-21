<?php

declare(strict_types=1);

use MattHarvey\Auraxx\Action;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TestUtil\Factory\ActionFactory;

final class DummyController
{
    public function show(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        $responseBody = $psr17Factory->createStream("Hi! You passed me: $id.");
        $response = $psr17Factory->createResponse(200)->withBody($responseBody);
        return $response;
    }
}

final class DummyMiddlewareA implements MiddlewareInterface
{
    public int $called = 0;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->called++;
        return $handler->handle($request);
    }
}

final class DummyMiddlewareB implements MiddlewareInterface
{
    public int $called = 0;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->called++;
        return $handler->handle($request);
    }
}

final class ActionTest extends TestCase
{
    private ActionFactory $actionFactory;

    protected function setUp(): void
    {
        $controller = new DummyController;
        $middlewareA = new DummyMiddlewareA;
        $middlewareB = new DummyMiddlewareB;
        $logger = $this->createMock(LoggerInterface::class);
        $this->actionFactory = new ActionFactory(
            controller: $controller,
            controllerMethod: 'index',
            routeParameters: ['id' => '3'],
            permittedRoles: ['premium'],
            middlewares: [$middlewareA, $middlewareB],
            logger: $logger,
        );
    }

    public function testGetController(): void
    {
        $controller = new DummyController;
        $action = $this->actionFactory->createAction(['controller' => $controller]);
        $returnedController = $action->getController();
        $this->assertInstanceOf(DummyController::class, $returnedController);
        $this->assertTrue($returnedController === $controller);
    }

    public function testGetPermittedRoles(): void
    {
        $action = $this->actionFactory->createAction(['permittedRoles' => ['premium']]);
        $this->assertEquals(['premium'], $action->getPermittedRoles());
    }

    public function testHandle(): void
    {
        $controller = new DummyController;
        $middlewareA = new DummyMiddlewareA;
        $middlewareB = new DummyMiddlewareB;
        $action = $this->actionFactory->createAction([
            'controller' => $controller,
            'controllerMethod' => 'show',
            'routeParameters' => ['id' => '3'],
            'middlewares' => [$middlewareA, $middlewareB],
        ]);
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $action->handle($request);
        $this->assertEquals(1, $middlewareA->called);
        $this->assertEquals(1, $middlewareB->called);
        $this->assertEquals('Hi! You passed me: 3.', (string) $response->getBody());
    }
}

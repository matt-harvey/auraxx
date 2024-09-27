<?php

declare(strict_types=1);

namespace TestUtil\Fixture;

use Exception;
use MattHarvey\Auraxx\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use TestUtil\Fixture\Controller\Admin\DashboardController;
use TestUtil\Fixture\Controller\AuthenticationController;
use TestUtil\Fixture\Controller\DogController;
use TestUtil\Fixture\Controller\ErrorController;
use TestUtil\Fixture\Controller\HomeController;
use TestUtil\Fixture\Middleware\MiddlewareA;
use TestUtil\Fixture\Middleware\MiddlewareB;
use TestUtil\Fixture\Middleware\MiddlewareC;

/**
 * A simple PSR-11 dependendency injection (DI) container for testing purposes.
 *
 * Real applications should probably use a full-fledged DI library such as PHP-DI.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, string> $map */
    private array $providers = [
        AuthenticationController::class => 'provideAuthenticationController',
        DashboardController::class => 'provideAdminDashboardController',
        DogController::class => 'provideDogController',
        ErrorController::class => 'provideErrorController',
        HomeController::class => 'provideHomeController',
        LoggerInterface::class => 'provideLogger',
        MiddlewareA::class => 'provideMiddlewareA',
        MiddlewareB::class => 'provideMiddlewareB',
        MiddlewareC::class => 'provideMiddlewareC',
        Psr17Factory::class => 'providePsr17Factory',
        ResponseFactoryInterface::class => 'providePsr17Factory',
        Router::class => 'provideAppRouter',
        ServerRequestCreator::class => 'provideServerRequestCreator',
        ServerRequestInterface::class => 'providePsr17Factory',
        StreamFactoryInterface::class => 'providePsr17Factory',
        UploadedFileFactoryInterface::class => 'providePsr17Factory',
        UriFactoryInterface::class => 'providePsr17Factory',
    ];

    /** @var array<string, mixed> $members */
    private array $members = [];

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->members)) {
            return $this->members[$id];
        }
        if (array_key_exists($id, $this->providers)) {
            $provider = $this->providers[$id];
            $member = $this->$provider();
            $this->members[$id] = $member;
            return $member;
        }
        throw new Exception("Container could not provider dependency for id $id");
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->providers);
    }

    private function provideAdminDashboardController(): DashboardController
    {
        return new DashboardController(
            $this->get(ResponseFactoryInterface::class),
            $this->get(StreamFactoryInterface::class),
        );
    }

    private function provideAuthenticationController(): AuthenticationController
    {
        return new AuthenticationController(
            $this->get(ResponseFactoryInterface::class),
            $this->get(StreamFactoryInterface::class),
        );
    }

    private function provideAppRouter(): AppRouter
    {
        return new AppRouter(
            $this->get(UriFactoryInterface::class),
        );
    }

    private function provideDogController(): DogController
    {
        return new DogController(
            $this->get(ResponseFactoryInterface::class),
            $this->get(StreamFactoryInterface::class),
        );
    }

    private function provideErrorController(): ErrorController
    {
        return new ErrorController(
            $this->get(ResponseFactoryInterface::class),
            $this->get(StreamFactoryInterface::class),
        );
    }

    private function provideMiddlewareA(): MiddlewareA
    {
        return new MiddlewareA;
    }

    private function provideMiddlewareB(): MiddlewareB
    {
        return new MiddlewareB;
    }

    private function provideMiddlewareC(): MiddlewareC
    {
        return new MiddlewareC;
    }

    private function provideHomeController(): HomeController
    {
        return new HomeController(
            $this->get(ResponseFactoryInterface::class),
            $this->get(StreamFactoryInterface::class),
        );
    }

    private function providePsr17Factory(): Psr17Factory
    {
        return new Psr17Factory;
    }

    private function provideServerRequestCreator(): ServerRequestCreator
    {
        return new ServerRequestCreator(
            $this->get(ServerRequestFactoryInterface::class),
            $this->get(UriFactoryInterface::class),
            $this->get(UploadedFileFactoryInterface::class),
            $this->get(StreamFactoryInterface::class),
        );
    }
}

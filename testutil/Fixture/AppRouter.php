<?php

declare(strict_types=1);

namespace TestUtil\Fixture;

use Aura\Router\Map;
use MattHarvey\Auraxx\Router;
use Psr\Http\Message\UriFactoryInterface;
use TestUtil\Fixture\Middleware\MiddlewareA;
use TestUtil\Fixture\Middleware\MiddlewareB;
use TestUtil\Fixture\Middleware\MiddlewareC;

final class AppRouter extends Router
{
    public function __construct(UriFactoryInterface $uriFactory)
    {
        parent::__construct($uriFactory, 'error.notFound', ['TestUtil', 'Fixture', 'Controller']);
    }

    /** @return array<string, bool> $defaultMiddlewares */
    protected function getDefaultMiddlewares(): array {
        return [
            MiddlewareA::class => true,
            MiddlewareB::class => false,
            MiddlewareC::class => true
        ];
    }

    /**
     * Suppression required because of the way our customer Route::middleware method is dynamically
     * forwarded from the router map.
     * @suppress PhanUndeclaredMethod
     */
    protected function configureRoutes(Map $map): void
    {
        $map->get('home.index', '/');
        $map->get('dog.show', '/dog/{id}');
        $map->get('dog.create', '/dog/new');
        $map->post('dog.store', '/dog/new');

        $map->attach('admin.', '/admin', function ($map) {
            $map->permittedRoles(['admin']);

            $map->get('dashboard.index', '');
            $map->post('dashboard.inviteUser', '/invite-user');
        });

        $map->get('authentication.index', '/sign-in')
            ->middleware([MiddlewareA::class => false, MiddlewareB::class => true]);
        $map->post('authentication.signIn', '/sign-in')
            ->middleware([MiddlewareA::class => false, MiddlewareB::class => true]);

        $map->get('error.notFound', '')
            ->isRoutable(false)
            ->middleware([MiddlewareA::class => false]);
    }
}

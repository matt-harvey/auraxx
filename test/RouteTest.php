<?php

declare(strict_types=1);

use Aura\Router\Route as AuraRoute;
use MattHarvey\Auraxx\Route;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    public function testConstruct(): void
    {
        $route = new Route;
        $this->assertInstanceOf(Route::class, $route);
        $this->assertInstanceOf(AuraRoute::class, $route);
    }

    public function testMiddleware(): void
    {
        $route = new Route;
        $route->middleware(['dummy' => true, 'cool' => false]);
        $this->assertSame(['dummy' => true, 'cool' => false], $route->middleware);
    }

    public function testPermittedRoles(): void
    {
        $route = new Route;
        $route->permittedRoles(['admin', 'sales_rep']);
        $this->assertSame(['admin', 'sales_rep'], $route->permittedRoles);

        $route = new Route;
        $route->permittedRoles(null);
        $this->assertNull($route->permittedRoles);
    }
}



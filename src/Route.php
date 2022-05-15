<?php

declare(strict_types=1);

namespace MattHarvey\Auraxx;

use Aura\Router\Route as AuraRoute;

/**
 * Extends the Aura Route class with facilities for configuring middlewares and role-based
 * authorization.
 */
final class Route extends AuraRoute
{
    /** @var array<string, bool> */
    protected array $middleware = [];

    /** @var ?array<string> */
    protected ?array $permittedRoles = null;

    /**
     * @param array<string, bool> $middleware
     *
     * This should be passed a map from middleware class names to booleans indicating whether or not
     * each middleware should be applied for this route. This map will be merged into the global
     * map returned from `Router\getDefaultMiddlewares()`, overwriting the "on"/"off" settings in
     * that global map, for each middleware in the passed map. Note the *order* in which the middlewares
     * are called is always as per the global map, and cannot be altered by this method.
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * @param ?array<string> $roles
     *
     * This should be passed an array of roles indicating that the user must have at least one of
     * these roles in order to access the route.
     *
     * If passed `null`, then there is no role-based restriction on access, i.e. the user
     * can access the route regardless of their roles (although there may be other
     * restrictions on access e.g. they might need to be signed in, which would be enforced
     * by the authentication middleware).
     *
     * If passed `[]`, then the route cannot be accessed regardless of roles (i.e. it is
     * literally inaccessible to anyone).
     */
    public function permittedRoles(?array $roles): self
    {
        $this->permittedRoles = $roles;
        return $this;
    }
}

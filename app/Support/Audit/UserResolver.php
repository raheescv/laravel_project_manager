<?php

namespace App\Support\Audit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\UserResolver as UserResolverContract;
use OwenIt\Auditing\Resolvers\UserResolver as GuardUserResolver;

class UserResolver implements UserResolverContract
{
    /**
     * Resolve the acting user from the configured guards (web session, then
     * Sanctum token), detached from its access token and loaded relations.
     *
     * Audits are queued, and the resolved user is serialized into the job as
     * is — a token user would otherwise drag its PersonalAccessToken along.
     * Only the id and morph type are stored, so a bare copy is enough.
     */
    public static function resolve(): ?Authenticatable
    {
        $user = GuardUserResolver::resolve();

        if (! $user instanceof Model) {
            return $user;
        }

        /** @var Authenticatable&Model $detached */
        $detached = $user->newFromBuilder($user->getAttributes(), $user->getConnectionName());

        return $detached;
    }
}

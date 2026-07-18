<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Gate::before(fn (User $user, string $ability): ?bool => $user->hasRole('super_admin') ? true : null);
    }
}

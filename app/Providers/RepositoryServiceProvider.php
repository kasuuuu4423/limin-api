<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\Eloquent\EloquentItemRepository;
use App\Infrastructure\Eloquent\EloquentSessionRepository;
use App\Infrastructure\Eloquent\EloquentUserRepository;
use Domain\Limin\Repository\ItemRepositoryInterface;
use Domain\Limin\Repository\SessionRepositoryInterface;
use Domain\Limin\Repository\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            ItemRepositoryInterface::class,
            EloquentItemRepository::class
        );

        $this->app->bind(
            SessionRepositoryInterface::class,
            EloquentSessionRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Discovers every app/Modules/{Module}/Database/Migrations directory and
 * registers it with the migrator, so modules own their own migrations
 * instead of everything living under the top-level database/migrations/.
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (! File::isDirectory($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $moduleDirectory) {
            $migrations = $moduleDirectory.'/Database/Migrations';

            if (File::isDirectory($migrations)) {
                $this->loadMigrationsFrom($migrations);
            }

            // Test-only fixture tables (e.g. ApprovalWorkflow's demo item,
            // used to prove the generic engine end-to-end) never run outside
            // the testing environment, so they never reach a real schema.
            if ($this->app->environment('testing')) {
                $fixtureMigrations = $moduleDirectory.'/Tests/Fixtures/Migrations';

                if (File::isDirectory($fixtureMigrations)) {
                    $this->loadMigrationsFrom($fixtureMigrations);
                }
            }
        }
    }
}

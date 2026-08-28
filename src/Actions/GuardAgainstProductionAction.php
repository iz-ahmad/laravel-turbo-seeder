<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Actions;

use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Exceptions\ProductionEnvironmentException;

final class GuardAgainstProductionAction
{
    /**
     * every ->run() call the seeder makes during
     * that command invocation is covered by one confirmation.
     */
    public const CONFIRMED_BINDING = 'turbo-seeder.production_confirmed';

    public function __invoke(SeederConfigurationDTO $config): void
    {
        if ($config->isForced()) {
            return;
        }

        if (app()->bound(self::CONFIRMED_BINDING)) {
            return;
        }

        if (app()->environment('local', 'testing')) {
            return;
        }

        throw new ProductionEnvironmentException(sprintf(
            'TurboSeeder: refusing to seed table [%s] on the [%s] environment. '
            .'Call force() on the builder, or run via "php artisan turbo-seeder:run --force", '
            .'or confirm the interactive prompt to proceed.',
            $config->table,
            app()->environment(),
        ));
    }
}

<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

use IzAhmad\TurboSeeder\Actions\SyncPostgresSequencesAction;

/**
 * Requires the following properties in the using class:
 * - \IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO $dbConnection
 * - \IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO $config
 */
trait SyncsPostgresSequences
{
    protected function afterSuccessfulCleanup(): void
    {
        app(SyncPostgresSequencesAction::class)($this->dbConnection, $this->config);
    }
}

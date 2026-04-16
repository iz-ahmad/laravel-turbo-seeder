<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Trait to manage database environment preparation and cleanup.
 *
 * Requires the following properties in the using class:
 * - \IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO $dbConnection
 * - \IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO $config
 * - \IzAhmad\TurboSeeder\Actions\PrepareEnvironmentAction $prepareAction
 * - \IzAhmad\TurboSeeder\Actions\CleanupEnvironmentAction $cleanupAction
 */
trait ManagesEnvironment
{
    protected bool $environmentPrepared = false;

    protected bool $transactionStartedByUs = false;

    /**
     * Prepare the database environment for seeding.
     */
    public function prepareEnvironment(): void
    {
        if ($this->environmentPrepared) {
            return;
        }

        ($this->prepareAction)($this->dbConnection, $this->config);

        if ($this->config->options['use_transactions'] ?? true) {
            $connection = DB::connection($this->dbConnection->name);
            $levelBefore = $connection->transactionLevel();
            $connection->beginTransaction();
            $this->transactionStartedByUs = $connection->transactionLevel() > $levelBefore;
        }

        $this->environmentPrepared = true;
    }

    /**
     * Clean up and restore database environment after seeding.
     *
     * @param  bool  $fromException  Whether the cleanup is due to an exception.
     */
    public function cleanup(bool $fromException = false): void
    {
        if (! $this->environmentPrepared) {
            return;
        }

        if (($this->config->options['use_transactions'] ?? true) && $this->transactionStartedByUs) {
            $connection = DB::connection($this->dbConnection->name);

            if ($fromException) {
                if ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            } else {
                if ($connection->transactionLevel() > 0) {
                    $connection->commit();
                }
            }

            $this->transactionStartedByUs = false;
        }

        ($this->cleanupAction)($this->dbConnection, $this->config);

        $this->environmentPrepared = false;
    }
}

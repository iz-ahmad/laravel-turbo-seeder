<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Trait to manage database environment preparation and cleanup.
 *
 * Requires the following properties in the using class:
 * - DatabaseConnectionDTO $dbConnection
 * - SeederConfigurationDTO $config
 * - PrepareEnvironmentAction $prepareAction
 * - CleanupEnvironmentAction $cleanupAction
 */
trait ManagesEnvironment
{
    protected bool $environmentPrepared = false;

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
            DB::connection($this->dbConnection->name)->beginTransaction();
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

        if (($this->config->options['use_transactions'] ?? true) && ! $fromException) {
            $connection = DB::connection($this->dbConnection->name);

            if ($connection->transactionLevel() > 0) {
                $connection->commit();
            }
        }

        ($this->cleanupAction)($this->dbConnection, $this->config);

        $this->environmentPrepared = false;
    }
}

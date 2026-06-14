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

    /** @var array<string, mixed> */
    protected array $environmentContext = [];

    public function prepareEnvironment(): void
    {
        if ($this->environmentPrepared) {
            return;
        }

        // Phase 1: prepare session-level settings that must run BEFORE the transaction
        // (e.g. MySQL FK checks, SQLite PRAGMAs - SQLite rejects PRAGMA changes inside a txn).
        $this->environmentContext = $this->prepareAction->prepareBeforeTransaction($this->dbConnection, $this->config);

        if ($this->config->shouldUseTransactions()) {
            $connection = DB::connection($this->dbConnection->name);
            $levelBefore = $connection->transactionLevel();
            $connection->beginTransaction();
            $this->transactionStartedByUs = $connection->transactionLevel() > $levelBefore;
        }

        // Phase 3: prepare settings that REQUIRE an open transaction
        // (e.g. PostgreSQL's SET CONSTRAINTS ALL DEFERRED).
        $this->prepareAction->prepareAfterTransaction($this->dbConnection, $this->config);

        $this->environmentPrepared = true;
    }

    /**
     * @param  bool  $fromException  Whether the cleanup is due to an exception.
     */
    public function cleanup(bool $fromException = false): void
    {
        if (! $this->environmentPrepared) {
            return;
        }

        if ($this->config->shouldUseTransactions() && $this->transactionStartedByUs) {
            $connection = DB::connection($this->dbConnection->name);

            if ($fromException || $this->config->isDryRun()) {
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

        ($this->cleanupAction)($this->dbConnection, $this->config, $this->environmentContext);

        $this->environmentPrepared = false;
    }
}

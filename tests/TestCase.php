<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Tests;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\TurboSeederServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->artisan('migrate')->run();

        DB::table('test_posts')->delete();
        DB::table('test_users')->delete();
    }

    protected function getPackageProviders($app): array
    {
        return [
            TurboSeederServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            // 'driver' => 'mysql',
            // 'host' => '127.0.0.1',
            // 'port' => '3306',
            // 'database' => 'test_database',
            // 'username' => 'root',
            // 'password' => '',
        ]);

        $app['config']->set('turbo-seeder', [
            'default_chunk_size' => 100,
            'chunk_sizes' => [
                'mysql' => 100,
                'pgsql' => 80,
                'sqlite' => 50,
            ],
            'memory' => [
                'limit_mb' => 256,
                'gc_threshold_percent' => 80,
                'force_gc_after_chunks' => 5,
            ],
            'performance' => [
                'disable_query_log' => true,
                'disable_foreign_keys' => true,
                'use_transactions' => true,
            ],
            'csv_strategy' => [
                'enabled' => true,
                'temp_path' => sys_get_temp_dir().'/turbo-seeder-test',
                'buffer_size' => 8192,
                'line_terminator' => "\n",
                'field_delimiter' => ',',
                'field_enclosure' => '"',
            ],
            'progress' => [
                'enabled' => true,
                'update_frequency' => 100,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->cleanupTempFiles();

        parent::tearDown();
    }

    protected function cleanupTempFiles(): void
    {
        $tempPath = sys_get_temp_dir().'/turbo-seeder-test';

        if (is_dir($tempPath)) {
            $files = glob($tempPath.'/*');

            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }

    /**
     * Safely truncate a table by disabling foreign key checks.
     */
    protected function truncateTable(string $table): void
    {
        $defaultConnection = config('database.default');
        $driver = config('database.connections.'.$defaultConnection.'.driver');

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table($table)->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'pgsql') {
            DB::statement('SET session_replication_role = replica');
            DB::table($table)->truncate();
            DB::statement('SET session_replication_role = DEFAULT');
        } else {
            DB::table($table)->delete();
        }
    }
}

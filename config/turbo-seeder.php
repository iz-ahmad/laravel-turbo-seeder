<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Chunk Size
    |--------------------------------------------------------------------------
    |
    | The default number of records to insert per chunk. This will be
    | overridden by database-specific chunk sizes if not explicitly set.
    |
    */
    'default_chunk_size' => 5000,

    /*
    |--------------------------------------------------------------------------
    | Database-Specific Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | Optimal chunk sizes for each database driver. These values are tuned
    | for maximum performance based on each database's characteristics.
    |
    */
    'chunk_sizes' => [
        'mysql' => 5000,
        'pgsql' => 3000,
        'sqlite' => 2000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Management
    |--------------------------------------------------------------------------
    |
    | Configure memory usage limits and garbage collection behavior.
    |
    */
    'memory' => [
        'limit_mb' => 256,
        'gc_threshold_percent' => 80,
        'force_gc_after_chunks' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimizations
    |--------------------------------------------------------------------------
    |
    | Enable or disable various performance optimizations.
    |
    */
    'performance' => [
        'disable_query_log' => true,
        'disable_foreign_keys' => true,
        'use_transactions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | CSV Strategy Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the CSV-based seeding strategy.
    |
    */
    'csv_strategy' => [
        'enabled' => true,
        'temp_path' => storage_path('app/turbo-seeder'),
        'buffer_size' => 8192,
        'line_terminator' => "\n",
        'field_delimiter' => ',',
        'field_enclosure' => '"',
    ],

    /*
    |--------------------------------------------------------------------------
    | Progress Tracking
    |--------------------------------------------------------------------------
    |
    | Configure progress bar and status updates.
    |
    */
    'progress' => [
        'enabled' => true,
        'update_frequency' => 1000,
    ],
];

<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Chunk Size
    |--------------------------------------------------------------------------
    |
    | The default number of records to insert per chunk regardles of the database driver. This will be
    | used as a fallback when database-specific chunk sizes are not set.
    |
    */
    'default_chunk_size' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Database-Specific Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | Optimal chunk sizes for each database driver. These values will get priority over the default chunk size.
    | And these will be overridden if custom chunk size is set in the seeder class.
    |
    */
    'chunk_sizes' => [
        'mysql' => 1000,
        'pgsql' => 800,
        'sqlite' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Classes Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace for seeder classes using the turbo seeder package. This is used to automatically
    | resolve the seeder class name if it is not fully qualified.
    |
    */
    'seeder_classes_namespace' => 'Database\\Seeders\\',

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
        'batch_size' => 10000,
        'gc_frequency' => 5,
        'reader_chunk_size_for_sqlite' => 500,
        'fallback_to_default_strategy_on_config_error' => true,
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

    /*
    |--------------------------------------------------------------------------
    | Get Error Trace on Console
    |--------------------------------------------------------------------------
    |
    | Configure whether to get error trace on console.
    |
    */
    'get_error_trace_on_console' => false,

    /*
    |--------------------------------------------------------------------------
    | Max Error Message Length in Console
    |--------------------------------------------------------------------------
    |
    | Configure the maximum length of the error message shown in the console output.
    |
    */
    'max_error_message_length_in_console' => 600,
];

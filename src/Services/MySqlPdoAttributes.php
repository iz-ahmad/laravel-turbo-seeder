<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

final class MySqlPdoAttributes
{
    /**
     * The PDO "local infile" attribute, resolved via constant() on both paths.
     */
    public static function localInfileAttribute(): int
    {
        return PHP_VERSION_ID >= 80400
            ? (int) constant('Pdo\\Mysql::ATTR_LOCAL_INFILE')
            : (int) constant('PDO::MYSQL_ATTR_LOCAL_INFILE');
    }
}

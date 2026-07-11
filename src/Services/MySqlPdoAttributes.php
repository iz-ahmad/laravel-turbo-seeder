<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

final class MySqlPdoAttributes
{
    /**
     * The PDO "local infile" attribute, resolved via constant() on both paths so
     * neither the deprecated PDO::MYSQL_ATTR_LOCAL_INFILE (PHP 8.5+) nor the
     * PHP 8.4+ Pdo\Mysql class is referenced statically.
     */
    public static function localInfileAttribute(): int
    {
        return PHP_VERSION_ID >= 80400
            ? (int) constant('Pdo\\Mysql::ATTR_LOCAL_INFILE')
            : (int) constant('PDO::MYSQL_ATTR_LOCAL_INFILE');
    }
}

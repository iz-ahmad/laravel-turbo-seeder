<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Resolves a table name, an Eloquent Model class-string, or a Model instance
 * into a table name and the model's connection (null for a literal table name,
 * or a model that uses the app's default connection).
 */
final class ModelTableResolver
{
    /**
     * @param  class-string<Model>|Model|string  $table
     * @param  string  $context  Calling method name(s), used in error messages
     * @return array{table: string, connection: ?string}
     */
    public static function resolve(string|Model $table, string $context): array
    {
        if ($table instanceof Model) {
            return ['table' => $table->getTable(), 'connection' => $table->getConnectionName()];
        }

        if (class_exists($table)) {
            if (! is_subclass_of($table, Model::class)) {
                throw new \InvalidArgumentException(
                    "Class [{$table}] is not an Eloquent model. {$context} accepts a table name, or an Eloquent Model class/instance."
                );
            }

            if (! (new \ReflectionClass($table))->isInstantiable()) {
                throw new \InvalidArgumentException(
                    "Class [{$table}] is an abstract Eloquent model and cannot be instantiated. {$context} requires a concrete Model class or instance."
                );
            }

            $model = new $table;

            return ['table' => $model->getTable(), 'connection' => $model->getConnectionName()];
        }

        return ['table' => $table, 'connection' => null];
    }
}

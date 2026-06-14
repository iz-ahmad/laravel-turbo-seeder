<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class MakeTurboSeederCommand extends GeneratorCommand
{
    protected $name = 'make:turbo-seeder';

    protected $description = 'Create a new TurboSeeder seeder class';

    protected $type = 'Seeder';

    protected function getStub(): string
    {
        return $this->option('factory')
            ? __DIR__.'/stubs/turbo-seeder.factory.stub'
            : __DIR__.'/stubs/turbo-seeder.stub';
    }

    protected function getPath($name): string
    {
        $name = str_replace('\\', '/', Str::replaceFirst($this->rootNamespace(), '', $name));

        return $this->laravel->databasePath().'/seeders/'.$name.'.php';
    }

    protected function rootNamespace(): string
    {
        return 'Database\\Seeders\\';
    }

    /**
     * @param  string  $name
     */
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $table = (string) ($this->option('table') ?: 'your_table');
        $count = max(1, (int) ($this->option('count') ?: 1000));
        $columns = $this->resolveColumns($table);

        return str_replace(
            ['{{ table }}', '{{ count }}', '{{ columns }}', '{{ generator }}', '{{ model }}'],
            [
                $table,
                (string) $count,
                $this->formatColumnsArray($columns),
                $this->formatGeneratorBody($columns),
                $this->guessModelClass($table),
            ],
            $stub,
        );
    }

    /**
     * Introspect the table's columns (excluding the auto-increment key).
     *
     * @return array<int, string>
     */
    private function resolveColumns(string $table): array
    {
        if ($table === 'your_table' || ! Schema::hasTable($table)) {
            return ['name', 'created_at', 'updated_at'];
        }

        try {
            $columns = [];

            foreach (Schema::getColumns($table) as $column) {
                if (($column['auto_increment'] ?? false) === true) {
                    continue;
                }

                $columns[] = (string) $column['name'];
            }

            return $columns !== [] ? $columns : array_values(array_filter(Schema::getColumnListing($table), fn ($c) => $c !== 'id'));
        } catch (\Throwable) {
            return array_values(array_filter(Schema::getColumnListing($table), fn ($c) => $c !== 'id'));
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function formatColumnsArray(array $columns): string
    {
        return implode(', ', array_map(fn ($c) => "'{$c}'", $columns));
    }

    /**
     * Build a sensible generator body from the column names.
     *
     * @param  array<int, string>  $columns
     */
    private function formatGeneratorBody(array $columns): string
    {
        $lines = [];

        foreach ($columns as $column) {
            $lines[] = "                '{$column}' => {$this->guessValueExpression($column)},";
        }

        return implode("\n", $lines);
    }

    private function guessValueExpression(string $column): string
    {
        return match (true) {
            str_ends_with($column, '_at') => 'TurboData::nowOnce()',
            $column === 'password' => 'TurboData::hashedPassword()',
            $column === 'email' || str_ends_with($column, '_email') => '"user{$index}@example.test"',
            str_ends_with($column, '_id') => 'TurboData::randomInt(1, 100)', // or TurboData::fromTable(...)
            default => '"'.Str::headline($column).' {$index}"',
        };
    }

    private function guessModelClass(string $table): string
    {
        return '\\App\\Models\\'.Str::studly(Str::singular($table));
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['table', null, InputOption::VALUE_OPTIONAL, 'Table to introspect for columns'],
            ['count', null, InputOption::VALUE_OPTIONAL, 'Number of records to seed', '1000'],
            ['factory', null, InputOption::VALUE_NONE, 'Generate a fromFactory()-based stub'],
            ['force', null, InputOption::VALUE_NONE, 'Overwrite the seeder if it already exists'],
        ];
    }
}

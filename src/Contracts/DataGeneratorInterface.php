<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

interface DataGeneratorInterface
{
    /**
     * Generate a single record at the given index.
     *
     * @return array<string, mixed>
     */
    public function generate(int $index): array;

    /**
     * Get the column names that this generator produces.
     *
     * @return array<int, string>
     */
    public function getColumns(): array;

    /**
     * Validate the generated data structure.
     *
     * @param  array<string, mixed>  $data
     */
    public function validate(array $data): bool;
}

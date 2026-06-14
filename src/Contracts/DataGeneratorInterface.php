<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

interface DataGeneratorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function generate(int $index): array;

    /**
     * @return array<int, string>
     */
    public function getColumns(): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function validate(array $data): bool;
}

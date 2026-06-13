<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Enums;

/**
 * Selection mode for TurboData::fromTable() reference pools.
 */
enum FromTableMode: string
{
    /** Walk the pool in order, wrapping by index ($index % count). */
    case CYCLE = 'cycle';

    /** Pick a uniformly random value from the pool on every call. */
    case RANDOM = 'random';

    /**
     * Normalise a string|enum mode, accepting the legacy string form.
     */
    public static function normalize(self|string $mode): self
    {
        if ($mode instanceof self) {
            return $mode;
        }

        return self::tryFrom($mode) ?? throw new \InvalidArgumentException(
            'fromTable() $mode must be "cycle" or "random".'
        );
    }
}

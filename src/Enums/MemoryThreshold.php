<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Enums;

enum MemoryThreshold: int
{
    case LOW = 50;
    case MEDIUM = 70;
    case HIGH = 80;
    case CRITICAL = 90;

    public function getPercentage(): int
    {
        return $this->value;
    }

    public function shouldGarbageCollect(): bool
    {
        return $this->value >= self::HIGH->value;
    }

    public function shouldWarn(): bool
    {
        return $this->value >= self::CRITICAL->value;
    }

    public static function fromPercentage(float $percentage): self
    {
        return match (true) {
            $percentage >= 90 => self::CRITICAL,
            $percentage >= 80 => self::HIGH,
            $percentage >= 70 => self::MEDIUM,
            default => self::LOW,
        };
    }
}


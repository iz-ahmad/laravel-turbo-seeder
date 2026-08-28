<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Exceptions;

use RuntimeException;

/**
 * Thrown when a seeder runs against a non-local/non-testing environment
 * without an explicit force() or confirmation.
 */
final class ProductionEnvironmentException extends RuntimeException {}

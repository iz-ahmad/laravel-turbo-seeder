<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

final class ExceptionFormatter
{
    /**
     * Format exception message for user-friendly display.
     */
    public static function format(\Throwable $exception): string
    {
        $message = $exception->getMessage();
        $maxErrorMessageLength = config('turbo-seeder.max_error_message_length_in_console', 600);

        if (strlen($message) > $maxErrorMessageLength) {
            return substr($message, 0, $maxErrorMessageLength) . '... (truncated)';
        }

        return $message;
    }
}

<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

/**
 * Writes rows in PostgreSQL COPY *text* format (the format PDO's
 * pgsqlCopyFromFile consumes), streamed to a temp file.
 *
 * Text format escapes special characters with a backslash, so embedded
 * delimiters, quotes and newlines need no field enclosure, and a literal
 * "\N" in the data is written as "\\N" — distinct from the NULL sentinel
 * "\N". This means there is no null-marker collision on the PostgreSQL path.
 */
final class PostgresCopyWriter
{
    /** Column delimiter for COPY text format. */
    public const DELIMITER = "\t";

    /** NULL sentinel for COPY text format. */
    public const NULL_MARKER = '\N';

    /** @var resource|null */
    private $handle = null;

    private int $bufferSize = 8192;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly string $filepath,
        private readonly array $config = []
    ) {}

    public function open(): void
    {
        $directory = dirname($this->filepath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException("Failed to create directory: {$directory}");
        }

        $handle = fopen($this->filepath, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open file for writing: {$this->filepath}");
        }

        $this->handle = $handle;

        chmod($this->filepath, 0600);

        $this->bufferSize = $this->config['buffer_size'] ?? 8192;
        stream_set_write_buffer($this->handle, $this->bufferSize);
    }

    /**
     * Write a single record (column => value) as one COPY text line.
     *
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $columns
     */
    public function writeRecord(array $record, array $columns): void
    {
        if (! $this->handle) {
            throw new \RuntimeException('File handle not initialized. Call open() first.');
        }

        if (fwrite($this->handle, $this->formatLine($record, $columns)) === false) {
            throw new \RuntimeException('Failed to write COPY row');
        }
    }

    /**
     * Build a COPY text-format line (including trailing newline) for a record.
     *
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $columns
     */
    public function formatLine(array $record, array $columns): string
    {
        $fields = [];

        foreach ($columns as $column) {
            $fields[] = $this->encodeField($record[$column] ?? null);
        }

        return implode(self::DELIMITER, $fields)."\n";
    }

    /**
     * Encode a single value for COPY text format.
     */
    private function encodeField(mixed $value): string
    {
        $formatted = ValueFormatter::format($value);

        if ($formatted === null) {
            return self::NULL_MARKER;
        }

        // Escape backslash first, then the structural characters. strtr applies
        // replacements simultaneously, so escaped output is not re-scanned.
        return strtr((string) $formatted, [
            '\\' => '\\\\',
            "\t" => '\\t',
            "\n" => '\\n',
            "\r" => '\\r',
        ]);
    }

    public function close(): void
    {
        if ($this->handle) {
            fflush($this->handle);
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}

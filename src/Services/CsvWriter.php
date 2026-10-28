<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

final class CsvWriter
{
    /**
     * @var resource|null
     */
    private $handle = null;

    private int $rowsWritten = 0;

    private string $delimiter = ',';

    private string $enclosure = '"';

    private int $bufferSize = 8192;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly string $filepath,
        private readonly array $config = []
    ) {}

    /**
     * Open the file for writing.
     */
    public function open(): void
    {
        $this->ensureDirectory();

        $this->handle = fopen($this->filepath, 'w');

        if ($this->handle === false) {
            throw new \RuntimeException("Cannot open file for writing: {$this->filepath}");
        }

        chmod($this->filepath, 0600);

        $this->delimiter = $this->config['field_delimiter'] ?? ',';
        $this->enclosure = $this->config['field_enclosure'] ?? '"';
        $this->bufferSize = $this->config['buffer_size'] ?? 8192;

        stream_set_write_buffer($this->handle, $this->bufferSize);
    }

    /**
     * Write a single row to the CSV file.
     *
     * @param  array<int|string, mixed>  $row
     */
    public function writeRow(array $row): void
    {
        if (! $this->handle) {
            throw new \RuntimeException('File handle not initialized. Call open() first.');
        }

        if ($this->needsForcedQuoting($row)) {
            $success = fwrite($this->handle, $this->encodeRowQuoted($row)) !== false;
        } else {
            // empty escape = RFC 4180 mode (enclosure doubled, never backslash-escaped).
            // This also ensures null markers like \N are written unquoted, so MySQL/PG
            // correctly recognise them as NULL rather than the literal string \N.
            $success = fputcsv(
                $this->handle,
                $row,
                $this->delimiter,
                $this->enclosure,
                ''
            );
        }

        if ($success === false) {
            throw new \RuntimeException('Failed to write CSV row');
        }

        $this->rowsWritten++;
    }

    /**
     * fputcsv leaves a bare NULL unquoted, which MySQL's LOAD DATA reads as SQL NULL,
     * and writes a lone empty field as a blank line, which readers drop to NULL.
     *
     * @param  array<int|string, mixed>  $row
     */
    private function needsForcedQuoting(array $row): bool
    {
        return in_array('NULL', $row, true) || (count($row) === 1 && reset($row) === '');
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    private function encodeRowQuoted(array $row): string
    {
        $multiColumn = count($row) > 1;
        $fields = [];

        foreach ($row as $value) {
            $value = (string) $value;

            if ($value === '' && $multiColumn) {
                $fields[] = '';

                continue;
            }

            $fields[] = $value === 'NULL' || $value === '' || $this->containsSpecialCharacter($value)
                ? $this->enclosure.str_replace($this->enclosure, $this->enclosure.$this->enclosure, $value).$this->enclosure
                : $value;
        }

        return implode($this->delimiter, $fields)."\n";
    }

    private function containsSpecialCharacter(string $value): bool
    {
        return strpbrk($value, $this->delimiter.$this->enclosure."\n\r\t ") !== false;
    }

    /**
     * Flush and close the file.
     */
    public function close(): void
    {
        if ($this->handle) {
            fflush($this->handle);
            fclose($this->handle);
            $this->handle = null;
        }
    }

    /**
     * Get the number of rows written.
     */
    public function getRowsWritten(): int
    {
        return $this->rowsWritten;
    }

    /**
     * Get the file path.
     */
    public function getFilePath(): string
    {
        return $this->filepath;
    }

    /**
     * Get the file size in bytes.
     */
    public function getFileSize(): int
    {
        if (! file_exists($this->filepath)) {
            return 0;
        }

        return filesize($this->filepath) ?: 0;
    }

    /**
     * Ensure the directory exists.
     */
    private function ensureDirectory(): void
    {
        $directory = dirname($this->filepath);

        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new \RuntimeException("Failed to create directory: {$directory}");
            }
        }
    }

    /**
     * Destructor to ensure file is closed.
     */
    public function __destruct()
    {
        $this->close();
    }
}

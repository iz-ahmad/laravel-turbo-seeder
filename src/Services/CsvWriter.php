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

    private string $escape = '\\';

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

        $this->delimiter = $this->config['field_delimiter'] ?? ',';
        $this->enclosure = $this->config['field_enclosure'] ?? '"';
        $this->escape = $this->config['escape_char'] ?? '\\';
        $this->bufferSize = $this->config['buffer_size'] ?? 8192;

        stream_set_write_buffer($this->handle, $this->bufferSize);
    }

    /**
     * Write multiple rows at once.
     *
     * @param  array<int, array<int|string, mixed>>  $rows
     */
    public function writeRows(array $rows): void
    {
        foreach ($rows as $row) {
            if (! $this->handle) {
                throw new \RuntimeException('File handle not initialized. Call open() first.');
            }

            $success = fputcsv(
                $this->handle,
                $row,
                $this->delimiter,
                $this->enclosure,
                $this->escape
            );

            if ($success === false) {
                throw new \RuntimeException('Failed to write CSV row');
            }

            $this->rowsWritten++;
        }
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

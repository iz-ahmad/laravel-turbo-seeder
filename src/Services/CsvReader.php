<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use Generator;

final class CsvReader
{
    /**
     * @var resource|null
     */
    private $handle = null;

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
     * Open the file for reading.
     */
    public function open(): void
    {
        if (! file_exists($this->filepath)) {
            throw new \RuntimeException("File does not exist: {$this->filepath}");
        }

        $this->handle = fopen($this->filepath, 'r');

        if ($this->handle === false) {
            throw new \RuntimeException("Cannot open file for reading: {$this->filepath}");
        }

        $this->bufferSize = $this->config['buffer_size'] ?? 8192;

        stream_set_read_buffer($this->handle, $this->bufferSize);
    }

    /**
     * read rows one at a time using a generator for memory efficiency.
     *
     * @return Generator<int, array<int, string|null>>
     */
    public function readRows(): Generator
    {
        if (! $this->handle) {
            throw new \RuntimeException('File handle not initialized. Call open() first.');
        }

        $this->delimiter = $this->config['field_delimiter'] ?? ',';
        $this->enclosure = $this->config['field_enclosure'] ?? '"';
        $this->escape = $this->config['escape_char'] ?? '\\';

        while (
            ($row = fgetcsv(
                $this->handle,
                0,
                $this->delimiter,
                $this->enclosure,
                $this->escape
            )) !== false
        ) {
            yield $row;
        }
    }

    /**
     * Read rows in chunks for batch processing.
     *
     * @return Generator<int, array<int, array<int, string|null>>>
     */
    public function readChunks(int $chunkSize): Generator
    {
        $chunk = [];
        $count = 0;

        foreach ($this->readRows() as $row) {
            $chunk[] = $row;
            $count++;

            if ($count >= $chunkSize) {
                yield $chunk;
                $chunk = [];
                $count = 0;
            }
        }

        if (! empty($chunk)) {
            yield $chunk;
        }
    }

    /**
     * Close the file.
     */
    public function close(): void
    {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    /**
     * Get the file path.
     */
    public function getFilePath(): string
    {
        return $this->filepath;
    }

    /**
     * Destructor to ensure file is closed.
     */
    public function __destruct()
    {
        $this->close();
    }
}

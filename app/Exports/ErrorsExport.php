<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ErrorsExport implements FromArray, WithHeadings
{
    protected array $data;

    /** @var array<int, string> */
    protected array $headings;

    /** @var array<int, string> */
    protected array $columnKeys;

    public function __construct(array $data)
    {
        $this->data = $this->normalizeRows($data);
        $this->columnKeys = $this->collectColumnKeys($this->data);
        $this->headings = $this->buildHeadings($this->columnKeys);
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $row) {
            $values = [];
            foreach ($this->columnKeys as $key) {
                $values[] = $row[$key] ?? '';
            }
            $rows[] = $values;
        }

        return $rows;
    }

    /**
     * Normalize each error row to an array (handles Collection/object from imports).
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $data): array
    {
        $normalized = [];
        foreach ($data as $row) {
            if (is_array($row)) {
                $normalized[] = $row;
            } elseif (is_object($row) && method_exists($row, 'toArray')) {
                $normalized[] = $row->toArray();
            } else {
                $normalized[] = (array) $row;
            }
        }

        return $normalized;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * Collect all unique keys from error rows, with message/file/line always last.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array<int, string>
     */
    private function collectColumnKeys(array $data): array
    {
        $keys = [];
        $priority = ['message', 'file', 'line'];
        foreach ($data as $row) {
            foreach (array_keys($row) as $k) {
                if (! in_array($k, $keys, true) && ! in_array($k, $priority, true)) {
                    $keys[] = $k;
                }
            }
        }
        // Always include message, file, line so the file property and error details are present
        foreach ($priority as $p) {
            $keys[] = $p;
        }

        return $keys;
    }

    /**
     * Build header labels from column keys (human-readable).
     *
     * @param  array<int, string>  $columnKeys
     * @return array<int, string>
     */
    private function buildHeadings(array $columnKeys): array
    {
        $labels = [
            'message' => 'Error Message',
            'file' => 'File',
            'line' => 'Line',
        ];

        return array_map(function ($key) use ($labels) {
            return $labels[$key] ?? str_replace('_', ' ', ucfirst($key));
        }, $columnKeys);
    }
}

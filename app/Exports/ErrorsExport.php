<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ErrorsExport implements FromArray, WithHeadings
{
    protected $data;

    protected $headings;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->headings = array_shift($data);
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}

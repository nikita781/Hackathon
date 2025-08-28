<?php

namespace App\Exports;

use App\Models\Hackathon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HackathonUsersExport implements FromCollection, ShouldAutoSize, WithStyles
{
    private Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'family' => 'Calibri'],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C4D89B']],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12, 'family' => 'Calibri'],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '8CB4E2']],
            ],
            'A1:B' . ($this->rows->count() + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => 'thin',
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],

        ];
    }

}

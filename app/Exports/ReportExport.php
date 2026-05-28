<?php

namespace App\Exports;

use App\Reports\ReportDataBuilder;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel/CSV export. PDF is handled by ReportExporter using a Blade view.
 */
class ReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $data;

    public function __construct(
        private string $type,
        private array $filters
    ) {
        $from = Carbon::parse($this->filters['from'] ?? now()->startOfMonth());
        $to = Carbon::parse($this->filters['to'] ?? now()->endOfDay());

        $this->data = (new ReportDataBuilder($from, $to, $filters))->build($type);
    }

    public function collection()
    {
        return $this->data['rows'];
    }

    public function headings(): array
    {
        return $this->data['headings'];
    }

    public function title(): string
    {
        return substr($this->data['title'], 0, 31); // Excel sheet name limit
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}

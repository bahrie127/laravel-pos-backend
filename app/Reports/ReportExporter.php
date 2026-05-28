<?php

namespace App\Reports;

use App\Exports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dispatches a report export to xlsx, csv, or pdf format.
 */
class ReportExporter
{
    public const FORMATS = ['xlsx', 'csv', 'pdf'];

    public function __construct(
        private string $type,
        private array $filters
    ) {}

    public function download(string $format): Response
    {
        $format = in_array($format, self::FORMATS, true) ? $format : 'xlsx';
        $filename = "report-{$this->type}-" . now()->format('Ymd-His') . ".{$format}";

        if ($format === 'pdf') {
            return $this->pdf($filename);
        }

        $writer = $format === 'csv' ? ExcelType::CSV : ExcelType::XLSX;

        return Excel::download(new ReportExport($this->type, $this->filters), $filename, $writer);
    }

    private function pdf(string $filename): Response
    {
        $from = Carbon::parse($this->filters['from'] ?? now()->startOfMonth());
        $to = Carbon::parse($this->filters['to'] ?? now()->endOfDay());

        $data = (new ReportDataBuilder($from, $to, $this->filters))->build($this->type);

        $pdf = Pdf::loadView('exports.report-pdf', [
            'data' => $data,
            'from' => $from,
            'to' => $to,
            'filters' => $this->filters,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }
}

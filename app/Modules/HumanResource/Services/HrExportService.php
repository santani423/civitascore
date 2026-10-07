<?php

namespace Modules\HumanResource\Services;

use App\Support\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\University;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export tabel laporan SDM ke Excel (.xlsx via openspout, streaming —
 * tidak menampung seluruh file di memori) atau PDF (dompdf, mekanisme yang
 * sama dengan PDF Modul Ujian).
 */
class HrExportService
{
    /**
     * @param  array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>, summary?: array<string, mixed>}  $report
     */
    public function download(string $format, string $title, array $report): Response
    {
        $filename = Str::slug($title).'-'.now()->format('Ymd-His');

        return $format === 'pdf'
            ? $this->pdf($title, $report, $filename.'.pdf')
            : $this->xlsx($report, $filename.'.xlsx');
    }

    /**
     * @param  array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>}  $report
     */
    private function xlsx(array $report, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($report): void {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValuesWithStyle(
                array_column($report['columns'], 'label'),
                (new Style)->withFontBold(true),
            ));

            foreach ($report['rows'] as $row) {
                $writer->addRow(Row::fromValues(array_map(
                    fn (array $column) => $this->cellValue($row[$column['key']] ?? null),
                    $report['columns'],
                )));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>, summary?: array<string, mixed>}  $report
     */
    private function pdf(string $title, array $report, string $filename): Response
    {
        $university = University::query()->find(app(TenantContext::class)->universityId());

        return Pdf::loadView('hr.report-pdf', [
            'title' => $title,
            'universityName' => $university->name ?? '',
            'columns' => $report['columns'],
            'rows' => $report['rows'],
            'summary' => $report['summary'] ?? [],
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', count($report['columns']) > 6 ? 'landscape' : 'portrait')->download($filename);
    }

    private function cellValue(mixed $value): string|int|float|bool|null
    {
        return is_scalar($value) || $value === null ? $value : (string) json_encode($value);
    }
}

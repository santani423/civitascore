<?php

namespace Modules\Admission\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\FileManagement\Models\FileUpload;

/** PDF sistem (LoA, kartu peserta, kuitansi, bukti registrasi) — 11 §B.8. */
class PdfRenderer
{
    public function __construct(private readonly PmbFileStorage $storage) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $pathParts
     */
    public function render(string $view, array $data, string $fileName, array $pathParts): FileUpload
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4');

        return $this->storage->storeGenerated($pdf->output(), 'application/pdf', 'pdf', $fileName, $pathParts);
    }
}

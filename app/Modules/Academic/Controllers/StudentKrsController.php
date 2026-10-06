<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Controllers\Concerns\ResolvesCurrentStudent;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Requests\AddKrsItemRequest;
use Modules\Academic\Services\KrsPlanService;

/**
 * Portal Mahasiswa — KRS mandiri. Setiap aksi mengembalikan ringkasan KRS
 * terbaru (overview) supaya halaman cukup mengganti state dari satu
 * respons. Seluruh validasi ada di KrsPlanService.
 */
class StudentKrsController extends Controller
{
    use ResolvesCurrentStudent;

    public function __construct(private readonly KrsPlanService $krs) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->krs->overview($this->currentStudent($request)));
    }

    public function offerings(Request $request): JsonResponse
    {
        return ApiResponse::success($this->krs->offerings($this->currentStudent($request)));
    }

    public function history(Request $request): JsonResponse
    {
        return ApiResponse::success($this->krs->history($this->currentStudent($request)));
    }

    public function addItem(AddKrsItemRequest $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        $this->krs->addItem($student, (string) $request->validated('class_section_id'));

        return ApiResponse::success($this->krs->overview($student), 'Mata kuliah ditambahkan ke KRS.', status: 201);
    }

    public function removeItem(Request $request, KrsItem $krsItem): JsonResponse
    {
        $student = $this->currentStudent($request);
        $this->authorize('viewOwn', $krsItem);

        $this->krs->removeItem($student, $krsItem);

        return ApiResponse::success($this->krs->overview($student), 'Mata kuliah dihapus dari KRS.');
    }

    public function submit(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        $this->krs->submit($student);

        return ApiResponse::success($this->krs->overview($student), 'KRS berhasil diajukan dan menunggu persetujuan dosen wali.');
    }

    public function cancel(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        $this->krs->cancelSubmission($student);

        return ApiResponse::success($this->krs->overview($student), 'Pengajuan KRS ditarik kembali. KRS dapat diubah lagi.');
    }
}

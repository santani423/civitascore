<?php

namespace Modules\HumanResource\Resources\Concerns;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Modules\Academic\Models\Employee;
use Modules\FileManagement\Models\FileUpload;
use Modules\FileManagement\Resources\FileUploadResource;

/**
 * Bentuk ringkas relasi yang berulang di banyak resource SDM: pegawai
 * pemilik record dan berkas lampiran.
 *
 * @mixin JsonResource
 */
trait PresentsHrRelations
{
    /**
     * @return array<string, mixed>|MissingValue
     */
    protected function employeeSummary(string $relation = 'employee'): array|MissingValue
    {
        return $this->whenLoaded($relation, function () use ($relation) {
            /** @var Employee|null $employee */
            $employee = $this->resource->getRelation($relation);

            return $employee === null ? null : [
                'id' => $employee->id,
                'name' => $employee->name,
                'nip' => $employee->nip,
                'employee_type' => $employee->employee_type->value,
                'employee_type_label' => $employee->employee_type->label(),
                'unit_kerja' => $employee->unit_kerja,
            ];
        });
    }

    protected function fileSummary(string $relation): FileUploadResource|MissingValue|null
    {
        return $this->whenLoaded($relation, function () use ($relation) {
            /** @var FileUpload|null $file */
            $file = $this->resource->getRelation($relation);

            return $file === null ? null : new FileUploadResource($file);
        });
    }
}

<?php

namespace Modules\Admission\Jobs;

use App\Support\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Services\Selection\SelectionRunService;

class ComputeSelectionRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public readonly string $runId) {}

    public function handle(SelectionRunService $runs, TenantContext $tenant): void
    {
        $run = SelectionRun::query()->withoutGlobalScopes()->find($this->runId);

        if ($run === null) {
            return;
        }

        $previous = $tenant->universityId();
        $tenant->setUniversityId((string) $run->university_id);

        try {
            $runs->compute($run);
        } finally {
            $tenant->setUniversityId($previous);
        }
    }
}

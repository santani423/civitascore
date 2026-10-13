<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\Application;
use Modules\Admission\Support\Actor;

/**
 * Basis controller portal pendaftar: semua record dicari lewat relasi akun
 * yang login (policy kepemilikan, 08 §5 butir 2) → 404 untuk milik orang lain.
 */
abstract class ApplicantController extends Controller
{
    protected function account(Request $request): ApplicantAccount
    {
        /** @var ApplicantAccount $account */
        $account = $request->user();

        return $account;
    }

    protected function actor(Request $request): Actor
    {
        return Actor::applicant($this->account($request));
    }

    protected function application(Request $request, string $id): Application
    {
        return $this->account($request)->applications()->whereKey($id)->firstOrFail();
    }
}

<?php

namespace Modules\Academic\Controllers\Concerns;

use Illuminate\Http\Request;
use Modules\Academic\Models\Student;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Seluruh endpoint Portal Mahasiswa melayani mahasiswa milik akun yang
 * login (`students.user_id`) — tidak pernah dari student_id kiriman klien.
 *
 * Melempar AccessDeniedHttpException (bukan abort(403)): handler di
 * bootstrap/app.php merender pesan exception ini apa adanya, sedangkan
 * HttpException generik dari abort() berubah menjadi "Terjadi kesalahan
 * pada server" saat APP_DEBUG mati.
 */
trait ResolvesCurrentStudent
{
    protected function currentStudent(Request $request): Student
    {
        $student = $request->user()?->student;

        if ($student === null) {
            throw new AccessDeniedHttpException('Akun ini tidak tertaut ke data mahasiswa.');
        }

        return $student;
    }
}

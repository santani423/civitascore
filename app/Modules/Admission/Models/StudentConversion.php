<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Student;
use Modules\Admission\Enums\ConversionStatus;

class StudentConversion extends PmbModel
{
    protected $table = 'pmb_student_conversions';

    protected function casts(): array
    {
        return [
            'status' => ConversionStatus::class,
            'steps_completed' => 'array',
            'attempts' => 'integer',
            'locked_until' => 'datetime',
            'next_attempt_at' => 'datetime',
            'succeeded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return BelongsTo<ReRegistration, $this>
     */
    public function reRegistration(): BelongsTo
    {
        return $this->belongsTo(ReRegistration::class, 're_registration_id');
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

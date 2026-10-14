<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Student;
use Modules\Admission\Enums\ConversionStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property string $re_registration_id
 * @property ConversionStatus $status
 * @property string|null $nim
 * @property string|null $student_id
 * @property string|null $user_id
 * @property array<mixed> $steps_completed
 * @property int $attempts
 * @property CarbonImmutable|null $locked_until
 * @property CarbonImmutable|null $next_attempt_at
 * @property string|null $last_error_code
 * @property string|null $last_error_message
 * @property string|null $email_override
 * @property CarbonImmutable|null $succeeded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Application $application
 * @property-read ReRegistration $reRegistration
 * @property-read Student|null $student
 * @property-read User|null $user
 */
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

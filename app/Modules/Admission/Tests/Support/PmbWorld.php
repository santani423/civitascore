<?php

namespace Modules\Admission\Tests\Support;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Testing\TestResponse;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;
use Modules\Admission\Database\Seeders\AdmissionSeeder;
use Modules\Admission\Models\StudySystem;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Database\Seeders\OrganizationalRoleSeeder;
use Modules\UserManagement\Database\Seeders\RolePermissionSeeder;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;
use Modules\UserManagement\Support\PermissionRegistry;
use Tests\TestCase;

/**
 * Fixture bersama tes fitur PMB: satu tenant dengan role & permission hasil
 * seeder produksi (bukan permission tiruan), staf per role fungsional, data
 * akademik minimal, dan helper request staf/pendaftar.
 */
final class PmbWorld
{
    public University $university;

    public AcademicTerm $term;

    public Faculty $faculty;

    public StudyProgram $program;

    public StudyProgram $otherProgram;

    public StudySystem $system;

    /** @var array<string, User> */
    public array $staff = [];

    public function __construct(private readonly TestCase $test) {}

    public static function create(TestCase $test, string $code = 'UPMB'): self
    {
        $world = new self($test);
        $test->seed([RolePermissionSeeder::class, OrganizationalRoleSeeder::class]);
        $world->university = University::factory()->create(['code' => $code, 'is_active' => true, 'timezone' => 'Asia/Jakarta']);

        $tenant = app(TenantContext::class);
        $tenant->setUniversityId($world->university->getKey());
        $world->term = AcademicTerm::factory()->create(['university_id' => $world->university->getKey()]);
        $world->faculty = Faculty::factory()->create(['university_id' => $world->university->getKey()]);
        $world->program = StudyProgram::factory()->create(['university_id' => $world->university->getKey(), 'faculty_id' => $world->faculty->getKey(), 'code' => 'TI', 'degree_level' => 'S1']);
        $world->otherProgram = StudyProgram::factory()->create(['university_id' => $world->university->getKey(), 'faculty_id' => $world->faculty->getKey(), 'code' => 'SI', 'degree_level' => 'S1']);
        (new AdmissionSeeder)->run($world->university);
        $world->system = StudySystem::query()->where('code', 'REG')->firstOrFail();
        $tenant->setUniversityId(null);

        foreach (['admission_administrator', 'admission_approver', 'admission_verifier', 'admission_selection_committee', 'admission_registrar', 'finance_administrator'] as $role) {
            $world->staff[$role] = $world->staffWithRole($role);
        }

        return $world;
    }

    /**
     * @param  array{type: string, id: string}|null  $scope
     */
    public function staffWithRole(string $roleSlug, ?array $scope = null): User
    {
        $user = User::factory()->create();
        UserUniversity::query()->create([
            'user_id' => $user->getKey(), 'university_id' => $this->university->getKey(),
            'membership_type' => MembershipType::Admin, 'status' => MembershipStatus::Active, 'joined_at' => now(), 'is_default' => true,
        ]);
        UserRole::query()->create([
            'user_id' => $user->getKey(), 'role_id' => Role::query()->where('slug', $roleSlug)->whereNull('university_id')->firstOrFail()->getKey(),
            'university_id' => $this->university->getKey(), 'assigned_at' => now(),
            'scope_type' => $scope['type'] ?? null, 'scope_id' => $scope['id'] ?? null,
        ]);
        PermissionRegistry::flushAll();

        return $user;
    }

    /** @param  array<string, mixed>  $data */
    public function asStaff(User|string $user, string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $user = is_string($user) ? $this->staff[$user] : $user;
        $this->reset();

        return $this->test->actingAs($user)->json($method, '/api/v1/pmb/admin/'.ltrim($uri, '/'), $data, ['X-University-ID' => $this->university->getKey(), ...$headers]);
    }

    /** @param  array<string, mixed>  $data */
    public function asApplicant(string $token, string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $this->reset();

        return $this->test->withHeaders(['Authorization' => 'Bearer '.$token, ...$headers])->json($method, '/api/v1/pmb/applicant/'.ltrim($uri, '/'), $data);
    }

    /** @param  array<string, mixed>  $data */
    public function asPublic(string $method, string $uri, array $data = []): TestResponse
    {
        $this->reset();

        return $this->test->json($method, '/api/v1/pmb/'.ltrim($uri, '/'), $data, ['X-University-Code' => $this->university->code]);
    }

    /** Setiap request seperti request baru: guard, header bawaan, dan tenant dikosongkan. */
    private function reset(): void
    {
        app('auth')->forgetGuards();
        $this->test->flushHeaders();
        app(TenantContext::class)->setUniversityId(null);
    }

    public function tenant(): void
    {
        app(TenantContext::class)->setUniversityId($this->university->getKey());
    }
}

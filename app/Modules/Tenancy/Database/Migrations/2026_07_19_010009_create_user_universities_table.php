<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;

/**
 * A user's identity stays global (users table) — this is the membership
 * record that ties a user to one or more universities. A user with no row
 * here (e.g. the platform super_admin) belongs to no tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_universities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->enum('membership_type', array_column(MembershipType::cases(), 'value'));
            $table->enum('status', array_column(MembershipStatus::cases(), 'value'))->default(MembershipStatus::Active->value);
            $table->timestampTz('joined_at')->nullable();
            $table->timestampTz('left_at')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();

            $table->unique(['user_id', 'university_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_universities');
    }
};

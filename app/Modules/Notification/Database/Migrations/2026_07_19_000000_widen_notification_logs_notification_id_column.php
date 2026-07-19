<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notification_id was a ulid() (26 chars), but Laravel's built-in
 * notifications (e.g. Illuminate\Auth\Notifications\ResetPassword) use a
 * UUID id (36 chars) — every log insert for those truncated and failed.
 * Widened to a plain string since this column has no FK and holds IDs from
 * both ULID- and UUID-based notification classes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table): void {
            $table->string('notification_id', 191)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table): void {
            $table->char('notification_id', 26)->nullable()->change();
        });
    }
};

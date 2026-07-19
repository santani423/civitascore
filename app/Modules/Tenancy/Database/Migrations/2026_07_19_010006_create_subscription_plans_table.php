<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            // e.g. {"max_students": 5000, "max_users": 300, "max_storage_gb": 50}
            $table->json('limits')->nullable();
            $table->decimal('price', 14, 2)->default(0);
            $table->string('billing_period')->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};

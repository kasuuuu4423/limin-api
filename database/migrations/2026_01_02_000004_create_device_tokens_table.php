<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 200);
            $table->string('platform', 20)->default('ios');
            $table->string('device_name', 100)->nullable();
            $table->string('bundle_id', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampsTz();

            $table->index('user_id', 'device_tokens_user_id_index');
            $table->unique('token', 'device_tokens_token_unique');
            $table->index(['user_id', 'is_active'], 'device_tokens_user_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};

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
        Schema::create('limin_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id', 100)->nullable();
            $table->timestampTz('started_at')->useCurrent();
            $table->timestampTz('stopped_at')->nullable();
            $table->uuid('current_item_id')->nullable();
            $table->timestampTz('current_item_presented_at')->nullable();
            $table->timestampTz('interrupt_offered_at')->nullable();
            $table->timestampTz('interrupt_accepted_at')->nullable();
            $table->timestampsTz();

            $table->index('user_id', 'limin_sessions_user_id_index');
            $table->index(['user_id', 'stopped_at'], 'limin_sessions_user_active_index');
            $table->index('current_item_id', 'limin_sessions_current_item_id_index');

            $table->foreign('current_item_id')
                ->references('id')
                ->on('items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('limin_sessions');
    }
};

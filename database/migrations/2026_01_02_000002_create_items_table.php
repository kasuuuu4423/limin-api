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
        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('task');
            $table->string('state', 20)->default('DO');
            $table->string('availability', 20)->default('NOW');
            $table->string('next_action', 500);
            $table->timestampTz('due_at')->nullable();
            $table->integer('timebox')->nullable();
            $table->boolean('meta')->default(false);
            $table->timestampTz('last_presented_at')->nullable();
            $table->timestampTz('done_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['user_id', 'availability', 'state', 'created_at'], 'items_user_availability_state_created_index');
            $table->index(['user_id', 'due_at'], 'items_user_due_at_index');
            $table->index(['user_id', 'meta'], 'items_user_meta_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

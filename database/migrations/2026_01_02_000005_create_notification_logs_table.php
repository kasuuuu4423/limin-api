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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('device_token_id');
            $table->uuid('item_id')->nullable();
            $table->string('notification_type', 50);
            $table->jsonb('payload');
            $table->string('status', 20);
            $table->uuid('apns_id')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index('user_id', 'notification_logs_user_id_index');
            $table->index('device_token_id', 'notification_logs_device_token_id_index');
            $table->index('item_id', 'notification_logs_item_id_index');
            $table->index('status', 'notification_logs_status_index');
            $table->index('created_at', 'notification_logs_created_at_index');

            $table->foreign('device_token_id')
                ->references('id')
                ->on('device_tokens')
                ->cascadeOnDelete();
            $table->foreign('item_id')
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
        Schema::dropIfExists('notification_logs');
    }
};

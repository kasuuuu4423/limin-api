<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: title カラムを nullable で追加
        Schema::table('items', function (Blueprint $table) {
            $table->string('title', 500)->nullable()->after('availability');
        });

        // Step 2: 既存データの移行: next_action の値を title にコピー
        DB::table('items')->whereNotNull('next_action')->update([
            'title' => DB::raw('next_action'),
        ]);

        // Step 3: title を NOT NULL に変更、next_action を nullable に変更
        Schema::table('items', function (Blueprint $table) {
            $table->string('title', 500)->nullable(false)->change();
            $table->string('next_action', 500)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // next_action を NOT NULL に戻す（title の値をコピー）
        DB::table('items')->whereNull('next_action')->update([
            'next_action' => DB::raw('title'),
        ]);

        Schema::table('items', function (Blueprint $table) {
            $table->string('next_action', 500)->nullable(false)->change();
            $table->dropColumn('title');
        });
    }
};


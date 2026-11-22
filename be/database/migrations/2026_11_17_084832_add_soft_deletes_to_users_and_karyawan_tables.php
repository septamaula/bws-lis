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
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('karyawan') && ! Schema::hasColumn('karyawan', 'deleted_at')) {
            Schema::table('karyawan', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('user') && ! Schema::hasColumn('user', 'deleted_at')) {
            Schema::table('user', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('karyawan') && Schema::hasColumn('karyawan', 'deleted_at')) {
            Schema::table('karyawan', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('user') && Schema::hasColumn('user', 'deleted_at')) {
            Schema::table('user', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};

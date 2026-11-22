<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. user_level
        if (! Schema::hasTable('user_level')) {
            Schema::create('user_level', function (Blueprint $table) {
                $table->id('level_id');
                $table->string('level', 100);
                $table->string('role', 50)->unique();
                $table->text('keterangan')->nullable();
                $table->boolean('is_admin')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. user_access
        if (! Schema::hasTable('user_access')) {
            Schema::create('user_access', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('level_id');
                $table->string('controller_key', 150);
                $table->timestamps();

                $table->foreign('level_id')->references('level_id')->on('user_level')->onDelete('cascade');
                $table->index(['level_id', 'controller_key']);
            });
        }

        // 3. user (LDMS structure)
        if (! Schema::hasTable('user')) {
            Schema::create('user', function (Blueprint $table) {
                $table->id('user_id');
                $table->string('username', 100)->unique();
                $table->string('password', 255);
                $table->string('nama', 150);
                $table->string('telpon', 50)->nullable();
                $table->string('email', 100)->unique();
                $table->unsignedBigInteger('level_id')->nullable();
                $table->unsignedBigInteger('karyawan_id')->nullable();
                $table->integer('project_id')->nullable();
                $table->string('foto', 255)->nullable();
                $table->string('remember_token', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('level_id')->references('level_id')->on('user_level')->onDelete('set null');
                if (Schema::hasTable('karyawan')) {
                    $table->foreign('karyawan_id')->references('karyawan_id')->on('karyawan')->onDelete('set null');
                }
            });
        }

        // 4. Update users table with level_id and karyawan_id if not present
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'level_id')) {
                    $table->unsignedBigInteger('level_id')->nullable()->after('role_name');
                    $table->foreign('level_id')->references('level_id')->on('user_level')->onDelete('set null');
                }
                if (! Schema::hasColumn('users', 'karyawan_id')) {
                    $table->unsignedBigInteger('karyawan_id')->nullable()->after('level_id');
                    if (Schema::hasTable('karyawan')) {
                        $table->foreign('karyawan_id')->references('karyawan_id')->on('karyawan')->onDelete('set null');
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'karyawan_id')) {
                    $table->dropForeign(['karyawan_id']);
                    $table->dropColumn('karyawan_id');
                }
                if (Schema::hasColumn('users', 'level_id')) {
                    $table->dropForeign(['level_id']);
                    $table->dropColumn('level_id');
                }
            });
        }
        Schema::dropIfExists('user');
        Schema::dropIfExists('user_access');
        Schema::dropIfExists('user_level');
    }
};

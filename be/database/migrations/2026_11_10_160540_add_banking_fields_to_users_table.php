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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 50)->nullable()->unique()->after('id');
            $table->string('role_name', 50)->default('Employee / Participant')->after('email');
            $table->string('department', 100)->nullable()->after('role_name');
            $table->string('branch', 100)->nullable()->after('department');
            $table->string('phone', 30)->nullable()->after('branch');
            $table->string('avatar')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'role_name',
                'department',
                'branch',
                'phone',
                'avatar',
                'is_active',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};

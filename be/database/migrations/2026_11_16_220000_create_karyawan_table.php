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
        if (! Schema::hasTable('karyawan')) {
            Schema::create('karyawan', function (Blueprint $table) {
                $table->bigIncrements('karyawan_id');
                $table->string('nik', 50)->unique();
                $table->string('nama', 150);
                $table->string('nama_panggilan', 50)->nullable();
                $table->string('jk', 10)->nullable();
                $table->string('email', 100)->unique();
                $table->string('hp', 30)->nullable();
                $table->string('departemen', 100)->nullable();
                $table->string('jabatan', 100)->nullable();
                $table->string('kantor_cabang', 100)->nullable();
                $table->string('golongan', 50)->nullable();
                $table->string('st_kerja', 30)->default('AKTIF');
                $table->date('tgl_masuk')->nullable();
                $table->string('foto')->nullable();
                $table->string('role_name', 50)->default('Employee / Participant');
                $table->integer('target_jam_pelatihan')->default(20);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawan');
    }
};

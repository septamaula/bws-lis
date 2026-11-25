<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('training_locations')) {
            Schema::create('training_locations', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('type', 50)->default('Physical Room'); // Physical Room, Virtual Platform, Auditorium, Computer Lab
                $table->string('building', 150)->nullable();
                $table->string('floor', 50)->nullable();
                $table->integer('capacity')->default(30);
                $table->text('facilities')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Seed initial training locations
        $locations = [
            [
                'code' => 'RNG-01',
                'name' => 'Ruang 1 - BWS Corporate Learning Center',
                'type' => 'Physical Room',
                'building' => 'BWS Corporate Learning Center',
                'floor' => 'Lt. 8',
                'capacity' => 30,
                'facilities' => 'Proyektor HD, Audio System, Wi-Fi High Speed, AC Central, Smart Board',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'RNG-02',
                'name' => 'Ruang 2 - BWS Corporate Learning Center',
                'type' => 'Physical Room',
                'building' => 'BWS Corporate Learning Center',
                'floor' => 'Lt. 8',
                'capacity' => 25,
                'facilities' => 'Smart TV 75", Soundbar, Wi-Fi, AC Central, Whiteboard',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'RNG-101',
                'name' => 'Ruang Diklat 101 - BWS Tower',
                'type' => 'Physical Room',
                'building' => 'Menara BWS Kantor Pusat',
                'floor' => 'Lt. 10',
                'capacity' => 40,
                'facilities' => 'Dual Projector, Microphone Wireless, Sound System, Wi-Fi, Podium',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'AUD-01',
                'name' => 'BWS Auditorium Lt. 7 Sudirman',
                'type' => 'Auditorium',
                'building' => 'Menara BWS Sudirman',
                'floor' => 'Lt. 7',
                'capacity' => 100,
                'facilities' => 'Panggung Acara, Sound System Konser, Videotron LED Wall, Lighting',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'LAB-01',
                'name' => 'Laboratorium Komputer & Simulasi Banking',
                'type' => 'Computer Lab',
                'building' => 'BWS Corporate Learning Center',
                'floor' => 'Lt. 8',
                'capacity' => 30,
                'facilities' => '30 PC Terminal Core Banking, Dual Monitor, LAN Gigabit, Proyektor',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'VRT-01',
                'name' => 'Virtual Classroom (Zoom / MS Teams BWS)',
                'type' => 'Virtual Platform',
                'building' => 'Online / Cloud',
                'floor' => '-',
                'capacity' => 500,
                'facilities' => 'Akun Zoom Pro Enterprise BWS, Breakout Rooms, Cloud Recording',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'LMS-01',
                'name' => 'ELAPSE E-Learning LMS Portal',
                'type' => 'Virtual Platform',
                'building' => 'Online / Cloud',
                'floor' => '-',
                'capacity' => 1000,
                'facilities' => 'Server SCORM 2004, Quiz Engine, Automated Certificate, 24/7 Access',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($locations as $loc) {
            DB::table('training_locations')->updateOrInsert(
                ['code' => $loc['code']],
                $loc
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_locations');
    }
};

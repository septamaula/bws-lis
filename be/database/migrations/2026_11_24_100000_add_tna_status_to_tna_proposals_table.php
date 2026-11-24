<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tna_proposals', function (Blueprint $table) {
            if (! Schema::hasColumn('tna_proposals', 'tna_status')) {
                $table->string('tna_status', 20)->default('Aktif')->after('status');
            }
        });

        // Set 2026 TNA to 'Aktif', older years to 'Closed'
        DB::table('tna_proposals')->where('year', 2026)->update(['tna_status' => 'Aktif']);
        DB::table('tna_proposals')->where('year', '<', 2026)->update(['tna_status' => 'Closed']);
    }

    public function down(): void
    {
        Schema::table('tna_proposals', function (Blueprint $table) {
            if (Schema::hasColumn('tna_proposals', 'tna_status')) {
                $table->dropColumn('tna_status');
            }
        });
    }
};

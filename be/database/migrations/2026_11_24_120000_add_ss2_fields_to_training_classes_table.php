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
        Schema::table('training_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('training_classes', 'tna_proposal_id')) {
                $table->foreignId('tna_proposal_id')->nullable()->after('program_id')->constrained('tna_proposals')->nullOnDelete();
            }
            if (! Schema::hasColumn('training_classes', 'pic_name')) {
                $table->string('pic_name', 150)->nullable()->after('class_code');
            }
            if (! Schema::hasColumn('training_classes', 'documents')) {
                $table->json('documents')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('training_classes', 'documentation_photos')) {
                $table->json('documentation_photos')->nullable()->after('documents');
            }
            if (! Schema::hasColumn('training_classes', 'evaluation_config')) {
                $table->json('evaluation_config')->nullable()->after('documentation_photos');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_classes', function (Blueprint $table) {
            $table->dropForeign(['tna_proposal_id']);
            $table->dropColumn([
                'tna_proposal_id',
                'pic_name',
                'documents',
                'documentation_photos',
                'evaluation_config',
            ]);
        });
    }
};

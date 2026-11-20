<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance tna_proposals table
        Schema::table('tna_proposals', function (Blueprint $table) {
            if (!Schema::hasColumn('tna_proposals', 'minimum_regulator_investment')) {
                $table->decimal('minimum_regulator_investment', 15, 2)->nullable()->after('total_budget_requested');
            }
            if (!Schema::hasColumn('tna_proposals', 'realization_amount')) {
                $table->decimal('realization_amount', 15, 2)->default(0)->after('minimum_regulator_investment');
            }
        });

        // 2. Enhance tna_items table
        Schema::table('tna_items', function (Blueprint $table) {
            if (!Schema::hasColumn('tna_items', 'realization_amount')) {
                $table->decimal('realization_amount', 15, 2)->default(0)->after('budget');
            }
        });

        // 3. Enhance training_programs table
        Schema::table('training_programs', function (Blueprint $table) {
            if (!Schema::hasColumn('training_programs', 'start_date')) {
                $table->date('start_date')->nullable()->after('title');
            }
            if (!Schema::hasColumn('training_programs', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('training_programs', 'facilitator')) {
                $table->string('facilitator', 150)->nullable()->after('location');
            }
            if (!Schema::hasColumn('training_programs', 'duration_text')) {
                $table->string('duration_text', 100)->nullable()->after('duration_hours');
            }
            if (!Schema::hasColumn('training_programs', 'program_type')) {
                $table->string('program_type', 100)->nullable()->after('facilitator');
            }
            if (!Schema::hasColumn('training_programs', 'unit_initiator')) {
                $table->string('unit_initiator', 150)->nullable()->after('program_type');
            }
            if (!Schema::hasColumn('training_programs', 'activity_type')) {
                $table->string('activity_type', 100)->nullable()->after('unit_initiator');
            }
            if (!Schema::hasColumn('training_programs', 'bi_category')) {
                $table->string('bi_category', 150)->nullable()->after('activity_type');
            }
            // 10 Budget line items
            if (!Schema::hasColumn('training_programs', 'budget_fee_facilitator')) {
                $table->decimal('budget_fee_facilitator', 15, 2)->default(0)->after('total_budget');
            }
            if (!Schema::hasColumn('training_programs', 'budget_meals')) {
                $table->decimal('budget_meals', 15, 2)->default(0)->after('budget_fee_facilitator');
            }
            if (!Schema::hasColumn('training_programs', 'budget_hotel')) {
                $table->decimal('budget_hotel', 15, 2)->default(0)->after('budget_meals');
            }
            if (!Schema::hasColumn('training_programs', 'budget_meeting_package')) {
                $table->decimal('budget_meeting_package', 15, 2)->default(0)->after('budget_hotel');
            }
            if (!Schema::hasColumn('training_programs', 'budget_transportation')) {
                $table->decimal('budget_transportation', 15, 2)->default(0)->after('budget_meeting_package');
            }
            if (!Schema::hasColumn('training_programs', 'budget_business_trip')) {
                $table->decimal('budget_business_trip', 15, 2)->default(0)->after('budget_transportation');
            }
            if (!Schema::hasColumn('training_programs', 'budget_reward')) {
                $table->decimal('budget_reward', 15, 2)->default(0)->after('budget_business_trip');
            }
            if (!Schema::hasColumn('training_programs', 'budget_material')) {
                $table->decimal('budget_material', 15, 2)->default(0)->after('budget_reward');
            }
            if (!Schema::hasColumn('training_programs', 'budget_internet')) {
                $table->decimal('budget_internet', 15, 2)->default(0)->after('budget_material');
            }
            if (!Schema::hasColumn('training_programs', 'budget_miscellaneous')) {
                $table->decimal('budget_miscellaneous', 15, 2)->default(0)->after('budget_internet');
            }
            // Realization on program
            if (!Schema::hasColumn('training_programs', 'realization_amount')) {
                $table->decimal('realization_amount', 15, 2)->default(0)->after('budget_miscellaneous');
            }
            // Documents 1 & 2
            if (!Schema::hasColumn('training_programs', 'doc1_path')) {
                $table->string('doc1_path')->nullable()->after('approval_doc_path');
            }
            if (!Schema::hasColumn('training_programs', 'doc1_name')) {
                $table->string('doc1_name')->nullable()->after('doc1_path');
            }
            if (!Schema::hasColumn('training_programs', 'doc1_remarks')) {
                $table->string('doc1_remarks')->nullable()->after('doc1_name');
            }
            if (!Schema::hasColumn('training_programs', 'doc2_path')) {
                $table->string('doc2_path')->nullable()->after('doc1_remarks');
            }
            if (!Schema::hasColumn('training_programs', 'doc2_name')) {
                $table->string('doc2_name')->nullable()->after('doc2_path');
            }
            // Notes & approval scenario
            if (!Schema::hasColumn('training_programs', 'notes')) {
                $table->text('notes')->nullable()->after('rejection_notes');
            }
            if (!Schema::hasColumn('training_programs', 'approval_scenario')) {
                $table->string('approval_scenario', 50)->default('WITH_BUDGET')->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tna_proposals', function (Blueprint $table) {
            $table->dropColumn(['minimum_regulator_investment', 'realization_amount']);
        });

        Schema::table('tna_items', function (Blueprint $table) {
            $table->dropColumn(['realization_amount']);
        });

        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropColumn([
                'start_date', 'end_date', 'facilitator', 'duration_text', 'program_type',
                'unit_initiator', 'activity_type', 'bi_category',
                'budget_fee_facilitator', 'budget_meals', 'budget_hotel', 'budget_meeting_package',
                'budget_transportation', 'budget_business_trip', 'budget_reward', 'budget_material',
                'budget_internet', 'budget_miscellaneous', 'realization_amount',
                'doc1_path', 'doc1_name', 'doc1_remarks', 'doc2_path', 'doc2_name',
                'notes', 'approval_scenario'
            ]);
        });
    }
};

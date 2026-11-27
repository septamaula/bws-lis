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
        Schema::table('training_programs', function (Blueprint $table) {
            if (Schema::hasColumn('training_programs', 'passing_grade')) {
                $table->integer('passing_grade')->default(70)->change();
            }
        });

        Schema::table('training_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('training_classes', 'instructors')) {
                $table->json('instructors')->nullable()->after('trainer_name');
            }
            if (Schema::hasColumn('training_classes', 'passing_grade')) {
                $table->integer('passing_grade')->default(70)->change();
            }
        });

        Schema::table('class_enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('class_enrollments', 'participant_code')) {
                $table->string('participant_code', 50)->nullable()->index()->after('id');
            }
            if (! Schema::hasColumn('class_enrollments', 'attendance_remark')) {
                $table->string('attendance_remark', 60)->nullable()->after('attendance_status');
            }
            if (! Schema::hasColumn('class_enrollments', 'invitation_point')) {
                $table->unsignedTinyInteger('invitation_point')->default(1)->after('attendance_remark');
            }
            if (! Schema::hasColumn('class_enrollments', 'point_add')) {
                $table->unsignedTinyInteger('point_add')->default(0)->after('invitation_point');
            }
            if (! Schema::hasColumn('class_enrollments', 'leave_status')) {
                $table->string('leave_status', 40)->nullable()->after('attendance_percentage');
            }
            if (! Schema::hasColumn('class_enrollments', 'leave_notes')) {
                $table->text('leave_notes')->nullable()->after('leave_status');
            }
            if (! Schema::hasColumn('class_enrollments', 'leave_attachment_url')) {
                $table->string('leave_attachment_url', 255)->nullable()->after('leave_notes');
            }
            if (! Schema::hasColumn('class_enrollments', 'commitment_signed_at')) {
                $table->timestamp('commitment_signed_at')->nullable()->after('certificate_issued_at');
            }
            if (! Schema::hasColumn('class_enrollments', 'eval_committee_rating')) {
                $table->decimal('eval_committee_rating', 4, 2)->nullable()->after('eval_facility_rating');
            }
            if (! Schema::hasColumn('class_enrollments', 'imported_at')) {
                $table->timestamp('imported_at')->nullable()->after('commitment_signed_at');
            }

            $table->decimal('eval_instructor_rating', 4, 2)->nullable()->change();
            $table->decimal('eval_material_rating', 4, 2)->nullable()->change();
            $table->decimal('eval_facility_rating', 4, 2)->nullable()->change();
        });

        Schema::table('class_evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('class_evaluations', 'evaluation_code')) {
                $table->string('evaluation_code', 50)->nullable()->index()->after('id');
            }
            if (! Schema::hasColumn('class_evaluations', 'committee_rating')) {
                $table->decimal('committee_rating', 4, 2)->nullable()->after('facility_rating');
            }
            if (! Schema::hasColumn('class_evaluations', 'material_scores')) {
                $table->json('material_scores')->nullable()->after('committee_rating');
            }
            if (! Schema::hasColumn('class_evaluations', 'instructor_scores')) {
                $table->json('instructor_scores')->nullable()->after('material_scores');
            }
            if (! Schema::hasColumn('class_evaluations', 'committee_scores')) {
                $table->json('committee_scores')->nullable()->after('instructor_scores');
            }
            if (! Schema::hasColumn('class_evaluations', 'material_avg')) {
                $table->decimal('material_avg', 4, 2)->nullable()->after('committee_scores');
            }
            if (! Schema::hasColumn('class_evaluations', 'instructor_avg')) {
                $table->decimal('instructor_avg', 4, 2)->nullable()->after('material_avg');
            }
            if (! Schema::hasColumn('class_evaluations', 'committee_avg')) {
                $table->decimal('committee_avg', 4, 2)->nullable()->after('instructor_avg');
            }
            if (! Schema::hasColumn('class_evaluations', 'overall_avg')) {
                $table->decimal('overall_avg', 4, 2)->nullable()->after('committee_avg');
            }

            $table->decimal('instructor_rating', 4, 2)->default(4)->change();
            $table->decimal('material_rating', 4, 2)->default(4)->change();
            $table->decimal('facility_rating', 4, 2)->default(4)->change();
            $table->decimal('recommendation_rating', 4, 2)->default(4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_evaluations', function (Blueprint $table) {
            $cols = array_filter([
                'evaluation_code',
                'committee_rating',
                'material_scores',
                'instructor_scores',
                'committee_scores',
                'material_avg',
                'instructor_avg',
                'committee_avg',
                'overall_avg',
            ], fn (string $c): bool => Schema::hasColumn('class_evaluations', $c));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('class_enrollments', function (Blueprint $table) {
            $cols = array_filter([
                'participant_code',
                'attendance_remark',
                'invitation_point',
                'point_add',
                'leave_status',
                'leave_notes',
                'leave_attachment_url',
                'commitment_signed_at',
                'eval_committee_rating',
                'imported_at',
            ], fn (string $c): bool => Schema::hasColumn('class_enrollments', $c));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('training_classes', function (Blueprint $table) {
            if (Schema::hasColumn('training_classes', 'instructors')) {
                $table->dropColumn('instructors');
            }
        });
    }
};

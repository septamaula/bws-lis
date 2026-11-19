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
        // 1. Enhance training_classes
        Schema::table('training_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('training_classes', 'daily_schedule')) {
                $table->string('daily_schedule', 100)->nullable()->default('08:30 - 16:30 WIB');
            }
            if (!Schema::hasColumn('training_classes', 'trainer_name')) {
                $table->string('trainer_name', 150)->nullable();
            }
            if (!Schema::hasColumn('training_classes', 'passing_grade')) {
                $table->integer('passing_grade')->default(75);
            }
            if (!Schema::hasColumn('training_classes', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('training_classes', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('training_classes', 'is_attendance_open')) {
                $table->boolean('is_attendance_open')->default(true);
            }
        });

        // 2. Enhance class_enrollments
        try {
            Schema::table('class_enrollments', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->change();
            });
        } catch (\Throwable $e) {
            // Already nullable or driver-specific constraint
        }

        Schema::table('class_enrollments', function (Blueprint $table) {

            if (!Schema::hasColumn('class_enrollments', 'nik')) {
                $table->string('nik', 50)->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'participant_name')) {
                $table->string('participant_name', 150)->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'department')) {
                $table->string('department', 100)->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'branch')) {
                $table->string('branch', 100)->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'position')) {
                $table->string('position', 100)->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'attendance_percentage')) {
                $table->decimal('attendance_percentage', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('class_enrollments', 'eval_instructor_rating')) {
                $table->integer('eval_instructor_rating')->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'eval_material_rating')) {
                $table->integer('eval_material_rating')->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'eval_facility_rating')) {
                $table->integer('eval_facility_rating')->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'eval_notes')) {
                $table->text('eval_notes')->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'grade')) {
                $table->string('grade', 5)->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'certificate_number')) {
                $table->string('certificate_number', 100)->nullable();
            }
            if (!Schema::hasColumn('class_enrollments', 'certificate_issued_at')) {
                $table->timestamp('certificate_issued_at')->nullable();
            }
        });

        // 3. Create class_sessions
        if (!Schema::hasTable('class_sessions')) {
            Schema::create('class_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_class_id')->constrained('training_classes')->cascadeOnDelete();
                $table->integer('session_number')->default(1);
                $table->string('session_name', 150);
                $table->date('session_date');
                $table->string('start_time', 20)->default('08:30');
                $table->string('end_time', 20)->default('16:30');
                $table->string('topic', 255)->nullable();
                $table->string('trainer_name', 150)->nullable();
                $table->string('location_or_url', 255)->nullable();
                $table->timestamps();
            });
        }

        // 4. Create class_attendances
        if (!Schema::hasTable('class_attendances')) {
            Schema::create('class_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_class_id')->constrained('training_classes')->cascadeOnDelete();
                $table->foreignId('class_session_id')->nullable()->constrained('class_sessions')->cascadeOnDelete();
                $table->foreignId('class_enrollment_id')->constrained('class_enrollments')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('attendance_date');
                $table->string('status', 30)->default('HADIR'); // HADIR, IZIN, SAKIT, ALPA
                $table->string('check_in_time', 50)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['training_class_id', 'class_session_id', 'class_enrollment_id'], 'cls_att_unique_session_enrollment');
            });
        }

        // 5. Create class_evaluations
        if (!Schema::hasTable('class_evaluations')) {
            Schema::create('class_evaluations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_class_id')->constrained('training_classes')->cascadeOnDelete();
                $table->foreignId('class_enrollment_id')->constrained('class_enrollments')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('instructor_rating')->default(5);
                $table->integer('material_rating')->default(5);
                $table->integer('facility_rating')->default(5);
                $table->integer('recommendation_rating')->default(5);
                $table->text('feedback_notes')->nullable();
                $table->timestamps();

                $table->unique(['training_class_id', 'class_enrollment_id'], 'cls_eval_unique_enrollment');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_evaluations');
        Schema::dropIfExists('class_attendances');
        Schema::dropIfExists('class_sessions');

        Schema::table('class_enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'participant_name',
                'department',
                'branch',
                'position',
                'attendance_percentage',
                'eval_instructor_rating',
                'eval_material_rating',
                'eval_facility_rating',
                'eval_notes',
                'grade',
                'certificate_number',
                'certificate_issued_at',
            ]);
        });

        Schema::table('training_classes', function (Blueprint $table) {
            $table->dropColumn([
                'daily_schedule',
                'trainer_name',
                'passing_grade',
                'description',
                'notes',
                'is_attendance_open',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Modul yang Terdaftar untuk Multi-Approval
        if (!Schema::hasTable('approval_modules')) {
            Schema::create('approval_modules', function (Blueprint $table) {
                $table->id();
                $table->string('module_code', 50)->unique(); // tna, class, transaction, certification
                $table->string('module_name', 150);
                $table->text('description')->nullable();
                $table->string('icon_name', 50)->default('FileCheck2');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Tabel Tahapan Alur Multi-Approval (Workflow Steps)
        if (!Schema::hasTable('approval_flow_steps')) {
            Schema::create('approval_flow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_id')->constrained('approval_modules')->cascadeOnDelete();
                $table->integer('step_order')->default(1); // 1, 2, 3, 4
                $table->string('step_name', 150); // e.g. "Reviewer 1 - HCBP", "Checker - HC Learning Head", etc.
                $table->string('role_name', 100); // Super Admin, Dept Head, HCBP, HC Learning Head (Checker), Executive
                $table->decimal('min_amount', 15, 2)->nullable()->default(0);
                $table->decimal('max_amount', 15, 2)->nullable();
                $table->integer('sla_days')->default(3); // SLA hari kerja
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['module_id', 'step_order']);
            });
        }

        // 3. Tabel Rekam Jejak Audit Multi-Approval (Approval Histories / Logs)
        if (!Schema::hasTable('approval_histories')) {
            Schema::create('approval_histories', function (Blueprint $table) {
                $table->id();
                $table->string('module_code', 50);
                $table->unsignedBigInteger('record_id'); // ID dari tna_proposals, training_classes, dll.
                $table->integer('step_order');
                $table->string('step_name', 150);
                $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('approver_name', 150);
                $table->string('approver_nik', 50)->nullable();
                $table->string('role_name', 100);
                $table->string('action', 50); // SUBMITTED, APPROVED, REJECTED, REVISED
                $table->text('notes')->nullable();
                $table->decimal('approved_amount', 15, 2)->nullable();
                $table->timestamp('action_at');
                $table->timestamps();

                $table->index(['module_code', 'record_id']);
            });
        }

        // 4. Update tabel tna_proposals dengan atribut standar perbankan
        if (Schema::hasTable('tna_proposals')) {
            Schema::table('tna_proposals', function (Blueprint $table) {
                if (!Schema::hasColumn('tna_proposals', 'strategic_objective')) {
                    $table->text('strategic_objective')->nullable();
                }
                if (!Schema::hasColumn('tna_proposals', 'target_quarter')) {
                    $table->string('target_quarter', 50)->default('All Quarters (Q1-Q4)');
                }
                if (!Schema::hasColumn('tna_proposals', 'current_step_order')) {
                    $table->integer('current_step_order')->default(1);
                }
                if (!Schema::hasColumn('tna_proposals', 'approval_status_badge')) {
                    $table->string('approval_status_badge', 100)->default('Menunggu Review');
                }
                if (!Schema::hasColumn('tna_proposals', 'revision_count')) {
                    $table->integer('revision_count')->default(0);
                }
                if (!Schema::hasColumn('tna_proposals', 'rejection_notes')) {
                    $table->text('rejection_notes')->nullable();
                }
                if (!Schema::hasColumn('tna_proposals', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 5. Update tabel tna_items dengan atribut rincian kompetensi & biaya perbankan
        if (Schema::hasTable('tna_items')) {
            Schema::table('tna_items', function (Blueprint $table) {
                if (!Schema::hasColumn('tna_items', 'job_family')) {
                    $table->string('job_family', 100)->default('General Banking');
                }
                if (!Schema::hasColumn('tna_items', 'training_type')) {
                    $table->string('training_type', 100)->default('In-House Training');
                }
                if (!Schema::hasColumn('tna_items', 'execution_quarter')) {
                    $table->string('execution_quarter', 20)->default('Q1');
                }
                if (!Schema::hasColumn('tna_items', 'cost_per_participant')) {
                    $table->decimal('cost_per_participant', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('tna_items', 'impact_kpi')) {
                    $table->text('impact_kpi')->nullable();
                }
                if (!Schema::hasColumn('tna_items', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
        Schema::dropIfExists('approval_flow_steps');
        Schema::dropIfExists('approval_modules');
    }
};

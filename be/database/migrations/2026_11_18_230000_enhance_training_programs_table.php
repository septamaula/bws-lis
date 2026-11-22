<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan atribut TNA, Finansial RAB, dan Otorisasi pada training_programs
        Schema::table('training_programs', function (Blueprint $table) {
            if (! Schema::hasColumn('training_programs', 'tna_proposal_id')) {
                $table->foreignId('tna_proposal_id')->nullable()->constrained('tna_proposals')->nullOnDelete();
            }
            if (! Schema::hasColumn('training_programs', 'tna_item_id')) {
                $table->foreignId('tna_item_id')->nullable()->constrained('tna_items')->nullOnDelete();
            }
            if (! Schema::hasColumn('training_programs', 'department')) {
                $table->string('department', 100)->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'year')) {
                $table->integer('year')->default(2026);
            }
            if (! Schema::hasColumn('training_programs', 'execution_quarter')) {
                $table->string('execution_quarter', 50)->default('Q1');
            }
            if (! Schema::hasColumn('training_programs', 'target_audience')) {
                $table->string('target_audience', 255)->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'target_participants_count')) {
                $table->integer('target_participants_count')->default(20);
            }
            if (! Schema::hasColumn('training_programs', 'participant_prerequisites')) {
                $table->text('participant_prerequisites')->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'method')) {
                $table->string('method', 50)->default('Hybrid'); // In-Class, Virtual, Hybrid, E-Learning
            }
            if (! Schema::hasColumn('training_programs', 'location')) {
                $table->string('location', 255)->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'approval_type')) {
                $table->string('approval_type', 50)->default('WORKFLOW'); // DOCUMENT or WORKFLOW
            }
            if (! Schema::hasColumn('training_programs', 'approval_doc_number')) {
                $table->string('approval_doc_number', 100)->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'approval_doc_date')) {
                $table->date('approval_doc_date')->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'approval_doc_signer')) {
                $table->string('approval_doc_signer', 150)->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'approval_doc_path')) {
                $table->string('approval_doc_path')->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'approval_doc_name')) {
                $table->string('approval_doc_name')->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'approval_doc_size')) {
                $table->integer('approval_doc_size')->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'total_budget')) {
                $table->decimal('total_budget', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('training_programs', 'status')) {
                $table->string('status', 50)->default('DRAFT'); // DRAFT, SUBMITTED, APPROVED, REJECTED, REVISED
            }
            if (! Schema::hasColumn('training_programs', 'current_step_order')) {
                $table->integer('current_step_order')->default(1);
            }
            if (! Schema::hasColumn('training_programs', 'approval_status_badge')) {
                $table->string('approval_status_badge', 100)->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'rejection_notes')) {
                $table->text('rejection_notes')->nullable();
            }
            if (! Schema::hasColumn('training_programs', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('training_programs', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
        });

        // 2. Tabel Rincian Anggaran Biaya Program (RAB)
        if (! Schema::hasTable('program_budget_items')) {
            Schema::create('program_budget_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_program_id')->constrained('training_programs')->cascadeOnDelete();
                $table->string('item_type', 50); // SPEAKER, TRANSPORT, ACCOMMODATION, LUNCH, SNACK, MATERIALS, VENUE, CERTIFICATION, OTHER
                $table->string('description', 255);
                $table->decimal('quantity', 10, 2)->default(1);
                $table->string('unit', 50)->default('Paket');
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Tabel Daftar Nominasi Peserta Didik
        if (! Schema::hasTable('program_participants')) {
            Schema::create('program_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_program_id')->constrained('training_programs')->cascadeOnDelete();
                $table->string('nik', 50)->nullable();
                $table->string('name', 150);
                $table->string('department', 100)->nullable();
                $table->string('branch', 100)->nullable();
                $table->string('position', 100)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('status', 50)->default('NOMINATED'); // NOMINATED, CONFIRMED, ATTENDED
                $table->timestamps();
            });
        }

        // 4. Daftarkan module program ke approval_modules jika belum ada
        if (Schema::hasTable('approval_modules')) {
            $existing = DB::table('approval_modules')->where('module_code', 'program')->first();
            if (! $existing) {
                $modId = DB::table('approval_modules')->insertGetId([
                    'module_code' => 'program',
                    'module_name' => 'Program Kerja & Rencana Anggaran Biaya (RAB) Diklat',
                    'description' => 'Otorisasi pengesahan program kerja pelatihan tahunan dan rincian anggarannya (Multi-Approval Workflow)',
                    'icon_name' => 'BookOpen',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (Schema::hasTable('approval_flow_steps')) {
                    DB::table('approval_flow_steps')->insert([
                        [
                            'module_id' => $modId,
                            'step_order' => 1,
                            'step_name' => 'Reviewer 1 - HC Business Partner (HCBP)',
                            'role_name' => 'HCBP',
                            'sla_days' => 2,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'module_id' => $modId,
                            'step_order' => 2,
                            'step_name' => 'Checker - HC Learning Head',
                            'role_name' => 'HC Learning Head (Checker)',
                            'sla_days' => 3,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'module_id' => $modId,
                            'step_order' => 3,
                            'step_name' => 'Approver Final - Kepala Divisi Human Capital',
                            'role_name' => 'Kadiv HC',
                            'sla_days' => 2,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('program_participants');
        Schema::dropIfExists('program_budget_items');
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropForeign(['tna_proposal_id']);
            $table->dropForeign(['tna_item_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'tna_proposal_id', 'tna_item_id', 'department', 'year', 'execution_quarter',
                'target_audience', 'target_participants_count', 'participant_prerequisites',
                'method', 'location', 'approval_type', 'approval_doc_number', 'approval_doc_date',
                'approval_doc_signer', 'approval_doc_path', 'approval_doc_name', 'approval_doc_size',
                'total_budget', 'status', 'current_step_order', 'approval_status_badge',
                'rejection_notes', 'approved_by', 'approved_at',
            ]);
        });
    }
};

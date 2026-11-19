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
        // 1. TNA Proposals
        Schema::create('tna_proposals', function (Blueprint $table) {
            $table->id();
            $table->string('proposal_code', 50)->unique();
            $table->string('department', 100);
            $table->integer('year');
            $table->foreignId('proposed_by')->constrained('users');
            $table->string('priority', 30)->default('Medium'); // Critical, High, Medium, Low
            $table->decimal('total_budget_requested', 15, 2)->default(0);
            $table->decimal('total_budget_approved', 15, 2)->nullable();
            $table->string('status', 30)->default('DRAFT'); // DRAFT, SUBMITTED, HCBP_REVIEWED, APPROVED, REJECTED
            $table->text('rejection_reason')->nullable();
            $table->text('hcbp_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // 2. TNA Items (Individual Competency Gaps)
        Schema::create('tna_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tna_proposal_id')->constrained('tna_proposals')->cascadeOnDelete();
            $table->string('competency_gap');
            $table->string('recommended_program');
            $table->string('target_audience');
            $table->integer('target_participants_count')->default(1);
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->string('urgency', 30)->default('Medium');
            $table->timestamps();
        });

        // 3. Training Programs (Catalog)
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->string('program_code', 50)->unique();
            $table->string('title');
            $table->string('category', 100); // Leadership, Banking Operations, Compliance & Risk, IT / Digital, Sales
            $table->text('description')->nullable();
            $table->integer('passing_grade')->default(75);
            $table->integer('duration_hours')->default(8);
            $table->boolean('is_mandatory')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 4. Training Classes (Batches & Schedules with Maker-Checker dual control)
        Schema::create('training_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('training_programs')->cascadeOnDelete();
            $table->string('class_code', 50)->unique();
            $table->string('batch_name');
            $table->string('method', 50); // ELAPSE E-Learning, Virtual Classroom, In-Classroom, Hybrid
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('max_quota')->default(30);
            $table->integer('enrolled_count')->default(0);
            $table->string('location_or_url');
            $table->foreignId('facilitator_id')->nullable()->constrained('users');
            $table->string('dual_control_status', 30)->default('DRAFT'); // DRAFT, PENDING_CHECKER, APPROVED, ONGOING, COMPLETED, CANCELLED
            $table->foreignId('maker_id')->constrained('users');
            $table->foreignId('checker_id')->nullable()->constrained('users');
            $table->text('checker_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // 5. Class Enrollments & ELAPSE Tracking
        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_class_id')->constrained('training_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attendance_status', 30)->default('REGISTERED'); // REGISTERED, PRESENT, ABSENT, PERMISSION
            $table->decimal('pre_test_score', 5, 2)->nullable();
            $table->decimal('post_test_score', 5, 2)->nullable();
            $table->decimal('task_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->integer('elapse_progress_pct')->default(0); // 0 - 100%
            $table->string('completion_status', 30)->default('NOT_STARTED'); // NOT_STARTED, IN_PROGRESS, PASSED, FAILED
            $table->string('task_submission_url')->nullable();
            $table->timestamp('task_submitted_at')->nullable();
            $table->text('task_feedback')->nullable();
            $table->string('certificate_id', 100)->nullable()->unique();
            $table->string('certificate_url')->nullable();
            $table->timestamps();
        });

        // 6. Financial Transactions (Dual Control Maker-Checker for Training Budgets)
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('trx_number', 50)->unique();
            $table->foreignId('training_class_id')->nullable()->constrained('training_classes')->nullOnDelete();
            $table->foreignId('tna_proposal_id')->nullable()->constrained('tna_proposals')->nullOnDelete();
            $table->string('category', 100); // Vendor Fee, Venue & Catering, Certification Fee, Facilitator Honorarium, Learning Materials
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->foreignId('maker_id')->constrained('users');
            $table->foreignId('checker_id')->nullable()->constrained('users');
            $table->string('status', 30)->default('PENDING_APPROVAL'); // PENDING_APPROVAL, APPROVED, REJECTED, DISBURSED
            $table->text('checker_notes')->nullable();
            $table->string('receipt_attachment_url')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('class_enrollments');
        Schema::dropIfExists('training_classes');
        Schema::dropIfExists('training_programs');
        Schema::dropIfExists('tna_items');
        Schema::dropIfExists('tna_proposals');
    }
};

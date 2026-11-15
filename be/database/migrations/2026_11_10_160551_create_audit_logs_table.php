<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for immutable banking audit trail.
     * Complies with OJK POJK No. 11/POJK.03/2022.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_nik', 50)->nullable();
            $table->string('user_name', 150)->nullable();
            $table->string('role', 100)->nullable();
            $table->string('action', 100)->index(); // LOGIN, CREATE_TNA, APPROVE_BUDGET, MAKER_SUBMIT, CHECKER_APPROVE, etc.
            $table->string('entity_type', 150)->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->string('hash', 64)->nullable(); // SHA-256 tamper-evident chain verification hash
            $table->timestamp('created_at')->useCurrent()->index(); // Append-only timestamp
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

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
        Schema::create('memos', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['INTERNAL', 'EKSTERNAL'])->default('INTERNAL');
            $table->date('memo_date');
            $table->string('memo_number')->index();
            $table->string('pic');
            $table->string('receiver')->nullable()->comment('Specific for EKSTERNAL memo');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('document_name')->nullable();
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memos');
    }
};

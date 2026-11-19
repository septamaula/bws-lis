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
        // 1. Master Categories (Rumpun / Kategori Utama Pelatihan)
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('icon', 50)->nullable()->default('Building2');
                $table->string('color', 20)->nullable()->default('#0067AC');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Master Sub-Categories (Sub Kategori Pelatihan dengan Relasi ke Kategori Induk)
        if (!Schema::hasTable('sub_categories')) {
            Schema::create('sub_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('target_competency', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Master Fiscal Years (Tahun Anggaran & Periode Diklat)
        if (!Schema::hasTable('fiscal_years')) {
            Schema::create('fiscal_years', function (Blueprint $table) {
                $table->id();
                $table->integer('year')->unique();
                $table->string('name', 100);
                $table->string('theme', 255)->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('total_budget_cap', 15, 2)->default(0);
                $table->string('status', 30)->default('AKTIF'); // AKTIF, PERENCANAAN, TUTUP_BUKU
                $table->boolean('is_active')->default(false);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Enhance tna_proposals with document upload & fiscal year
        Schema::table('tna_proposals', function (Blueprint $table) {
            if (!Schema::hasColumn('tna_proposals', 'attachment_path')) {
                $table->string('attachment_path', 255)->nullable();
            }
            if (!Schema::hasColumn('tna_proposals', 'attachment_name')) {
                $table->string('attachment_name', 255)->nullable();
            }
            if (!Schema::hasColumn('tna_proposals', 'attachment_size')) {
                $table->integer('attachment_size')->nullable();
            }
            if (!Schema::hasColumn('tna_proposals', 'fiscal_year_id')) {
                $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years')->nullOnDelete();
            }
        });

        // 5. Enhance tna_items with category, sub_category, and direct budget fields
        Schema::table('tna_items', function (Blueprint $table) {
            if (!Schema::hasColumn('tna_items', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('tna_items', 'category_name')) {
                $table->string('category_name', 150)->nullable();
            }
            if (!Schema::hasColumn('tna_items', 'sub_category_id')) {
                $table->foreignId('sub_category_id')->nullable()->constrained('sub_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('tna_items', 'sub_category_name')) {
                $table->string('sub_category_name', 150)->nullable();
            }
            if (!Schema::hasColumn('tna_items', 'budget')) {
                $table->decimal('budget', 15, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tna_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['sub_category_id']);
            $table->dropColumn(['category_id', 'category_name', 'sub_category_id', 'sub_category_name', 'budget']);
        });

        Schema::table('tna_proposals', function (Blueprint $table) {
            $table->dropForeign(['fiscal_year_id']);
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_size', 'fiscal_year_id']);
        });

        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('sub_categories');
        Schema::dropIfExists('categories');
    }
};

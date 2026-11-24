<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Facilitator / Lembaga Pelatihan
        if (! Schema::hasTable('facilitators')) {
            Schema::create('facilitators', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('type', 50)->default('External Vendor'); // External Vendor, Lembaga Sertifikasi, Internal Trainer, Institusi / Akademisi
                $table->string('contact_person', 100)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('specialization', 255)->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Master Category BI / Regulasi OJK
        if (! Schema::hasTable('bi_categories')) {
            Schema::create('bi_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('regulation_reference', 150)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_mandatory')->default(true);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(1);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Seed initial Facilitators
        $facilitators = [
            [
                'code' => 'FAC-001',
                'name' => 'PT Mitra Konsultan Perbankan',
                'type' => 'External Vendor',
                'contact_person' => 'Bambang Supriyadi',
                'phone' => '+62 812-3456-7890',
                'email' => 'info@mitrakonsultan.co.id',
                'specialization' => 'Operasional Perbankan, Service Excellence & Customer Protection',
                'address' => 'Gedung Menara Sudirman Lt. 12, Jl. Jend. Sudirman Kav. 60, Jakarta Selatan',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FAC-002',
                'name' => 'LPPI (Lembaga Pengembangan Perbankan Indonesia)',
                'type' => 'Lembaga Sertifikasi',
                'contact_person' => 'Dra. Sri Mulyani, MM',
                'phone' => '+62 21-7179-0131',
                'email' => 'diklat@lppi.or.id',
                'specialization' => 'General Banking, Analisis Kredit Komersial, Treasury & Trade Finance',
                'address' => 'Jl. Kemang Raya No. 35, Bangka, Mampang Prapatan, Jakarta Selatan',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FAC-003',
                'name' => 'BWS Corporate Academy (Internal Trainer)',
                'type' => 'Internal Trainer',
                'contact_person' => 'HC Learning Division',
                'phone' => '+62 21-5088-2000',
                'email' => 'academy@bws.co.id',
                'specialization' => 'Budaya Kerja BWS, Core Banking System, Onboarding & Officer Development Program',
                'address' => 'BWS Learning Center, Gedung Kantor Pusat Bank Woori Saudara, Jakarta',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FAC-004',
                'name' => 'Badan Sertifikasi Manajemen Risiko (BSMR)',
                'type' => 'Lembaga Sertifikasi',
                'contact_person' => 'Sekretariat BSMR',
                'phone' => '+62 21-5296-0331',
                'email' => 'sertifikasi@bsmr.or.id',
                'specialization' => 'Sertifikasi Manajemen Risiko Perbankan Tingkat 1 sampai Tingkat 4',
                'address' => 'Plaza Mandiri Lt. 28, Jl. Jend. Gatot Subroto Kav. 36-38, Jakarta',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FAC-005',
                'name' => 'PT Cyber Defense Nusantara',
                'type' => 'External Vendor',
                'contact_person' => 'Aditya Pratama, CISSP',
                'phone' => '+62 811-9876-5432',
                'email' => 'contact@cyberdefense.id',
                'specialization' => 'Keamanan Siber Core Banking, Threat Modeling, POJK 29/2022 & Vulnerability Assessment',
                'address' => 'Cyber 2 Tower Lt. 18, Jl. HR Rasuna Said Blok X-5, Kuningan, Jakarta Selatan',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FAC-006',
                'name' => 'Center for Banking Compliance & Legal Advisory',
                'type' => 'Institusi / Akademisi',
                'contact_person' => 'Prof. Dr. Hendra Wijaya, SH, LLM',
                'phone' => '+62 21-5790-4411',
                'email' => 'info@cbcla.org',
                'specialization' => 'Kepatuhan Regulasi OJK, UU PDP, Pencegahan Fraud & APU-PPT',
                'address' => 'Sampoerna Strategic Square South Tower Lt. 15, Jl. Jend. Sudirman Kav. 45, Jakarta',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($facilitators as $fac) {
            DB::table('facilitators')->updateOrInsert(['code' => $fac['code']], $fac);
        }

        // Seed initial BI Categories (Regulasi BI & OJK)
        $biCategories = [
            [
                'code' => 'BI-OPS-01',
                'name' => 'Operasional Perbankan & Sistem Pembayaran',
                'regulation_reference' => 'PADG BI No. 24/2022 & SKNBI/BI-FAST',
                'description' => 'Ketentuan penyelenggaraan transfer dana, kliring BI-FAST, kas fisik, dan operasional kantor cabang perbankan.',
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BI-CRD-02',
                'name' => 'Kredit, Pembiayaan Komersial & Analisis Risiko',
                'regulation_reference' => 'POJK No. 40/POJK.03/2019 Kualitas Aset & BMPK',
                'description' => 'Pedoman kelayakan penyaluran kredit, penilaian BMPK/Lending Limit, dan restrukturisasi kredit perbankan.',
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BI-RSK-03',
                'name' => 'Manajemen Risiko Perbankan (BSMR)',
                'regulation_reference' => 'POJK No. 18/POJK.03/2016 Penerapan Manajemen Risiko',
                'description' => 'Kewajiban sertifikasi kompetensi manajemen risiko bank (Pasar, Kredit, Likuiditas, Operasional, Hukum, Reputasi, Stratejik, Kepatuhan).',
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BI-AML-04',
                'name' => 'Anti Pencucian Uang & Pencegahan Terorisme (APU-PPT)',
                'regulation_reference' => 'POJK No. 12/POJK.01/2017 & UU No. 8/2010',
                'description' => 'Penerapan program APU-PPT, Customer Due Diligence (CDD), Enhanced Due Diligence (EDD), dan pelaporan PPATK.',
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BI-IT-05',
                'name' => 'Teknologi Informasi, Keamanan Siber & POJK 29/2022',
                'regulation_reference' => 'POJK No. 29/POJK.03/2022 Ketahanan Siber Bank Umum',
                'description' => 'Standar tata kelola TI, manajemen kerentanan siber, pengamanan data nasabah, disaster recovery plan & BCM.',
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BI-COMP-06',
                'name' => 'Kepatuhan, Regulasi OJK & Perlindungan Konsumen',
                'regulation_reference' => 'POJK No. 6/POJK.07/2022 Perlindungan Konsumen & UU PDP',
                'description' => 'Kepatuhan prinsip keterbukaan informasi, kerahasiaan data pribadi nasabah (UU PDP), dan penyelesaian pengaduan.',
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BI-LDR-07',
                'name' => 'Kepemimpinan, Budaya Kerja & Human Capital',
                'regulation_reference' => 'POJK No. 55/POJK.03/2016 Tata Kelola (GCG) Bank Umum',
                'description' => 'Standar kompetensi pimpinan cabang, integritas, whistleblowing system, anti-fraud awareness dan kode etik bank.',
                'is_mandatory' => false,
                'is_active' => true,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($biCategories as $bi) {
            DB::table('bi_categories')->updateOrInsert(['code' => $bi['code']], $bi);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facilitators');
        Schema::dropIfExists('bi_categories');
    }
};

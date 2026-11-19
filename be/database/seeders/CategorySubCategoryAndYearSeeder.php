<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySubCategoryAndYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Master Fiscal Years (Tahun Anggaran & Periode Diklat)
        $years = [
            [
                'year' => 2024,
                'name' => 'Tahun Anggaran & Diklat 2024',
                'theme' => 'Stabilisasi Operasional Cabang & Peningkatan Kualitas Kredit Pasca-Pandemi',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'total_budget_cap' => 3800000000.00,
                'status' => 'TUTUP_BUKU',
                'is_active' => false,
                'description' => 'Tutup buku tahunan audit BWS 2024 telah diselesaikan dan diaudit OJK.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'year' => 2025,
                'name' => 'Tahun Anggaran & Diklat 2025',
                'theme' => 'Modernisasi Infrastruktur Core Banking & Penguatan Budaya Kepatuhan',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'total_budget_cap' => 4500000000.00,
                'status' => 'TUTUP_BUKU',
                'is_active' => false,
                'description' => 'Realisasi anggaran diklat 2025 tercapai 94.2% dengan indeks kepatuhan 99.8%.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'year' => 2026,
                'name' => 'Tahun Anggaran & Diklat 2026',
                'theme' => 'Akselerasi Transformasi Digital, Penguatan Keamanan Siber POJK 29/2022 & Mitigasi NPL',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'total_budget_cap' => 5250000000.00,
                'status' => 'AKTIF',
                'is_active' => true,
                'description' => 'Tahun anggaran berjalan (Current Fiscal Year) untuk seluruh realisasi program diklat BWS.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'year' => 2027,
                'name' => 'Tahun Anggaran & Diklat 2027',
                'theme' => 'Next-Gen Intelligent Banking, AI Productivity & Sustainable ESG Capability',
                'start_date' => '2027-01-01',
                'end_date' => '2027-12-31',
                'total_budget_cap' => 6000000000.00,
                'status' => 'PERENCANAAN',
                'is_active' => false,
                'description' => 'Siklus pengusulan TNA tahun depan (Future TNA Planning Cycle).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($years as $y) {
            DB::table('fiscal_years')->updateOrInsert(
                ['year' => $y['year']],
                $y
            );
        }

        // 2. Seed Master Categories (Rumpun / Kategori Utama)
        $categories = [
            [
                'code' => 'CAT-OPS',
                'name' => 'Operasional & Layanan Perbankan',
                'description' => 'Standardisasi layanan frontliner cabang, kepatuhan transaksi kas, kliring BI-FAST, trade finance, dan tresuri.',
                'icon' => 'Building2',
                'color' => '#0067AC',
                'is_active' => true,
                'sort_order' => 1,
                'sub_categories' => [
                    [
                        'code' => 'SUB-OPS-001',
                        'name' => 'Frontliner, Teller & Service Excellence',
                        'description' => 'Standar pelayanan nasabah BWS, ketelitian penanganan uang tunai, pencegahan selisih kas, dan service mindset.',
                        'target_competency' => 'SOP Kas, Deteksi Uang Palsu, Handling Complain & Service Mindset',
                    ],
                    [
                        'code' => 'SUB-OPS-002',
                        'name' => 'Back Office, Kliring & Sistem Pembayaran (SKNBI & BI-FAST)',
                        'description' => 'Operasional administrasi cabang, kliring warkat, rekonsiliasi harian, dan kepatuhan transaksi Bank Indonesia.',
                        'target_competency' => 'Operasional SKNBI, Settlement BI-FAST, Rekonsiliasi Kas GL',
                    ],
                    [
                        'code' => 'SUB-OPS-003',
                        'name' => 'Trade Finance & Transaksi Ekspor-Impor',
                        'description' => 'Penerbitan Letter of Credit (L/C), SKBDN, Bank Garansi, dan pembiayaan perdagangan internasional.',
                        'target_competency' => 'UCP 600, Standar L/C Ekspor-Impor, Verifikasi Dokumen Perdagangan',
                    ],
                    [
                        'code' => 'SUB-OPS-004',
                        'name' => 'Treasury Operations & Pasar Uang',
                        'description' => 'Manajemen likuiditas bank, transaksi valuta asing (Valas), pasar uang antar bank (PUAB), dan surat berharga.',
                        'target_competency' => 'Dealing Room Support, Sertifikasi SKSP Bank Indonesia, Pengelolaan Likuiditas',
                    ],
                ],
            ],
            [
                'code' => 'CAT-CRD',
                'name' => 'Kredit & Pembiayaan Komersial',
                'description' => 'Peningkatan kompetensi analisa kelayakan pembiayaan, mitigasi risiko kredit, restrukturisasi, dan penanganan NPL.',
                'icon' => 'CreditCard',
                'color' => '#003876',
                'is_active' => true,
                'sort_order' => 2,
                'sub_categories' => [
                    [
                        'code' => 'SUB-CRD-001',
                        'name' => 'Analisis Kelayakan Kredit & Cashflow Modelling Komersial',
                        'description' => 'Bedah laporan keuangan calon debitur, analisa rasio keuangan, proyeksi cashflow, dan verifikasi kelayakan usaha.',
                        'target_competency' => 'Analisa 5C, Proyeksi Cashflow Bisnis, Stress Testing Debitur Komersial',
                    ],
                    [
                        'code' => 'SUB-CRD-002',
                        'name' => 'Mitigasi Risiko Kredit & Pencegahan Non-Performing Loan (NPL)',
                        'description' => 'Sistem peringatan dini (Early Warning System), monitoring agunan kredit, dan mitigasi potensi kredit macet.',
                        'target_competency' => 'Early Warning Signal, Penilaian Agunan Legal, Manajemen Kolektibilitas',
                    ],
                    [
                        'code' => 'SUB-CRD-003',
                        'name' => 'Restrukturisasi Kredit & Penyelamatan Aset (Remedial)',
                        'description' => 'Strategi restrukturisasi pembiayaan debitur terdampak, negosiasi novasi/rescheduling, dan lelang eksekusi hak tanggungan.',
                        'target_competency' => 'Hukum Kepailitan & Eksekusi Jaminan, Manajemen Kredit Bermasalah',
                    ],
                    [
                        'code' => 'SUB-CRD-004',
                        'name' => 'Kredit Konsumer, KPR & Pembiayaan Pensiunan BWS',
                        'description' => 'Produk unggulan BWS Kredit Pegawai Aktif & Pensiunan, analisis scoring pembiayaan ritel, dan KPR.',
                        'target_competency' => 'Credit Scoring Ritel, Penilaian Kemampuan Angsuran Pensiun BWS',
                    ],
                ],
            ],
            [
                'code' => 'CAT-RSK',
                'name' => 'Manajemen Risiko, Kepatuhan & Regulasi OJK',
                'description' => 'Sertifikasi wajib regulator perbankan OJK & BI, kepatuhan Anti-Pencucian Uang (APU-PPT), dan tata kelola GCG.',
                'icon' => 'ShieldCheck',
                'color' => '#002855',
                'is_active' => true,
                'sort_order' => 3,
                'sub_categories' => [
                    [
                        'code' => 'SUB-RSK-001',
                        'name' => 'Sertifikasi Manajemen Risiko Perbankan (BSMR Level 1 - 4)',
                        'description' => 'Persiapan ujian sertifikasi Badan Sertifikasi Manajemen Risiko (BSMR) sesuai regulasi mandatori OJK.',
                        'target_competency' => 'Risiko Pasar, Kredit, Operasional, Likuiditas, Strategik & Kepatuhan',
                    ],
                    [
                        'code' => 'SUB-RSK-002',
                        'name' => 'APU-PPT (Anti Pencucian Uang & Pencegahan Pendanaan Terorisme)',
                        'description' => 'Customer Due Diligence (CDD), Enhanced Due Diligence (EDD), pemantauan Transaksi Keuangan Mencurigakan (LTKM).',
                        'target_competency' => 'Kepatuhan PPATK, Penerapan 5 Pilar APU-PPT, Red Flags Pencucian Uang',
                    ],
                    [
                        'code' => 'SUB-RSK-003',
                        'name' => 'Audit Internal Perbankan & Investigasi Anti-Fraud',
                        'description' => 'Teknik audit operasional cabang, sampling audit kepatuhan, audit forensik internal, dan zero tolerance fraud.',
                        'target_competency' => 'Risk-Based Internal Audit, Investigasi Fraud, Fraud Risk Management',
                    ],
                    [
                        'code' => 'SUB-RSK-004',
                        'name' => 'Perlindungan Konsumen & Kerahasiaan Data (UU PDP Perbankan)',
                        'description' => 'Kepatuhan Undang-Undang Perlindungan Data Pribadi (UU PDP) dan regulasi perlindungan konsumen sektor perbankan.',
                        'target_competency' => 'UU No. 27/2022 UU PDP, Tata Kelola Data Nasabah, Kepatuhan Konsumen OJK',
                    ],
                ],
            ],
            [
                'code' => 'CAT-IT',
                'name' => 'Teknologi Informasi & Keamanan Siber',
                'description' => 'Pemenuhan POJK 29/2022 terkait ketahanan siber, administrasi core banking system, digital banking, dan cloud IT.',
                'icon' => 'Terminal',
                'color' => '#00A0E9',
                'is_active' => true,
                'sort_order' => 4,
                'sub_categories' => [
                    [
                        'code' => 'SUB-IT-001',
                        'name' => 'Keamanan Siber Perbankan & Kepatuhan POJK 29/2022',
                        'description' => 'Penerapan Information Security Framework, incident response siber, vulnerability assessment, dan kepatuhan POJK 29/2022.',
                        'target_competency' => 'CSIRT Bank, Kepatuhan Regulasi Siber POJK, Threat Hunting & Hardening',
                    ],
                    [
                        'code' => 'SUB-IT-002',
                        'name' => 'Core Banking Administration & Database Tuning',
                        'description' => 'Manajemen basis data core banking (PostgreSQL/Oracle), batch process end-of-day (EOD), dan pemeliharaan transaksi 24/7.',
                        'target_competency' => 'PostgreSQL DBA, EOD Batch Handling, High-Availability Database Clustering',
                    ],
                    [
                        'code' => 'SUB-IT-003',
                        'name' => 'Digital Banking, API Open Banking & Cloud Architecture',
                        'description' => 'Standar Nasional Open API Pembayaran (SNAP BI), arsitektur microservices, dan pengembangan aplikasi mobile banking BWS.',
                        'target_competency' => 'Standar SNAP BI, RESTful API Gateway Security, Containerization Docker',
                    ],
                    [
                        'code' => 'SUB-IT-004',
                        'name' => 'IT Disaster Recovery & Business Continuity Management (BCM)',
                        'description' => 'Simulasi failover Data Center (DC) ke Disaster Recovery Center (DRC), RPO/RTO testing, dan audit IT kontinuitas bisnis.',
                        'target_competency' => 'ISO 22301 BCM, Uji Coba DRC Tahunan, RPO/RTO Mandatori Bank',
                    ],
                ],
            ],
            [
                'code' => 'CAT-LDR',
                'name' => 'Kepemimpinan, Manajerial & Budaya Kerja',
                'description' => 'Pengembangan kader pimpinan bank, kepemimpinan branch manager, coaching & mentoring, serta budaya integritas BWS.',
                'icon' => 'Award',
                'color' => '#0067AC',
                'is_active' => true,
                'sort_order' => 5,
                'sub_categories' => [
                    [
                        'code' => 'SUB-LDR-001',
                        'name' => 'Officer Development Program (ODP) BWS Corporate Academy',
                        'description' => 'Kurikulum intensif pembentukan perwira bank, fondasi perbankan menyeluruh, rotasi magang cabang, dan leadership sprint.',
                        'target_competency' => 'Banking Fundamentals, Presentation Skill, Strategic Problem Solving',
                    ],
                    [
                        'code' => 'SUB-LDR-002',
                        'name' => 'Branch Manager Leadership & P&L Business Mastery',
                        'description' => 'Strategi pengelolaan cabang, pencapaian target profit & loss (P&L), CASA growth, dan kepemimpinan tim cabang.',
                        'target_competency' => 'P&L Branch Management, Commercial Negotiation, People Leadership',
                    ],
                    [
                        'code' => 'SUB-LDR-003',
                        'name' => 'Supervisory Coaching, Mentoring & People Development',
                        'description' => 'Keterampilan coaching bagi Supervisor dan Section Head dalam meningkatkan performa dan retensi talenta staf.',
                        'target_competency' => 'GROW Coaching Model, Constructive Feedback, Talent Retention Strategy',
                    ],
                    [
                        'code' => 'SUB-LDR-004',
                        'name' => 'Budaya Kerja Perbankan BWS & Integrity Excellence',
                        'description' => 'Internalisasi nilai-nilai budaya Bank Woori Saudara, kode etik bankir, whistleblowing system, dan profesionalisme kerja.',
                        'target_competency' => 'Code of Conduct, Whistleblowing System, Nilai Budaya Perbankan BWS',
                    ],
                ],
            ],
        ];

        foreach ($categories as $cat) {
            $catId = DB::table('categories')->updateOrInsert(
                ['code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'is_active' => $cat['is_active'],
                    'sort_order' => $cat['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $parent = DB::table('categories')->where('code', $cat['code'])->first();

            foreach ($cat['sub_categories'] as $subIdx => $sub) {
                DB::table('sub_categories')->updateOrInsert(
                    ['code' => $sub['code']],
                    [
                        'category_id' => $parent->id,
                        'name' => $sub['name'],
                        'description' => $sub['description'],
                        'target_competency' => $sub['target_competency'],
                        'is_active' => true,
                        'sort_order' => $subIdx + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // Link existing TNA items with categories if available
        $catOps = DB::table('categories')->where('code', 'CAT-OPS')->first();
        $catCrd = DB::table('categories')->where('code', 'CAT-CRD')->first();
        $catRsk = DB::table('categories')->where('code', 'CAT-RSK')->first();
        $catIt  = DB::table('categories')->where('code', 'CAT-IT')->first();

        $subIt1 = DB::table('sub_categories')->where('code', 'SUB-IT-001')->first();
        $subCrd1 = DB::table('sub_categories')->where('code', 'SUB-CRD-001')->first();
        $subRsk1 = DB::table('sub_categories')->where('code', 'SUB-RSK-001')->first();

        if ($catIt && $subIt1) {
            DB::table('tna_items')->where('recommended_program', 'like', '%Cyber Security%')->update([
                'category_id' => $catIt->id,
                'category_name' => $catIt->name,
                'sub_category_id' => $subIt1->id,
                'sub_category_name' => $subIt1->name,
                'budget' => 60000000.00,
            ]);
        }

        if ($catCrd && $subCrd1) {
            DB::table('tna_items')->where('recommended_program', 'like', '%Credit%')->update([
                'category_id' => $catCrd->id,
                'category_name' => $catCrd->name,
                'sub_category_id' => $subCrd1->id,
                'sub_category_name' => $subCrd1->name,
                'budget' => 54000000.00,
            ]);
        }

        if ($catRsk && $subRsk1) {
            DB::table('tna_items')->where('recommended_program', 'like', '%BSMR%')->update([
                'category_id' => $catRsk->id,
                'category_name' => $catRsk->name,
                'sub_category_id' => $subRsk1->id,
                'sub_category_name' => $subRsk1->name,
                'budget' => 45000000.00,
            ]);
        }
    }
}

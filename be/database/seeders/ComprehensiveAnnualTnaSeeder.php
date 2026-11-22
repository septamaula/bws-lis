<?php

namespace Database\Seeders;

use App\Models\ApprovalHistory;
use App\Models\Category;
use App\Models\FiscalYear;
use App\Models\SubCategory;
use App\Models\TnaItem;
use App\Models\TnaProposal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ComprehensiveAnnualTnaSeeder extends Seeder
{
    /**
     * Run the database seeds to create complete annual TNA data.
     */
    public function run(): void
    {
        // 1. Resolve Users for Multi-Approval
        $deptHead = User::where('role_name', 'Dept Head')->first() ?? User::where('email', 'depthead.it@bws.co.id')->first() ?? User::first();
        $hcbp = User::where('role_name', 'HCBP')->first() ?? User::where('email', 'hcbp@bws.co.id')->first() ?? User::first();
        $checker = User::where('role_name', 'HC Learning Head (Checker)')->first() ?? User::where('email', 'checker.head@bws.co.id')->first() ?? User::first();
        $approver = User::where('role_name', 'Executive')->first() ?? User::where('email', 'bod.executive@bws.co.id')->first() ?? User::first();

        // 2. Resolve Active Fiscal Year 2026
        $fiscalYear = FiscalYear::where('year', 2026)->first() ?? FiscalYear::where('is_active', true)->first();

        // 3. Ensure Storage Directory & Dummy Attachment PDF exists
        if (! Storage::disk('public')->exists('attachments/tna')) {
            Storage::disk('public')->makeDirectory('attachments/tna');
        }
        $attachmentRelPath = 'attachments/tna/TOR_Rencana_Induk_TNA_Tahunan_BWS_2026.pdf';
        if (! Storage::disk('public')->exists($attachmentRelPath)) {
            $dummyPdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF\n";
            Storage::disk('public')->put($attachmentRelPath, $dummyPdfContent);
        }

        // 4. Create Flagship Master TNA Proposal (Covering ALL 20 Sub-Categories in 1 Year)
        $proposalCode = 'TNA-2026-CORP-ALL';
        $proposal = TnaProposal::updateOrCreate(
            ['proposal_code' => $proposalCode],
            [
                'department' => 'Human Capital & Learning Division (Bank-Wide Plan)',
                'year' => 2026,
                'fiscal_year_id' => $fiscalYear ? $fiscalYear->id : null,
                'proposed_by' => $deptHead ? $deptHead->id : null,
                'priority' => 'Critical',
                'target_quarter' => 'Q1 - Q4 (1 Tahun Penuh)',
                'strategic_objective' => 'Rencana Induk Kebutuhan Pelatihan Terpadu Tahunan Bank Woori Saudara 2026: Akselerasi Transformasi Digital & Core Banking, Mitigasi Risiko Kredit Komersial & Pencegahan NPL, Pemenuhan Sertifikasi Mandatori Regulasi OJK & BI (BSMR, APU-PPT, SKSP, UU PDP), Penguatan Ketahanan Siber Sesuai POJK 29/2022, serta Pembentukan Kader Pimpinan Cabang & Budaya Integritas Perbankan BWS.',
                'total_budget_requested' => 1485000000.00,
                'total_budget_approved' => 1485000000.00,
                'status' => 'APPROVED',
                'current_step_order' => 4,
                'approved_by' => $approver ? $approver->id : null,
                'approved_at' => now()->subDays(2),
                'hcbp_notes' => 'Telah diverifikasi sesuai Rencana Kerja & Anggaran Tahunan (RKAT) BWS L&D 2026.',
                'attachment_path' => $attachmentRelPath,
                'attachment_name' => 'TOR_Rencana_Induk_TNA_Tahunan_BWS_2026.pdf',
                'attachment_size' => 2450820,
            ]
        );

        // 5. Populate All 20 Sub-Categories from all 5 Categories
        $subCategoryPlan = [
            // Kategori 1: Operasional & Layanan Perbankan (CAT-OPS)
            'SUB-OPS-001' => [
                'cat' => 'CAT-OPS',
                'quarter' => 'Q1',
                'participants' => 25,
                'budget' => 65000000.00,
                'training_type' => 'In-House & Workshop',
                'target_audience' => 'Frontliner, Teller, Customer Service Cabang',
                'gap' => 'Standarisasi layanan prima, pencegahan selisih kas, dan service excellence perbankan.',
                'kpi' => 'Customer Satisfaction Index >= 95% & Zero Cash Difference',
            ],
            'SUB-OPS-002' => [
                'cat' => 'CAT-OPS',
                'quarter' => 'Q2',
                'participants' => 18,
                'budget' => 45000000.00,
                'training_type' => 'Hybrid (LMS + Hands-on)',
                'target_audience' => 'Back Office, Settlement, Kliring Staff',
                'gap' => 'Peningkatan kecepatan rekonsiliasi GL dan kepatuhan penyelesaian transaksi SKNBI & BI-FAST.',
                'kpi' => 'Waktu rekonsiliasi harian EOD selesai < 18:00 WIB',
            ],
            'SUB-OPS-003' => [
                'cat' => 'CAT-OPS',
                'quarter' => 'Q3',
                'participants' => 12,
                'budget' => 55000000.00,
                'training_type' => 'Workshop Sertifikasi',
                'target_audience' => 'Trade Finance Officer & Cabang Devisa',
                'gap' => 'Verifikasi dokumen L/C ekspor-impor, SKBDN, dan kepatuhan standar internasional UCP 600.',
                'kpi' => 'Zero Discrepancy Penalty & Pertumbuhan Fee Ekspor Impor 15%',
            ],
            'SUB-OPS-004' => [
                'cat' => 'CAT-OPS',
                'quarter' => 'Q4',
                'participants' => 10,
                'budget' => 70000000.00,
                'training_type' => 'Sertifikasi Mandatori BI',
                'target_audience' => 'Dealing Room & Treasury Operations Staff',
                'gap' => 'Sertifikasi SKSP Pasar Uang & Valuta Asing Bank Indonesia dan mitigasi risiko likuiditas.',
                'kpi' => 'Kelulusan Sertifikasi SKSP BI 100%',
            ],

            // Kategori 2: Kredit & Pembiayaan Komersial (CAT-CRD)
            'SUB-CRD-001' => [
                'cat' => 'CAT-CRD',
                'quarter' => 'Q1',
                'participants' => 20,
                'budget' => 85000000.00,
                'training_type' => 'Intensive Case Study Workshop',
                'target_audience' => 'Account Officer & Analis Kredit Komersial',
                'gap' => 'Ketajaman analisa cashflow calon debitur, stress testing kemampuan bayar, dan verifikasi 5C.',
                'kpi' => 'Kualitas portofolio kredit baru dengan kolektibilitas Lancar 99%',
            ],
            'SUB-CRD-002' => [
                'cat' => 'CAT-CRD',
                'quarter' => 'Q2',
                'participants' => 15,
                'budget' => 60000000.00,
                'training_type' => 'Classroom & On-the-job review',
                'target_audience' => 'Credit Risk Reviewer & Branch Manager',
                'gap' => 'Penerapan Early Warning System (EWS) sinyal kredit macet dan monitoring legalitas agunan.',
                'kpi' => 'Penurunan rasio NPL Komersial di bawah 1.8%',
            ],
            'SUB-CRD-003' => [
                'cat' => 'CAT-CRD',
                'quarter' => 'Q3',
                'participants' => 12,
                'budget' => 50000000.00,
                'training_type' => 'Hukum & Legal Workshop',
                'target_audience' => 'Remedial & Special Asset Management',
                'gap' => 'Strategi novasi restrukturisasi, negosiasi debitur macet, dan eksekusi lelang hak tanggungan.',
                'kpi' => 'Recovery Rate Kredit Bermasalah >= 65%',
            ],
            'SUB-CRD-004' => [
                'cat' => 'CAT-CRD',
                'quarter' => 'Q4',
                'participants' => 30,
                'budget' => 75000000.00,
                'training_type' => 'Pelatihan Ritel Cabang',
                'target_audience' => 'Marketing Kredit Pensiunan & KPR BWS',
                'gap' => 'Teknik pemasaran kredit pegawai aktif/pensiunan BWS dan credit scoring ritel presisi.',
                'kpi' => 'Pencapaian target ekspansi kredit ritel cabang 110%',
            ],

            // Kategori 3: Manajemen Risiko, Kepatuhan & Regulasi OJK (CAT-RSK)
            'SUB-RSK-001' => [
                'cat' => 'CAT-RSK',
                'quarter' => 'Q1',
                'participants' => 16,
                'budget' => 95000000.00,
                'training_type' => 'Sertifikasi Mandatori OJK',
                'target_audience' => 'Pejabat Eksekutif, Manager & Pemimpin Cabang',
                'gap' => 'Pemenuhan sertifikasi Badan Sertifikasi Manajemen Risiko (BSMR Level 1 - 4) regulasi OJK.',
                'kpi' => 'Tingkat kelulusan ujian BSMR OJK 100%',
            ],
            'SUB-RSK-002' => [
                'cat' => 'CAT-RSK',
                'quarter' => 'Q2',
                'participants' => 40,
                'budget' => 40000000.00,
                'training_type' => 'E-Learning & Refreshment',
                'target_audience' => 'Seluruh Pegawai Operasional & Marketing',
                'gap' => 'Penerapan Customer Due Diligence (CDD), Enhanced Due Diligence (EDD), dan pelaporan LTKM PPATK.',
                'kpi' => 'Zero Finding Audit Kepatuhan APU-PPT dari OJK & BI',
            ],
            'SUB-RSK-003' => [
                'cat' => 'CAT-RSK',
                'quarter' => 'Q3',
                'participants' => 15,
                'budget' => 55000000.00,
                'training_type' => 'Audit Forensik Workshop',
                'target_audience' => 'Internal Audit & Anti-Fraud Section',
                'gap' => 'Pemeriksaan audit berbasis risiko, deteksi modus fraud transaksi kas, dan digital audit trail.',
                'kpi' => 'Zero Tolerance Fraud Incident di seluruh jaringan BWS',
            ],
            'SUB-RSK-004' => [
                'cat' => 'CAT-RSK',
                'quarter' => 'Q4',
                'participants' => 35,
                'budget' => 45000000.00,
                'training_type' => 'Seminar & Compliance Workshop',
                'target_audience' => 'Customer Care, Legal, Data Custodian',
                'gap' => 'Implementasi UU No. 27/2022 tentang Pelindungan Data Pribadi (UU PDP) sektor perbankan.',
                'kpi' => 'Kepatuhan penuh audit kerahasiaan data nasabah 100%',
            ],

            // Kategori 4: Teknologi Informasi & Keamanan Siber (CAT-IT)
            'SUB-IT-001' => [
                'cat' => 'CAT-IT',
                'quarter' => 'Q1',
                'participants' => 20,
                'budget' => 110000000.00,
                'training_type' => 'Hands-on Siber Lab & Sertifikasi',
                'target_audience' => 'CSIRT Bank, IT Security & System Admin',
                'gap' => 'Pemenuhan POJK 29/2022 tentang Ketahanan Siber, Incident Response, dan Threat Hunting.',
                'kpi' => 'Waktu mitigasi insiden siber MTTR < 30 menit & Lolos Audit OJK',
            ],
            'SUB-IT-002' => [
                'cat' => 'CAT-IT',
                'quarter' => 'Q2',
                'participants' => 12,
                'budget' => 65000000.00,
                'training_type' => 'Technical Boot Camp',
                'target_audience' => 'Database Administrator & Core Banking Team',
                'gap' => 'Tuning performa PostgreSQL/Oracle, optimasi query transaksi cabang, dan kelancaran EOD batch.',
                'kpi' => 'Waktu eksekusi proses EOD batch turun 40%',
            ],
            'SUB-IT-003' => [
                'cat' => 'CAT-IT',
                'quarter' => 'Q3',
                'participants' => 15,
                'budget' => 80000000.00,
                'training_type' => 'Arsitektur & API Workshop',
                'target_audience' => 'Digital Banking Developers & Cloud Engineers',
                'gap' => 'Penerapan Standar Nasional Open API Pembayaran (SNAP BI) dan arsitektur microservices aman.',
                'kpi' => 'Sertifikasi kepatuhan SNAP BI 100% tepat waktu',
            ],
            'SUB-IT-004' => [
                'cat' => 'CAT-IT',
                'quarter' => 'Q4',
                'participants' => 18,
                'budget' => 70000000.00,
                'training_type' => 'Simulasi BCM & Disaster Recovery',
                'target_audience' => 'Tim BCM, IT Infrastructure & Network',
                'gap' => 'Uji coba failover berkala DRC (Disaster Recovery Center) dan sertifikasi ISO 22301 BCM.',
                'kpi' => 'RTO < 2 Jam & RPO < 15 Menit saat simulasi DRC',
            ],

            // Kategori 5: Kepemimpinan, Manajerial & Budaya Kerja (CAT-LDR)
            'SUB-LDR-001' => [
                'cat' => 'CAT-LDR',
                'quarter' => 'Q1',
                'participants' => 20,
                'budget' => 150000000.00,
                'training_type' => 'ODP Banking Academy 6 Bulan',
                'target_audience' => 'Kandidat Officer Development Program (ODP)',
                'gap' => 'Pembekalan komprehensif perbankan, rotasi bisnis cabang, dan leadership sprint perwira BWS.',
                'kpi' => 'Graduation Rate ODP >= 95% dengan rating kompetensi Sangat Baik',
            ],
            'SUB-LDR-002' => [
                'cat' => 'CAT-LDR',
                'quarter' => 'Q2',
                'participants' => 15,
                'budget' => 90000000.00,
                'training_type' => 'Executive Leadership Retreat',
                'target_audience' => 'Pemimpin Cabang (Branch Manager) & Sub-Branch Head',
                'gap' => 'Penguasaan P&L Cabang, optimalisasi dana murah (CASA), dan coaching tim penjualan cabang.',
                'kpi' => 'Pertumbuhan laba cabang peserta minimal +18% YoY',
            ],
            'SUB-LDR-003' => [
                'cat' => 'CAT-LDR',
                'quarter' => 'Q3',
                'participants' => 25,
                'budget' => 60000000.00,
                'training_type' => 'GROW Coaching Workshop',
                'target_audience' => 'Supervisor Cabang & Section Head',
                'gap' => 'Kemampuan coaching berkala, pemberian feedback konstruktif, dan retensi talenta staf.',
                'kpi' => 'Penurunan angka turnover staf cabang sebesar 25%',
            ],
            'SUB-LDR-004' => [
                'cat' => 'CAT-LDR',
                'quarter' => 'Q4',
                'participants' => 50,
                'budget' => 50000000.00,
                'training_type' => 'Budaya & Integritas Conclave',
                'target_audience' => 'Perwakilan Seluruh Kantor Cabang & Kantor Pusat',
                'gap' => 'Internalisasi budaya Bank Woori Saudara, kode etik bankir, dan zero tolerance pelanggaran.',
                'kpi' => 'Indeks Budaya Perusahaan BWS >= 90%',
            ],
        ];

        // Delete existing items for clean re-insertion
        TnaItem::where('tna_proposal_id', $proposal->id)->delete();

        foreach ($subCategoryPlan as $subCode => $spec) {
            $cat = Category::where('code', $spec['cat'])->first();
            $sub = SubCategory::where('code', $subCode)->first();

            TnaItem::create([
                'tna_proposal_id' => $proposal->id,
                'category_id' => $cat ? $cat->id : null,
                'category_name' => $cat ? $cat->name : null,
                'sub_category_id' => $sub ? $sub->id : null,
                'sub_category_name' => $sub ? $sub->name : null,
                'job_family' => $cat ? $cat->name : 'General Banking',
                'recommended_program' => $sub ? $sub->name : $subCode,
                'competency_gap' => $spec['gap'],
                'training_type' => $spec['training_type'],
                'target_audience' => $spec['target_audience'],
                'target_participants_count' => $spec['participants'],
                'cost_per_participant' => round($spec['budget'] / $spec['participants'], 2),
                'estimated_cost' => $spec['budget'],
                'budget' => $spec['budget'],
                'execution_quarter' => $spec['quarter'],
                'urgency' => in_array($spec['quarter'], ['Q1', 'Q2']) ? 'Critical' : 'High',
                'impact_kpi' => $spec['kpi'],
            ]);
        }

        // 6. Multi-Approval History Trail (Audit Trail Log)
        ApprovalHistory::where('module_code', 'tna')->where('record_id', $proposal->id)->delete();

        ApprovalHistory::create([
            'module_code' => 'tna',
            'record_id' => $proposal->id,
            'step_order' => 1,
            'step_name' => 'Review Kebutuhan & Rekomendasi HCBP',
            'role_name' => 'HCBP',
            'approver_id' => $hcbp ? $hcbp->id : null,
            'approver_name' => $hcbp ? $hcbp->name : 'Siti Rahmawati',
            'approver_nik' => $hcbp ? ($hcbp->nik ?? 'BWS-HCP-019') : 'BWS-HCP-019',
            'action' => 'APPROVED',
            'notes' => 'Telah diverifikasi silang dengan usulan 28 Divisi dan Kantor Wilayah. Program diklat mencakup 5 rumpun lengkap secara seimbang.',
            'approved_amount' => 1485000000.00,
            'action_at' => now()->subDays(4),
        ]);

        ApprovalHistory::create([
            'module_code' => 'tna',
            'record_id' => $proposal->id,
            'step_order' => 2,
            'step_name' => 'Verifikasi Anggaran & Kurikulum Checker',
            'role_name' => 'HC Learning Head (Checker)',
            'approver_id' => $checker ? $checker->id : null,
            'approver_name' => $checker ? $checker->name : 'Dr. Maya Wulandari',
            'approver_nik' => $checker ? ($checker->nik ?? 'BWS-TRN-002') : 'BWS-TRN-002',
            'action' => 'APPROVED',
            'notes' => 'Seluruh silabus 20 materi, vendor pengajar, dan kepatuhan regulasi OJK & BI telah sesuai standar BWS L&D. Pagu disetujui penuh.',
            'approved_amount' => 1485000000.00,
            'action_at' => now()->subDays(3),
        ]);

        ApprovalHistory::create([
            'module_code' => 'tna',
            'record_id' => $proposal->id,
            'step_order' => 3,
            'step_name' => 'Persetujuan Final Direksi / Executive',
            'role_name' => 'Executive',
            'approver_id' => $approver ? $approver->id : null,
            'approver_name' => $approver ? $approver->name : 'Kwon Jin-Woo',
            'approver_nik' => $approver ? ($approver->nik ?? 'BWS-EXEC-003') : 'BWS-EXEC-003',
            'action' => 'APPROVED',
            'notes' => 'Disetujui penuh untuk penguatan ketahanan operasional, transformasi digital, dan mitigasi NPL cabang di tahun 2026.',
            'approved_amount' => 1485000000.00,
            'action_at' => now()->subDays(2),
        ]);
    }
}

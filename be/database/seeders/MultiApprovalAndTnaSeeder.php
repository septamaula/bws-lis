<?php

namespace Database\Seeders;

use App\Models\ApprovalFlowStep;
use App\Models\ApprovalHistory;
use App\Models\ApprovalModule;
use App\Models\TnaItem;
use App\Models\TnaProposal;
use App\Models\User;
use Illuminate\Database\Seeder;

class MultiApprovalAndTnaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. SEED APPROVAL MODULES & STEPS
        $modules = [
            [
                'module_code' => 'tna',
                'module_name' => 'Usulan TNA & Annual Training Plan',
                'description' => 'Alur verifikasi dan pengesahan pagu anggaran kebutuhan diklat per divisi kerja Bank Woori Saudara.',
                'icon_name' => 'Compass',
                'steps' => [
                    [
                        'step_order' => 1,
                        'step_name' => 'Reviewer 1 - Human Capital Business Partner (HCBP)',
                        'role_name' => 'HCBP',
                        'min_amount' => 0,
                        'max_amount' => null,
                        'sla_days' => 2,
                    ],
                    [
                        'step_order' => 2,
                        'step_name' => 'Checker - HC Learning Head (Review Anggaran & Metode)',
                        'role_name' => 'HC Learning Head (Checker)',
                        'min_amount' => 0,
                        'max_amount' => null,
                        'sla_days' => 3,
                    ],
                    [
                        'step_order' => 3,
                        'step_name' => 'Approver Final - Kepala Divisi Human Capital / Executive BoD',
                        'role_name' => 'Super Admin',
                        'min_amount' => 0,
                        'max_amount' => null,
                        'sla_days' => 3,
                    ],
                ],
            ],
            [
                'module_code' => 'class',
                'module_name' => 'Jadwal & Pembentukan Batch Kelas Diklat',
                'description' => 'Mekanisme dual control (Maker-Checker) sebelum kelas dipublikasikan dan undangan peserta dikirimkan.',
                'icon_name' => 'GraduationCap',
                'steps' => [
                    [
                        'step_order' => 1,
                        'step_name' => 'Checker - Validasi Jadwal, Ruangan & Instruktur',
                        'role_name' => 'HC Learning Head (Checker)',
                        'min_amount' => 0,
                        'max_amount' => null,
                        'sla_days' => 2,
                    ],
                    [
                        'step_order' => 2,
                        'step_name' => 'Otorisasi Final Kuota & Anggaran Kelas',
                        'role_name' => 'Super Admin',
                        'min_amount' => 0,
                        'max_amount' => null,
                        'sla_days' => 2,
                    ],
                ],
            ],
            [
                'module_code' => 'transaction',
                'module_name' => 'Transaksi Keuangan & Klaim Biaya Diklat',
                'description' => 'Dual control pengeluaran kas, reimbursement akomodasi peserta, dan pembayaran honorarium instruktur.',
                'icon_name' => 'Receipt',
                'steps' => [
                    [
                        'step_order' => 1,
                        'step_name' => 'Checker 1 - Verifikasi Bukti Kuitansi & Anggaran',
                        'role_name' => 'HC Learning Head (Checker)',
                        'min_amount' => 0,
                        'max_amount' => 50000000,
                        'sla_days' => 2,
                    ],
                    [
                        'step_order' => 2,
                        'step_name' => 'Approver Final - Otorisasi Pengeluaran Kas (> Rp 50 Juta)',
                        'role_name' => 'Super Admin',
                        'min_amount' => 50000000,
                        'max_amount' => null,
                        'sla_days' => 3,
                    ],
                ],
            ],
            [
                'module_code' => 'certification',
                'module_name' => 'Sertifikasi Wajib Regulasi OJK & BI',
                'description' => 'Verifikasi eligibilitas kepatuhan lisensi profesi perbankan (BSMR, SKSP, Tresuri, APU-PPT).',
                'icon_name' => 'ShieldAlert',
                'steps' => [
                    [
                        'step_order' => 1,
                        'step_name' => 'Reviewer - Validasi Masa Berlaku & Kelayakan Peserta',
                        'role_name' => 'HCBP',
                        'min_amount' => 0,
                        'max_amount' => null,
                        'sla_days' => 2,
                    ],
                    [
                        'step_order' => 2,
                        'step_name' => 'Otorisasi Pendaftaran Ujian Sertifikasi LSP/OJK',
                        'role_name' => 'HC Learning Head (Checker)',
                        'min_amount' => 0,
                        'max_amount' => null,
                        'sla_days' => 2,
                    ],
                ],
            ],
        ];

        foreach ($modules as $modData) {
            $steps = $modData['steps'];
            unset($modData['steps']);

            $module = ApprovalModule::updateOrCreate(
                ['module_code' => $modData['module_code']],
                $modData
            );

            // Re-seed steps
            ApprovalFlowStep::where('module_id', $module->id)->delete();
            foreach ($steps as $stepData) {
                $stepData['module_id'] = $module->id;
                ApprovalFlowStep::create($stepData);
            }
        }

        // 2. SEED REALISTIC BANKING TNA PROPOSALS & ITEMS
        $users = User::all()->keyBy('role_name');
        $superAdmin = $users['Super Admin'] ?? User::first();
        $deptHead = $users['Dept Head'] ?? User::where('email', 'depthead.it@bws.co.id')->first() ?? $superAdmin;
        $hcbp = $users['HCBP'] ?? User::where('email', 'hcbp@bws.co.id')->first() ?? $superAdmin;
        $checker = $users['HC Learning Head (Checker)'] ?? User::where('email', 'checker.head@bws.co.id')->first() ?? $superAdmin;

        // Proposal 1: IT Division (TNA-2026-IT-001) - Under Checker Review (Step 2)
        $tna1 = TnaProposal::updateOrCreate(
            ['proposal_code' => 'TNA-2026-IT-001'],
            [
                'department' => 'Information Technology',
                'year' => 2026,
                'proposed_by' => $deptHead->id,
                'priority' => 'Critical',
                'total_budget_requested' => 185000000,
                'total_budget_approved' => 175000000,
                'status' => 'CHECKER_REVIEW',
                'strategic_objective' => 'Penguatan Keamanan Siber Sesuai Regulasi POJK 29/2022, Modernisasi Arsitektur Core Banking API, dan Mitigasi Insiden Data Breach Perbankan.',
                'target_quarter' => 'Q1 - Q2 2026',
                'current_step_order' => 2,
                'approval_status_badge' => 'Menunggu Otorisasi Checker',
                'revision_count' => 0,
                'created_at' => now()->subDays(5),
            ]
        );

        TnaItem::where('tna_proposal_id', $tna1->id)->delete();
        TnaItem::create([
            'tna_proposal_id' => $tna1->id,
            'job_family' => 'IT & Digital Banking',
            'competency_gap' => 'Pengetahuan Mendalam Terkait Vulnerability Assessment & Standar POJK 29/2022 Keamanan Siber',
            'recommended_program' => 'Certified Banking Cyber Security Officer & Threat Modeling (BWS Academy)',
            'training_type' => 'In-House Training (Internal Academy)',
            'target_audience' => 'Officer - IT Security & Core Banking Infrastructure',
            'target_participants_count' => 10,
            'cost_per_participant' => 8500000,
            'estimated_cost' => 85000000,
            'execution_quarter' => 'Q1',
            'urgency' => 'Critical',
            'impact_kpi' => 'Zero High-Risk Vulnerability Finding pada Audit IT OJK & BI 2026.',
        ]);
        TnaItem::create([
            'tna_proposal_id' => $tna1->id,
            'job_family' => 'IT & Digital Banking',
            'competency_gap' => 'Desain Arsitektur Microservices Perbankan, Event-Driven Architecture & Real-Time Settlement',
            'recommended_program' => 'Cloud Native Banking Architecture & API Modernization',
            'training_type' => 'Public / External Course (LPPI / Certified)',
            'target_audience' => 'Lead Developer & System Architect',
            'target_participants_count' => 10,
            'cost_per_participant' => 6000000,
            'estimated_cost' => 60000000,
            'execution_quarter' => 'Q2',
            'urgency' => 'High',
            'impact_kpi' => 'SLA Sistem Core Banking & Mobile Apps LIS mencapai 99.98% uptime.',
        ]);
        TnaItem::create([
            'tna_proposal_id' => $tna1->id,
            'job_family' => 'Risk & Compliance',
            'competency_gap' => 'Tata Kelola TI Berdasarkan Framework COBIT 2019 & Sertifikasi ISO/IEC 27001 Lead Implementer',
            'recommended_program' => 'COBIT 2019 Foundation & ISO 27001 Information Security Lead Implementer',
            'training_type' => 'Sertifikasi Wajib Regulasi (OJK/BI)',
            'target_audience' => 'Manager & Quality Assurance IT',
            'target_participants_count' => 4,
            'cost_per_participant' => 10000000,
            'estimated_cost' => 40000000,
            'execution_quarter' => 'Q2',
            'urgency' => 'High',
            'impact_kpi' => 'Lolos resertifikasi ISO 27001:2022 tanpa temuan mayor.',
        ]);

        // Seed History for TNA 1
        ApprovalHistory::where('module_code', 'tna')->where('record_id', $tna1->id)->delete();
        ApprovalHistory::create([
            'module_code' => 'tna',
            'record_id' => $tna1->id,
            'step_order' => 1,
            'step_name' => 'Pengajuan Dokumen TNA Divisi IT',
            'approver_id' => $deptHead->id,
            'approver_name' => $deptHead->name,
            'approver_nik' => $deptHead->nik ?? 'BWS-DHD-042',
            'role_name' => 'Dept Head',
            'action' => 'SUBMITTED',
            'notes' => 'Usulan diklat prioritas TI tahun 2026 guna mendukung kepatuhan POJK 29/2022 dan ketahanan siber operasional cabang.',
            'approved_amount' => 185000000,
            'action_at' => now()->subDays(5),
        ]);
        ApprovalHistory::create([
            'module_code' => 'tna',
            'record_id' => $tna1->id,
            'step_order' => 1,
            'step_name' => 'Reviewer 1 - Human Capital Business Partner (HCBP)',
            'approver_id' => $hcbp->id,
            'approver_name' => $hcbp->name,
            'approver_nik' => $hcbp->nik ?? 'BWS-HCP-019',
            'role_name' => 'HCBP',
            'action' => 'APPROVED',
            'notes' => 'Kurikulum telah diverifikasi sesuai matrix kompetensi IT Bank Woori. Rekomendasi pagu disetujui Rp 175.000.000 dengan efisiensi paket kelas in-house.',
            'approved_amount' => 175000000,
            'action_at' => now()->subDays(2),
        ]);

        // Proposal 2: Commercial Credit (TNA-2026-CRD-002) - Awaiting Step 1 Review
        $tna2 = TnaProposal::updateOrCreate(
            ['proposal_code' => 'TNA-2026-CRD-002'],
            [
                'department' => 'Commercial & Corporate Credit',
                'year' => 2026,
                'proposed_by' => $deptHead->id,
                'priority' => 'High',
                'total_budget_requested' => 120000000,
                'total_budget_approved' => null,
                'status' => 'SUBMITTED',
                'strategic_objective' => 'Peningkatan Kualitas Portofolio Kredit Komersial, Analisis Risiko Arus Kas Sektor Riil, dan Mitigasi Non-Performing Loan (NPL).',
                'target_quarter' => 'Q1 - Q3 2026',
                'current_step_order' => 1,
                'approval_status_badge' => 'Menunggu Review HCBP',
                'revision_count' => 0,
                'created_at' => now()->subDays(1),
            ]
        );

        TnaItem::where('tna_proposal_id', $tna2->id)->delete();
        TnaItem::create([
            'tna_proposal_id' => $tna2->id,
            'job_family' => 'Credit & Financing',
            'competency_gap' => 'Kemampuan Pemodelan Cashflow Prospektif & Penilaian Agunan Komersial Berisiko Tinggi',
            'recommended_program' => 'Advanced Corporate Credit Analysis & Stress Testing (LPPI)',
            'training_type' => 'Public / External Course (LPPI / Certified)',
            'target_audience' => 'Relationship Manager & Credit Analyst Komersial',
            'target_participants_count' => 15,
            'cost_per_participant' => 5000000,
            'estimated_cost' => 75000000,
            'execution_quarter' => 'Q1',
            'urgency' => 'High',
            'impact_kpi' => 'NPL Netto kredit komersial terjaga di bawah rasio 1.5%.',
        ]);
        TnaItem::create([
            'tna_proposal_id' => $tna2->id,
            'job_family' => 'Credit & Financing',
            'competency_gap' => 'Implementasi Sistem Peringatan Dini Kredit Bermasalah & Strategi Penagihan Efektif',
            'recommended_program' => 'Early Warning System (EWS) & Loan Restructuring Masterclass',
            'training_type' => 'In-House Training (Internal Academy)',
            'target_audience' => 'Remedial Officer & Special Asset Management',
            'target_participants_count' => 9,
            'cost_per_participant' => 5000000,
            'estimated_cost' => 45000000,
            'execution_quarter' => 'Q2',
            'urgency' => 'Medium',
            'impact_kpi' => 'Tingkat kesuksesan restrukturisasi debitur watch-list mencapai 85%.',
        ]);

        ApprovalHistory::where('module_code', 'tna')->where('record_id', $tna2->id)->delete();
        ApprovalHistory::create([
            'module_code' => 'tna',
            'record_id' => $tna2->id,
            'step_order' => 1,
            'step_name' => 'Pengajuan Dokumen TNA Divisi Kredit',
            'approver_id' => $deptHead->id,
            'approver_name' => 'Agus Wicaksono (Dept Head Credit)',
            'approver_nik' => 'BWS-CRD-031',
            'role_name' => 'Dept Head',
            'action' => 'SUBMITTED',
            'notes' => 'Pengajuan program pelatihan mitigasi risiko kredit komersial menyambut ekspansi kredit tahun 2026.',
            'approved_amount' => 120000000,
            'action_at' => now()->subDays(1),
        ]);

        // Proposal 3: Risk & Compliance (TNA-2026-RSK-003) - Fully APPROVED
        $tna3 = TnaProposal::updateOrCreate(
            ['proposal_code' => 'TNA-2026-RSK-003'],
            [
                'department' => 'Risk Management & Compliance',
                'year' => 2026,
                'proposed_by' => $deptHead->id,
                'priority' => 'Critical',
                'total_budget_requested' => 165000000,
                'total_budget_approved' => 165000000,
                'status' => 'APPROVED',
                'strategic_objective' => 'Pemenuhan Ketentuan POJK & Bank Indonesia Terkait Sertifikasi Manajemen Risiko Bank (BSMR) Level 1-3 serta Penerapan APU-PPT Berbasis Risiko Terkini.',
                'target_quarter' => 'Q1 - Q4 2026',
                'current_step_order' => 3,
                'approval_status_badge' => 'Disetujui Final (Kadiv HC)',
                'revision_count' => 0,
                'approved_by' => $superAdmin->id,
                'approved_at' => now()->subDay(),
                'created_at' => now()->subDays(10),
            ]
        );

        TnaItem::where('tna_proposal_id', $tna3->id)->delete();
        TnaItem::create([
            'tna_proposal_id' => $tna3->id,
            'job_family' => 'Risk & Compliance',
            'competency_gap' => 'Sertifikasi Keahlian Manajemen Risiko Regulasi OJK Tingkat 1 & 2',
            'recommended_program' => 'Sertifikasi Manajemen Risiko Perbankan (BSMR Level 1 & 2)',
            'training_type' => 'Sertifikasi Wajib Regulasi (OJK/BI)',
            'target_audience' => 'Pejabat Operasional & Risk Champion Cabang',
            'target_participants_count' => 20,
            'cost_per_participant' => 5500000,
            'estimated_cost' => 110000000,
            'execution_quarter' => 'Q1',
            'urgency' => 'Critical',
            'impact_kpi' => 'Tingkat kepatuhan sertifikasi manajemen risiko BWS 100% memenuhi ketentuan OJK.',
        ]);
        TnaItem::create([
            'tna_proposal_id' => $tna3->id,
            'job_family' => 'Risk & Compliance',
            'competency_gap' => 'Pendeteksian Modus Transaksi Mencurigakan Terkini Sesuai Pedoman PPATK',
            'recommended_program' => 'Workshop Investigasi APU-PPT Berbasis Risiko & Tindak Pidana Perbankan',
            'training_type' => 'In-House Training (Internal Academy)',
            'target_audience' => 'Compliance Officer & AML Specialist',
            'target_participants_count' => 11,
            'cost_per_participant' => 5000000,
            'estimated_cost' => 55000000,
            'execution_quarter' => 'Q2',
            'urgency' => 'High',
            'impact_kpi' => 'Zero warning letter dan zero denda regulasi dari PPATK maupun OJK.',
        ]);

        ApprovalHistory::where('module_code', 'tna')->where('record_id', $tna3->id)->delete();
        ApprovalHistory::create([
            'module_code' => 'tna',
            'record_id' => $tna3->id,
            'step_order' => 1,
            'step_name' => 'Pengajuan Dokumen TNA Divisi Risiko',
            'approver_id' => $deptHead->id,
            'approver_name' => 'Farhan Hidayat (Dept Head Risk)',
            'approver_nik' => 'BWS-RSK-015',
            'role_name' => 'Dept Head',
            'action' => 'SUBMITTED',
            'notes' => 'Kewajiban sertifikasi berkala regulasi OJK untuk pejabat pemutus kredit dan operasional.',
            'approved_amount' => 165000000,
            'action_at' => now()->subDays(10),
        ]);
        ApprovalHistory::create([
            'module_code' => 'tna',
            'record_id' => $tna3->id,
            'step_order' => 1,
            'step_name' => 'Reviewer 1 - Human Capital Business Partner (HCBP)',
            'approver_id' => $hcbp->id,
            'approver_name' => $hcbp->name,
            'approver_nik' => $hcbp->nik ?? 'BWS-HCP-019',
            'role_name' => 'HCBP',
            'action' => 'APPROVED',
            'notes' => 'Mandatori regulasi OJK & BI, direkomendasikan prioritas utama tanpa penyesuaian pagu.',
            'approved_amount' => 165000000,
            'action_at' => now()->subDays(8),
        ]);
        ApprovalHistory::create([
            'module_code' => 'tna',
            'record_id' => $tna3->id,
            'step_order' => 2,
            'step_name' => 'Checker - HC Learning Head (Review Anggaran & Metode)',
            'approver_id' => $checker->id,
            'approver_name' => $checker->name,
            'approver_nik' => $checker->nik ?? 'BWS-TRN-002',
            'role_name' => 'HC Learning Head (Checker)',
            'action' => 'APPROVED',
            'notes' => 'Pagu anggaran telah dicocokkan dengan alokasi tahunan L&D BWS 2026. Jadwal ujian LSPP terkonfirmasi.',
            'approved_amount' => 165000000,
            'action_at' => now()->subDays(5),
        ]);
        ApprovalHistory::create([
            'module_code' => 'tna',
            'record_id' => $tna3->id,
            'step_order' => 3,
            'step_name' => 'Approver Final - Kepala Divisi Human Capital / Executive BoD',
            'approver_id' => $superAdmin->id,
            'approver_name' => $superAdmin->name,
            'approver_nik' => $superAdmin->nik ?? 'BWS-ADM-001',
            'role_name' => 'Super Admin',
            'action' => 'APPROVED',
            'notes' => 'Otorisasi final disahkan. Program resmi masuk dalam Annual Training Plan Bank Woori Saudara 2026.',
            'approved_amount' => 165000000,
            'action_at' => now()->subDay(),
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\FiscalYear;
use App\Models\TnaItem;
use App\Models\TnaProposal;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Seeder;

class AlignExcelWireframeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first() ?? User::create([
            'nik' => '990001',
            'name' => 'HC & LD Super Admin',
            'email' => 'admin@bankwoorisaudara.com',
            'password' => bcrypt('password'),
            'role_name' => 'Super Admin',
            'department' => 'Human Capital & Corporate Academy',
        ]);

        // 1. Seed TNA Proposals matching Excel Template
        $tnaYears = [
            [
                'year' => 2020,
                'proposal_code' => 'TNA-2020-BWS',
                'investment' => 1000000000,
                'realization' => 980000000,
                'regulator' => 800000000,
            ],
            [
                'year' => 2021,
                'proposal_code' => 'TNA-2021-BWS',
                'investment' => 1050000000,
                'realization' => 1048600000,
                'regulator' => 850000000,
            ],
            [
                'year' => 2022,
                'proposal_code' => 'TNA-2022-BWS',
                'investment' => 1102500000,
                'realization' => 1069572000,
                'regulator' => 900000000,
            ],
            [
                'year' => 2023,
                'proposal_code' => 'TNA-2023-BWS',
                'investment' => 1157625000,
                'realization' => 1155137760,
                'regulator' => 950000000,
            ],
            [
                'year' => 2024,
                'proposal_code' => 'TNA-2024-BWS',
                'investment' => 1400000000,
                'realization' => 1307000000,
                'regulator' => 1000000000,
            ],
            [
                'year' => 2026,
                'proposal_code' => 'TNA-2026-BWS',
                'investment' => 1485000000,
                'realization' => 425000000,
                'regulator' => 1100000000,
            ],
        ];

        foreach ($tnaYears as $data) {
            $fy = FiscalYear::firstOrCreate(
                ['year' => $data['year']],
                [
                    'name' => "Tahun Anggaran {$data['year']}",
                    'theme' => "Transformasi Kapabilitas Digital & Bisnis Perbankan BWS {$data['year']}",
                    'status' => $data['year'] === 2026 ? 'AKTIF' : 'TUTUP_BUKU',
                    'is_active' => $data['year'] === 2026,
                    'start_date' => "{$data['year']}-01-01",
                    'end_date' => "{$data['year']}-12-31",
                    'total_budget_cap' => $data['investment'],
                ]
            );

            $tna = TnaProposal::updateOrCreate(
                ['year' => $data['year']],
                [
                    'proposal_code' => $data['proposal_code'],
                    'department' => 'Human Capital & Corporate Academy (Bank-Wide Plan)',
                    'fiscal_year_id' => $fy->id,
                    'proposed_by' => $admin->id,
                    'priority' => 'Critical',
                    'total_budget_requested' => $data['investment'],
                    'total_budget_approved' => $data['investment'],
                    'minimum_regulator_investment' => $data['regulator'],
                    'realization_amount' => $data['realization'],
                    'status' => 'APPROVED',
                    'approval_status_badge' => 'Disetujui Final Direksi & OJK',
                    'strategic_objective' => "Peningkatan Indeks Kompetensi dan Kepatuhan Regulasi POJK & Standar BWS {$data['year']}",
                    'target_quarter' => "Q1 - Q4 {$data['year']}",
                    'current_step_order' => 3,
                    'attachment_name' => "Rencana_Induk_TNA_Tahunan_BWS_{$data['year']}.pdf",
                    'attachment_path' => "attachments/tna/Rencana_Induk_TNA_Tahunan_BWS_{$data['year']}.pdf",
                ]
            );

            // Jika tahun 2026, masukkan 11 item program pelatihan bank-wide (Total: Rp 1.485.000.000, Realisasi: Rp 425.000.000)
            if ($data['year'] === 2026) {
                $tna->items()->delete();
                $items2026 = [
                    // Teknologi Informasi & Siber (Total: Rp 275.000.000, Real: Rp 173.500.000)
                    ['cat' => 'Teknologi Informasi & Siber', 'prog' => 'PRG-CYBER-01 - Cyber Security Defense & Anti-Phishing in Core Banking', 'inv' => 85000000, 'real' => 78500000, 'q' => 'Q1'],
                    ['cat' => 'Teknologi Informasi & Siber', 'prog' => 'Cloud Native Banking Migration & Container Architecture', 'inv' => 100000000, 'real' => 95000000, 'q' => 'Q1'],
                    ['cat' => 'Teknologi Informasi & Siber', 'prog' => 'IT Disaster Recovery, High Availability & BCM', 'inv' => 90000000, 'real' => 0, 'q' => 'Q3'],

                    // Manajemen Risiko & OJK (Total: Rp 315.000.000, Real: Rp 187.000.000)
                    ['cat' => 'Manajemen Risiko, Kepatuhan & OJK', 'prog' => 'PRG-APU-02 - Penerapan Anti Pencucian Uang & Pencegahan Pendanaan Terorisme (APU PPT)', 'inv' => 75000000, 'real' => 72000000, 'q' => 'Q1'],
                    ['cat' => 'Manajemen Risiko, Kepatuhan & OJK', 'prog' => 'Sertifikasi Manajemen Risiko Perbankan (BSMR Tingkat 1 - 4)', 'inv' => 160000000, 'real' => 115000000, 'q' => 'Q2'],
                    ['cat' => 'Manajemen Risiko, Kepatuhan & OJK', 'prog' => 'Kepatuhan Regulasi POJK 29/2022 & Audit Kepatuhan Perbankan', 'inv' => 80000000, 'real' => 0, 'q' => 'Q3'],

                    // Kredit & Pembiayaan Komersial (Total: Rp 325.000.000, Real: Rp 64.500.000)
                    ['cat' => 'Kredit & Pembiayaan Komersial', 'prog' => 'PRG-CRE-03 - Comprehensive Commercial & SME Credit Risk Underwriting', 'inv' => 185000000, 'real' => 64500000, 'q' => 'Q2'],
                    ['cat' => 'Kredit & Pembiayaan Komersial', 'prog' => 'Restrukturisasi Kredit & Penanganan Kredit Bermasalah (NPL Remedial)', 'inv' => 140000000, 'real' => 0, 'q' => 'Q4'],

                    // Operasional & Layanan Perbankan (Total: Rp 320.000.000, Real: Rp 0)
                    ['cat' => 'Operasional & Layanan Perbankan', 'prog' => 'Service Excellence, Teller & Frontliner Customer Protection', 'inv' => 170000000, 'real' => 0, 'q' => 'Q3'],
                    ['cat' => 'Operasional & Layanan Perbankan', 'prog' => 'Sistem Pembayaran BI-FAST, SKNBI & Operasional Treasury', 'inv' => 150000000, 'real' => 0, 'q' => 'Q4'],

                    // Kepemimpinan & Budaya Kerja (Total: Rp 250.000.000, Real: Rp 0)
                    ['cat' => 'Kepemimpinan & Budaya Kerja', 'prog' => 'PRG-LEAD-04 - Strategic Banking Leadership & High Impact Branch Execution', 'inv' => 250000000, 'real' => 0, 'q' => 'Q4'],
                ];

                foreach ($items2026 as $i) {
                    TnaItem::create([
                        'tna_proposal_id' => $tna->id,
                        'category_name' => $i['cat'],
                        'sub_category_name' => $i['prog'],
                        'recommended_program' => $i['prog'],
                        'budget' => $i['inv'],
                        'estimated_cost' => $i['inv'],
                        'realization_amount' => $i['real'],
                        'target_participants_count' => 25,
                        'execution_quarter' => $i['q'],
                        'job_family' => $i['cat'],
                        'competency_gap' => 'Peningkatan gap kompetensi teknis perbankan',
                        'target_audience' => 'Pegawai BWS Terkait',
                        'urgency' => 'High',
                    ]);
                }
            }

            // Jika tahun 2024, masukkan 9 item persis sesuai gambar Excel
            if ($data['year'] === 2024) {
                $tna->items()->delete();
                $items2024 = [
                    // Category 1
                    ['cat' => 'Category 1', 'prog' => 'Training Program 1', 'inv' => 500000000, 'real' => 485000000],
                    ['cat' => 'Category 1', 'prog' => 'Training Program 2', 'inv' => 200000000, 'real' => 190000000],
                    ['cat' => 'Category 1', 'prog' => 'Training Program 3', 'inv' => 100000000, 'real' => 94000000],
                    // Category 2
                    ['cat' => 'Category 2', 'prog' => 'Training Program 4', 'inv' => 100000000, 'real' => 87000000],
                    ['cat' => 'Category 2', 'prog' => 'Training Program 5', 'inv' => 100000000, 'real' => 77000000],
                    ['cat' => 'Category 2', 'prog' => 'Training Program 6', 'inv' => 100000000, 'real' => 99000000],
                    // Category 3
                    ['cat' => 'Category 3', 'prog' => 'Training Program 7', 'inv' => 100000000, 'real' => 94000000],
                    ['cat' => 'Category 3', 'prog' => 'Training Program 8', 'inv' => 100000000, 'real' => 91000000],
                    ['cat' => 'Category 3', 'prog' => 'Training Program 9', 'inv' => 100000000, 'real' => 90000000],
                ];

                foreach ($items2024 as $i) {
                    TnaItem::create([
                        'tna_proposal_id' => $tna->id,
                        'category_name' => $i['cat'],
                        'sub_category_name' => $i['prog'],
                        'recommended_program' => $i['prog'],
                        'budget' => $i['inv'],
                        'estimated_cost' => $i['inv'],
                        'realization_amount' => $i['real'],
                        'target_participants_count' => 25,
                        'execution_quarter' => 'Q1',
                        'job_family' => $i['cat'],
                        'competency_gap' => 'Peningkatan gap kompetensi teknis perbankan',
                        'target_audience' => 'Pegawai BWS Terkait',
                        'urgency' => 'High',
                    ]);
                }
            }
        }

        // 2. Seed Programs matching Excel Template (D24_001, D24_002, D24_003)
        $tna2024 = TnaProposal::where('year', 2024)->first();
        $samplePrograms = [
            [
                'program_code' => 'D24_001',
                'title' => 'Program A',
                'start_date' => '2024-02-02',
                'end_date' => '2024-02-10',
                'location' => 'Ruang 1',
                'facilitator' => 'PT A',
                'total_budget' => 2000000,
                'realization_amount' => 1600000,
                'category' => 'Category 1',
                'bi_category' => 'Operasional Perbankan',
                'program_type' => 'In-House Training',
                'unit_initiator' => 'Operasional & Layanan',
                'activity_type' => 'Pelatihan',
                'duration_text' => '8 Hari (40 Jam)',
                'year' => 2024,
                'status' => 'APPROVED',
                'approval_scenario' => 'WITH_BUDGET',
                'budget_fee_facilitator' => 800000,
                'budget_meals' => 300000,
                'budget_hotel' => 0,
                'budget_meeting_package' => 400000,
                'budget_transportation' => 100000,
                'budget_business_trip' => 0,
                'budget_reward' => 0,
                'budget_material' => 200000,
                'budget_internet' => 100000,
                'budget_miscellaneous' => 100000,
            ],
            [
                'program_code' => 'D24_002',
                'title' => 'Program B',
                'start_date' => '2024-02-09',
                'end_date' => '2024-02-09',
                'location' => 'Ruang 2',
                'facilitator' => 'PT B',
                'total_budget' => 1000000,
                'realization_amount' => 740000,
                'category' => 'Category 1',
                'bi_category' => 'Kredit & Pembiayaan Komersial',
                'program_type' => 'In-House Training',
                'unit_initiator' => 'Kredit & Pembiayaan',
                'activity_type' => 'Workshop',
                'duration_text' => '1 Hari (8 Jam)',
                'year' => 2024,
                'status' => 'APPROVED',
                'approval_scenario' => 'WITH_BUDGET',
                'budget_fee_facilitator' => 500000,
                'budget_meals' => 200000,
                'budget_hotel' => 0,
                'budget_meeting_package' => 150000,
                'budget_transportation' => 50000,
                'budget_business_trip' => 0,
                'budget_reward' => 0,
                'budget_material' => 50000,
                'budget_internet' => 25000,
                'budget_miscellaneous' => 25000,
            ],
            [
                'program_code' => 'D24_003',
                'title' => 'Program C',
                'start_date' => '2024-03-01',
                'end_date' => '2024-03-02',
                'location' => 'Virtual',
                'facilitator' => 'PT C',
                'total_budget' => 1000000,
                'realization_amount' => 770000,
                'category' => 'Category 2',
                'bi_category' => 'Manajemen Risiko, Kepatuhan & OJK',
                'program_type' => 'Virtual Webinar',
                'unit_initiator' => 'Kepatuhan & AML',
                'activity_type' => 'Sertifikasi',
                'duration_text' => '2 Hari (16 Jam)',
                'year' => 2024,
                'status' => 'APPROVED',
                'approval_scenario' => 'WITH_BUDGET',
                'budget_fee_facilitator' => 600000,
                'budget_meals' => 0,
                'budget_hotel' => 0,
                'budget_meeting_package' => 0,
                'budget_transportation' => 0,
                'budget_business_trip' => 0,
                'budget_reward' => 0,
                'budget_material' => 200000,
                'budget_internet' => 100000,
                'budget_miscellaneous' => 100000,
            ],
            // 2026 sample program
            [
                'program_code' => 'D26_001',
                'title' => 'Executive Cyber Security & Cloud Defense BWS 2026',
                'start_date' => '2026-03-15',
                'end_date' => '2026-03-18',
                'location' => 'Ruang Diklat 101 - BWS Tower',
                'facilitator' => 'PT Cyber Security Global',
                'total_budget' => 85000000,
                'realization_amount' => 78500000,
                'category' => 'Teknologi Informasi & Siber',
                'bi_category' => 'Teknologi Informasi & Siber (POJK 29/2022)',
                'program_type' => 'In-House Training',
                'unit_initiator' => 'Divisi Teknologi Informasi',
                'activity_type' => 'Pelatihan & Uji Penetrasi',
                'duration_text' => '4 Hari (32 Jam)',
                'year' => 2026,
                'status' => 'APPROVED',
                'approval_scenario' => 'WITH_BUDGET',
                'budget_fee_facilitator' => 45000000,
                'budget_meals' => 8000000,
                'budget_hotel' => 12000000,
                'budget_meeting_package' => 6000000,
                'budget_transportation' => 4000000,
                'budget_business_trip' => 0,
                'budget_reward' => 0,
                'budget_material' => 5000000,
                'budget_internet' => 2000000,
                'budget_miscellaneous' => 3000000,
            ],
        ];

        foreach ($samplePrograms as $p) {
            TrainingProgram::updateOrCreate(
                ['program_code' => $p['program_code']],
                array_merge($p, [
                    'created_by' => $admin->id,
                    'tna_proposal_id' => $p['year'] === 2024 ? $tna2024?->id : null,
                    'approval_status_badge' => 'Disetujui Final',
                ])
            );
        }
    }
}

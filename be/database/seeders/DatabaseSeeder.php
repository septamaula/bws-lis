<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\ClassEnrollment;
use App\Models\FinancialTransaction;
use App\Models\TnaItem;
use App\Models\TnaProposal;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\Karyawan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Core Permissions
        $permissions = [
            'view_dashboard',
            'manage_tna_proposals',
            'approve_tna_proposals',
            'manage_programs',
            'create_class_maker',
            'approve_class_checker',
            'grade_participants',
            'view_own_learning',
            'submit_task',
            'manage_financials_maker',
            'approve_financials_checker',
            'view_executive_reports',
            'view_audit_trail',
            'system_administration',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 2. Define the 8 Multi-Role RBAC Archetypes
        $roleMap = [
            'Super Admin' => $permissions,
            'Dept Head' => ['view_dashboard', 'manage_tna_proposals', 'view_executive_reports'],
            'HCBP' => ['view_dashboard', 'manage_tna_proposals', 'approve_tna_proposals', 'view_executive_reports'],
            'HC Training (Maker)' => ['view_dashboard', 'manage_programs', 'create_class_maker', 'manage_financials_maker'],
            'HC Learning Head (Checker)' => ['view_dashboard', 'approve_class_checker', 'approve_financials_checker', 'view_executive_reports', 'view_audit_trail'],
            'Facilitator / Trainer' => ['view_dashboard', 'grade_participants'],
            'Employee / Participant' => ['view_dashboard', 'view_own_learning', 'submit_task'],
            'Executive' => ['view_dashboard', 'view_executive_reports'],
        ];

        foreach ($roleMap as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($perms);
        }

        // 3. Seed Demo Users for each Role
        $usersData = [
            [
                'nik' => 'BWS-ADM-001',
                'name' => 'Budi Pratama (Super Admin)',
                'email' => 'admin@bws.co.id',
                'role_name' => 'Super Admin',
                'department' => 'IT Security & Governance',
                'branch' => 'Kantor Pusat Sudirman',
                'phone' => '+62 811-1001-001',
            ],
            [
                'nik' => 'BWS-DHD-042',
                'name' => 'Hendro Santoso (Dept Head IT)',
                'email' => 'depthead.it@bws.co.id',
                'role_name' => 'Dept Head',
                'department' => 'Information Technology',
                'branch' => 'Kantor Pusat Sudirman',
                'phone' => '+62 812-2002-042',
            ],
            [
                'nik' => 'BWS-HCP-019',
                'name' => 'Siti Rahmawati (HCBP Lead)',
                'email' => 'hcbp@bws.co.id',
                'role_name' => 'HCBP',
                'department' => 'Human Capital Business Partner',
                'branch' => 'Kantor Pusat Sudirman',
                'phone' => '+62 813-3003-019',
            ],
            [
                'nik' => 'BWS-TRN-101',
                'name' => 'Aditya Nugraha (HC Training Maker)',
                'email' => 'maker.training@bws.co.id',
                'role_name' => 'HC Training (Maker)',
                'department' => 'HC Learning & Development',
                'branch' => 'Kantor Pusat Sudirman',
                'phone' => '+62 814-4004-101',
            ],
            [
                'nik' => 'BWS-TRN-002',
                'name' => 'Dr. Maya Wulandari (HC Learning Head)',
                'email' => 'checker.head@bws.co.id',
                'role_name' => 'HC Learning Head (Checker)',
                'department' => 'HC Learning & Development',
                'branch' => 'Kantor Pusat Sudirman',
                'phone' => '+62 815-5005-002',
            ],
            [
                'nik' => 'BWS-FAC-014',
                'name' => 'Bambang Soedjarwo (Senior Banking Trainer)',
                'email' => 'facilitator@bws.co.id',
                'role_name' => 'Facilitator / Trainer',
                'department' => 'BWS Corporate Academy',
                'branch' => 'Learning Center Bandung',
                'phone' => '+62 816-6006-014',
            ],
            [
                'nik' => 'BWS-EMP-9821',
                'name' => 'Yazid Bustomi (Credit & Operations Officer)',
                'email' => 'participant@bws.co.id',
                'role_name' => 'Employee / Participant',
                'department' => 'Branch Banking Operations',
                'branch' => 'KCU Bandung Merdeka',
                'phone' => '+62 817-7007-821',
            ],
            [
                'nik' => 'BWS-EXEC-003',
                'name' => 'Kwon Jin-Woo (Director of Human Capital & Ops)',
                'email' => 'bod.executive@bws.co.id',
                'role_name' => 'Executive',
                'department' => 'Board of Directors',
                'branch' => 'Kantor Pusat Sudirman',
                'phone' => '+62 818-8008-003',
            ],
        ];

        $createdUsers = [];
        foreach ($usersData as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'nik' => $u['nik'],
                    'name' => $u['name'],
                    'role_name' => $u['role_name'],
                    'department' => $u['department'],
                    'branch' => $u['branch'],
                    'phone' => $u['phone'],
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                    'last_login_at' => now()->subHours(rand(1, 48)),
                    'last_login_ip' => '10.20.'.rand(1, 50).'.'.rand(1, 250),
                ]
            );
            $user->assignRole($u['role_name']);
            $createdUsers[$u['role_name']] = $user;

            Karyawan::firstOrCreate(
                ['nik' => $u['nik']],
                [
                    'nama' => $u['name'],
                    'nama_panggilan' => explode(' ', $u['name'])[0],
                    'jk' => str_contains($u['name'], 'Siti') || str_contains($u['name'], 'Maya') ? 'P' : 'L',
                    'email' => $u['email'],
                    'hp' => $u['phone'],
                    'departemen' => $u['department'],
                    'jabatan' => $u['role_name'],
                    'kantor_cabang' => $u['branch'],
                    'golongan' => str_contains($u['role_name'], 'Executive') ? 'Executive' : (str_contains($u['role_name'], 'Head') ? 'Senior Manager' : 'Officer'),
                    'st_kerja' => 'AKTIF',
                    'tgl_masuk' => now()->subYears(3)->format('Y-m-d'),
                    'role_name' => $u['role_name'],
                    'target_jam_pelatihan' => 20,
                    'is_active' => true,
                ]
            );
        }

        // 4. Seed Training Programs
        $programs = [
            [
                'program_code' => 'PRG-CYBER-01',
                'title' => 'Cyber Security Defense & Anti-Phishing in Core Banking',
                'category' => 'Compliance & Risk',
                'description' => 'Pelatihan intensif mitigasi ancaman ransomware, social engineering, perlindungan kredensial OJK & pematuhan ISO 27001 pada ekosistem perbankan BWS.',
                'passing_grade' => 80,
                'duration_hours' => 16,
                'is_mandatory' => true,
                'created_by' => $createdUsers['HC Training (Maker)']->id,
            ],
            [
                'program_code' => 'PRG-APU-02',
                'title' => 'Penerapan Anti Pencucian Uang & Pencegahan Pendanaan Terorisme (APU PPT)',
                'category' => 'Compliance & Risk',
                'description' => 'Kewajiban regulator Bank Indonesia & PPATK untuk identifikasi transaksi mencurigakan (STR), Customer Due Diligence (CDD), dan Enhanced Due Diligence (EDD).',
                'passing_grade' => 75,
                'duration_hours' => 12,
                'is_mandatory' => true,
                'created_by' => $createdUsers['HC Training (Maker)']->id,
            ],
            [
                'program_code' => 'PRG-CRE-03',
                'title' => 'Comprehensive Commercial & SME Credit Risk Underwriting',
                'category' => 'Banking Operations',
                'description' => 'Analisis rasio 5C, kelayakan agunan, restrukturisasi kredit, dan simulasi stress-testing NPL pada segmen pembiayaan korporasi.',
                'passing_grade' => 75,
                'duration_hours' => 24,
                'is_mandatory' => false,
                'created_by' => $createdUsers['HC Training (Maker)']->id,
            ],
            [
                'program_code' => 'PRG-LEAD-04',
                'title' => 'Strategic Banking Leadership & High Impact Branch Execution',
                'category' => 'Leadership',
                'description' => 'Transformasi manajerial bagi calon Pemimpin Cabang dan Team Leader dalam mengakselerasi CASA, fee-based income, dan coaching SDM perbankan.',
                'passing_grade' => 70,
                'duration_hours' => 32,
                'is_mandatory' => false,
                'created_by' => $createdUsers['HC Training (Maker)']->id,
            ],
        ];

        $savedPrograms = [];
        foreach ($programs as $prog) {
            $savedPrograms[] = TrainingProgram::firstOrCreate(['program_code' => $prog['program_code']], $prog);
        }

        // 5. Seed TNA Proposals & Items
        $tna = TnaProposal::firstOrCreate(
            ['proposal_code' => 'TNA-2026-IT-001'],
            [
                'department' => 'Information Technology',
                'year' => 2026,
                'proposed_by' => $createdUsers['Dept Head']->id,
                'priority' => 'High',
                'total_budget_requested' => 185000000,
                'total_budget_approved' => 175000000,
                'status' => 'APPROVED',
                'hcbp_notes' => 'Disetujui untuk peningkatan kesiapan audit OJK IT Security semester 1.',
                'approved_by' => $createdUsers['HCBP']->id,
                'approved_at' => now()->subDays(5),
            ]
        );

        TnaItem::firstOrCreate(
            ['tna_proposal_id' => $tna->id, 'competency_gap' => 'Security Operations Center (SOC) Incident Response'],
            [
                'recommended_program' => 'PRG-CYBER-01 - Cyber Security Defense',
                'target_audience' => 'IT Infrastructure & Security Engineers',
                'target_participants_count' => 15,
                'estimated_cost' => 85000000,
                'urgency' => 'Critical',
            ]
        );

        TnaItem::firstOrCreate(
            ['tna_proposal_id' => $tna->id, 'competency_gap' => 'Cloud Native Banking Migration Resilience'],
            [
                'recommended_program' => 'PostgreSQL & Enterprise Container Architecture',
                'target_audience' => 'Core Banking Developers & DBA',
                'target_participants_count' => 10,
                'estimated_cost' => 100000000,
                'urgency' => 'High',
            ]
        );

        // 6. Seed Training Class (Maker-Checker Flow)
        $class1 = TrainingClass::firstOrCreate(
            ['class_code' => 'CLS-2026-001'],
            [
                'program_id' => $savedPrograms[0]->id,
                'batch_name' => 'Batch 01 - Cyber Defense Core Banking',
                'method' => 'Hybrid (ELAPSE + In-Classroom)',
                'start_date' => now()->addDays(2),
                'end_date' => now()->addDays(5),
                'max_quota' => 25,
                'enrolled_count' => 18,
                'location_or_url' => 'BWS Auditorium Lt. 7 Sudirman & ELAPSE Portal',
                'facilitator_id' => $createdUsers['Facilitator / Trainer']->id,
                'dual_control_status' => 'APPROVED',
                'maker_id' => $createdUsers['HC Training (Maker)']->id,
                'checker_id' => $createdUsers['HC Learning Head (Checker)']->id,
                'checker_notes' => 'Silabus dan vendor penguji kredensial telah terverifikasi sesuai standar OJK.',
                'approved_at' => now()->subDay(),
            ]
        );

        $class2 = TrainingClass::firstOrCreate(
            ['class_code' => 'CLS-2026-002'],
            [
                'program_id' => $savedPrograms[1]->id,
                'batch_name' => 'Batch 02 - Sertifikasi APU PPT Seluruh Frontliner',
                'method' => 'ELAPSE E-Learning',
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(21),
                'max_quota' => 50,
                'enrolled_count' => 42,
                'location_or_url' => 'https://learning.bankwoorisaudara.com/courses/apu-ppt-2026',
                'facilitator_id' => $createdUsers['Facilitator / Trainer']->id,
                'dual_control_status' => 'PENDING_CHECKER',
                'maker_id' => $createdUsers['HC Training (Maker)']->id,
                'checker_id' => null,
                'checker_notes' => null,
            ]
        );

        // 7. Seed Participant Enrollment (ELAPSE Tracking)
        $enrollment = ClassEnrollment::firstOrCreate(
            [
                'training_class_id' => $class1->id,
                'user_id' => $createdUsers['Employee / Participant']->id,
            ],
            [
                'attendance_status' => 'PRESENT',
                'pre_test_score' => 65.00,
                'post_test_score' => 88.50,
                'task_score' => 92.00,
                'final_score' => 89.20,
                'elapse_progress_pct' => 100,
                'completion_status' => 'PASSED',
                'task_submission_url' => '/secure_storage/submissions/action_plan_bws_emp_9821.pdf',
                'task_submitted_at' => now()->subHours(6),
                'task_feedback' => 'Analisis mitigasi brute-force attack dan pemetaan flow OJK sangat komprehensif. Lulus dengan predikat Sangat Baik.',
                'certificate_id' => 'BWS-CERT-2026-CYBER-8812',
                'certificate_url' => '/secure_storage/certificates/cert_bws_emp_9821.pdf',
            ]
        );

        // 8. Seed Financial Transaction (Dual Control Maker-Checker)
        FinancialTransaction::firstOrCreate(
            ['trx_number' => 'TRX-2026-0081'],
            [
                'training_class_id' => $class1->id,
                'tna_proposal_id' => $tna->id,
                'category' => 'Vendor Fee & Certification Exam',
                'description' => 'Pembayaran vendor materi sertifikasi keamanan data perbankan & voucher ujian peserta Batch 01.',
                'amount' => 45000000,
                'maker_id' => $createdUsers['HC Training (Maker)']->id,
                'checker_id' => $createdUsers['HC Learning Head (Checker)']->id,
                'status' => 'APPROVED',
                'checker_notes' => 'Nominal tagihan sesuai dengan kontrak PO No. PO/BWS/HC/2026/012.',
                'receipt_attachment_url' => '/secure_storage/invoices/inv_vendor_cyber_0081.pdf',
                'approved_at' => now()->subHours(12),
                'disbursed_at' => now()->subHours(4),
            ]
        );

        FinancialTransaction::firstOrCreate(
            ['trx_number' => 'TRX-2026-0082'],
            [
                'training_class_id' => $class2->id,
                'tna_proposal_id' => $tna->id,
                'category' => 'Learning Materials & LMS Hosting',
                'description' => 'Lisensi modul interaktif ELAPSE e-Learning untuk 50 peserta frontliner.',
                'amount' => 18500000,
                'maker_id' => $createdUsers['HC Training (Maker)']->id,
                'checker_id' => null,
                'status' => 'PENDING_APPROVAL',
                'checker_notes' => null,
                'receipt_attachment_url' => null,
            ]
        );

        // 9. Seed Initial Immutable Audit Trail (OJK Compliance)
        $auditLogs = [
            [
                'action' => 'SYSTEM_INIT',
                'nik' => 'BWS-ADM-001',
                'name' => 'Budi Pratama (Super Admin)',
                'role' => 'Super Admin',
                'entity_type' => 'App\Models\User',
                'entity_id' => 1,
                'ip' => '10.20.1.1',
                'desc' => ['event' => 'Initial security baseline configuration with RBAC 8 roles and PostgreSQL encryption.'],
            ],
            [
                'action' => 'TNA_PROPOSAL_SUBMITTED',
                'nik' => 'BWS-DHD-042',
                'name' => 'Hendro Santoso (Dept Head IT)',
                'role' => 'Dept Head',
                'entity_type' => 'App\Models\TnaProposal',
                'entity_id' => $tna->id,
                'ip' => '10.20.4.15',
                'desc' => ['proposal_code' => 'TNA-2026-IT-001', 'total_budget' => 185000000],
            ],
            [
                'action' => 'TNA_HCBP_APPROVAL',
                'nik' => 'BWS-HCP-019',
                'name' => 'Siti Rahmawati (HCBP Lead)',
                'role' => 'HCBP',
                'entity_type' => 'App\Models\TnaProposal',
                'entity_id' => $tna->id,
                'ip' => '10.20.3.22',
                'desc' => ['status' => 'APPROVED', 'approved_budget' => 175000000],
            ],
            [
                'action' => 'MAKER_CLASS_CREATION',
                'nik' => 'BWS-TRN-101',
                'name' => 'Aditya Nugraha (HC Training Maker)',
                'role' => 'HC Training (Maker)',
                'entity_type' => 'App\Models\TrainingClass',
                'entity_id' => $class1->id,
                'ip' => '10.20.2.11',
                'desc' => ['class_code' => 'CLS-2026-001', 'action' => 'Submitted for Dual-Control Checker approval.'],
            ],
            [
                'action' => 'CHECKER_CLASS_APPROVAL',
                'nik' => 'BWS-TRN-002',
                'name' => 'Dr. Maya Wulandari (HC Learning Head)',
                'role' => 'HC Learning Head (Checker)',
                'entity_type' => 'App\Models\TrainingClass',
                'entity_id' => $class1->id,
                'ip' => '10.20.2.1',
                'desc' => ['class_code' => 'CLS-2026-001', 'decision' => 'APPROVED', 'notes' => 'Verified syllabus & instructor credentials.'],
            ],
        ];

        $prevHash = '0000000000000000000000000000000000000000000000000000000000000000';
        foreach ($auditLogs as $idx => $log) {
            $timestamp = now()->subHours(10 - $idx * 2);
            $payload = json_encode([
                'prev' => $prevHash,
                'user' => $log['nik'],
                'action' => $log['action'],
                'entity' => $log['entity_type'],
                'ip' => $log['ip'],
                'time' => $timestamp->toIso8601String(),
                'data' => $log['desc'],
            ]);
            $hash = hash('sha256', $payload);

            AuditLog::create([
                'user_id' => 1,
                'user_nik' => $log['nik'],
                'user_name' => $log['name'],
                'role' => $log['role'],
                'action' => $log['action'],
                'entity_type' => $log['entity_type'],
                'entity_id' => $log['entity_id'],
                'ip_address' => $log['ip'],
                'user_agent' => 'BWS-Enterprise-Core/v1.0 (Banking Security Node)',
                'old_values' => null,
                'new_values' => $log['desc'],
                'hash' => $hash,
                'created_at' => $timestamp,
            ]);

            $prevHash = $hash;
        }
    }
}

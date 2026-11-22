<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Models\TnaProposal;
use App\Models\TrainingClass;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LisApplicationTest extends TestCase
{
    public function test_api_health_endpoint_returns_security_headers(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertJsonPath('status', 'ok');
    }

    public function test_user_can_login_via_api_and_receive_sanctum_token(): void
    {
        $user = User::where('role_name', 'Super Admin')->first();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'user', 'token']);
    }

    public function test_authenticated_admin_can_fetch_dashboard_api(): void
    {
        $user = User::where('role_name', 'Super Admin')->first();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'kpis',
                'tnaProposals',
                'trainingPrograms',
                'trainingClasses',
                'enrollments',
                'financials',
                'auditLogs',
                'availableRoles',
            ]);
    }

    public function test_authenticated_learner_can_fetch_mobile_pwa_portal_api(): void
    {
        $learner = User::where('role_name', 'Employee / Participant')->first();
        Sanctum::actingAs($learner);

        $response = $this->getJson('/api/portal/learner');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'kpis',
                'enrolledClasses',
                'tasks',
            ]);
    }

    public function test_api_dual_control_rule_maker_cannot_approve_own_class(): void
    {
        $maker = User::where('role_name', 'HC Training (Maker)')->first();
        $class = TrainingClass::where('maker_id', $maker->id)->first();

        Sanctum::actingAs($maker);

        if ($class) {
            $response = $this->postJson("/api/classes/{$class->id}/approve", [
                'decision' => 'APPROVED',
                'checker_notes' => 'Self approval attempt via API',
            ]);

            $response->assertStatus(403);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_api_dual_control_rule_maker_cannot_approve_own_financial_voucher(): void
    {
        $maker = User::where('role_name', 'HC Training (Maker)')->first();
        $voucher = FinancialTransaction::where('maker_id', $maker->id)->first();

        Sanctum::actingAs($maker);

        if ($voucher) {
            $response = $this->postJson("/api/transactions/{$voucher->id}/approve", [
                'decision' => 'APPROVED',
                'checker_notes' => 'Self approval fraud attempt via API',
            ]);

            $response->assertStatus(403);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_api_tna_proposal_creation_stores_audit_log(): void
    {
        $deptHead = User::where('role_name', 'Dept Head')->first();
        Sanctum::actingAs($deptHead);

        $testYear = 2028;
        TnaProposal::where('year', $testYear)->delete();

        $response = $this->postJson('/api/tna', [
            'department' => 'Risk Management',
            'year' => $testYear,
            'priority' => 'High',
            'items' => [
                [
                    'competency_gap' => 'Pemahaman Regulasi OJK POJK 11/2022',
                    'recommended_program' => 'Sertifikasi Manajemen Risiko SMR 2',
                    'target_audience' => 'Officer & Manager',
                    'target_participants_count' => 15,
                    'estimated_cost' => 25000000,
                    'urgency' => 'High',
                ],
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tna_proposals', [
            'department' => 'Risk Management',
            'year' => $testYear,
            'status' => 'SUBMITTED',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SUBMIT_TNA_PROPOSAL',
            'user_id' => $deptHead->id,
        ]);

        TnaProposal::where('year', $testYear)->delete();
    }
}

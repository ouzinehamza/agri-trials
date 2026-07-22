<?php
namespace Tests\Feature;

use App\Models\{Trial, User, Workspace};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** SPEC §4: external partners see only the trials assigned to them, not the whole workspace. */
class PartnerTrialScopingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{admin:User,manager:User,partner:User,ws:Workspace,assigned:Trial,other:Trial} */
    private function scenario(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $manager = User::factory()->create(['role' => 'manager', 'is_external' => false, 'email_verified_at' => now()]);
        $partner = User::factory()->create(['role' => 'partner', 'is_external' => true, 'email_verified_at' => now()]);
        $ws = Workspace::create(['name' => 'WS', 'created_by' => $admin->id]);
        $ws->members()->attach([$manager->id => ['role' => 'manager'], $partner->id => ['role' => 'partner']]);
        $assigned = Trial::create(['code' => 'PA0001', 'variety' => 'V1', 'culture' => 'Melon', 'workspace_id' => $ws->id]);
        $other = Trial::create(['code' => 'PA0002', 'variety' => 'V2', 'culture' => 'Melon', 'workspace_id' => $ws->id]);
        $partner->assignedTrials()->sync([$assigned->id]);

        return compact('admin', 'manager', 'partner', 'ws', 'assigned', 'other');
    }

    public function test_partner_index_lists_only_assigned_trials(): void
    {
        $s = $this->scenario();
        $this->actingAs($s['partner'])->get('/trials')
            ->assertInertia(fn ($page) => $page->component('Trials/Index')->has('trials', 1)
                ->where('trials.0.code', 'PA0001'));
    }

    public function test_partner_cannot_open_unassigned_trial_in_same_workspace(): void
    {
        $s = $this->scenario();
        $this->actingAs($s['partner'])->get("/trials/{$s['other']->id}")->assertForbidden();
    }

    public function test_partner_can_open_assigned_trial(): void
    {
        $s = $this->scenario();
        $this->actingAs($s['partner'])->get("/trials/{$s['assigned']->id}")->assertOk();
    }

    public function test_manager_can_assign_and_scope_follows(): void
    {
        $s = $this->scenario();
        // Manager assigns the partner to the previously-unassigned trial.
        $this->actingAs($s['manager'])
            ->put("/trials/{$s['other']->id}/assignees", ['user_ids' => [$s['partner']->id]])
            ->assertRedirect();
        $this->assertDatabaseHas('trial_user', ['trial_id' => $s['other']->id, 'user_id' => $s['partner']->id]);
        $this->actingAs($s['partner'])->get("/trials/{$s['other']->id}")->assertOk();
    }

    public function test_partner_cannot_assign(): void
    {
        $s = $this->scenario();
        $this->actingAs($s['partner'])
            ->put("/trials/{$s['assigned']->id}/assignees", ['user_ids' => [$s['partner']->id]])
            ->assertForbidden();
    }

    public function test_non_members_of_workspace_cannot_be_assigned(): void
    {
        $s = $this->scenario();
        $outsider = User::factory()->create(['role' => 'partner', 'is_external' => true, 'email_verified_at' => now()]);
        $this->actingAs($s['manager'])
            ->put("/trials/{$s['assigned']->id}/assignees", ['user_ids' => [$outsider->id]])
            ->assertRedirect();
        // Outsider is silently filtered out (not a workspace member).
        $this->assertDatabaseMissing('trial_user', ['trial_id' => $s['assigned']->id, 'user_id' => $outsider->id]);
    }
}

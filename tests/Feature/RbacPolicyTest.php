<?php
namespace Tests\Feature;

use App\Models\{Trial, User, Workspace};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/** SPEC §4 / DEVELOPMENT_RULES §6: workspace-scoped RBAC is enforced by TrialPolicy (+ admin bypass). */
class RbacPolicyTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $ws;
    private Trial $trial;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->ws = Workspace::create(['name' => 'WS', 'created_by' => $admin->id]);
        $this->trial = Trial::create(['code' => 'RB1', 'variety' => 'V', 'culture' => 'Melon', 'workspace_id' => $this->ws->id]);
    }

    private function member(string $role, bool $external = false): User
    {
        $u = User::factory()->create(['role' => $role, 'is_external' => $external, 'email_verified_at' => now()]);
        $this->ws->members()->attach($u->id, ['role' => $role]);

        return $u;
    }

    private function allows(User $u, string $ability): bool
    {
        return Gate::forUser($u)->allows($ability, $this->trial);
    }

    public function test_admin_bypasses_all_abilities(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        foreach (['view', 'record', 'reopen', 'assign', 'decide'] as $ability) {
            $this->assertTrue($this->allows($admin, $ability), "admin should be allowed to {$ability}");
        }
    }

    public function test_workspace_roles_map_to_abilities(): void
    {
        $matrix = [
            // role        => [view, record, reopen, assign, decide]
            'manager'    => [true,  true,  true,  true,  false],
            'agronomist' => [true,  true,  false, false, false],
            'technician' => [true,  true,  false, false, false],
            'viewer'     => [true,  false, false, false, false],
        ];
        foreach ($matrix as $role => [$view, $record, $reopen, $assign, $decide]) {
            $u = $this->member($role);
            $this->assertSame($view, $this->allows($u, 'view'), "$role view");
            $this->assertSame($record, $this->allows($u, 'record'), "$role record");
            $this->assertSame($reopen, $this->allows($u, 'reopen'), "$role reopen");
            $this->assertSame($assign, $this->allows($u, 'assign'), "$role assign");
            $this->assertSame($decide, $this->allows($u, 'decide'), "$role decide");
        }
    }

    public function test_non_member_cannot_view(): void
    {
        $outsider = User::factory()->create(['role' => 'agronomist', 'email_verified_at' => now()]);
        $this->assertFalse($this->allows($outsider, 'view'));
    }

    public function test_external_partner_needs_assignment_to_view(): void
    {
        $partner = $this->member('partner', external: true);
        $this->assertFalse($this->allows($partner, 'view'), 'unassigned partner cannot view');

        $partner->assignedTrials()->attach($this->trial->id);
        $this->assertTrue($this->allows($partner->fresh(), 'view'), 'assigned partner can view');
        $this->assertFalse($this->allows($partner->fresh(), 'record'), 'partner never records');
    }
}

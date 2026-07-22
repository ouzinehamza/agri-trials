<?php

namespace App\Policies;

use App\Models\Trial;
use App\Models\User;

/**
 * Trial authorization (SPEC §4). Admin is handled by the global Gate::before bypass, so these methods
 * only encode the workspace-scoped, role-based rules for non-admins.
 *
 *  - view    : any member of the trial's workspace; external partners must also be assigned to it.
 *  - record  : data entry (stage fields/measurements, harvests, notes) — manager/agronomist/technician.
 *  - reopen  : re-open a completed stage — workspace manager (lifecycle control).
 *  - assign  : manage the trial's assigned users — workspace manager.
 *  - decide  : record a Go/No-Go decision — Admin only (never granted here).
 */
class TrialPolicy
{
    public function view(User $user, Trial $trial): bool
    {
        if ($this->role($user, $trial) === null) {
            return false;
        }

        return ! $user->isExternalPartner() || $this->assigned($user, $trial);
    }

    public function record(User $user, Trial $trial): bool
    {
        return in_array($this->role($user, $trial), ['manager', 'agronomist', 'technician'], true);
    }

    public function reopen(User $user, Trial $trial): bool
    {
        return $this->role($user, $trial) === 'manager';
    }

    public function assign(User $user, Trial $trial): bool
    {
        return $this->role($user, $trial) === 'manager';
    }

    public function decide(User $user, Trial $trial): bool
    {
        return false;
    }

    /** The user's role within the trial's workspace, or null if not a member. */
    private function role(User $user, Trial $trial): ?string
    {
        return optional($user->workspaces()->where('workspaces.id', $trial->workspace_id)->first())->pivot?->role;
    }

    private function assigned(User $user, Trial $trial): bool
    {
        return $trial->assignees()->whereKey($user->id)->exists();
    }
}

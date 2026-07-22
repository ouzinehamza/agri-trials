<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

/**
 * Workspace authorization (SPEC §4). Admin bypasses via Gate::before; membership management itself is
 * Admin-only (route-gated). This policy covers workspace-scoped abilities exercised by members.
 */
class WorkspacePolicy
{
    /** Create a trial inside this workspace — the workspace manager. */
    public function createTrial(User $user, Workspace $workspace): bool
    {
        return optional($user->workspaces()->where('workspaces.id', $workspace->id)->first())->pivot?->role === 'manager';
    }
}

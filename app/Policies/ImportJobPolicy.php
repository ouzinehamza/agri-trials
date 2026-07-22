<?php

namespace App\Policies;

use App\Models\ImportJob;
use App\Models\User;

/**
 * Import jobs are private to the user who uploaded/previewed them (SPEC §3.6). Admin bypasses via
 * Gate::before.
 */
class ImportJobPolicy
{
    public function view(User $user, ImportJob $job): bool
    {
        return $job->user_id === $user->id;
    }

    public function commit(User $user, ImportJob $job): bool
    {
        return $job->user_id === $user->id;
    }
}

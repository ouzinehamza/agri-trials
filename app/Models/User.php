<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_external', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use \App\Models\Concerns\Auditable;
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** RBAC roles (SPEC §4). */
    public const ROLES = ['admin', 'manager', 'agronomist', 'technician', 'partner', 'viewer'];

    public const ROLE_LABELS = [
        'admin' => 'Administrateur',
        'manager' => 'Responsable d\'espace',
        'agronomist' => 'Agronome',
        'technician' => 'Technicien terrain',
        'partner' => 'Partenaire externe',
        'viewer' => 'Lecteur',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_external' => 'boolean',
        ];
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'memberships')->withPivot('role')->withTimestamps();
    }

    /** Trials this user is explicitly assigned to (drives external-partner scoping — SPEC §4). */
    public function assignedTrials(): BelongsToMany
    {
        return $this->belongsToMany(Trial::class)->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * External partners (third-party nurseries/farmers) see only the trials assigned to them,
     * not every trial in their workspace. SPEC §4.
     */
    public function isExternalPartner(): bool
    {
        return $this->role === 'partner' || (bool) $this->is_external;
    }

    /** @return array<int, int> */
    public function workspaceIds(): array
    {
        return $this->workspaces()->pluck('workspaces.id')->all();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    /** Roles assignable within a workspace (admin is a global role, not a membership role). */
    private const MEMBER_ROLES = ['manager', 'agronomist', 'technician', 'partner', 'viewer'];

    public function index(): Response
    {
        $workspaces = Workspace::withCount('trials')->with('members:id,name,email,is_external')->orderBy('name')->get()
            ->map(fn (Workspace $w) => [
                'id' => $w->id,
                'name' => $w->name,
                'description' => $w->description,
                'trials_count' => $w->trials_count,
                'members' => $w->members->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'is_external' => $u->is_external,
                    'role' => $u->pivot->role,
                    'role_label' => User::ROLE_LABELS[$u->pivot->role] ?? $u->pivot->role,
                ]),
            ]);

        return Inertia::render('Workspaces/Index', [
            'workspaces' => $workspaces,
            'users' => User::where('role', '!=', 'admin')->orderBy('name')->get(['id', 'name', 'email']),
            'roles' => collect(self::MEMBER_ROLES)->map(fn ($r) => ['value' => $r, 'label' => User::ROLE_LABELS[$r]]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:200'],
        ]);
        Workspace::create([...$data, 'created_by' => $request->user()->id]);

        return redirect()->route('workspaces.index')->with('success', 'Espace de travail créé.');
    }

    public function addMember(Request $request, Workspace $workspace): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', Rule::in(self::MEMBER_ROLES)],
        ]);
        $workspace->members()->syncWithoutDetaching([$data['user_id'] => ['role' => $data['role']]]);

        return redirect()->route('workspaces.index')->with('success', 'Membre ajouté.');
    }

    public function removeMember(Workspace $workspace, User $user): RedirectResponse
    {
        $workspace->members()->detach($user->id);

        return redirect()->route('workspaces.index')->with('success', 'Membre retiré.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Users/Index', [
            'users' => User::orderBy('name')->get()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'role_label' => User::ROLE_LABELS[$u->role] ?? $u->role,
                'is_external' => $u->is_external,
                'status' => $u->status,
                'is_self' => $u->id === request()->user()->id,
            ]),
            'roles' => collect(User::ROLES)->map(fn ($r) => ['value' => $r, 'label' => User::ROLE_LABELS[$r]]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(User::ROLES)],
            'is_external' => ['boolean'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['sometimes', Rule::in(User::ROLES)],
            'status' => ['sometimes', Rule::in(['active', 'invited', 'disabled'])],
        ]);

        // Guard: an admin cannot demote or disable themselves (keep at least one admin in control).
        if ($user->id === $request->user()->id && (($data['role'] ?? 'admin') !== 'admin' || ($data['status'] ?? 'active') !== 'active')) {
            return back()->with('error', 'Vous ne pouvez pas modifier votre propre rôle ou statut.');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Trial;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@agri.test')->first();
        $u = fn (string $email) => User::where('email', $email)->value('id');

        $melon = Workspace::updateOrCreate(['name' => 'Campagne Melon 2026'], ['description' => 'Essais melon — région du Souss', 'created_by' => $admin?->id]);
        $tomate = Workspace::updateOrCreate(['name' => 'Campagne Tomate 2026'], ['description' => 'Essais tomate', 'created_by' => $admin?->id]);

        $melon->members()->syncWithoutDetaching([
            $u('laila@agri.test') => ['role' => 'manager'],
            $u('youssef@agri.test') => ['role' => 'agronomist'],
            $u('hamid@agri.test') => ['role' => 'technician'],
            $u('brahim@soussplants.ma') => ['role' => 'partner'],
        ]);
        $tomate->members()->syncWithoutDetaching([
            $u('laila@agri.test') => ['role' => 'manager'],
            $u('youssef@agri.test') => ['role' => 'agronomist'],
        ]);

        Trial::whereIn('code', ['P00017', 'P00002', 'P00007', 'P00016', 'P00001'])->update(['workspace_id' => $melon->id]);
        Trial::where('code', 'P00003')->update(['workspace_id' => $tomate->id]);

        // External partner sees only the trials assigned to them (SPEC §4), not the whole workspace.
        $brahim = $u('brahim@soussplants.ma');
        if ($brahim) {
            $assigned = Trial::whereIn('code', ['P00017', 'P00002'])->pluck('id')->all();
            User::find($brahim)?->assignedTrials()->sync($assigned);
        }
    }
}

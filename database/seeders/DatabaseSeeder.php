<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@agri.test'],
            [
                'name' => 'Assma Benhammou',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'is_external' => false,
                'status' => 'active',
            ],
        );

        $team = [
            ['name' => 'Laila Amrani', 'email' => 'laila@agri.test', 'role' => 'manager', 'is_external' => false],
            ['name' => 'Youssef Idrissi', 'email' => 'youssef@agri.test', 'role' => 'agronomist', 'is_external' => false],
            ['name' => 'Hamid Ouazzani', 'email' => 'hamid@agri.test', 'role' => 'technician', 'is_external' => false],
            ['name' => 'M. Brahim (Souss Plants)', 'email' => 'brahim@soussplants.ma', 'role' => 'partner', 'is_external' => true],
        ];
        foreach ($team as $u) {
            User::updateOrCreate(['email' => $u['email']], [
                ...$u,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
            ]);
        }

        $this->call([
            FieldDefinitionSeeder::class,
            SupplierSeeder::class,
            ReferentielSeeder::class,
            TrialSeeder::class,
            WorkflowSeeder::class,
            HarvestSeeder::class,
            WorkspaceSeeder::class,
            StockSeeder::class,
            ExpenseSeeder::class,
        ]);
    }
}

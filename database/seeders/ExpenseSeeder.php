<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        // Third-party invoices (e.g. external nursery for the Semis step).
        $souss = Invoice::updateOrCreate(['number' => 'FAC-2026-001'], [
            'partner' => 'Pépinière Souss Plants', 'trial_code' => 'P00017', 'amount' => 12000,
            'currency' => 'MAD', 'status' => 'sent', 'issued_on' => now()->subDays(20)->toDateString(), 'due_on' => now()->addDays(10)->toDateString(),
        ]);
        Invoice::updateOrCreate(['number' => 'FAC-2026-002'], [
            'partner' => 'Ferme El Guerdane', 'trial_code' => 'P00003', 'amount' => 3000,
            'currency' => 'MAD', 'status' => 'paid', 'issued_on' => now()->subDays(40)->toDateString(), 'due_on' => now()->subDays(10)->toDateString(),
        ]);
        Invoice::updateOrCreate(['number' => 'FAC-2026-003'], [
            'partner' => 'Pépinière Souss Plants', 'trial_code' => 'P00007', 'amount' => 5000,
            'currency' => 'MAD', 'status' => 'overdue', 'issued_on' => now()->subDays(60)->toDateString(), 'due_on' => now()->subDays(15)->toDateString(),
        ]);

        $expenses = [
            ['label' => 'Achat semences CLX 7702', 'category' => 'Semences', 'amount' => 4500, 'incurred_on' => now()->subDays(30)->toDateString(), 'trial_code' => 'P00017'],
            ['label' => 'Pépinière externe (semis)', 'category' => 'Pépinière', 'amount' => 12000, 'incurred_on' => now()->subDays(20)->toDateString(), 'trial_code' => 'P00017', 'partner' => 'Pépinière Souss Plants', 'invoice_id' => $souss->id],
            ['label' => 'Analyses °Brix', 'category' => 'Analyse', 'amount' => 800, 'incurred_on' => now()->subDays(12)->toDateString(), 'trial_code' => 'P00002'],
            ['label' => 'Main d\'œuvre transplantation', 'category' => 'Main d\'œuvre', 'amount' => 2600, 'incurred_on' => now()->subDays(25)->toDateString(), 'trial_code' => 'P00017'],
        ];
        foreach ($expenses as $e) {
            Expense::updateOrCreate(['label' => $e['label'], 'trial_code' => $e['trial_code']], array_merge(['currency' => 'MAD'], $e));
        }
    }
}

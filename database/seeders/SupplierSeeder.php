<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Clause', 'code' => 'FOU-01', 'email' => 'contact@clause.com', 'phone' => '+33 4 90 00 00 00', 'country' => 'France', 'custom_data' => ['linkedin_url' => 'https://www.linkedin.com/company/clause', 'payment_terms' => '30j', 'description' => ['fr' => 'Semencier français, spécialiste du melon.', 'en' => 'French seed company, melon specialist.']]],
            ['name' => 'Rijk Zwaan', 'code' => 'FOU-02', 'email' => 'info@rijkzwaan.nl', 'phone' => '+31 174 000 000', 'country' => 'Pays-Bas', 'custom_data' => ['linkedin_url' => 'https://www.linkedin.com/company/rijk-zwaan', 'payment_terms' => '60j', 'description' => ['fr' => 'Sélectionneur néerlandais de légumes.', 'en' => 'Dutch vegetable breeder.']]],
            ['name' => 'Nunhems (BASF)', 'code' => 'FOU-03', 'email' => 'info@nunhems.com', 'phone' => '+49 621 000 000', 'country' => 'Allemagne', 'custom_data' => ['linkedin_url' => 'https://www.linkedin.com/company/basf', 'payment_terms' => '30j', 'description' => ['fr' => 'Division semences potagères de BASF.', 'en' => 'BASF vegetable seeds division.']]],
            ['name' => 'Syngenta', 'code' => 'FOU-04', 'email' => 'contact@syngenta.com', 'phone' => '+41 61 000 00 00', 'country' => 'Suisse', 'custom_data' => ['linkedin_url' => 'https://www.linkedin.com/company/syngenta', 'payment_terms' => 'immediate', 'description' => ['fr' => 'Groupe agrochimique et semencier suisse.', 'en' => 'Swiss agrochemical and seed group.']]],
            ['name' => 'Enza Zaden', 'code' => 'FOU-05', 'email' => 'info@enzazaden.nl', 'phone' => '+31 228 000 000', 'country' => 'Pays-Bas', 'custom_data' => ['linkedin_url' => null, 'payment_terms' => '30j', 'description' => ['fr' => 'Maison de semences familiale néerlandaise.', 'en' => 'Dutch family-owned seed house.']]],
        ];

        foreach ($suppliers as $s) {
            Supplier::updateOrCreate(['name' => $s['name']], $s);
        }
    }
}

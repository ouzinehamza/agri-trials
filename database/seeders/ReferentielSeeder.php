<?php

namespace Database\Seeders;

use App\Models\Control;
use App\Models\Partner;
use App\Models\Rootstock;
use App\Models\Segment;
use App\Models\Variety;
use Illuminate\Database\Seeder;

class ReferentielSeeder extends Seeder
{
    public function run(): void
    {
        $varieties = [
            ['name' => 'CLX 7702', 'custom_data' => ['ref_code' => 'E00002', 'supplier' => 'Clause', 'culture' => 'Melon', 'origin' => 'Espagne', 'segment' => 'Melon / Charentais / Charentais jaune', 'description' => ['fr' => 'Charentais jaune précoce.', 'en' => 'Early yellow Charentais.']]],
            ['name' => 'RZ 24-118', 'custom_data' => ['ref_code' => 'E00001', 'supplier' => 'Rijk Zwaan', 'culture' => 'Melon', 'origin' => 'Pays-Bas', 'segment' => 'Melon / Galia / Galia précoce']],
            ['name' => 'NUN 8812', 'custom_data' => ['ref_code' => 'E00004', 'supplier' => 'Nunhems (BASF)', 'culture' => 'Melon', 'origin' => 'Pays-Bas', 'segment' => 'Melon / Honeydew / Honeydew vert']],
            ['name' => 'SYN 3391', 'custom_data' => ['ref_code' => 'E00003', 'supplier' => 'Syngenta', 'culture' => 'Tomate', 'origin' => 'États-Unis', 'segment' => 'Tomate / Ronde / Ronde grappe']],
        ];
        foreach ($varieties as $v) {
            Variety::updateOrCreate(['name' => $v['name']], $v);
        }

        $controls = [
            ['name' => 'Magenta', 'custom_data' => ['ref_code' => 'T00045', 'supplier' => 'Clause', 'culture' => 'Melon', 'distributor' => 'Agri-Sud']],
            ['name' => 'Novitus', 'custom_data' => ['ref_code' => 'T00030', 'supplier' => 'Rijk Zwaan', 'culture' => 'Melon', 'distributor' => 'Agri-Sud']],
            ['name' => 'Avast', 'custom_data' => ['ref_code' => 'T00031', 'supplier' => 'Nunhems (BASF)', 'culture' => 'Melon', 'distributor' => 'Agri-Nord']],
            ['name' => 'Anasta F1', 'custom_data' => ['ref_code' => 'T00002', 'supplier' => 'Syngenta', 'culture' => 'Tomate', 'distributor' => 'Agri-Nord']],
        ];
        foreach ($controls as $c) {
            Control::updateOrCreate(['name' => $c['name']], $c);
        }

        foreach ([['name' => 'Shintosa', 'custom_data' => ['origin' => 'Japon']], ['name' => 'Cobalt', 'custom_data' => ['origin' => 'Pays-Bas']]] as $r) {
            Rootstock::updateOrCreate(['name' => $r['name']], $r);
        }

        $partners = [
            ['name' => 'Pépinière Souss Plants', 'custom_data' => ['type' => 'external_nursery', 'contact_name' => 'M. Brahim', 'phone' => '+212 528 00 00 00', 'email' => 'contact@soussplants.ma', 'is_internal' => false]],
            ['name' => 'Pépinière interne Agadir', 'custom_data' => ['type' => 'internal_nursery', 'contact_name' => 'Équipe interne', 'is_internal' => true]],
            ['name' => 'Ferme El Guerdane', 'custom_data' => ['type' => 'farmer', 'contact_name' => 'M. Amrani', 'phone' => '+212 661 00 00 00', 'is_internal' => false]],
        ];
        foreach ($partners as $p) {
            Partner::updateOrCreate(['name' => $p['name']], $p);
        }

        $segments = [
            ['name' => 'Charentais jaune', 'custom_data' => ['culture' => 'Melon', 'parent' => 'Charentais']],
            ['name' => 'Galia précoce', 'custom_data' => ['culture' => 'Melon', 'parent' => 'Galia']],
            ['name' => 'Ronde grappe', 'custom_data' => ['culture' => 'Tomate', 'parent' => 'Ronde']],
        ];
        foreach ($segments as $s) {
            Segment::updateOrCreate(['name' => $s['name']], $s);
        }
    }
}

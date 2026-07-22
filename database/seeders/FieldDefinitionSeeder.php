<?php

namespace Database\Seeders;

use App\Models\FieldDefinition;
use Illuminate\Database\Seeder;

class FieldDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $defs = [
            'supplier' => [
                ['key' => 'name', 'label' => 'Nom', 'required' => true, 'is_system' => true],
                ['key' => 'code', 'label' => 'Code', 'is_system' => true],
                ['key' => 'country', 'label' => 'Pays', 'is_system' => true],
                ['key' => 'email', 'label' => 'E-mail', 'type' => 'email', 'is_system' => true, 'show_in_table' => false],
                ['key' => 'phone', 'label' => 'Téléphone', 'type' => 'tel', 'is_system' => true, 'show_in_table' => false],
                ['key' => 'linkedin_url', 'label' => 'LinkedIn', 'type' => 'url', 'help_text' => 'Profil LinkedIn du fournisseur'],
                ['key' => 'payment_terms', 'label' => 'Conditions de paiement', 'type' => 'select', 'options' => [
                    ['value' => 'immediate', 'label' => 'Comptant'], ['value' => '30j', 'label' => '30 jours'], ['value' => '60j', 'label' => '60 jours'],
                ]],
                ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'translatable' => true, 'show_in_table' => false, 'help_text' => 'Description multilingue'],
            ],
            'variety' => [
                ['key' => 'name', 'label' => 'Nom variété', 'required' => true, 'is_system' => true],
                ['key' => 'ref_code', 'label' => 'Référence'],
                ['key' => 'supplier', 'label' => 'Fournisseur'],
                ['key' => 'culture', 'label' => 'Culture'],
                ['key' => 'origin', 'label' => 'Origine'],
                ['key' => 'segment', 'label' => 'Segment', 'show_in_table' => false],
                ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'translatable' => true, 'show_in_table' => false],
            ],
            'control' => [
                ['key' => 'name', 'label' => 'Nom témoin', 'required' => true, 'is_system' => true],
                ['key' => 'ref_code', 'label' => 'Référence'],
                ['key' => 'supplier', 'label' => 'Fournisseur'],
                ['key' => 'culture', 'label' => 'Culture'],
                ['key' => 'distributor', 'label' => 'Distributeur'],
            ],
            'rootstock' => [
                ['key' => 'name', 'label' => 'Nom', 'required' => true, 'is_system' => true],
                ['key' => 'origin', 'label' => 'Origine'],
            ],
            'partner' => [
                ['key' => 'name', 'label' => 'Nom', 'required' => true, 'is_system' => true],
                ['key' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => [
                    ['value' => 'internal_nursery', 'label' => 'Pépinière interne'], ['value' => 'external_nursery', 'label' => 'Pépinière externe'],
                    ['value' => 'farmer', 'label' => 'Agriculteur'], ['value' => 'other', 'label' => 'Autre'],
                ]],
                ['key' => 'contact_name', 'label' => 'Contact'],
                ['key' => 'phone', 'label' => 'Téléphone', 'type' => 'tel', 'show_in_table' => false],
                ['key' => 'email', 'label' => 'E-mail', 'type' => 'email', 'show_in_table' => false],
                ['key' => 'is_internal', 'label' => 'Interne', 'type' => 'boolean', 'show_in_table' => false],
            ],
            'segment' => [
                ['key' => 'name', 'label' => 'Nom', 'required' => true, 'is_system' => true],
                ['key' => 'culture', 'label' => 'Culture'],
                ['key' => 'parent', 'label' => 'Sous-segment de', 'show_in_table' => false],
            ],
            'stock_item' => [
                ['key' => 'name', 'label' => 'Article', 'required' => true, 'is_system' => true],
                ['key' => 'ref_code', 'label' => 'Référence', 'is_system' => true],
                ['key' => 'unit', 'label' => 'Unité', 'required' => true, 'is_system' => true],
                ['key' => 'storage_notes', 'label' => 'Consignes de stockage', 'type' => 'textarea', 'translatable' => true, 'show_in_table' => false],
            ],
        ];

        foreach ($defs as $modelType => $fields) {
            foreach ($fields as $i => $f) {
                FieldDefinition::updateOrCreate(
                    ['model_type' => $modelType, 'key' => $f['key']],
                    array_merge([
                        'model_type' => $modelType,
                        'type' => 'text',
                        'required' => false,
                        'is_system' => false,
                        'translatable' => false,
                        'show_in_table' => true,
                        'sort_order' => $i + 1,
                        'options' => null,
                        'help_text' => null,
                    ], $f),
                );
            }
        }
    }
}

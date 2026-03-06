<?php

namespace Database\Seeders;

use App\Models\TailoringCategory;
use App\Models\TailoringCategoryMeasurement;
use Illuminate\Database\Seeder;

class TailoringCategoryMeasurementSeeder extends Seeder
{
    public function run(): void
    {
        $sirwal = TailoringCategory::where('name', 'Sirwal')->first();

        if (! $sirwal) {
            return;
        }

        $measurements = [
            // Basic & Body
            ['field_key' => 'tailoring_category_model_id', 'label' => 'SIRWAL MODEL', 'field_type' => 'select', 'options_source' => 'category_models', 'section' => 'basic_body', 'sort_order' => 5],
            ['field_key' => 'length', 'label' => 'LENGTH', 'field_type' => 'input', 'section' => 'basic_body', 'sort_order' => 10],
            ['field_key' => 'shoulder', 'label' => 'SHOULDER', 'field_type' => 'input', 'section' => 'basic_body', 'sort_order' => 20],
            ['field_key' => 'sleeve', 'label' => 'SLEEVE', 'field_type' => 'input', 'section' => 'basic_body', 'sort_order' => 30],
            ['field_key' => 'chest', 'label' => 'CHEST', 'field_type' => 'input', 'section' => 'basic_body', 'sort_order' => 40],
            ['field_key' => 'stomach', 'label' => 'STOMACH', 'field_type' => 'input', 'section' => 'basic_body', 'sort_order' => 50],
            ['field_key' => 'neck', 'label' => 'NECK', 'field_type' => 'input', 'section' => 'basic_body', 'sort_order' => 60],
            ['field_key' => 'bottom', 'label' => 'BOTTOM', 'field_type' => 'input', 'section' => 'basic_body', 'sort_order' => 70],
            ['field_key' => 'sl_chest', 'label' => 'S.L CHEST', 'field_type' => 'input', 'section' => 'basic_body', 'sort_order' => 80],
            ['field_key' => 'sl_so', 'label' => 'S.L SO', 'field_type' => 'input', 'section' => 'basic_body', 'sort_order' => 90],

            // Collar & Cuff
            ['field_key' => 'collar', 'label' => 'COLLAR TYPE', 'field_type' => 'select', 'options_source' => 'collar', 'section' => 'collar_cuff', 'sort_order' => 100],
            ['field_key' => 'collar_size', 'label' => 'COLLAR SIZE', 'field_type' => 'input', 'section' => 'collar_cuff', 'sort_order' => 110],
            ['field_key' => 'collar_cloth', 'label' => 'COLLAR CLOTH', 'field_type' => 'select', 'options_source' => 'collar_cloth', 'section' => 'collar_cuff', 'sort_order' => 120],
            ['field_key' => 'collar_model', 'label' => 'COLLAR MODEL', 'field_type' => 'select', 'options_source' => 'collar_model', 'section' => 'collar_cuff', 'sort_order' => 130],
            ['field_key' => 'cuff', 'label' => 'CUFF TYPE', 'field_type' => 'select', 'options_source' => 'cuff', 'section' => 'collar_cuff', 'sort_order' => 140],
            ['field_key' => 'cuff_size', 'label' => 'CUFF SIZE', 'field_type' => 'input', 'section' => 'collar_cuff', 'sort_order' => 150],
            ['field_key' => 'cuff_cloth', 'label' => 'CUFF CLOTH', 'field_type' => 'select', 'options_source' => 'cuff_cloth', 'section' => 'collar_cuff', 'sort_order' => 160],
            ['field_key' => 'cuff_model', 'label' => 'CUFF MODEL', 'field_type' => 'select', 'options_source' => 'cuff_model', 'section' => 'collar_cuff', 'sort_order' => 170],

            // Specifications
            ['field_key' => 'mar_size', 'label' => 'MAR SIZE', 'field_type' => 'input', 'section' => 'specifications', 'sort_order' => 180],
            ['field_key' => 'mar_model', 'label' => 'MAR MODEL', 'field_type' => 'select', 'options_source' => 'mar_model', 'section' => 'specifications', 'sort_order' => 190],
            ['field_key' => 'neck_d_button', 'label' => 'N.D BUTTON', 'field_type' => 'input', 'section' => 'specifications', 'sort_order' => 200],
            ['field_key' => 'mobile_pocket', 'label' => 'MOB PKT', 'field_type' => 'select', 'options_source' => 'mobile_pocket', 'section' => 'specifications', 'sort_order' => 210],
            ['field_key' => 'side_pt_size', 'label' => 'SIDE PT', 'field_type' => 'input', 'section' => 'specifications', 'sort_order' => 220],
            ['field_key' => 'side_pt_model', 'label' => 'PT MODEL', 'field_type' => 'select', 'options_source' => 'side_pt_model', 'section' => 'specifications', 'sort_order' => 230],
            ['field_key' => 'regal_size', 'label' => 'REGAL', 'field_type' => 'input', 'section' => 'specifications', 'sort_order' => 240],
            ['field_key' => 'knee_loose', 'label' => 'KNEE LOOSE', 'field_type' => 'input', 'section' => 'specifications', 'sort_order' => 250],
            ['field_key' => 'fp_down', 'label' => 'FP DOWN', 'field_type' => 'input', 'section' => 'specifications', 'sort_order' => 260],
            ['field_key' => 'fp_size', 'label' => 'FP SIZE', 'field_type' => 'input', 'section' => 'specifications', 'sort_order' => 270],
            ['field_key' => 'fp_model', 'label' => 'FP MODEL', 'field_type' => 'select', 'options_source' => 'fp_model', 'section' => 'specifications', 'sort_order' => 280],
            ['field_key' => 'pen', 'label' => 'PEN PKT', 'field_type' => 'input', 'section' => 'specifications', 'sort_order' => 290],
            ['field_key' => 'stitching', 'label' => 'STITCHING', 'field_type' => 'select', 'options_source' => 'stitching', 'section' => 'specifications', 'sort_order' => 300],
            ['field_key' => 'button', 'label' => 'BUTTON', 'field_type' => 'select', 'options_source' => 'button', 'section' => 'specifications', 'sort_order' => 310],
            ['field_key' => 'button_no', 'label' => 'BTN NO', 'field_type' => 'input', 'section' => 'specifications', 'sort_order' => 320],
            ['field_key' => 'tailoring_notes', 'label' => 'NOTES', 'field_type' => 'input', 'section' => 'specifications', 'sort_order' => 330],
        ];

        foreach ($measurements as $measurement) {
            TailoringCategoryMeasurement::firstOrCreate(
                [
                    'tenant_id' => $sirwal->tenant_id,
                    'tailoring_category_id' => $sirwal->id,
                    'field_key' => $measurement['field_key'],
                ],
                array_merge($measurement, [
                    'is_active' => true,
                    'is_required' => false,
                ])
            );
        }
    }
}

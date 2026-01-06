<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MeasurementTemplate;

class MeasurementTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Example templates for category_id = 2
        $templates = [
            ['name' => 'Waist'],
            ['name' => 'Hip'],
            ['name' => 'Thigh'],
            ['name' => 'Length'],
            ['name' => 'Knee'],
            ['name' => 'Cuff'],
        ];

        foreach ($templates as $template) {
            MeasurementTemplate::firstOrCreate(
                [
                    'category_id' => 2,
                    'name' => $template['name'],
                ]
            );
        }

        $this->command->info('Measurement templates seeded for category_id 2.');
    }
}

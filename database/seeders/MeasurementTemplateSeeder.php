<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MeasurementTemplate;
use Illuminate\Support\Facades\DB;

class MeasurementTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all measurement categories from live
        $categories = DB::table('measurement_categories')->get();

        if ($categories->isEmpty()) {
            $this->command->info('No measurement categories found. Please create categories first!');
            return;
        }

        // Example data to insert
        $templates = [
            ['name' => 'Length'],
            ['name' => 'Width'],
            ['name' => 'Chest'],
            ['name' => 'Waist'],
            ['name' => 'Hip'],
            ['name' => 'Sleeve'],
            ['name' => 'Inseam'],
        ];

        foreach ($categories as $category) {
            foreach ($templates as $template) {
                MeasurementTemplate::firstOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $template['name']
                    ],
                    [
                        'category_id' => $category->id,
                        'name' => $template['name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('Measurement templates seeded successfully.');
    }
}

<?php

namespace Database\Seeders;

use App\Models\TailoringCategory;
use App\Models\TailoringCategoryModel;
use Illuminate\Database\Seeder;

class TailoringCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Thob',
                'description' => 'Thob category',
                'order' => 1,
                'models' => ['KUWAITY', 'QATARY'],
            ],
            [
                'name' => 'Sirwal',
                'description' => 'Sirwal category',
                'order' => 2,
                'models' => ['Standard', 'Classic'],
            ],
            [
                'name' => 'Vest',
                'description' => 'Vest category',
                'order' => 3,
                'models' => ['Standard', 'Premium'],
            ],
        ];

        foreach ($categories as $categoryData) {
            $models = $categoryData['models'];
            unset($categoryData['models']);

            $category = TailoringCategory::firstOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );

            foreach ($models as $modelName) {
                TailoringCategoryModel::firstOrCreate([
                    'tailoring_category_id' => $category->id,
                    'name' => $modelName,
                ], [
                    'is_active' => true,
                ]);
            }
        }
    }
}

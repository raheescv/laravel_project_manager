<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorkingDay;

class WorkingDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = [
            'MONDAY',
            'TUESDAY',
            'WEDNESDAY',
            'THURSDAY',
            'FRIDAY',
            'SATURDAY',
            'SUNDAY',
        ];

        foreach ($days as $index => $day) {
            WorkingDay::firstOrCreate([
                'tenant_id' => 1,
                'day_name' => $day,
            ],[
                'is_working' => true,
                'order_no' => $index + 1
            ]);
        }
    }
}

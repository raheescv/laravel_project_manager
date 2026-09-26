<?php

namespace Database\Seeders;

use App\Models\WorkingDay;
use Illuminate\Database\Seeder;

class WorkingDaySeeder extends Seeder
{
    /** The tenant these rows belong to; the Tenant Control provisioner points it elsewhere. */
    public int $tenantId = 1;

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
                'tenant_id' => $this->tenantId,
                'day_name' => $day,
            ], [
                'is_working' => true,
                'order_no' => $index + 1,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class BookingPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'sale.booking',
            'sale.booking.create',
            'sale.booking.view',
            'sale.booking.edit',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name], ['guard_name' => 'web']);
        }
    }
}
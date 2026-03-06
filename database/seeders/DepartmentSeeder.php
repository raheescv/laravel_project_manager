<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('departments')->truncate();
        $data = [];
        $data[] = ['tenant_id' => 1, 'name' => 'Food'];
        $data[] = ['tenant_id' => 1, 'name' => 'Kitchen'];
        $data[] = ['tenant_id' => 1, 'name' => 'Beverage'];
        $data[] = ['tenant_id' => 1, 'name' => 'Bakery'];
        DB::table('departments')->insert($data);
    }
}

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
        $data[] = ['name' => 'Food'];
        $data[] = ['name' => 'Kitchen'];
        $data[] = ['name' => 'Beverage'];
        $data[] = ['name' => 'Bakery'];
        DB::table('departments')->insert($data);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('branches')->truncate();
        $data = [];
        $data[] = ['name' => 'Main', 'code' => 'M', 'location' => ''];
        DB::table('branches')->insert($data);
    }
}

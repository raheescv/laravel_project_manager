<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('accounts')->truncate();
        $data = [];
        $data[] = ['name' => 'Cash', 'account_type' => 'asset', 'model' => null];
        $data[] = ['name' => 'Card', 'account_type' => 'asset', 'model' => null];
        $data[] = ['name' => 'General Customer', 'account_type' => 'asset', 'model' => 'customer'];
        DB::table('accounts')->insert($data);
    }
}

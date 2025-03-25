<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class View extends Seeder
{
    public function run()
    {
        $data = [];
        $data[] = '2025_01_07_130622_create_ledgers_table';
        DB::table('migrations')->whereIn('migration', $data)->delete();
        foreach ($data as $value) {
            Artisan::call('migrate --path=database/migrations/'.$value.'.php');
        }
    }
}

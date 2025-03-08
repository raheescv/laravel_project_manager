<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS ledgers');
        $path = database_path('views/ledgers_view.sql');
        DB::unprepared(file_get_contents($path));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ledgers');
    }
};

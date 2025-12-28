<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE sales 
            MODIFY status 
            ENUM('draft','started','stitched','completed','cancelled') 
            NOT NULL DEFAULT 'completed'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE sales 
            MODIFY status 
            ENUM('draft','completed','cancelled') 
            NOT NULL DEFAULT 'draft'
        ");
    }
};

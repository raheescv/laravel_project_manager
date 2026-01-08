<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        // Disable FK checks (VERY IMPORTANT)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1️⃣ Clear child table
       

        // 2️⃣ Clear templates
        DB::table('measurement_templates')->truncate();

        // 3️⃣ Clear categories
        DB::table('measurement_categories')->truncate();

        // Enable FK checks back
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // No rollback needed
    }
};

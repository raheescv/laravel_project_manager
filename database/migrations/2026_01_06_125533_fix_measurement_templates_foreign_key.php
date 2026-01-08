<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         
            // Drop foreign key if exists
             DB::statement('ALTER TABLE measurement_templates DROP FOREIGN KEY IF EXISTS measurement_templates_category_id_foreign');

        // Change category_id to plain integer (nullable)
        DB::statement('ALTER TABLE measurement_templates MODIFY category_id INT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         DB::statement('ALTER TABLE measurement_templates MODIFY category_id INT NOT NULL');
        DB::statement('ALTER TABLE measurement_templates
            ADD CONSTRAINT measurement_templates_category_id_foreign
            FOREIGN KEY (category_id)
            REFERENCES measurement_categories(id)
            ON DELETE CASCADE');
    }
};

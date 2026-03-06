<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tailoring_order_items')) {
            return;
        }

        if (! Schema::hasColumn('tailoring_order_items', 'total_stitch_rate')) {
            DB::statement('
                ALTER TABLE tailoring_order_items
                ADD COLUMN total_stitch_rate DECIMAL(16,2)
                GENERATED ALWAYS AS (stitch_rate * quantity) STORED
                AFTER stitch_rate
            ');
        }

        DB::statement('
            ALTER TABLE tailoring_order_items
            MODIFY COLUMN gross_amount DECIMAL(16,2)
            GENERATED ALWAYS AS ((unit_price * quantity * quantity_per_item) + total_stitch_rate) STORED
        ');

        DB::statement('
            ALTER TABLE tailoring_order_items
            MODIFY COLUMN total DECIMAL(16,2)
            GENERATED ALWAYS AS (net_amount + tax_amount) STORED
        ');
    }

    public function down(): void
    {
        if (! Schema::hasTable('tailoring_order_items')) {
            return;
        }

        DB::statement('
            ALTER TABLE tailoring_order_items
            MODIFY COLUMN gross_amount DECIMAL(16,2)
            GENERATED ALWAYS AS (unit_price * quantity * quantity_per_item) STORED
        ');

        DB::statement('
            ALTER TABLE tailoring_order_items
            MODIFY COLUMN total DECIMAL(16,2)
            GENERATED ALWAYS AS (net_amount + tax_amount + (stitch_rate * quantity)) STORED
        ');

        if (Schema::hasColumn('tailoring_order_items', 'total_stitch_rate')) {
            DB::statement('ALTER TABLE tailoring_order_items DROP COLUMN total_stitch_rate');
        }
    }
};

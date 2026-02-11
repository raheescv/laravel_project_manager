<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('issue_items', function (Blueprint $table) {
            $table->date('item_date')->nullable()->after('quantity_out');
        });

        if (Schema::hasColumn('issue_items', 'issued_date')) {
            DB::table('issue_items')->update([
                'item_date' => DB::raw('COALESCE(issued_date, returned_date)'),
            ]);
            Schema::table('issue_items', function (Blueprint $table) {
                $table->dropColumn(['issued_date', 'returned_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('issue_items', function (Blueprint $table) {
            if (Schema::hasColumn('issue_items', 'item_date')) {
                $table->dropColumn('item_date');
            }
        });
        if (! Schema::hasColumn('issue_items', 'issued_date')) {
            Schema::table('issue_items', function (Blueprint $table) {
                $table->date('issued_date')->nullable()->after('quantity_out');
                $table->date('returned_date')->nullable()->after('issued_date');
            });
        }
    }
};

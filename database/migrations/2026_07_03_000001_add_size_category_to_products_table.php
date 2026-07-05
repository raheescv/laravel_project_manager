<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('size_category')->nullable()->after('size')->index();
        });

        // Backfill existing products from their raw size string.
        DB::table('products')
            ->whereNotNull('size')
            ->where('size', '!=', '')
            ->select('id', 'size')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('products')
                        ->where('id', $row->id)
                        ->update(['size_category' => Product::classifySizeCategory($row->size)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['size_category']);
            $table->dropColumn('size_category');
        });
    }
};

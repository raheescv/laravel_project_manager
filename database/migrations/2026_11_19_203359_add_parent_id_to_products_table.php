<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Size / colour variants: every variant is a full product row whose parent_id
 * points at the family head (the product the variants were made from). The
 * head keeps parent_id NULL and is itself one of the family's variants.
 *
 * Variants share their head's name, so the old unique (tenant_id, name, type)
 * index would reject them. It is replaced by a unique index on a generated
 * `head_name` (the name for heads / standalone products, NULL for variants):
 * head and standalone names stay unique exactly as before, siblings may repeat.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('tenant_id');
                $table->foreign('parent_id')->references('id')->on('products')->restrictOnDelete();
                $table->index(['parent_id', 'size', 'color'], 'products_parent_size_color_index');
            }
            if (! Schema::hasColumn('products', 'head_name')) {
                $table->string('head_name')->nullable()->virtualAs('IF(`parent_id` IS NULL, `name`, NULL)')->after('name');
            }
        });

        if ($this->hasIndex('product_tenant_name_type_index')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropUnique('product_tenant_name_type_index'));
        }
        if (! $this->hasIndex('product_tenant_head_name_type_index')) {
            Schema::table('products', fn (Blueprint $table) => $table->unique(['tenant_id', 'head_name', 'type'], 'product_tenant_head_name_type_index'));
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('product_tenant_head_name_type_index')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropUnique('product_tenant_head_name_type_index'));
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'head_name')) {
                $table->dropColumn('head_name');
            }
            if (Schema::hasColumn('products', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropIndex('products_parent_size_color_index');
                $table->dropColumn('parent_id');
            }
        });

        if (! $this->hasIndex('product_tenant_name_type_index')) {
            Schema::table('products', fn (Blueprint $table) => $table->unique(['tenant_id', 'name', 'type'], 'product_tenant_name_type_index'));
        }
    }

    private function hasIndex(string $name): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'products')
            ->where('index_name', $name)
            ->exists();
    }
};

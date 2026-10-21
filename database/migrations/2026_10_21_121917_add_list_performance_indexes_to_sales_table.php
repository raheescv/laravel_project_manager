<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Sales list (web + API) filters on tenant, branch, status and created_by,
 * then a date range. The older composites put the range column `date` second,
 * so branch/status could not narrow the index seek. These lead with tenant_id,
 * then the equality filters, then the range/sort column, so every query seeks
 * only its own tenant's slice. InnoDB appends `id` to each secondary index, so
 * `ORDER BY date DESC, id DESC` is served by the index too.
 *
 * sale_payments had no index on sale_id at all (and has no tenant_id column);
 * sale view/edit/delete look payments up by it.
 */
return new class() extends Migration
{
    /**
     * @var array<string, array{0: string, 1: list<string>}>
     */
    private array $indexes = [
        'sales_tenant_branch_date_index' => ['sales', ['tenant_id', 'branch_id', 'date']],
        'sales_tenant_branch_status_date_index' => ['sales', ['tenant_id', 'branch_id', 'status', 'date']],
        'sales_tenant_branch_created_at_index' => ['sales', ['tenant_id', 'branch_id', 'created_at']],
        'sales_tenant_created_by_date_index' => ['sales', ['tenant_id', 'created_by', 'date']],
        'sale_payments_sale_id_index' => ['sale_payments', ['sale_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $name => [$table, $columns]) {
            if (! Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $name => [$table]) {
            if (Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
            }
        }
    }
};

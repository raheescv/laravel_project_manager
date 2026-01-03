<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                // Check if the old unique index exists before trying to drop it
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes('accounts');
                $oldIndexName = 'accounts_account_type_name_mobile_unique';

                if (isset($indexesFound[$oldIndexName])) {
                    $table->dropUnique(['account_type', 'name', 'mobile']);
                }

                // Check if the new unique index already exists before creating it
                $newIndexName = 'account_tenant_account_type_mobile_name_index';
                if (! isset($indexesFound[$newIndexName])) {
                    $table->unique(['tenant_id', 'account_type', 'name', 'mobile'], 'account_tenant_account_type_mobile_name_index');
                    $table->index('tenant_id');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};

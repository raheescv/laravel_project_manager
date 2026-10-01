<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings property_leads level with the accounts lead form: company contact
 * person, sub source / sub status, and the property requirement block
 * (property type, rental type, budget range).
 *
 * Also renames the legacy "Leasing" type to "Rentout". In accounts a Leasing
 * lead transferred to a rental booking, which is exactly what Rentout means
 * here; left as is, every migrated leasing lead fails the form's type rule.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('property_leads', function (Blueprint $table): void {
            if (! Schema::hasColumn('property_leads', 'company_contact_person')) {
                $table->string('company_contact_person')->nullable()->after('company_name');
            }
            if (! Schema::hasColumn('property_leads', 'sub_source')) {
                $table->string('sub_source')->nullable()->after('source');
            }
            if (! Schema::hasColumn('property_leads', 'property_type_id')) {
                $table->unsignedBigInteger('property_type_id')->nullable()->after('property_group_id');
            }
            if (! Schema::hasColumn('property_leads', 'rental_type')) {
                $table->string('rental_type', 20)->nullable()->after('property_type_id');
            }
            if (! Schema::hasColumn('property_leads', 'budget_min')) {
                $table->decimal('budget_min', 15, 2)->nullable()->after('rental_type');
            }
            if (! Schema::hasColumn('property_leads', 'budget_max')) {
                $table->decimal('budget_max', 15, 2)->nullable()->after('budget_min');
            }
            if (! Schema::hasColumn('property_leads', 'sub_status')) {
                $table->string('sub_status')->nullable()->after('status');
            }
            if (! Schema::hasColumn('property_leads', 'reassigned_at')) {
                $table->timestamp('reassigned_at')->nullable()->after('assign_date');
                $table->index('reassigned_at');
            }
        });

        DB::table('property_leads')->where('type', 'Leasing')->update(['type' => 'Rentout']);
    }

    public function down(): void
    {
        // The Leasing rename is not reversed: Rentout leads created since then are indistinguishable.
        Schema::table('property_leads', function (Blueprint $table): void {
            $table->dropColumn([
                'company_contact_person',
                'sub_source',
                'property_type_id',
                'rental_type',
                'budget_min',
                'budget_max',
                'sub_status',
            ]);
        });
    }
};

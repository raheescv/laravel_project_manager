<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('journal_entries', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->references('id')->on('branches')->nullable()->after('journal_id')->index();
            }
            if (! Schema::hasColumn('journal_entries', 'date')) {
                $table->date('date')->nullable()->after('counter_account_id')->index();
            }
            if (! Schema::hasColumn('journal_entries', 'source')) {
                $table->string('source')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('journal_entries', 'person_name')) {
                $table->string('person_name')->nullable()->after('source');
            }
            if (! Schema::hasColumn('journal_entries', 'description')) {
                $table->string('description')->nullable()->after('person_name');
            }
            if (! Schema::hasColumn('journal_entries', 'journal_remarks')) {
                $table->string('journal_remarks')->nullable()->after('description');
            }
            if (! Schema::hasColumn('journal_entries', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('journal_remarks');
            }
            if (! Schema::hasColumn('journal_entries', 'journal_model')) {
                $table->string('journal_model')->nullable()->after('reference_number');
            }
            if (! Schema::hasColumn('journal_entries', 'journal_model_id')) {
                $table->string('journal_model_id')->nullable()->after('journal_model');
            }
        });
        if (Schema::hasTable('journal_entries') && ! Schema::hasIndex('journal_entries', 'date_branch_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->index(['date', 'branch_id'], 'date_branch_id_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entries', 'branch_id')) {
                // Get all foreign key constraints for the table
                $foreignKeys = DB::select(
                    "SELECT CONSTRAINT_NAME
                     FROM information_schema.TABLE_CONSTRAINTS
                     WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'journal_entries'
                     AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
                );

                // Drop any foreign keys that reference branch_id
                foreach ($foreignKeys as $foreignKey) {
                    $keyName = $foreignKey->CONSTRAINT_NAME;
                    // Check if this foreign key is related to branch_id
                    $keyColumns = DB::select(
                        "SELECT COLUMN_NAME
                         FROM information_schema.KEY_COLUMN_USAGE
                         WHERE TABLE_SCHEMA = DATABASE()
                         AND TABLE_NAME = 'journal_entries'
                         AND CONSTRAINT_NAME = ?",
                        [$keyName]
                    );
                    foreach ($keyColumns as $keyColumn) {
                        if ($keyColumn->COLUMN_NAME === 'branch_id') {
                            DB::statement("ALTER TABLE `journal_entries` DROP FOREIGN KEY `{$keyName}`");
                            break;
                        }
                    }
                }

                $table->dropColumn('branch_id');
            }
            if (Schema::hasColumn('journal_entries', 'date')) {
                $table->dropColumn('date');
            }
            if (Schema::hasColumn('journal_entries', 'source')) {
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('journal_entries', 'person_name')) {
                $table->dropColumn('person_name');
            }
            if (Schema::hasColumn('journal_entries', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('journal_entries', 'journal_remarks')) {
                $table->dropColumn('journal_remarks');
            }
            if (Schema::hasColumn('journal_entries', 'reference_number')) {
                $table->dropColumn('reference_number');
            }
            if (Schema::hasColumn('journal_entries', 'journal_model')) {
                $table->dropColumn('journal_model');
            }
            if (Schema::hasColumn('journal_entries', 'journal_model_id')) {
                $table->dropColumn('journal_model_id');
            }
        });

        if (Schema::hasTable('journal_entries') && Schema::hasIndex('journal_entries', 'date_branch_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex('date_branch_id_index');
            });
        }
    }
};

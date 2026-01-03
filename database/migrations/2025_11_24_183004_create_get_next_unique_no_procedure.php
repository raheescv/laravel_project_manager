<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::unprepared(' DROP PROCEDURE IF EXISTS getNextUniqueNumber; ');

        DB::unprepared('
            CREATE PROCEDURE getNextUniqueNumber(
                IN in_tenant_id BIGINT UNSIGNED,
                IN in_year VARCHAR(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                IN in_branch VARCHAR(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                IN in_segment VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                OUT out_unique_no INT
            )
            BEGIN
                INSERT INTO unique_no_counters (tenant_id, year, branch_code, segment, number)
                VALUES (in_tenant_id, in_year, in_branch, in_segment, 1)
                ON DUPLICATE KEY UPDATE number = number + 1;

                SELECT number INTO out_unique_no
                FROM unique_no_counters
                WHERE tenant_id = in_tenant_id
                AND year = in_year
                AND branch_code = in_branch
                AND segment = in_segment ;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS getNextUniqueNumber');
    }
};

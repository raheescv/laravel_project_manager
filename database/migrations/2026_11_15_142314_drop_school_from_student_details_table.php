<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A student's school is their branch: student_details keeps `branch_id` only, and
 * the school shown anywhere is the branch's location (StudentDetail::school).
 *
 * Before the free-text column goes, each student without a branch is matched to
 * one of their tenant's branches from what it said: the same location or name
 * (ignoring case, spaces and dashes), else a branch whose location the text
 * contains ("AIA - Doha" → the branch at "Doha"), longest location first so
 * "Doha - B2" wins over "Doha". Text that matches no branch is dropped.
 */
return new class() extends Migration
{
    public function up(): void
    {
        $key = fn (?string $value): string => preg_replace('/[\s\-_]+/u', '', mb_strtolower(trim((string) $value)));

        DB::table('student_details')->whereNull('branch_id')->whereNotNull('school')->where('school', '<>', '')
            ->orderBy('id')->get(['id', 'tenant_id', 'school'])
            ->groupBy('tenant_id')
            ->each(function ($rows, $tenantId) use ($key): void {
                $branches = DB::table('branches')->where('tenant_id', $tenantId)->get(['id', 'name', 'location'])
                    ->sortByDesc(fn ($branch) => mb_strlen($key($branch->location)));

                foreach ($rows as $row) {
                    $text = $key($row->school);
                    $branch = $branches->first(fn ($b) => $text === $key($b->location) || $text === $key($b->name))
                        ?? $branches->first(fn ($b) => $key($b->location) !== '' && str_contains($text, $key($b->location)));
                    if ($branch) {
                        DB::table('student_details')->where('id', $row->id)->update(['branch_id' => $branch->id]);
                    }
                }
            });

        Schema::table('student_details', function (Blueprint $table): void {
            $table->dropColumn('school');
        });
    }

    public function down(): void
    {
        Schema::table('student_details', function (Blueprint $table): void {
            $table->string('school', 100)->nullable()->after('admission_no');
        });

        DB::table('student_details')->whereNotNull('branch_id')->orderBy('id')->get(['id', 'branch_id'])->each(function ($row): void {
            $branch = DB::table('branches')->where('id', $row->branch_id)->first(['name', 'location']);
            DB::table('student_details')->where('id', $row->id)->update(['school' => trim((string) $branch?->location) ?: $branch?->name]);
        });
    }
};

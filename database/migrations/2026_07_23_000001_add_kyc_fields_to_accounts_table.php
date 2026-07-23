<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    protected $columns = [
        'emergency_contact_no' => ['string', 20],
        'po_box' => ['string', 20],
        'id_expiry_date' => ['date'],
        'passport_no' => ['string', 30],
        'marital_status' => ['string', 30],
        'occupation' => ['string', 100],
        'job' => ['string', 100],
        'sponsor_name' => ['string', 100],
        'position_nature_of_business' => ['string', 150],
        'monthly_income' => ['decimal', 15, 2],
        'residential_address' => ['text'],
        'employer_address' => ['text'],
        'contact_person' => ['string', 100],
        'contact_person_mobile' => ['string', 20],
        'cr_number' => ['string', 50],
        'cr_issue_date' => ['date'],
        'cr_expiry_date' => ['date'],
        'cp_number' => ['string', 50],
        'cp_issue_date' => ['date'],
        'cp_expiry_date' => ['date'],
        'eid_number' => ['string', 50],
        'eid_issue_date' => ['date'],
        'eid_expiry_date' => ['date'],
        'tax_card_no' => ['string', 50],
        'tax_card_issue_date' => ['date'],
        'kyc_confirmed_at' => ['timestamp'],
        'kyc_confirmed_by' => ['unsignedBigInteger'],
    ];

    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            foreach ($this->columns as $name => $definition) {
                if (Schema::hasColumn('accounts', $name)) {
                    continue;
                }
                $type = array_shift($definition);
                $table->{$type}($name, ...$definition)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            foreach (array_keys($this->columns) as $name) {
                if (Schema::hasColumn('accounts', $name)) {
                    $table->dropColumn($name);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rent_outs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('property_id')->constrained('properties');
            $table->unsignedBigInteger('property_building_id');
            $table->unsignedBigInteger('property_type_id');
            $table->unsignedBigInteger('property_group_id');
            $table->unsignedBigInteger('account_id')->references('id')->on('accounts');
            $table->unsignedBigInteger('salesman_id')->nullable();

            // Agreement details
            $table->string('agreement_type')->default('rental'); // rental, lease
            $table->string('booking_type')->nullable();
            $table->string('status')->default('occupied'); // occupied, vacated, expired, booked, cancelled
            $table->string('booking_status')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('vacate_date')->nullable();

            // Financial
            $table->decimal('rent', 16, 2)->default(0);
            $table->integer('no_of_terms')->default(1);
            $table->string('payment_frequency')->nullable();
            $table->decimal('discount', 16, 2)->default(0);
            $table->integer('free_month')->default(0);
            $table->decimal('total', 16, 2)->default(0);

            // Collection
            $table->integer('collection_starting_day')->default(1);
            $table->string('collection_payment_mode')->default('cash'); // cash, cheque, pos, bank_transfer
            $table->string('collection_bank_name')->nullable();
            $table->string('collection_cheque_no')->nullable();

            // Management fee
            $table->decimal('management_fee', 16, 2)->default(0);
            $table->string('management_fee_payment_mode')->nullable();
            $table->text('management_fee_remarks')->nullable();

            // Down payment
            $table->decimal('down_payment', 16, 2)->default(0);
            $table->string('down_payment_mode')->nullable();
            $table->text('down_payment_remarks')->nullable();

            // Includes
            $table->string('include_electricity_water')->nullable();
            $table->string('include_ac')->nullable();
            $table->string('include_wifi')->nullable();

            // Terms and policies (bilingual)
            $table->text('remark')->nullable();
            $table->text('cancellation_policy_ar')->nullable();
            $table->text('cancellation_policy_en')->nullable();
            $table->text('payment_terms_ar')->nullable();
            $table->text('payment_terms_en')->nullable();
            $table->text('payment_terms_extended_ar')->nullable();
            $table->text('payment_terms_extended_en')->nullable();
            $table->text('mandatory_documents')->nullable();
            $table->json('reservation_fees_disclaimer_en')->nullable();
            $table->json('reservation_fees_disclaimer_ar')->nullable();

            // Payment term aggregates
            $table->decimal('payment_term_rent', 16, 2)->default(0);
            $table->decimal('payment_term_discount', 16, 2)->default(0);
            $table->decimal('payment_term_total', 16, 2)->default(0);
            $table->decimal('total_paid', 16, 2)->default(0);
            $table->decimal('total_current_rent', 16, 2)->default(0);

            // Workflow / approval
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('financial_approved_by')->nullable();
            $table->timestamp('financial_approved_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'status']);
            $table->index(['tenant_id', 'agreement_type', 'status']);
            $table->index(['tenant_id', 'property_id']);
            $table->index(['tenant_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_outs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How one vendor's invoice is laid out, remembered after the first scan.
 *
 * Reading a PDF or a photo of an invoice finds the columns by where the text
 * sits, but nothing in the file says which column is the rate and which is the
 * MRP — and plenty of vendors, this one included, draw their heading strip as a
 * picture, so there is not even a label to go on. The user says once what each
 * column is; `bands` records where those columns were and `mapping` records what
 * they meant, so the same vendor's next invoice needs no mapping at all.
 *
 * `bands` is [{x0, x1, numeric}] in the coordinates the scan reported — points
 * for a PDF, pixels for an image — which is why `source` is stored beside them:
 * a template measured off a PDF is meaningless for a photo.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_scan_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            // The vendor this layout belongs to.
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            $table->string('source', 10)->default('pdf');
            $table->json('bands');
            // field => column index, e.g. {"product_name": "1", "quantity": "3"}
            $table->json('mapping');
            $table->string('match_by', 10)->default('auto');
            $table->decimal('default_tax', 8, 3)->default(0);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'account_id', 'source'], 'purchase_scan_templates_vendor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_scan_templates');
    }
};

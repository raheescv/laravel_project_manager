<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * An application-wide log of outbound email.
     *
     * It stores the RENDERED subject and body, not just a pointer to the
     * template. Templates are tenant-editable, so re-rendering one later would
     * show what we would send today rather than what the customer actually
     * received — useless for a dispute and misleading in a support call.
     *
     * The owner is polymorphic so any module can log mail against its own
     * record without another table.
     */
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('module', 40);
            $table->string('type', 60);
            $table->nullableMorphs('related');
            $table->unsignedBigInteger('email_template_id')->nullable();
            $table->string('to_email', 120);
            $table->string('reply_to', 120)->nullable();
            $table->string('subject', 255)->nullable();
            $table->longText('body')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->text('error')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'status'], 'el_tenant_status_idx');
            $table->index(['tenant_id', 'module', 'type'], 'el_tenant_module_type_idx');
            $table->index(['tenant_id', 'created_at'], 'el_tenant_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};

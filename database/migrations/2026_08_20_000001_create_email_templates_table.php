<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * A general, application-wide email template table.
     *
     * Templates are not one module's concern, so the module that owns each
     * template is a column, and the catalogue of modules, events and the merge
     * variables each may use lives in config/email_templates.php.
     */
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('module', 40);
            $table->string('type', 60);
            $table->string('name', 120);
            $table->string('subject', 255);
            $table->longText('body')->nullable();
            $table->string('language', 5)->default('en');
            $table->string('reply_to', 120)->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'module', 'type'], 'et_tenant_module_type_idx');
        });

        // Exactly one ACTIVE template per module+type per tenant. MySQL has no
        // partial indexes, so a STORED generated column carries it: inactive and
        // soft-deleted rows generate NULL, and NULLs never collide in a unique
        // index, so they are exempt without needing application-level policing.
        DB::statement("
            ALTER TABLE email_templates
            ADD COLUMN active_key VARCHAR(104)
            GENERATED ALWAYS AS (
                CASE WHEN is_active = 1 AND deleted_at IS NULL THEN CONCAT(module, '.', type) ELSE NULL END
            ) STORED
        ");

        DB::statement('
            ALTER TABLE email_templates
            ADD UNIQUE INDEX et_active_key_unique (tenant_id, active_key)
        ');

    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

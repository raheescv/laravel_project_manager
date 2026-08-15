<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * A tenant-authored email template.
 *
 * The catalogue of modules, event types and the merge variables each type may
 * use lives in config/email_templates.php — this model never hardcodes them,
 * and no customer-facing wording is defined in PHP at all.
 */
class EmailTemplate extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'module',
        'type',
        'name',
        'subject',
        'body',
        'language',
        'reply_to',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // NOTE: active_key is a STORED generated column backing the unique index
    // that keeps exactly one template active per module+type. It is absent from
    // $fillable on purpose — the database owns it.

    public static function rules($id = 0, $merge = []): array
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'module' => ['required', Rule::in(array_keys(self::modules()))],
            'type' => ['required', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:120', Rule::unique(self::class, 'name')->where('tenant_id', $tenantId)->whereNull('deleted_at')->ignore($id)],
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'language' => 'required|string|max:5',
            'reply_to' => 'nullable|email|max:120',
        ], $merge);
    }

    /** @return array<string, array> */
    public static function modules(): array
    {
        return config('email_templates', []);
    }

    /** @return array<string, array> */
    public static function typesFor(string $module): array
    {
        return config("email_templates.{$module}.types", []);
    }

    /** Every merge variable this module+type is allowed to reference. */
    public static function variablesFor(string $module, string $type): array
    {
        return config("email_templates.{$module}.{$type}.variables")
            ?? config("email_templates.{$module}.types.{$type}.variables", []);
    }

    /** Starter wording for an event, or null when the event ships none. */
    public static function defaultFor(string $module, string $type): ?array
    {
        $default = config("email_templates.{$module}.types.{$type}.default");

        return filled($default['subject'] ?? null) ? $default : null;
    }

    public static function moduleLabel(string $module): string
    {
        return config("email_templates.{$module}.label", $module);
    }

    public static function typeLabelFor(string $module, string $type): string
    {
        return config("email_templates.{$module}.types.{$type}.label", $type);
    }

    public function moduleName(): string
    {
        return self::moduleLabel($this->module);
    }

    public function typeLabel(): string
    {
        return self::typeLabelFor($this->module, $this->type);
    }

    public function scopeActiveType(Builder $query, string $module, string $type): Builder
    {
        return $query->where('module', $module)->where('type', $type)->where('is_active', true);
    }
}

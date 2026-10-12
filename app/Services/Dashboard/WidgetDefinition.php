<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Support\ModuleAccess;
use Closure;

/**
 * Everything the dashboard needs to know about one widget before its data
 * loads: what it is called, how wide it starts, how narrow it may go, which
 * header switcher it has, and which permissions (all of them) unlock it.
 */
final class WidgetDefinition
{
    /**
     * @param  list<string>  $permissions  every one is required
     * @param  array<string, string>  $options  header switcher, value => label
     */
    public function __construct(
        public readonly string $key,
        public readonly string $group,
        public readonly string $title,
        public readonly string $subtitle,
        public readonly string $icon,
        public readonly string $kind,
        public readonly int $span,
        public readonly int $minSpan,
        public readonly array $permissions,
        public readonly array $options = [],
        public readonly ?string $defaultOption = null,
    ) {}

    /**
     * The user holds every permission, and each one belongs to the tenant's
     * system (see {@see ModuleAccess::permissionFilter()}): a permission left
     * over from another system can't be managed on the Roles screen, so it
     * doesn't count.
     *
     * @param  (Closure(string): bool)|null  $inSystem
     */
    public function isVisibleTo(User $user, ?Closure $inSystem = null): bool
    {
        $inSystem ??= ModuleAccess::permissionFilter();
        foreach ($this->permissions as $permission) {
            if (! $inSystem($permission) || ! $user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    public function resolveOption(?string $option): ?string
    {
        if ($this->options === []) {
            return null;
        }

        return array_key_exists((string) $option, $this->options) ? $option : $this->defaultOption;
    }

    /**
     * @return array{key: string, group: string, title: string, subtitle: string, icon: string, kind: string, span: int, minSpan: int, options: list<array{value: string, label: string}>, defaultOption: ?string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'group' => $this->group,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'icon' => $this->icon,
            'kind' => $this->kind,
            'span' => $this->span,
            'minSpan' => $this->minSpan,
            'options' => collect($this->options)->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])->values()->all(),
            'defaultOption' => $this->defaultOption,
        ];
    }
}

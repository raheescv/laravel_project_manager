<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\UserPreference;
use App\Services\Dashboard\Sources\AppointmentSource;
use App\Services\Dashboard\Sources\AssetSource;
use App\Services\Dashboard\Sources\ComplaintSource;
use App\Services\Dashboard\Sources\IncomeExpenseSource;
use App\Services\Dashboard\Sources\MaintenanceSource;
use App\Services\Dashboard\Sources\PackageCalendarSource;
use App\Services\Dashboard\Sources\ProjectContractSource;
use App\Services\Dashboard\Sources\PropertyFinancialSource;
use App\Services\Dashboard\Sources\PropertyLeadSource;
use App\Services\Dashboard\Sources\PropertyOverviewSource;
use App\Services\Dashboard\Sources\RentOutExpirySource;
use App\Services\Dashboard\Sources\SalesPerformanceSource;
use App\Services\Dashboard\Sources\SalesSummarySource;
use App\Services\Dashboard\Sources\SupplyRequestSource;
use App\Support\ModuleAccess;

/**
 * The home dashboard's widget catalogue and each user's saved arrangement of
 * it. A layout is an ordered list of widgets (`{key, span, collapsed, row?, tall?}`,
 * `row` pinning the widget to the start of a grid row, `tall` spanning two rows) and
 * section dividers (`{type: divider, id, title}`), stored per user under the
 * `dashboard.layout` preference.
 */
class WidgetRegistry
{
    public const PREFERENCE_KEY = 'dashboard.layout';

    public const SPANS = [4, 6, 8, 12];

    /** @var list<class-string<DashboardSource>> */
    protected const SOURCES = [
        SalesSummarySource::class,
        AssetSource::class,
        SalesPerformanceSource::class,
        AppointmentSource::class,
        IncomeExpenseSource::class,
        PackageCalendarSource::class,
        PropertyLeadSource::class,
        PropertyOverviewSource::class,
        MaintenanceSource::class,
        ComplaintSource::class,
        PropertyFinancialSource::class,
        SupplyRequestSource::class,
        RentOutExpirySource::class,
        ProjectContractSource::class,
    ];

    /**
     * The shipped arrangement: a divider title, then the widgets under it.
     *
     * @var array<string, list<string>>
     */
    protected const DEFAULT_SECTIONS = [
        'Business overview' => ['business-kpis', 'inventory', 'asset-kpis', 'asset-groups'],
        'Sales performance' => ['sale-overview', 'top-sale-items'],
        'Appointments' => ['appointment-chart', 'upcoming-appointments'],
        'Finance' => ['income-expense-bar', 'income-expense-pie'],
        'Packages' => ['package-calendar'],
        'Property leads' => ['lead-status', 'lead-activity', 'lead-units', 'lead-sources', 'lead-employees'],
        'Property overview' => ['property-occupancy', 'group-occupancy', 'availability', 'group-income', 'income-summary'],
        'Maintenance' => ['maintenance-kpis', 'maintenance-breakdown', 'maintenance-technicians', 'maintenance-groups', 'maintenance-recent'],
        'Complaints' => ['complaint-kpis', 'complaint-buildings', 'complaint-trend', 'complaint-resolution'],
        'Property financials' => ['property-financial'],
        'Supply requests' => ['supply-status', 'supply-summary', 'supply-recent'],
        'Rent out expiry' => ['rent-kpis', 'rent-upcoming', 'rent-expired'],
        'Agreements' => ['contract-kpis', 'contract-attention', 'contract-vendors', 'contract-due', 'contract-ending', 'contract-securities'],
    ];

    /** @var array<string, WidgetDefinition>|null */
    protected ?array $definitions = null;

    /** @var array<string, DashboardSource> */
    protected array $sourceFor = [];

    /**
     * @return array<string, WidgetDefinition>
     */
    public function all(): array
    {
        if ($this->definitions === null) {
            $this->definitions = [];
            foreach (self::SOURCES as $class) {
                $source = app($class);
                foreach ($source->widgets() as $definition) {
                    $this->definitions[$definition->key] = $definition;
                    $this->sourceFor[$definition->key] = $source;
                }
            }
        }

        return $this->definitions;
    }

    public function find(string $key): ?WidgetDefinition
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * @return array<string, WidgetDefinition>
     */
    public function visibleTo(User $user): array
    {
        $inSystem = ModuleAccess::permissionFilter();

        return array_filter($this->all(), fn (WidgetDefinition $definition): bool => $definition->isVisibleTo($user, $inSystem));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{blocks: list<array<string, mixed>>, link?: array{label: string, url: string}|null}
     */
    public function data(string $key, ?string $option, array $params = []): array
    {
        $definition = $this->all()[$key];

        return $this->sourceFor[$key]->data($key, $definition->resolveOption($option), $params);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function defaultLayout(): array
    {
        $layout = [];
        $index = 0;
        foreach (self::DEFAULT_SECTIONS as $title => $keys) {
            $layout[] = ['type' => 'divider', 'id' => 'section-'.++$index, 'title' => $title];
            foreach ($keys as $key) {
                $layout[] = $this->freshItem($this->all()[$key]);
            }
        }

        return $layout;
    }

    /**
     * The user's saved layout (or the default) cleaned against what they may
     * see today: widgets they lost access to are dropped, widgets that are new
     * to them are appended so new features still show up, and dividers left
     * with nothing under them before the next divider are removed.
     *
     * @return list<array<string, mixed>>
     */
    public function layoutFor(User $user): array
    {
        $saved = UserPreference::getValue(self::PREFERENCE_KEY, null, $user->id);
        $visible = $this->visibleTo($user);
        $layout = [];
        $placed = [];

        foreach (is_array($saved) ? $saved : $this->defaultLayout() as $item) {
            if (($item['type'] ?? null) === 'divider') {
                $layout[] = ['type' => 'divider', 'id' => (string) $item['id'], 'title' => (string) $item['title']];

                continue;
            }
            $key = $item['key'] ?? null;
            if (! is_string($key) || ! isset($visible[$key]) || isset($placed[$key])) {
                continue;
            }
            $placed[$key] = true;
            $layout[] = ['key' => $key, 'span' => $this->clampSpan((int) ($item['span'] ?? 0), $visible[$key]), 'collapsed' => (bool) ($item['collapsed'] ?? false)]
                + (empty($item['row']) ? [] : ['row' => true])
                + (empty($item['tall']) ? [] : ['tall' => true]);
        }

        $seen = is_array($saved) ? (array) UserPreference::getValue(self::PREFERENCE_KEY.'.seen', [], $user->id) : [];
        foreach ($visible as $key => $definition) {
            if (! isset($placed[$key]) && ! in_array($key, $seen, true)) {
                $layout[] = $this->freshItem($definition);
            }
        }

        return $this->withoutEmptySections($layout);
    }

    /**
     * Persist a layout. Every widget key the user could see at save time is
     * remembered, so one they chose to hide is not re-appended on next load.
     *
     * @param  list<array<string, mixed>>  $layout
     */
    public function saveLayout(User $user, array $layout): void
    {
        UserPreference::setValue(self::PREFERENCE_KEY, array_values($layout), $user->id);
        UserPreference::setValue(self::PREFERENCE_KEY.'.seen', array_keys($this->visibleTo($user)), $user->id);
    }

    /**
     * A widget as it first lands on a dashboard.
     *
     * @return array<string, mixed>
     */
    protected function freshItem(WidgetDefinition $definition): array
    {
        return ['key' => $definition->key, 'span' => $definition->span, 'collapsed' => false] + ($definition->tall ? ['tall' => true] : []);
    }

    protected function clampSpan(int $span, WidgetDefinition $definition): int
    {
        if (! in_array($span, self::SPANS, true)) {
            return $definition->span;
        }

        return max($span, $definition->minSpan);
    }

    /**
     * @param  list<array<string, mixed>>  $layout
     * @return list<array<string, mixed>>
     */
    protected function withoutEmptySections(array $layout): array
    {
        $result = [];
        foreach ($layout as $index => $item) {
            if (($item['type'] ?? null) === 'divider') {
                $next = $layout[$index + 1] ?? null;
                if ($next === null || ($next['type'] ?? null) === 'divider') {
                    continue;
                }
            }
            $result[] = $item;
        }

        return $result;
    }
}

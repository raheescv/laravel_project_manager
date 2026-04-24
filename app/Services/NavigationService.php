<?php

namespace App\Services;

use App\Models\Configuration;

class NavigationService
{
    /**
     * Returns the full default ordered list of all navigation items.
     * Each item has: id, label, icon, visible, children (labels for display).
     */
    public static function defaultItems(): array
    {
        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'fa fa-tachometer',
                'visible' => true,
                'children' => [],
            ],
            [
                'id' => 'inventory',
                'label' => 'Inventory',
                'icon' => 'fa fa-cubes',
                'visible' => true,
                'children' => ['List', 'Product Search', 'Barcode Cart', 'Inventory Transfer', 'Product Check', 'Stock Check'],
            ],
            [
                'id' => 'rent-out',
                'label' => 'Rent Out',
                'icon' => 'fa fa-home',
                'visible' => true,
                'children' => ['Booking', 'Tenant Details', 'Rentouts', 'Payments', 'Utilities', 'Services', 'Payment Due', 'Cheque Management', 'Payment History', 'Customer Property', 'Security Report', 'Day Book'],
            ],
            [
                'id' => 'property-sales',
                'label' => 'Sales',
                'icon' => 'fa fa-dollar',
                'visible' => true,
                'children' => ['Sales', 'Booking', 'Payments', 'Service Charge Report', 'Cheque Management'],
            ],
            [
                'id' => 'leads',
                'label' => 'Leads',
                'icon' => 'fa fa-filter',
                'visible' => true,
                'children' => ['All Leads', 'Lead Calendar'],
            ],
            [
                'id' => 'maintenance',
                'label' => 'Maintenance',
                'icon' => 'fa fa-wrench',
                'visible' => true,
                'children' => ['Registration', 'Technician'],
            ],
            [
                'id' => 'issue',
                'label' => 'Issue',
                'icon' => 'fa fa-share-square-o',
                'visible' => true,
                'children' => ['Create Issue', 'Create Return', 'List', 'Item Wise Report', 'Aging Report'],
            ],
            [
                'id' => 'appointments',
                'label' => 'Appointments',
                'icon' => 'fa fa-calendar',
                'visible' => true,
                'children' => ['Employee Calendar', 'List'],
            ],
            [
                'id' => 'tailoring',
                'label' => 'Tailoring',
                'icon' => 'fa fa-cut',
                'visible' => true,
                'children' => ['Create Order', 'Orders', 'Order Management', 'Job Completion', 'Item Wise Report', 'Tailor Wise Report', 'Non-Delivery Report'],
            ],
            [
                'id' => 'sale',
                'label' => 'Sale',
                'icon' => 'fa fa-shopping-cart',
                'visible' => true,
                'children' => ['Create', 'List', 'Item Wise Report', 'Receipts', 'Return Create', 'Return List', 'Return Payments'],
            ],
            [
                'id' => 'day-session',
                'label' => 'Day Session',
                'icon' => 'fa fa-sun-o',
                'visible' => true,
                'children' => ['Day Management', 'Day Sessions Report'],
            ],
            [
                'id' => 'purchase',
                'label' => 'Purchase',
                'icon' => 'fa fa-cart-plus',
                'visible' => true,
                'children' => ['Create', 'List', 'Item Wise Report', 'Payments', 'Return Create', 'Return List', 'Return Payments'],
            ],
            [
                'id' => 'package',
                'label' => 'Package',
                'icon' => 'fa fa-gift',
                'visible' => true,
                'children' => ['Create', 'List'],
            ],
            [
                'id' => 'account',
                'label' => 'Account',
                'icon' => 'fa fa-university',
                'visible' => true,
                'children' => ['Chart Of Account', 'Expense', 'Income', 'General Voucher', 'Day Book', 'Bank Reconciliation'],
            ],
            [
                'id' => 'employee',
                'label' => 'Employee',
                'icon' => 'fa fa-users',
                'visible' => true,
                'children' => ['List', 'Commission', 'Attendance'],
            ],
            [
                'id' => 'purchase-workflow',
                'label' => 'Purchase Workflow',
                'icon' => 'fa fa-sitemap',
                'visible' => true,
                'children' => ['Purchase Requests', 'LPO', 'GRN', 'LPO Invoice', 'Vendors'],
            ],
            [
                'id' => 'asset-supply',
                'label' => 'Asset Supply',
                'icon' => 'fa fa-truck',
                'visible' => true,
                'children' => ['Supply List', 'Supply Return List'],
            ],
            [
                'id' => 'users',
                'label' => 'Users',
                'icon' => 'fa fa-user',
                'visible' => true,
                'children' => ['List', 'Roles'],
            ],
            [
                'id' => 'tenants',
                'label' => 'Tenants',
                'icon' => 'fa fa-building',
                'visible' => true,
                'children' => [],
            ],
            [
                'id' => 'flat-trade',
                'label' => 'FlatTrade',
                'icon' => 'fa fa-line-chart',
                'visible' => true,
                'children' => ['Dashboard', 'Trade History', 'Connect Account'],
            ],
            [
                'id' => 'tickets',
                'label' => 'Tickets',
                'icon' => 'fa fa-ticket',
                'visible' => true,
                'children' => [],
            ],
            [
                'id' => 'log',
                'label' => 'Log',
                'icon' => 'fa fa-clipboard',
                'visible' => true,
                'children' => ['Api Log', 'Inventory', 'Visitor Analytics', 'System Health'],
            ],
        ];
    }

    /**
     * Returns the full ordered navigation list (without active-module filtering).
     * New items not in the saved order are appended at the end (visible by default).
     *
     * Use this when you need every item regardless of the active module — e.g.
     * persisting visibility preferences for items belonging to other modules.
     */
    public static function getOrderedItems(): array
    {
        $defaults = self::defaultItems();
        $saved = Configuration::where('key', 'nav_order')->value('value');
        $savedOrder = $saved ? json_decode($saved, true) : [];

        if (empty($savedOrder)) {
            return $defaults;
        }

        $defaultsMap = [];
        foreach ($defaults as $item) {
            $defaultsMap[$item['id']] = $item;
        }

        $result = [];

        foreach ($savedOrder as $pref) {
            $id = $pref['id'] ?? null;
            if ($id && isset($defaultsMap[$id])) {
                $item = $defaultsMap[$id];
                $item['visible'] = $pref['visible'] ?? true;
                $result[] = $item;
                unset($defaultsMap[$id]);
            }
        }

        foreach ($defaultsMap as $item) {
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Returns navigation items ordered by saved preferences and filtered by the
     * currently active system module. This is the single source of truth used
     * by both the rendered sidebar and the Navigation Order settings screen.
     */
    public static function getNavigationItems(): array
    {
        return self::filterByActiveModule(self::getOrderedItems());
    }

    /**
     * Filters a list of navigation items to only those belonging to the
     * currently selected "active_module" in system configuration. If no active
     * module is set, the list is returned unchanged.
     */
    public static function filterByActiveModule(array $items): array
    {
        $activeModule = Configuration::where('key', 'active_module')->value('value');

        if (! $activeModule) {
            return $items;
        }

        $enabledModuleKeys = config("modules.systems.{$activeModule}", []);
        if (empty($enabledModuleKeys)) {
            return $items;
        }

        $navModuleMap = self::navItemModuleMap();

        return array_values(array_filter($items, function (array $item) use ($enabledModuleKeys, $navModuleMap): bool {
            $id = $item['id'] ?? null;
            if (! $id) {
                return false;
            }

            $requiredModuleKeys = $navModuleMap[$id] ?? ['core'];

            return ! empty(array_intersect($requiredModuleKeys, $enabledModuleKeys));
        }));
    }

    /**
     * Maps each navigation item id to the module keys that enable it.
     * Items whose required modules don't intersect with the active system's
     * enabled modules are hidden from the sidebar.
     */
    private static function navItemModuleMap(): array
    {
        return [
            'dashboard' => ['core'],
            'inventory' => ['product_management', 'inventory_management'],
            'rent-out' => ['rent_out'],
            'property-sales' => ['lease'],
            'leads' => ['property_management'],
            'maintenance' => ['maintenance'],
            'issue' => ['support'],
            'appointments' => ['saloon'],
            'tailoring' => ['tailoring'],
            'sale' => ['sales'],
            'day-session' => ['sales'],
            'purchase' => ['simple_purchase_management'],
            'package' => ['saloon'],
            'account' => ['accounting'],
            'employee' => ['hr_management'],
            'purchase-workflow' => ['advanced_purchase_management'],
            'asset-supply' => ['maintenance'],
            'users' => ['core'],
            'tenants' => ['property_management'],
            'flat-trade' => ['core'],
            'tickets' => ['core'],
            'log' => ['core'],
        ];
    }
}

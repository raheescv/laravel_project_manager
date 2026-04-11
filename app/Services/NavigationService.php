<?php

namespace App\Services;

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
                'icon' => 'fa fa-dashboard',
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
                'icon' => 'fa fa-hand-o-right',
                'visible' => true,
                'children' => ['Sales', 'Booking', 'Payments', 'Service Charge Report', 'Cheque Management'],
            ],
            [
                'id' => 'leads',
                'label' => 'Leads',
                'icon' => 'fa fa-users',
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
                'icon' => 'fa fa-exchange',
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
                'icon' => 'fa fa-shopping-cart',
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
                'icon' => 'fa fa-bank',
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
                'icon' => 'fa fa-user',
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
                'icon' => 'fa fa-chart-line',
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
     * Returns navigation items ordered and filtered by the tenant-wide saved preferences.
     * New items not in the saved order are appended at the end (visible by default).
     */
    public static function getNavigationItems(): array
    {
        $defaults = self::defaultItems();

        $saved = \App\Models\Configuration::where('key', 'nav_order')->value('value');
        $savedOrder = $saved ? json_decode($saved, true) : [];

        if (empty($savedOrder)) {
            return $defaults;
        }

        // Build a keyed map for quick lookup
        $defaultsMap = [];
        foreach ($defaults as $item) {
            $defaultsMap[$item['id']] = $item;
        }

        $result = [];

        // Apply saved order and visibility
        foreach ($savedOrder as $pref) {
            $id = $pref['id'] ?? null;
            if ($id && isset($defaultsMap[$id])) {
                $item = $defaultsMap[$id];
                $item['visible'] = $pref['visible'] ?? true;
                $result[] = $item;
                unset($defaultsMap[$id]);
            }
        }

        // Append any new items not yet in saved config
        foreach ($defaultsMap as $item) {
            $result[] = $item;
        }

        return $result;
    }
}

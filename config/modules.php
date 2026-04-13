<?php

/*
|--------------------------------------------------------------------------
| Module Classification
|--------------------------------------------------------------------------
|
| This file classifies permissions (from config/permissions.php) into
| logical modules, and maps those modules to system/project types.
|
| Two formats are supported inside 'permissions':
|
|   'group_key'          — all actions of that group   (e.g. 'sale', 'product')
|   'group_key.action'   — one specific action         (e.g. 'report.sale item')
|
| Report permissions stay untouched in permissions.php ('report' group).
| Here they are split individually per report module using the
| 'report.{action}' dot-notation so each report can be toggled separately.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | System Types
    |--------------------------------------------------------------------------
    | Maps each system/project type to the module keys it includes.
    */
    'systems' => [

        'Tailor Module' => [
            'core',
            'product_management',
            'sales',
            'simple_purchase_management',
            'tailoring',
            'accounting',
            'hr_management',
            'reports_sales',
            'reports_accounting',
            'reports_hr',
            'reports_tailoring',
        ],

        'Property Management Module' => [
            'core',
            'property_management',
            'product_management',
            'inventory_management',
            'advanced_purchase_management',
            'rent_out',
            'lease',
            'maintenance',
            'accounting',
            'hr_management',
            'reports_accounting',
        ],

        'Stock Management Module' => [
            'core',
            'product_management',
            'inventory_management',
            'simple_purchase_management',
            'accounting',
            'hr_management',
            'reports_inventory',
            'reports_purchase',
            'reports_accounting',
        ],

        'Issues Module' => [
            'core',
            'support',
            'accounting',
            'hr_management',
            'reports_support',
            'reports_accounting',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Module Definitions
    |--------------------------------------------------------------------------
    |
    | 'permissions' accepts two formats:
    |   'group_key'         → include all actions for that group
    |   'group_key.action'  → include one specific permission
    |
    | Report modules use the second format so each report is individually
    | selectable without touching config/permissions.php.
    |
    */
    'modules' => [

        // ── Core ──────────────────────────────────────────────────────────────
        'core' => [
            'label' => 'System Administration',
            'permissions' => [
                'user',
                'role',
                'branch',
                'country',
                'configuration',
                'backup',
                'system health',
                'visitor analytics',
                'api_log',
                'whatsapp',
                'log',
            ],
        ],

        // ── Product ───────────────────────────────────────────────────────────
        'product_management' => [
            'label' => 'Product Management',
            'permissions' => [
                'product',
                'category',
                'brand',
                'unit',
                'rack',
                'service',
                'combo offer',
            ],
        ],

        // ── Inventory ─────────────────────────────────────────────────────────
        'inventory_management' => [
            'label' => 'Inventory Management',
            'permissions' => [
                'inventory',
                'inventory transfer',
            ],
        ],

        // ── Sales / POS ───────────────────────────────────────────────────────
        'sales' => [
            'label' => 'Sales & POS',
            'permissions' => [
                'customer',
                'customer type',
                'sale',
                'sales return',
                'day session',
                'day close',
            ],
        ],

        // ── Purchase ──────────────────────────────────────────────────────────
        'simple_purchase_management' => [
            'label' => 'Purchase Management',
            'permissions' => [
                'vendor',
                'purchase',
                'purchase return',
            ],
        ],

        // ── Purchase ──────────────────────────────────────────────────────────
        'advanced_purchase_management' => [
            'label' => 'Purchase Management',
            'permissions' => [
                'vendor',
                'purchase request',
                'local purchase order',
                'grn',
                'lpo-purchase',
            ],
        ],

        // ── Accounting ────────────────────────────────────────────────────────
        'accounting' => [
            'label' => 'Accounting',
            'permissions' => [
                'account',
                'account category',
                'account note',
                'expense',
                'income',
                'general voucher',
                'cheque',
            ],
        ],

        // ── HR ────────────────────────────────────────────────────────────────
        'hr_management' => [
            'label' => 'HR Management',
            'permissions' => [
                'employee',
                'employee attendance',
                'employee commission',
                'designation',
                'department',
            ],
        ],

        // ── Saloon ────────────────────────────────────────────────────────────
        'saloon' => [
            'label' => 'Saloon',
            'permissions' => [
                'appointment',
                'package category',
                'package',
            ],
        ],

        // ── Tailoring ─────────────────────────────────────────────────────────
        'tailoring' => [
            'label' => 'Tailoring',
            'permissions' => [
                'tailoring category',
                'tailoring measurement option',
                'tailoring order',
                'tailoring job completion',
            ],
        ],

        // ── Property Management ───────────────────────────────────────────────
        'property_management' => [
            'label' => 'Property Management',
            'permissions' => [
                'property',
                'property dashboard',
                'property group',
                'property building',
                'property type',
                'utility',
                'document type',
                'complaint category',
                'complaint',
                'tenant detail',
                'property lead',
            ],
        ],

        // ── Rent_out ───────────────────────────────────────────────────────────
        'rent_out' => [
            'label' => 'Rent_out',
            'permissions' => [
                'rent out',
                'rent out booking',
                'rent out security',
                'rent out cheque',
                'rent out utility',
                'rent out service',
                'rent out payment term',
                'rent out note',
            ],
        ],

        // ── Lease / Sale ──────────────────────────────────────────────────────
        'lease' => [
            'label' => 'Lease / Sale',
            'permissions' => [
                'rent out lease',
                'rent out lease booking',
                'rent out lease cheque',
            ],
        ],

        // ── Maintenance ───────────────────────────────────────────────────────
        'maintenance' => [
            'label' => 'Maintenance',
            'permissions' => [
                'maintenance',
                'supply request',
            ],
        ],

        // ── Support & Issues ──────────────────────────────────────────────────
        'support' => [
            'label' => 'Support & Issues',
            'permissions' => [
                'issue',
                'ticket',
            ],
        ],

        // ── Reports ───────────────────────────────────────────────────────────
        // Each report action from the 'report' group is listed individually
        // using 'report.{action}' so they can be toggled per module.
        // config/permissions.php is NOT changed — the 'report' group stays intact.

        'reports_sales' => [
            'label' => 'Reports - Sales',
            'permissions' => [
                'report.sale item',
                'report.sale return item',
                'report.daily sales insights',
                'report.sales overview',
                'report.sale calendar',
                'report.sale and sales return items',
                'report.day wise sale',
                'report.monthly sale',
                'report.day book',
                'report.day book export',
            ],
        ],

        'reports_purchase' => [
            'label' => 'Reports - Purchase',
            'permissions' => [
                'report.purchase item',
                'report.vendor aging',
            ],
        ],

        'reports_accounting' => [
            'label' => 'Reports - Accounting',
            'permissions' => [
                'report.income vs expense dashboard pie chart',
                'report.income vs expense dashboard bar chart',
                'report.profit loss',
                'report.profit loss statement',
                'report.trial balance',
                'report.balance sheet',
                'report.bank reconciliation report',
                'report.customer aging',
                'report.tax report',
                'report.day wise tax report',
            ],
        ],

        'reports_inventory' => [
            'label' => 'Reports - Inventory',
            'permissions' => [
                'report.stock analysis',
                'report.product',
            ],
        ],

        'reports_hr' => [
            'label' => 'Reports - HR',
            'permissions' => [
                'report.employee',
                'report.employee productivity',
            ],
        ],

        'reports_crm' => [
            'label' => 'Reports - Customer',
            'permissions' => [
                'report.customer',
                'report.customer callback reminder',
            ],
        ],

        'reports_support' => [
            'label' => 'Reports - Support',
            'permissions' => [
                'report.issue item',
                'report.issue aging',
            ],
        ],

        'reports_tailoring' => [
            'label' => 'Reports - Tailoring',
            'permissions' => [
                'report.tailoring order item',
                'report.tailoring non delivery',
                'report.tailoring order item tailor',
            ],
        ],

    ],

];

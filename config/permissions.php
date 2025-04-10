<?php

return [
    'account' => ['create', 'view', 'edit', 'delete', 'export'],
    'customer' => ['create', 'view', 'edit', 'delete', 'export'],
    'vendor' => ['create', 'view', 'edit', 'delete', 'export'],
    'user' => ['create', 'view', 'edit', 'delete'],
    'employee' => ['create', 'view', 'edit', 'delete', 'export'],
    'role' => ['create', 'view', 'edit', 'delete', 'permissions'],
    'branch' => ['create', 'view', 'edit', 'delete'],
    'product' => ['create', 'view', 'edit', 'delete', 'import', 'export'],
    'service' => ['create', 'view', 'edit', 'delete', 'import', 'export'],
    'inventory' => ['view', 'edit', 'delete', 'import', 'export'],
    'category' => ['create', 'view', 'edit', 'delete', 'import', 'export'],
    'sale' => ['create', 'view', 'edit', 'edit completed', 'delete', 'cancel', 'export', 'receipts', 'view journal entries'],
    'sale_return' => ['create', 'view', 'edit', 'edit completed', 'delete', 'export', 'payments', 'view journal entries'],
    'purchase' => ['create', 'view', 'edit', 'edit completed', 'delete', 'cancel', 'export', 'payments', 'view journal entries'],
    'unit' => ['create', 'view', 'edit', 'delete'],
    'department' => ['create', 'view', 'edit', 'delete'],
    'whatsapp' => ['integration'],
    'report' => ['sale item', 'sale return item', 'day book', 'purchase item'],
];

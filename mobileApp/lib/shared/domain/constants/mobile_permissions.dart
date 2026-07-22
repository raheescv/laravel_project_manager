import 'package:flutter/material.dart';

/// A single Spatie permission the mobile app actually gates a feature on.
/// The [slug] must match the backend permission name exactly (see the
/// EnsureMobilePermission middleware in routes/api_v1.php). [group] and [icon]
/// drive the grouped "My Permissions" screen.
@immutable
class MobilePermission {
  const MobilePermission({
    required this.slug,
    required this.label,
    required this.description,
    required this.group,
    required this.icon,
  });

  final String slug;
  final String label;
  final String description;
  final String group;
  final IconData icon;
}

/// Canonical permission slugs referenced by the app — router guards, the
/// dashboard/profile day-session actions, and the "My Permissions" list — so
/// each string lives in one place. Note: not every slug is enforced by the app
/// yet. The `sale.*` CRUD slugs are surfaced on the permissions screen for
/// visibility, but the mobile POS sale endpoints are currently ungated (see the
/// `sale` prefix group in routes/api_v1.php).
abstract final class PermissionSlug {
  static const salesOverview = 'report.sales overview';
  static const report = 'report.sale item';
  static const daySession = 'day session.create';
  static const saleCreate = 'sale.create';
  static const saleView = 'sale.view';
  static const saleEdit = 'sale.edit';
  static const saleDelete = 'sale.delete';
  static const saleReturnView = 'sales return.view';
  static const saleReturnCreate = 'sales return.create';
  static const saleReturnEdit = 'sales return.edit';
  static const stockCheck = 'inventory.stock check';
  // Same gate as the web Settings page — editing the shared Sale
  // Configuration (printer & receipt options) from the app.
  static const settings = 'configuration.settings';
}

/// The permissions surfaced on the "My Permissions" screen, grouped by module
/// so staff can see their access — and the exact backend permission name — at a
/// glance. Most rows are gated by the app (router / dashboard / module guards);
/// the "Sales" rows mirror the web Sale CRUD permissions for visibility (the
/// mobile POS sale endpoints are not yet gated on them, see routes/api_v1.php).
const mobilePermissions = <MobilePermission>[
  // Sales — core POS actions. Shown for visibility; see the note above.
  MobilePermission(
    slug: PermissionSlug.saleCreate,
    label: 'Create Sale',
    description: 'Ring up and save a new sale',
    group: 'Sales',
    icon: Icons.add_shopping_cart,
  ),
  MobilePermission(
    slug: PermissionSlug.saleView,
    label: 'View Sales',
    description: 'Open the sales list and invoices',
    group: 'Sales',
    icon: Icons.receipt_long,
  ),
  MobilePermission(
    slug: PermissionSlug.saleEdit,
    label: 'Edit Sale',
    description: 'Modify an existing sale',
    group: 'Sales',
    icon: Icons.edit_outlined,
  ),
  MobilePermission(
    slug: PermissionSlug.saleDelete,
    label: 'Delete Sale',
    description: 'Remove a sale',
    group: 'Sales',
    icon: Icons.delete_outline,
  ),
  MobilePermission(
    slug: PermissionSlug.salesOverview,
    label: 'Dashboard',
    description: 'View the admin dashboard',
    group: 'Administration',
    icon: Icons.dashboard_outlined,
  ),
  MobilePermission(
    slug: PermissionSlug.report,
    label: 'Reports',
    description: 'Itemwise & staff sales reports',
    group: 'Administration',
    icon: Icons.bar_chart,
  ),
  MobilePermission(
    slug: PermissionSlug.daySession,
    label: 'Day Session',
    description: 'Open and close the branch day',
    group: 'Administration',
    icon: Icons.event_available_outlined,
  ),
  MobilePermission(
    slug: PermissionSlug.saleReturnView,
    label: 'Sale Returns',
    description: 'View the returns list',
    group: 'Sale Returns',
    icon: Icons.assignment_return_outlined,
  ),
  MobilePermission(
    slug: PermissionSlug.saleReturnCreate,
    label: 'Create Return',
    description: 'Raise a return on an invoice',
    group: 'Sale Returns',
    icon: Icons.add,
  ),
  MobilePermission(
    slug: PermissionSlug.saleReturnEdit,
    label: 'Edit Return',
    description: 'Modify an existing return',
    group: 'Sale Returns',
    icon: Icons.edit_outlined,
  ),
  MobilePermission(
    slug: PermissionSlug.stockCheck,
    label: 'Stock Check',
    description: 'Count physical stock & reconcile inventory',
    group: 'Inventory',
    icon: Icons.fact_check_outlined,
  ),
  MobilePermission(
    slug: PermissionSlug.settings,
    label: 'Sale Configuration',
    description: 'Change the printer & receipt settings',
    group: 'Administration',
    icon: Icons.print_outlined,
  ),
];

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

/// Canonical permission slugs the app gates on. Referenced by the router
/// guards, the dashboard/profile day-session actions, and the list below so
/// there is a single source of truth for each string.
abstract final class PermissionSlug {
  static const salesOverview = 'report.sales overview';
  static const report = 'report.sale item';
  static const daySession = 'day session.create';
  static const saleReturnView = 'sales return.view';
  static const saleReturnCreate = 'sales return.create';
  static const saleReturnEdit = 'sales return.edit';
  static const stockCheck = 'inventory.stock check';
}

/// The permissions the app checks — the ONLY ones surfaced on the "My
/// Permissions" screen. Keep this list in sync with the router/dashboard
/// gates so the screen reflects exactly what the app enforces.
const mobilePermissions = <MobilePermission>[
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
];

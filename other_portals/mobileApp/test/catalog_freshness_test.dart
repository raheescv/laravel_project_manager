import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';

/// Selling from a stale snapshot is never blocked — refusing to trade spends
/// certain revenue to avoid a possible price error, and a till that cannot sell
/// during an outage is the failure the whole feature exists to prevent. What the
/// age changes is how loudly the screen says so, and these are the thresholds it
/// says it at.
void main() {
  test('a recent snapshot earns no extra warning', () {
    expect(CatalogFreshness.of(const Duration(hours: 1)), CatalogFreshness.fresh);
    expect(CatalogFreshness.of(const Duration(hours: 1)).needsSaying, isFalse);
  });

  test('past half a day it is worth saying prices may have moved', () {
    expect(CatalogFreshness.of(CatalogFreshness.agingAfter), CatalogFreshness.aging);
    expect(CatalogFreshness.of(const Duration(hours: 20)), CatalogFreshness.aging);
    expect(CatalogFreshness.of(const Duration(hours: 20)).needsSaying, isTrue);
  });

  test('past a full day it is said plainly', () {
    expect(CatalogFreshness.of(CatalogFreshness.staleAfter), CatalogFreshness.stale);
    expect(CatalogFreshness.of(const Duration(days: 4)), CatalogFreshness.stale);
  });

  test('never having snapshotted is not staleness', () {
    // The provisioning strip owns that case; treating it as stale would say it
    // twice, in two different colours.
    expect(CatalogFreshness.of(null), CatalogFreshness.fresh);
  });

  test('the age is worded once, so the strip and the grid chip agree', () {
    expect(catalogAgeLabel(DateTime.now().subtract(const Duration(minutes: 12))), '12 min ago');
    expect(catalogAgeLabel(DateTime.now().subtract(const Duration(hours: 6))), '6h ago');
    expect(catalogAgeLabel(DateTime.now().subtract(const Duration(days: 3))), '3d ago');
    expect(catalogAgeLabel(null), 'an earlier session');
  });
}

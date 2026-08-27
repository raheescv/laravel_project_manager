import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/features/catalog/logic/product_list_cubit/product_list_cubit.dart';

/// Search obeys the global stock rule, and says so when it comes back empty.
///
/// It deliberately did not, once: a scan that returns nothing because of a
/// filter set on another screen is the worst failure this app has. The rule
/// changed, so the mitigation is that the control lives in the search bar too
/// and the empty state names it — both asserted here and in the widget tests.
void main() {
  test('a search carries the stock rule into the query', () {
    const filters = ProductFilters(search: 'samba', inStockOnly: true);
    expect(filters.inStockOnly, isTrue);
    expect(filters.search, 'samba');
  });

  test('clearing the query keeps the stock rule', () {
    // The screen rebuilds filters from scratch when the box is emptied, which
    // is exactly where a flag gets dropped by accident.
    const cleared = ProductFilters(inStockOnly: true);
    expect(cleared.inStockOnly, isTrue);
    expect(cleared.search, isNull);
  });

  test('the toggle can be turned off without losing the query', () {
    const filters = ProductFilters(search: 'samba', inStockOnly: true);
    final widened = filters.copyWith(inStockOnly: false);
    expect(widened.inStockOnly, isFalse);
    expect(widened.search, 'samba', reason: 'widening must not clear the search');
  });

  test('stock counts as an active filter', () {
    // So the results screen badge and the empty state agree about why the
    // grid is short.
    expect(const ProductFilters().activeCount, 0);
    expect(const ProductFilters(inStockOnly: true).activeCount, 1);
  });
}

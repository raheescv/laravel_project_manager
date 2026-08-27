import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/shared/domain/models/index.dart';

/// What the product page's badge is drawn from.
///
/// It used to trust `stock_quantity_availability_status`, which the detail
/// endpoint derives from a session the public API does not have — so it never
/// said "in stock" and the badge read "sold out" over a full shelf. The
/// inventory rows are the thing that is actually true.
Product _product(List<InventoryLine> lines) => Product.fromJson({
      'id': 1,
      'name': 'Samba',
      'code': 'S1',
      'mrp': 480,
      'inventories': [
        for (final l in lines)
          {
            'branch': {'id': l.branchId, 'name': l.branchName, 'code': ''},
            'quantity': l.quantity,
          },
      ],
    });

InventoryLine _line(int branch, int qty) =>
    InventoryLine(branchId: branch, branchName: 'B$branch', branchCode: 'B$branch', quantity: qty);

void main() {
  test('reports what is on this shop’s shelf, not the chain’s', () {
    final p = _product([_line(1, 3), _line(2, 40)]);
    expect(p.stockAt(1), 3, reason: 'not the 43 across both shops');
    expect(p.stockAt(2), 40);
  });

  test('a shop with no row for the product has none of it', () {
    expect(_product([_line(2, 40)]).stockAt(1), 0);
  });

  test('negative stock is not a quantity anyone can be sold', () {
    // A live catalogue oversells and goes negative; "-2 available" must never
    // reach a customer, and it must not read as in stock either.
    expect(_product([_line(1, -2)]).stockAt(1), 0);
  });

  test('with no shop chosen it falls back to everything', () {
    expect(_product([_line(1, 3), _line(2, 40)]).stockAt(null), 43);
  });
}

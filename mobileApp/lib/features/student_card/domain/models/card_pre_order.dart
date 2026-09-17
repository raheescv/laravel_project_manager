import 'package:equatable/equatable.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';

/// Today's canteen pre-order a parent set up in the parent portal, as the card
/// lookup returns it (`pre_order` on `GET /students/card/{uid}`,
/// App\Actions\Student\PreOrder\ForTillAction).
///
/// Items only: the till puts them in the cart and the ordinary card sale charges
/// them. Each item carries the product in the catalogue's own shape, at today's
/// price. Sending [id] with the completed sale marks it collected for today.
class CardPreOrder extends Equatable {
  const CardPreOrder({
    required this.id,
    this.source = 'day',
    this.note = '',
    this.items = const [],
    this.total = 0,
    this.missing = 0,
  });

  factory CardPreOrder.fromJson(Map<String, dynamic> j) => CardPreOrder(
        id: asNum(j['id']).toInt(),
        source: asStr(j['source']),
        note: asStr(j['note']),
        items: (j['items'] as List<dynamic>? ?? [])
            .whereType<Map>()
            .map((e) => CardPreOrderItem.fromJson(Map<String, dynamic>.from(e)))
            .where((i) => i.product.id > 0 && i.quantity > 0)
            .toList(),
        total: asNum(j['total']).toDouble(),
        missing: asNum(j['missing']).toInt(),
      );

  final int id;

  /// 'day' (ordered for today) or 'weekly' (the standing weekly order).
  final String source;

  /// For the canteen, e.g. "No sauce".
  final String note;
  final List<CardPreOrderItem> items;
  final double total;

  /// Items the parent ordered that are no longer sold (left out of [items]).
  final int missing;

  bool get isWeekly => source == 'weekly';
  int get count => items.fold(0, (sum, i) => sum + i.quantity);

  /// "Happy Meal, 2 × Apple Juice"
  String get summary => items.map((i) => i.quantity > 1 ? '${i.quantity} × ${i.product.name}' : i.product.name).join(', ');

  @override
  List<Object?> get props => [id, source, note, items, total, missing];
}

class CardPreOrderItem extends Equatable {
  const CardPreOrderItem({required this.product, required this.quantity});

  factory CardPreOrderItem.fromJson(Map<String, dynamic> j) => CardPreOrderItem(
        product: Product.fromJson(j['product'] is Map ? Map<String, dynamic>.from(j['product'] as Map) : const {}),
        quantity: asNum(j['quantity']).toInt(),
      );

  final Product product;
  final int quantity;

  @override
  List<Object?> get props => [product, quantity];
}

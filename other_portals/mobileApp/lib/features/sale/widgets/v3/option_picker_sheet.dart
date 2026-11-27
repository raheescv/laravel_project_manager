import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// Option picker for an item with option groups (web Inventory → Option
/// Groups) — the mobile twin of resources/js/components/OptionPickerModal.vue.
/// Each group shows its rule; a pick-one group swaps its choice, a multi group
/// ticks up to its max. An option with a follow-up group (Red Bull → Sugar)
/// opens it right under its own group.
///
/// Click-and-go: when the last required choice is tapped and nothing on screen
/// is optional, it returns at once; otherwise "Add" confirms. Returns the picks,
/// or null when dismissed.
Future<List<OptionPick>?> pickOptions(BuildContext context, Product product) {
  return showModalBottomSheet<List<OptionPick>>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    constraints: const BoxConstraints(maxWidth: 760),
    builder: (_) => _OptionSheet(product: product),
  );
}

/// A group on screen: the product's own, or one opened by the pick [parentId].
class _Slot {
  const _Slot(this.group, this.parentId, this.parentName, this.depth);
  final OptionGroup group;
  final int parentId;
  final String parentName;
  final int depth;
  String get key => '$parentId|${group.id}';
}

class _OptionSheet extends StatefulWidget {
  const _OptionSheet({required this.product});
  final Product product;
  @override
  State<_OptionSheet> createState() => _OptionSheetState();
}

class _OptionSheetState extends State<_OptionSheet> {
  /// "parentOptionId|groupId" => picked option ids, in tap order.
  final Map<String, List<int>> _picked = {};

  @override
  void initState() {
    super.initState();
    _seed(widget.product.optionGroups, 0);
  }

  /// Start from the options marked as default.
  void _seed(List<OptionGroup> groups, int parentId) {
    for (final g in groups) {
      var defaults = g.items.where((o) => o.isDefault).toList();
      if (g.max != null && defaults.length > g.max!) defaults = defaults.sublist(0, g.max!);
      if (defaults.isEmpty) continue;
      _picked['$parentId|${g.id}'] = [for (final o in defaults) o.id];
      for (final o in defaults) {
        if (o.next != null) _seed([o.next!], o.id);
      }
    }
  }

  bool _isPicked(_Slot s, OptionItem o) => (_picked[s.key] ?? const []).contains(o.id);
  int _count(_Slot s) => (_picked[s.key] ?? const []).length;
  bool _isFull(_Slot s) => s.group.multi && s.group.max != null && _count(s) >= s.group.max!;

  /// Every group on screen: the product's own, each picked option's follow-up right after its group.
  List<_Slot> get _slots {
    final out = <_Slot>[];
    void walk(List<OptionGroup> groups, int parentId, String parentName, int depth) {
      for (final g in groups) {
        final slot = _Slot(g, parentId, parentName, depth);
        out.add(slot);
        for (final o in g.items) {
          if (o.next != null && _isPicked(slot, o)) walk([o.next!], o.id, o.name, depth + 1);
        }
      }
    }

    walk(widget.product.optionGroups, 0, '', 0);
    return out;
  }

  bool get _complete => _slots.every((s) {
        final n = _count(s);
        return n >= s.group.min && (s.group.max == null || n <= s.group.max!);
      });

  bool get _hasOpenChoices => _slots.any((s) => s.group.min == 0 || s.group.multi);

  List<OptionPick> get _picks => [
        for (final s in _slots)
          for (final o in s.group.items)
            if (_isPicked(s, o))
              OptionPick(itemId: o.id, parentItemId: s.parentId == 0 ? null : s.parentId, groupName: s.group.name, optionName: o.name, price: o.price),
      ];

  double get _extra => _picks.fold<double>(0, (sum, o) => sum + o.price);

  void _toggle(_Slot s, OptionItem o) {
    final current = [...(_picked[s.key] ?? const <int>[])];
    if (!s.group.multi) {
      // Pick one: tapping the chosen one again clears an optional group, keeps a required one.
      if (current.contains(o.id)) {
        if (s.group.required) return;
        current.clear();
      } else {
        current
          ..clear()
          ..add(o.id);
      }
    } else if (current.contains(o.id)) {
      current.remove(o.id);
    } else {
      if (_isFull(s)) return;
      current.add(o.id);
    }
    setState(() {
      _picked[s.key] = current;
      // A follow-up group closes with its option: forget what was picked in it.
      final live = {for (final slot in _slots) slot.key};
      _picked.removeWhere((key, _) => !live.contains(key));
    });
    if (_complete && !_hasOpenChoices) Navigator.of(context).pop(_picks);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final product = widget.product;
    final slots = _slots;
    final required = slots.where((s) => s.group.required).length;
    final summary = _picks.map((o) => o.optionName).join(' · ');
    return Container(
      constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.92),
      decoration: BoxDecoration(color: p.canvas, borderRadius: const BorderRadius.vertical(top: Radius.circular(30))),
      clipBehavior: Clip.antiAlias,
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 10, 18, 14),
              child: Column(children: [
                Container(width: 44, height: 5, margin: const EdgeInsets.only(bottom: 12),
                    decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3))),
                Row(children: [
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(product.name, maxLines: 2, overflow: TextOverflow.ellipsis,
                          style: ui(size: 19, weight: FontWeight.w900, color: p.ink, height: 1.15)),
                      const SizedBox(height: 3),
                      Text(required == 0 ? 'Optional extras' : '$required ${required == 1 ? 'choice' : 'choices'}${_hasOpenChoices ? '' : ' · then it goes on the ticket'}',
                          style: ui(size: 12.5, weight: FontWeight.w600, color: p.textSecondary)),
                    ]),
                  ),
                  Text(Money.plain(product.mrp + _extra), style: ui(size: 21, weight: FontWeight.w900, color: p.ink)),
                ]),
              ]),
            ),
            Divider(height: 1, color: p.hairline),
            Flexible(
              child: ListView(
                shrinkWrap: true,
                padding: const EdgeInsets.fromLTRB(18, 16, 18, 16),
                children: [for (final s in slots) _group(p, s)],
              ),
            ),
            Container(
              padding: const EdgeInsets.fromLTRB(18, 12, 18, 12),
              decoration: BoxDecoration(color: p.cardSolid, border: Border(top: BorderSide(color: p.hairline))),
              child: Row(children: [
                Expanded(
                  child: Text(summary.isEmpty ? 'Pick the options' : summary, maxLines: 2, overflow: TextOverflow.ellipsis,
                      style: ui(size: 13, weight: FontWeight.w600, color: p.textSecondary)),
                ),
                const SizedBox(width: 12),
                FilledButton.icon(
                  onPressed: _complete ? () => Navigator.of(context).pop(_picks) : null,
                  icon: const Icon(Icons.add_rounded),
                  label: Text('Add · ${Money.plain(product.mrp + _extra)}'),
                  style: FilledButton.styleFrom(
                    minimumSize: const Size(0, 50),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    textStyle: ui(size: 15, weight: FontWeight.w800, color: Colors.white),
                  ),
                ),
              ]),
            ),
          ],
        ),
      ),
    );
  }

  Widget _group(AstraPalette p, _Slot s) {
    final g = s.group;
    final body = Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        Flexible(
          child: Text('${s.depth > 0 ? '${s.parentName} · ' : ''}${g.name}'.toUpperCase(), maxLines: 1, overflow: TextOverflow.ellipsis,
              style: ui(size: 11.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.9)),
        ),
        if (g.rule.isNotEmpty) ...[
        const SizedBox(width: 8),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
          decoration: BoxDecoration(
            color: g.required ? AstraPalette.danger.withValues(alpha: 0.12) : p.hairline,
            borderRadius: BorderRadius.circular(99),
          ),
          child: Text(g.rule, style: ui(size: 11, weight: FontWeight.w700, color: g.required ? AstraPalette.danger : p.textSecondary)),
        ),
        ],
      ]),
      const SizedBox(height: 10),
      Wrap(spacing: 8, runSpacing: 8, children: [for (final o in g.items) _chip(p, s, o)]),
    ]);
    if (s.depth == 0) return Padding(padding: const EdgeInsets.only(bottom: 18), child: body);
    return Container(
      margin: EdgeInsets.only(left: 14.0 * s.depth, bottom: 18),
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
      decoration: BoxDecoration(
        color: p.primary.withValues(alpha: 0.07),
        border: Border(left: BorderSide(color: p.primary, width: 3)),
        borderRadius: const BorderRadius.horizontal(right: Radius.circular(14)),
      ),
      child: body,
    );
  }

  Widget _chip(AstraPalette p, _Slot s, OptionItem o) {
    final on = _isPicked(s, o);
    final multi = s.group.multi;
    final dim = !on && _isFull(s);
    final solid = on && !multi;
    final fg = solid ? Colors.white : p.ink;
    final radius = BorderRadius.circular(16);
    return Opacity(
      opacity: dim ? 0.4 : 1,
      child: Material(
        color: solid ? p.primary : (on ? p.primary.withValues(alpha: 0.08) : p.cardSolid),
        shape: RoundedRectangleBorder(
          borderRadius: radius,
          side: BorderSide(color: on ? p.primary : p.cardBorder, width: 1.5),
        ),
        child: InkWell(
          borderRadius: radius,
          onTap: () => _toggle(s, o),
          child: ConstrainedBox(
            constraints: const BoxConstraints(minHeight: 50),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                if (multi) ...[
                  Icon(on ? Icons.check_box_rounded : Icons.check_box_outline_blank_rounded, size: 20, color: on ? p.primary : p.textMuted),
                  const SizedBox(width: 8),
                ],
                Flexible(child: Text(o.name, style: ui(size: 15, weight: FontWeight.w800, color: fg))),
                if (o.price != 0) ...[
                  const SizedBox(width: 8),
                  Text('+${Money.plain(o.price)}', style: ui(size: 12, weight: FontWeight.w700, color: solid ? Colors.white70 : p.textMuted)),
                ],
              ]),
            ),
          ),
        ),
      ),
    );
  }
}

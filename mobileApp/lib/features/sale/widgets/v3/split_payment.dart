import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// The icon for a payment method — the one picked for its POS button on the
/// web (same keys as `posPaymentButtonIcons()`), otherwise one from its name.
IconData paymentMethodIcon(PaymentMethod method) {
  final picked = switch (method.icon) {
    'money' => Icons.payments_outlined,
    'credit-card' => Icons.credit_card,
    'university' => Icons.account_balance_outlined,
    'exchange' => Icons.swap_horiz,
    'bolt' => Icons.bolt,
    'mobile' => Icons.smartphone,
    'qrcode' => Icons.qr_code_2,
    'google-wallet' => Icons.account_balance_wallet_outlined,
    'paper-plane' => Icons.send_outlined,
    'gift' => Icons.card_giftcard,
    _ => null,
  };
  if (picked != null) return picked;
  final name = '${method.slug ?? ''} ${method.name}'.toLowerCase();
  if (name.contains('cash')) return Icons.payments_outlined;
  if (name.contains('bank') || name.contains('transfer')) return Icons.account_balance_outlined;
  return Icons.credit_card;
}

/// How one method looks across the split — its swatch and icon. The colour
/// follows the method's place in [methods], so it stays put as rows come and
/// go; the student wallet is always green.
({Color color, IconData icon}) _look(AstraPalette p, List<PaymentMethod> methods, int methodId, int? walletId) {
  if (methodId == walletId) return (color: AstraPalette.success, icon: Icons.school_outlined);
  final at = methods.indexWhere((m) => m.id == methodId);
  final swatches = [
    p.primary,
    p.accent,
    p.goldText,
    p.primaryDark,
    Color.lerp(p.primary, AstraPalette.success, 0.5)!,
  ];
  final method = at < 0 ? null : methods[at];
  return (
    color: swatches[(at < 0 ? 0 : at) % swatches.length],
    icon: method == null ? Icons.credit_card : paymentMethodIcon(method),
  );
}

Color _track(AstraPalette p) => p.isDark ? Colors.white.withValues(alpha: 0.06) : Colors.black.withValues(alpha: 0.045);

// ---- The panel ---------------------------------------------------------------

/// The split, edited in place: every available method as a chip above the
/// payment summary — tap one in or out — then a line per method with what it
/// pays. Tapping a line's amount opens the keypad ([onEdit]).
///
/// [wrapChips] lays the chips over several rows (a tall tablet rail); otherwise
/// they scroll sideways on one, so the summary keeps its height.
class SplitPaymentPanel extends StatelessWidget {
  const SplitPaymentPanel({
    super.key,
    required this.total,
    required this.rows,
    required this.methods,
    required this.onToggle,
    required this.onEdit,
    required this.onEven,
    this.walletId,
    this.walletCap,
    this.wrapChips = false,
  });

  final double total;
  final List<CustomPayment> rows;
  final List<PaymentMethod> methods;
  final ValueChanged<PaymentMethod> onToggle;
  final ValueChanged<CustomPayment> onEdit;
  final VoidCallback onEven;
  final int? walletId;
  final double? walletCap;
  final bool wrapChips;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final chips = [
      for (final m in methods)
        _MethodChip(
          method: m,
          icon: _look(p, methods, m.id, walletId).icon,
          wallet: m.id == walletId,
          on: rows.any((r) => r.methodId == m.id),
          onTap: () => onToggle(m),
        ),
    ];
    final even = rows.length > 1 ? _EvenPill(dim: !rows.any((r) => r.typed), onTap: onEven) : null;

    return Container(
      padding: const EdgeInsets.fromLTRB(10, 10, 10, 9),
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(16),
        boxShadow: context.astraTheme.softShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // The methods and Even on one line — tap a chip in or out.
          if (wrapChips)
            Wrap(spacing: 6, runSpacing: 6, crossAxisAlignment: WrapCrossAlignment.center, children: [...chips, ?even])
          else
            Row(
              children: [
                Expanded(
                  child: SizedBox(
                    height: 30,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      padding: EdgeInsets.zero,
                      itemCount: chips.length,
                      separatorBuilder: (_, __) => const SizedBox(width: 6),
                      itemBuilder: (_, i) => chips[i],
                    ),
                  ),
                ),
                if (even != null) ...[const SizedBox(width: 6), even],
              ],
            ),
          const SizedBox(height: 9),
          if (rows.isEmpty)
            Expanded(child: _EmptySplit(palette: p))
          else
            Expanded(
              child: LayoutBuilder(
                // The equation when the panel is tall enough for its slots (a
                // tablet rail, most phones); one-line rows when it is not.
                builder: (context, c) => c.maxHeight >= _equationHeight(c.maxWidth, rows.length)
                    ? _equation(p)
                    : _compactRows(p),
              ),
            ),
        ],
      ),
    );
  }

  /// What the equation layout needs: the total line, the slots, the share
  /// bar and the ledger.
  static double _equationHeight(double width, int count) => _Slots.heightFor(width, count) + 104;

  Widget _equation(AstraPalette p) {
    final paid = round2(rows.fold(0.0, (a, r) => a + r.amount));
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.baseline,
          textBaseline: TextBaseline.alphabetic,
          children: [
            Flexible(
              child: FittedBox(
                fit: BoxFit.scaleDown,
                alignment: Alignment.centerLeft,
                child: Text(Money.of(total), style: serif(size: 24, color: p.ink)),
              ),
            ),
            const SizedBox(width: 8),
            Text('=', style: serif(size: 20, color: p.textMuted)),
            const Spacer(),
            Text('Tap a slot to change it', style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
          ],
        ),
        const SizedBox(height: 8),
        _Slots(
          slots: [
            for (final r in rows)
              _SlotData(
                methodId: r.methodId,
                name: r.methodName,
                amount: Money.plain(r.amount),
                tag: _splitTag(r, r.methodId == walletId ? walletCap : null),
                tagColor: _splitTagColor(r, r.methodId == walletId ? walletCap : null, p),
              ),
          ],
          methods: methods,
          walletId: walletId,
          active: null,
          onTap: (id) => onEdit(rows.firstWhere((r) => r.methodId == id)),
          onRemove: (id) => onToggle(PaymentMethod(id: id, name: rows.firstWhere((r) => r.methodId == id).methodName)),
        ),
        const SizedBox(height: 10),
        _ShareBar(total: total, rows: rows, colorOf: (id) => _look(p, methods, id, walletId).color),
        const Spacer(),
        _Ledger(total: total, paid: paid),
      ],
    );
  }

  Widget _compactRows(AstraPalette p) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _ShareBar(total: total, rows: rows, colorOf: (id) => _look(p, methods, id, walletId).color),
          const SizedBox(height: 4),
          Expanded(
            child: ListView.separated(
              padding: EdgeInsets.zero,
              itemCount: rows.length,
              separatorBuilder: (_, __) => Divider(height: 1, color: p.hairline),
              itemBuilder: (_, i) {
                final row = rows[i];
                final look = _look(p, methods, row.methodId, walletId);
                return _SplitLine(
                  row: row,
                  color: look.color,
                  icon: look.icon,
                  walletCap: row.methodId == walletId ? walletCap : null,
                  onEdit: () => onEdit(row),
                  onRemove: () => onToggle(PaymentMethod(id: row.methodId, name: row.methodName)),
                );
              },
            ),
          ),
          const SizedBox(height: 6),
          _Ledger(total: total, paid: round2(rows.fold(0.0, (a, r) => a + r.amount))),
        ],
      );
}

/// A split row's tag: SET (typed) or AUTO (shares the rest); the wallet says
/// what the card can spend instead, or that it is over it.
String _splitTag(CustomPayment r, double? walletCap) {
  if (walletCap != null) return r.amount > walletCap + 0.004 ? 'OVER CARD' : 'MAX ${Money.plain(walletCap)}';
  return r.typed ? 'SET' : 'AUTO · SHARES REST';
}

Color _splitTagColor(CustomPayment r, double? walletCap, AstraPalette p) {
  if (walletCap != null && r.amount > walletCap + 0.004) return AstraPalette.danger;
  return r.typed ? p.goldText : p.textMuted;
}

class _MethodChip extends StatelessWidget {
  const _MethodChip({required this.method, required this.icon, required this.wallet, required this.on, required this.onTap});
  final PaymentMethod method;
  final IconData icon;
  final bool wallet;
  final bool on;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        height: 30,
        padding: const EdgeInsets.fromLTRB(6, 0, 10, 0),
        decoration: BoxDecoration(
          color: on ? p.tint : p.card,
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: on ? p.primary : p.hairline, width: 1.4),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            on
                ? Container(
                    width: 17,
                    height: 17,
                    decoration: BoxDecoration(color: p.primary, shape: BoxShape.circle),
                    child: const Icon(Icons.check_rounded, size: 12, color: Colors.white),
                  )
                : Icon(Icons.add_rounded, size: 16, color: p.textMuted),
            const SizedBox(width: 5),
            Icon(icon, size: 15, color: wallet ? AstraPalette.success : p.primary),
            const SizedBox(width: 5),
            Text(method.name, style: ui(size: 11.5, weight: FontWeight.w800, color: on ? p.primary : p.textSecondary)),
          ],
        ),
      ),
    );
  }
}

/// "Even" — every line back to an equal share. Quiet when it already is.
class _EvenPill extends StatelessWidget {
  const _EvenPill({required this.dim, required this.onTap});
  final bool dim;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final color = dim ? p.textMuted : p.primary;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
        decoration: BoxDecoration(color: dim ? _track(p) : p.tint, borderRadius: BorderRadius.circular(999)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.balance_rounded, size: 13, color: color),
            const SizedBox(width: 4),
            Text('Even', style: ui(size: 10.5, weight: FontWeight.w800, color: color)),
          ],
        ),
      ),
    );
  }
}

/// Each method's share of the total as a thin coloured bar; what's still owed
/// shows as track.
class _ShareBar extends StatelessWidget {
  const _ShareBar({required this.total, required this.rows, required this.colorOf});
  final double total;
  final List<CustomPayment> rows;
  final Color Function(int methodId) colorOf;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final paid = rows.fold(0.0, (a, r) => a + r.amount);
    final owed = total - paid;
    final parts = [
      for (final r in rows)
        if (r.amount > 0) (flex: (r.amount * 100).round(), color: colorOf(r.methodId)),
      if (owed > 0.004) (flex: (owed * 100).round(), color: _track(p)),
    ];
    return ClipRRect(
      borderRadius: BorderRadius.circular(9),
      child: SizedBox(
        height: 6,
        child: parts.isEmpty
            ? ColoredBox(color: _track(p))
            : Row(
                children: [
                  for (final (i, part) in parts.indexed) ...[
                    if (i > 0) const SizedBox(width: 2),
                    Expanded(flex: part.flex, child: ColoredBox(color: part.color)),
                  ],
                ],
              ),
      ),
    );
  }
}

/// One method in the split, on one line: what it pays, and whether that was
/// typed ("SET") or is its share of the rest ("AUTO"). The wallet says what the
/// card can spend instead, and turns red past it. Tap the amount to key it.
class _SplitLine extends StatelessWidget {
  const _SplitLine({
    required this.row,
    required this.color,
    required this.icon,
    required this.onEdit,
    required this.onRemove,
    this.walletCap,
  });
  final CustomPayment row;
  final Color color;
  final IconData icon;
  final double? walletCap;
  final VoidCallback onEdit;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final cap = walletCap;
    final over = cap != null && row.amount > cap + 0.004;
    final tag = cap != null
        ? (over ? 'OVER CARD' : 'MAX ${Money.plain(cap)}')
        : row.typed
            ? 'SET'
            : 'AUTO';
    final tagColor = over ? AstraPalette.danger : (row.typed ? p.goldText : p.textMuted);

    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onEdit,
      child: SizedBox(
        height: 40,
        child: Row(
          children: [
            Container(
              width: 24,
              height: 24,
              decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(7)),
              child: Icon(icon, size: 13, color: Colors.white),
            ),
            const SizedBox(width: 8),
            Flexible(
              child: Text(row.methodName,
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
            ),
            const SizedBox(width: 6),
            Text(tag, style: ui(size: 8.5, weight: FontWeight.w800, color: tagColor, letterSpacing: 0.6)),
            const Spacer(),
            // Underlined like an editable figure — the whole line opens the keypad.
            Container(
              padding: const EdgeInsets.only(bottom: 1),
              decoration: BoxDecoration(
                border: Border(bottom: BorderSide(color: p.primary.withValues(alpha: 0.5), width: 1.5)),
              ),
              child: Text(Money.plain(row.amount), style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
            ),
            GestureDetector(
              behavior: HitTestBehavior.opaque,
              onTap: onRemove,
              child: SizedBox(
                width: 30,
                height: 40,
                child: Icon(Icons.close_rounded, size: 15, color: p.textMuted),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Paid against the total, and what's left, on one line.
class _Ledger extends StatelessWidget {
  const _Ledger({required this.total, required this.paid});
  final double total;
  final double paid;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final balance = round2(total - paid);
    final settled = balance.abs() < 0.005;
    final color = settled ? AstraPalette.success : balance > 0 ? AstraPalette.danger : p.warnText;
    final label = settled ? 'Settled' : balance > 0 ? 'Due ${Money.plain(balance)}' : 'Over ${Money.plain(-balance)}';
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(color: _track(p), borderRadius: BorderRadius.circular(10)),
      child: Row(
        children: [
          Expanded(
            child: Text('${Money.plain(paid)} of ${Money.plain(total)}',
                maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12, weight: FontWeight.w700, color: p.textSecondary)),
          ),
          Icon(settled ? Icons.check_circle_rounded : Icons.warning_rounded, size: 14, color: color),
          const SizedBox(width: 4),
          Text(label, style: ui(size: 12, weight: FontWeight.w800, color: color)),
        ],
      ),
    );
  }
}

class _EmptySplit extends StatelessWidget {
  const _EmptySplit({required this.palette});
  final AstraPalette palette;

  @override
  Widget build(BuildContext context) {
    final p = palette;
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(13),
        border: Border.all(color: p.hairline, width: 1.5),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.touch_app_outlined, size: 26, color: p.primary),
          const SizedBox(height: 6),
          Text('Tap the methods above', style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
          const SizedBox(height: 2),
          Text('Two methods split the bill evenly.', style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
        ],
      ),
    );
  }
}

// ---- Sheets ------------------------------------------------------------------

/// A phone gets a bottom sheet. A tablet ([railWidth] set) gets a card floating
/// over the pay rail, so the receipt beside it stays in view.
Future<T?> _showSplitSurface<T>(BuildContext context, {double? railWidth, required Widget Function(bool floating) builder}) {
  if (railWidth == null) {
    return showModalBottomSheet<T>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      barrierColor: Colors.black.withValues(alpha: 0.45),
      builder: (_) => builder(false),
    );
  }
  return showGeneralDialog<T>(
    context: context,
    barrierDismissible: true,
    barrierLabel: 'Close',
    barrierColor: Colors.black.withValues(alpha: 0.45),
    transitionDuration: const Duration(milliseconds: 220),
    pageBuilder: (_, __, ___) => SafeArea(
      child: Align(
        alignment: Alignment.bottomRight,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: SizedBox(
            width: railWidth - 28,
            child: Material(type: MaterialType.transparency, child: builder(true)),
          ),
        ),
      ),
    ),
    transitionBuilder: (_, animation, __, child) {
      final curved = CurvedAnimation(parent: animation, curve: Curves.easeOutCubic);
      return FadeTransition(
        opacity: curved,
        child: SlideTransition(
          position: Tween(begin: const Offset(0, 0.12), end: Offset.zero).animate(curved),
          child: child,
        ),
      );
    },
  );
}

class _SheetFrame extends StatelessWidget {
  const _SheetFrame({required this.floating, required this.child});
  final bool floating;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * (floating ? 0.92 : 0.9)),
      decoration: BoxDecoration(
        color: p.canvas,
        borderRadius: floating ? BorderRadius.circular(24) : const BorderRadius.vertical(top: Radius.circular(26)),
        boxShadow: floating ? context.astraTheme.floatShadow(p.primaryDark) : null,
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(16, floating ? 14 : 10, 16, floating ? 16 : 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (!floating) ...[
                Center(
                  child: Container(
                    width: 38,
                    height: 4,
                    decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3)),
                  ),
                ),
                const SizedBox(height: 12),
              ],
              Flexible(child: child),
            ],
          ),
        ),
      ),
    );
  }
}

// ---- The split card ("Equation") ---------------------------------------------

/// What the split card edits — the ticket or a refund — as callbacks over the
/// bloc that holds it. Every write goes straight to the bloc, so the panel
/// underneath moves as the card is used.
class SplitTarget<S> {
  const SplitTarget({
    required this.bloc,
    required this.read,
    required this.onSplit,
    required this.onToggle,
    required this.onAmount,
    required this.onEven,
  });
  final StateStreamable<S> bloc;

  /// The total, and the split rows — empty until two methods are picked.
  final ({double total, List<CustomPayment> rows}) Function(S state) read;
  final ValueChanged<List<PaymentMethod>> onSplit;
  final ValueChanged<PaymentMethod> onToggle;
  final void Function(int methodId, double amount) onAmount;
  final VoidCallback onEven;
}

/// The ticket as a [SplitTarget].
SplitTarget<CartState> cartSplitTarget(CartCubit cart) => SplitTarget<CartState>(
      bloc: cart,
      read: (s) => (total: s.total, rows: s.payMode == PayMode.custom ? s.customPayments : const <CustomPayment>[]),
      onSplit: cart.splitBetween,
      onToggle: cart.toggleSplitMethod,
      onAmount: cart.setSplitAmount,
      onEven: cart.evenSplit,
    );

/// One card for the whole split: **total = [method] + [method]**. Tap a method
/// to fill the next slot — the second one applies an even split; tap a slot to
/// key its amount on the keypad that opens in the same card, and the other
/// slots re-balance as it is typed. [editMethodId] opens straight on that
/// slot's keypad (tapping an amount in the panel), and its Done closes the card.
///
/// [noun] titles the card ("Split the payment"/"Split the refund"); [verb]
/// says what a method does with its share ("pays"/"refunds").
Future<void> showSplitSheet<S>(
  BuildContext context, {
  required SplitTarget<S> target,
  required List<PaymentMethod> methods,
  int? walletId,
  double? walletCap,
  double? railWidth,
  String noun = 'payment',
  String verb = 'pays',
  int? editMethodId,
}) =>
    _showSplitSurface<void>(
      context,
      railWidth: railWidth,
      builder: (floating) => _SplitSheet<S>(
        target: target,
        methods: methods,
        walletId: walletId,
        walletCap: walletCap,
        floating: floating,
        noun: noun,
        verb: verb,
        editMethodId: editMethodId,
      ),
    );

class _SplitSheet<S> extends StatefulWidget {
  const _SplitSheet({
    required this.target,
    required this.methods,
    required this.floating,
    required this.noun,
    required this.verb,
    this.walletId,
    this.walletCap,
    this.editMethodId,
  });
  final SplitTarget<S> target;
  final List<PaymentMethod> methods;
  final int? walletId;
  final double? walletCap;
  final bool floating;
  final String noun;
  final String verb;
  final int? editMethodId;

  @override
  State<_SplitSheet<S>> createState() => _SplitSheetState<S>();
}

class _SplitSheetState<S> extends State<_SplitSheet<S>> {
  /// The first pick, held here until a second one makes it a split.
  final List<PaymentMethod> _picked = [];

  /// The slot whose keypad is open; null shows the methods to add instead.
  late int? _active = widget.editMethodId;

  /// What has been keyed since the slot opened; null shows its amount as it
  /// stands, and the first key replaces it rather than appending.
  String? _typed;

  void _close() => Navigator.of(context).pop();

  void _tapMethod(PaymentMethod m, List<CustomPayment> rows) {
    if (rows.isNotEmpty) return widget.target.onToggle(m);
    setState(() => _picked.contains(m) ? _picked.remove(m) : _picked.add(m));
    if (_picked.length == 2) {
      widget.target.onSplit(List.of(_picked));
      setState(_picked.clear);
    }
  }

  void _openSlot(int methodId) => setState(() {
        _active = methodId;
        _typed = null;
      });

  void _key(String key) {
    final id = _active;
    if (id == null) return;
    final decimals = Money.decimals;
    var next = _typed ?? '';
    if (key == '<') {
      next = next.isEmpty ? '' : next.substring(0, next.length - 1);
    } else if (key == '.') {
      if (decimals == 0 || next.contains('.')) return;
      next = '${next.isEmpty ? '0' : next}.';
    } else {
      final dot = next.indexOf('.');
      if (dot >= 0 && next.length - dot - 1 >= decimals) return;
      next = next == '0' ? key : next + key;
    }
    setState(() => _typed = next);
    widget.target.onAmount(id, double.tryParse(next) ?? 0);
  }

  void _set(double amount) {
    final id = _active;
    if (id == null) return;
    setState(() => _typed = null);
    widget.target.onAmount(id, double.parse(amount.toStringAsFixed(Money.decimals)));
  }

  void _even() {
    setState(() => _typed = null);
    widget.target.onEven();
  }

  /// Done on the keypad: back to the methods — or, when the card was opened on
  /// this slot, closed.
  void _done() => widget.editMethodId != null
      ? _close()
      : setState(() {
          _active = null;
          _typed = null;
        });

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return _SheetFrame(
      floating: widget.floating,
      child: BlocBuilder<StateStreamable<S>, S>(
        bloc: widget.target.bloc,
        builder: (context, state) {
          final view = widget.target.read(state);
          final rows = view.rows;
          final total = view.total;
          final activeRow = rows.where((r) => r.methodId == _active).firstOrNull;
          Color colorOf(int id) => _look(p, widget.methods, id, widget.walletId).color;
          final subtitle = activeRow != null
              ? '${activeRow.methodName} ${widget.verb} — ${rows.length == 2 ? 'the other re-balances' : 'the others re-balance'}'
              : rows.isEmpty
                  ? 'Tap two methods — it splits evenly'
                  : 'Tap a slot to change its amount';

          return SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Split the ${widget.noun}', style: serif(size: 20, color: p.ink)),
                          const SizedBox(height: 2),
                          Text(subtitle,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
                        ],
                      ),
                    ),
                    GestureDetector(
                      onTap: _close,
                      child: Container(
                        width: 34,
                        height: 34,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(11),
                          border: Border.all(color: p.hairline),
                        ),
                        child: Icon(Icons.close_rounded, size: 17, color: p.textMuted),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                // The equation's left side: what the methods add up to.
                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.baseline,
                    textBaseline: TextBaseline.alphabetic,
                    children: [
                      Text(Money.of(total), style: serif(size: 28, color: p.ink)),
                      const SizedBox(width: 8),
                      Text('=', style: serif(size: 24, color: p.textMuted)),
                    ],
                  ),
                ),
                const SizedBox(height: 10),
                _Slots(
                  slots: [
                    for (final r in rows)
                      _SlotData(
                        methodId: r.methodId,
                        name: r.methodName,
                        amount: r.methodId == _active && _typed != null ? (_typed!.isEmpty ? '0' : _typed!) : Money.plain(r.amount),
                        tag: _splitTag(r, r.methodId == widget.walletId ? widget.walletCap : null),
                        tagColor: _splitTagColor(r, r.methodId == widget.walletId ? widget.walletCap : null, p),
                      ),
                    for (final m in _picked)
                      _SlotData(methodId: m.id, name: m.name, amount: '—', tag: 'PICK ONE MORE', tagColor: p.textMuted),
                  ],
                  methods: widget.methods,
                  walletId: widget.walletId,
                  active: activeRow?.methodId,
                  onTap: (id) => rows.any((r) => r.methodId == id) ? _openSlot(id) : null,
                  onRemove: (id) {
                    final m = widget.methods.where((m) => m.id == id).firstOrNull ?? PaymentMethod(id: id, name: '');
                    if (_active == id) setState(() => _active = null);
                    _tapMethod(m, rows);
                  },
                ),
                if (rows.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  _ShareBar(total: total, rows: rows, colorOf: colorOf),
                ],
                const SizedBox(height: 12),
                if (activeRow != null) ...[
                  Row(
                    children: [
                      _QuickKey(label: 'Half · ${Money.plain(total / 2)}', onTap: () => _set(total / 2)),
                      const SizedBox(width: 6),
                      _QuickKey(label: 'All the rest', onTap: () {
                        final typedOthers = rows.where((r) => r.methodId != activeRow.methodId && r.typed).fold(0.0, (a, r) => a + r.amount);
                        _set(total - typedOthers < 0 ? 0 : total - typedOthers);
                      }),
                      const SizedBox(width: 6),
                      _QuickKey(label: 'Even', onTap: _even),
                    ],
                  ),
                  const SizedBox(height: 8),
                  _Keypad(onKey: _key),
                ] else ...[
                  Text(rows.length + _picked.length < 2 ? 'ADD A METHOD' : 'ADD ANOTHER',
                      style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.2)),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final m in widget.methods)
                        _AddChip(
                          name: m.name,
                          look: _look(p, widget.methods, m.id, widget.walletId),
                          added: rows.any((r) => r.methodId == m.id) || _picked.contains(m),
                          onTap: () => _tapMethod(m, rows),
                        ),
                    ],
                  ),
                ],
                if (rows.length >= 2) ...[
                  const SizedBox(height: 12),
                  _Ledger(total: total, paid: round2(rows.fold(0.0, (a, r) => a + r.amount))),
                ],
                const SizedBox(height: 12),
                if (activeRow != null)
                  AstraButton(label: 'Done', icon: Icons.check_rounded, onTap: _done)
                else
                  AstraButton(
                    label: rows.length >= 2 ? 'Apply split · ${rows.length} ways' : 'Pick two methods',
                    icon: Icons.call_split_rounded,
                    gold: true,
                    onTap: rows.length >= 2 ? _close : null,
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

}

class _SlotData {
  const _SlotData({required this.methodId, required this.name, required this.amount, required this.tag, required this.tagColor});
  final int methodId;
  final String name;
  final String amount;
  final String tag;
  final Color tagColor;
}

/// The equation's right side: one slot per method, `+` between them, with an
/// empty "Pick a method" slot until there are two. Too narrow for a row (a
/// phone, three or more ways) and they sit two to a line instead.
class _Slots extends StatelessWidget {
  const _Slots({
    required this.slots,
    required this.methods,
    required this.active,
    required this.onTap,
    required this.onRemove,
    this.walletId,
  });
  final List<_SlotData> slots;
  final List<PaymentMethod> methods;
  final int? walletId;
  final int? active;
  final ValueChanged<int> onTap;
  final ValueChanged<int> onRemove;

  static const _plus = 22.0;
  static const _slotHeight = 82.0;

  /// Whether [count] slots (at least two) fit on one line of [width].
  static bool _inRow(double width, int count) => (width - _plus * (count - 1)) / count >= 118;

  /// The height [count] slots take at [width] — one line, or two to a line.
  static double heightFor(double width, int count) {
    final n = count < 2 ? 2 : count;
    if (_inRow(width, n)) return _slotHeight;
    final lines = (n / 2).ceil();
    return lines * _slotHeight + (lines - 1) * 8;
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final cards = [
      for (final s in slots)
        _Slot(
          data: s,
          look: _look(p, methods, s.methodId, walletId),
          active: s.methodId == active,
          onTap: () => onTap(s.methodId),
          onRemove: () => onRemove(s.methodId),
        ),
      for (var i = slots.length; i < 2; i++) const _EmptySlot(),
    ];
    return LayoutBuilder(
      builder: (context, c) {
        if (!_inRow(c.maxWidth, cards.length)) {
          final w = (c.maxWidth - 8) / 2;
          return Wrap(spacing: 8, runSpacing: 8, children: [for (final card in cards) SizedBox(width: w, child: card)]);
        }
        return Row(
          children: [
            for (final (i, card) in cards.indexed) ...[
              if (i > 0) SizedBox(width: _plus, child: Center(child: Text('+', style: serif(size: 20, color: p.textMuted)))),
              Expanded(child: card),
            ],
          ],
        );
      },
    );
  }
}

class _Slot extends StatelessWidget {
  const _Slot({required this.data, required this.look, required this.active, required this.onTap, required this.onRemove});
  final _SlotData data;
  final ({Color color, IconData icon}) look;
  final bool active;
  final VoidCallback onTap;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        height: _Slots._slotHeight,
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: active ? p.primary : Colors.transparent, width: 1.5),
          boxShadow: active ? [BoxShadow(color: p.primary.withValues(alpha: 0.16), spreadRadius: 4)] : context.astraTheme.softShadow,
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(14.5),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // The method's colour, down the slot's edge.
              Container(width: 4, color: look.color),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(10, 9, 4, 8),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            width: 22,
                            height: 22,
                            decoration: BoxDecoration(color: look.color, borderRadius: BorderRadius.circular(7)),
                            child: Icon(look.icon, size: 12, color: Colors.white),
                          ),
                          const SizedBox(width: 7),
                          Expanded(
                            child: Text(data.name,
                                maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12, weight: FontWeight.w800, color: p.ink)),
                          ),
                          GestureDetector(
                            onTap: onRemove,
                            behavior: HitTestBehavior.opaque,
                            child: Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 6),
                              child: Icon(Icons.close_rounded, size: 14, color: p.textMuted),
                            ),
                          ),
                        ],
                      ),
                      const Spacer(),
                      FittedBox(
                        fit: BoxFit.scaleDown,
                        alignment: Alignment.centerLeft,
                        child: Text(data.amount, maxLines: 1, style: serif(size: 21, color: active ? p.primary : p.ink)),
                      ),
                      Text(data.tag,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: ui(size: 8.5, weight: FontWeight.w800, color: data.tagColor, letterSpacing: 0.6)),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EmptySlot extends StatelessWidget {
  const _EmptySlot();

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      height: 82,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: p.hairline, width: 1.5),
      ),
      child: Text('Pick a method', style: ui(size: 12, weight: FontWeight.w700, color: p.textMuted)),
    );
  }
}

/// A method to add: its colour swatch and name. Already in the split, it fades.
class _AddChip extends StatelessWidget {
  const _AddChip({required this.name, required this.look, required this.added, required this.onTap});
  final String name;
  final ({Color color, IconData icon}) look;
  final bool added;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: AnimatedOpacity(
        duration: const Duration(milliseconds: 150),
        opacity: added ? 0.5 : 1,
        child: Container(
          height: 40,
          padding: const EdgeInsets.fromLTRB(6, 0, 13, 0),
          decoration: BoxDecoration(
            color: p.card,
            borderRadius: BorderRadius.circular(13),
            border: Border.all(color: added ? look.color : Colors.transparent, width: 1.5),
            boxShadow: added ? null : context.astraTheme.softShadow,
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 28,
                height: 28,
                decoration: BoxDecoration(color: look.color, borderRadius: BorderRadius.circular(9)),
                child: Icon(added ? Icons.check_rounded : look.icon, size: 15, color: Colors.white),
              ),
              const SizedBox(width: 8),
              Text(name, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
            ],
          ),
        ),
      ),
    );
  }
}

class _Keypad extends StatelessWidget {
  const _Keypad({required this.onKey});
  final ValueChanged<String> onKey;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final keyHeight = MediaQuery.sizeOf(context).height < 720 ? 40.0 : 46.0;
    return Column(
      children: [
        for (final (r, keys) in const [
          ['1', '2', '3'],
          ['4', '5', '6'],
          ['7', '8', '9'],
          ['.', '0', '<'],
        ].indexed) ...[
          if (r > 0) const SizedBox(height: 7),
          Row(
            children: [
              for (final (i, k) in keys.indexed) ...[
                if (i > 0) const SizedBox(width: 7),
                Expanded(
                  child: GestureDetector(
                    onTap: () => onKey(k),
                    child: Container(
                      height: keyHeight,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: p.card,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: context.astraTheme.softShadow,
                      ),
                      child: k == '<'
                          ? const Icon(Icons.backspace_outlined, size: 19, color: AstraPalette.danger)
                          : Text(k,
                              style: ui(
                                size: 19,
                                weight: FontWeight.w700,
                                color: k == '.' && Money.decimals == 0 ? p.textMuted : p.ink,
                              )),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ],
      ],
    );
  }
}

class _QuickKey extends StatelessWidget {
  const _QuickKey({required this.label, required this.onTap});
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          height: 34,
          alignment: Alignment.center,
          padding: const EdgeInsets.symmetric(horizontal: 6),
          decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(10)),
          child: Text(label,
              maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 11.5, weight: FontWeight.w800, color: p.primary)),
        ),
      ),
    );
  }
}

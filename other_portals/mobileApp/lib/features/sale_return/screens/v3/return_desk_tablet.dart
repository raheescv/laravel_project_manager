import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart' show CustomPayment, PayMode;
import 'package:invo/features/sale/widgets/v3/split_payment.dart';
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/features/sale_return/logic/return_ops_cubit/return_ops_cubit.dart';
import 'package:invo/features/sale_return/screens/v3/new_sale_return_screen.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Tablet "Receipt Mirror" for a return: the original invoice as paper on the
/// left (tick a line, its stepper opens on the line), the return slip building
/// up on the right with the refund method and the button under it. Items and
/// refund are one screen here — the phone's separate Review step is skipped.
///
/// No header band: each paper carries its own actions (back + Return all on the
/// invoice, Clear + close on the slip), so the papers get the full height.
class ReturnDeskTablet extends StatefulWidget {
  const ReturnDeskTablet({super.key});
  @override
  State<ReturnDeskTablet> createState() => _ReturnDeskTabletState();
}

class _ReturnDeskTabletState extends State<ReturnDeskTablet> {
  /// Repository access for this flow (§10).
  final _ops = ReturnOpsCubit();
  bool _busy = false;
  List<PaymentMethod> _methods = [];

  /// The slip column's width — the split sheets float over it, clear of the invoice.
  double? _slipWidth;

  @override
  void initState() {
    super.initState();
    _loadMethods();
  }

  Future<void> _loadMethods() async {
    try {
      final methods = await _ops.paymentMethods();
      if (mounted) setState(() => _methods = methods);
    } catch (_) {
      // Cash/Card/To account still work without the configured list.
    }
  }

  /// Split. With only two methods, the refund splits evenly between them at
  /// once; with more, the split card asks which. Once split, the panel edits it.
  Future<void> _startSplit() async {
    final draft = context.read<ReturnDraftCubit>();
    if (draft.payMode == PayMode.custom) return;
    if (_methods.isEmpty) {
      await _loadMethods();
      if (_methods.isEmpty) {
        _error('No payment methods are configured for this business.');
        return;
      }
    }
    if (!mounted) return;
    if (_methods.length <= 2) return draft.splitBetween(_methods);
    await _openSplit(draft);
  }

  /// The split card — on [editMethodId]'s keypad when an amount was tapped.
  Future<void> _openSplit(ReturnDraftCubit draft, {int? editMethodId}) => showSplitSheet<ReturnDraftState>(
        context,
        target: SplitTarget<ReturnDraftState>(
          bloc: draft,
          read: (s) => (total: s.total, rows: s.payMode == PayMode.custom ? s.customPayments : const <CustomPayment>[]),
          onSplit: draft.splitBetween,
          onToggle: draft.toggleSplitMethod,
          onAmount: draft.setSplitAmount,
          onEven: draft.evenSplit,
        ),
        methods: _methods,
        railWidth: _slipWidth,
        noun: 'refund',
        verb: 'refunds',
        editMethodId: editMethodId,
      );

  Future<void> _refund() async {
    final draft = context.read<ReturnDraftCubit>();
    final editingId = draft.editingReturnId;
    setState(() => _busy = true);
    try {
      final saleReturn = await _ops.saveSaleReturn(editingId, draft.toPayload());
      draft.clear();
      if (mounted) context.pushReplacement(Routes.returnReceipt, extra: saleReturn);
    } on ApiException catch (e) {
      _error(e.message);
    } catch (_) {
      _error(editingId.isEmpty
          ? 'Could not save the return. Please try again.'
          : 'Could not update the return. Please try again.');
    }
    if (mounted) setState(() => _busy = false);
  }

  void _error(String m) {
    if (!mounted) return;
    AstraSnack.error(context, m);
  }

  void _back() => context.canPop() ? context.pop() : context.go(Routes.saleReturnPick);

  @override
  Widget build(BuildContext context) {
    final draft = context.watch<ReturnDraftCubit>();
    final split = draft.payMode == PayMode.custom && draft.customPayments.isNotEmpty;

    return Scaffold(
      body: AstraBackground(
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(18, 14, 18, 14),
            child: LayoutBuilder(
              builder: (context, c) {
                // Narrow (portrait, small tablets): the papers split evenly and
                // the arrow between them gives its width back.
                final wide = c.maxWidth >= 900;
                final bridge = wide ? 52.0 : 12.0;
                final (invoiceFlex, slipFlex) = wide ? (10, 11) : (1, 1);
                _slipWidth = (c.maxWidth - bridge) * slipFlex / (invoiceFlex + slipFlex) + 28;
                return Row(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Expanded(flex: invoiceFlex, child: _InvoicePaper(draft: draft, onBack: _back)),
                    SizedBox(width: bridge, child: wide ? _Bridge(count: draft.count) : null),
                    Expanded(
                      flex: slipFlex,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Expanded(flex: 4, child: _ReturnSlip(draft: draft, compact: split)),
                          const SizedBox(height: 12),
                          if (split)
                            Expanded(
                              flex: 5,
                              child: SplitPaymentPanel(
                                total: draft.total,
                                rows: draft.customPayments,
                                methods: _methods,
                                onToggle: draft.toggleSplitMethod,
                                onEdit: (row) => _openSplit(draft, editMethodId: row.methodId),
                                onEven: draft.evenSplit,
                              ),
                            )
                          else ...[
                            _RefundMethods(draft: draft, onSplit: _startSplit),
                            const SizedBox(height: 8),
                            _RefundStatus(draft: draft),
                          ],
                          const SizedBox(height: 10),
                          AstraButton(
                            label: _ctaLabel(draft),
                            gold: true,
                            busy: _busy,
                            onTap: draft.isEmpty ? null : _refund,
                          ),
                        ],
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
        ),
      ),
    );
  }

  String _ctaLabel(ReturnDraftCubit draft) {
    if (draft.isEmpty) return 'Tick the lines coming back';
    final total = Money.of(draft.total);
    if (draft.isEditing) return 'Update $total';
    if (draft.payMode == PayMode.credit) return 'Put $total on account';
    return draft.balance.abs() < 0.001 ? 'Refund $total' : 'Confirm Anyway';
  }
}

String _qty(double v) => v.toStringAsFixed(v % 1 == 0 ? 0 : 2);

/// A sheet of receipt paper: rounded top, a torn bottom edge.
class _Paper extends StatelessWidget {
  const _Paper({required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return PhysicalShape(
      clipper: const _TornBottomClipper(),
      color: p.cardSolid,
      elevation: 3,
      shadowColor: p.primaryDark.withValues(alpha: 0.3),
      child: Padding(padding: const EdgeInsets.only(bottom: 8), child: child),
    );
  }
}

/// The paper's masthead: a spaced caption over a serif headline and a context
/// line, with the paper's own actions either side.
class _PaperHead extends StatelessWidget {
  const _PaperHead({required this.caption, required this.title, required this.subtitle, this.leading, this.actions = const []});
  final String caption;
  final String title;
  final String subtitle;
  final Widget? leading;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 10, 10, 10),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.hairline))),
      child: Row(
        children: [
          if (leading != null) ...[leading!, const SizedBox(width: 8)] else const SizedBox(width: 6),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(caption, maxLines: 1, overflow: TextOverflow.ellipsis,
                    style: ui(size: 9, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 2)),
                const SizedBox(height: 2),
                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Text(title, maxLines: 1, style: serif(size: 19, color: p.ink)),
                ),
                Text(subtitle, maxLines: 1, overflow: TextOverflow.ellipsis,
                    style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          for (final a in actions) ...[const SizedBox(width: 6), a],
        ],
      ),
    );
  }
}

/// A small action on a paper's head — icon-only, or icon + label.
class _HeadButton extends StatelessWidget {
  const _HeadButton({required this.icon, required this.onTap, this.label, this.tooltip});
  final IconData icon;
  final VoidCallback onTap;
  final String? label;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final button = GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        height: 36,
        constraints: const BoxConstraints(minWidth: 36),
        padding: EdgeInsets.symmetric(horizontal: label == null ? 0 : 10),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: label == null ? Colors.transparent : p.tint,
          borderRadius: BorderRadius.circular(11),
          border: label == null ? Border.all(color: p.hairline) : null,
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 16, color: label == null ? p.ink : p.primary),
            if (label != null) ...[
              const SizedBox(width: 5),
              Text(label!, style: ui(size: 11.5, weight: FontWeight.w800, color: p.primary)),
            ],
          ],
        ),
      ),
    );
    return tooltip == null ? button : Tooltip(message: tooltip!, child: button);
  }
}

class _InvoicePaper extends StatelessWidget {
  const _InvoicePaper({required this.draft, required this.onBack});
  final ReturnDraftCubit draft;
  final VoidCallback onBack;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final date = Dates.human(draft.saleDate);
    final open = draft.lines.where((l) => !l.fullyReturned).length;
    final earlier = draft.lines.fold(0.0, (a, l) => a + l.source.returnedQuantity);
    final canReturnMore = draft.lines.any((l) => !l.fullyReturned && l.returnQty < l.returnable);

    return _Paper(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _PaperHead(
            caption: draft.isEditing ? 'EDIT RETURN · INVOICE' : 'NEW RETURN · INVOICE',
            title: draft.invoiceNo.isEmpty ? '—' : draft.invoiceNo,
            subtitle: [draft.customerName, if (date.isNotEmpty) date].join(' · '),
            leading: _HeadButton(icon: Icons.chevron_left, tooltip: 'Back', onTap: onBack),
            actions: [
              if (canReturnMore) _HeadButton(icon: Icons.done_all_rounded, label: 'Return all', onTap: draft.returnAll),
            ],
          ),
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.fromLTRB(10, 6, 10, 6),
              itemCount: draft.lines.length,
              itemBuilder: (_, i) => _InvoiceLine(draft: draft, line: draft.lines[i]),
            ),
          ),
          Container(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 9),
            decoration: BoxDecoration(border: Border(top: BorderSide(color: p.hairline))),
            child: Row(
              children: [
                Expanded(
                  child: Text('${draft.lines.length} line${draft.lines.length == 1 ? '' : 's'} · $open returnable',
                      maxLines: 1, overflow: TextOverflow.ellipsis,
                      style: ui(size: 11, weight: FontWeight.w700, color: p.textSecondary)),
                ),
                if (earlier > 0)
                  Text('${_qty(earlier)} returned earlier', style: ui(size: 11, weight: FontWeight.w700, color: p.textMuted)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// One sold line, on one row. Tapping it ticks it in (one unit) or out; once
/// in, a stepper takes the price's place — on the row when it is wide enough,
/// under it when not.
class _InvoiceLine extends StatelessWidget {
  const _InvoiceLine({required this.draft, required this.line});
  final ReturnDraftCubit draft;
  final ReturnLine line;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final on = line.isReturning;
    final off = line.fullyReturned;
    final sold = round2(line.unitPrice * line.sold - line.source.discount);
    final note = off
        ? 'Already returned in full'
        : [
            if (on) 'of ${_qty(line.returnable)} returnable' else if (line.source.returnedQuantity > 0) '${_qty(line.source.returnedQuantity)} returned earlier',
            '${Money.of(line.unitPrice)} each',
            if (line.employee.isNotEmpty) line.employee,
          ].join(' · ');

    final stepper = _Stepper(
      qty: _qty(line.returnQty),
      onMinus: line.returnQty <= 0 ? null : () => draft.changeQty(line, -1),
      onPlus: line.returnQty >= line.returnable ? null : () => draft.changeQty(line, 1),
    );
    final refund = Text('− ${Money.of(line.total)}', maxLines: 1, style: serif(size: 15, color: p.goldText));

    return LayoutBuilder(
      builder: (context, c) {
        final inline = c.maxWidth >= 400;
        return Padding(
          padding: const EdgeInsets.only(bottom: 2),
          child: GestureDetector(
            behavior: HitTestBehavior.opaque,
            onTap: off ? null : () => draft.setQty(line, on ? 0 : 1),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 160),
              padding: const EdgeInsets.fromLTRB(8, 7, 8, 7),
              decoration: BoxDecoration(
                color: on ? p.tint : Colors.transparent,
                borderRadius: BorderRadius.circular(11),
                border: on ? null : Border(bottom: BorderSide(color: p.hairline)),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      _Tick(on: on),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${_qty(line.sold)}× ${line.name}',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: ui(size: 13, weight: FontWeight.w700, color: off ? p.textMuted : p.ink).copyWith(
                                    decoration: off ? TextDecoration.lineThrough : null, decorationColor: p.textMuted)),
                            const SizedBox(height: 1),
                            Text(note, maxLines: 1, overflow: TextOverflow.ellipsis,
                                style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      if (on && inline) ...[
                        // Opaque so a tap between the keys doesn't tick the line out.
                        GestureDetector(behavior: HitTestBehavior.opaque, onTap: () {}, child: stepper),
                        const SizedBox(width: 10),
                        ConstrainedBox(
                          constraints: const BoxConstraints(minWidth: 84),
                          child: Align(alignment: Alignment.centerRight, widthFactor: 1, child: refund),
                        ),
                      ] else
                        Text(Money.of(sold), style: ui(size: 12.5, weight: FontWeight.w800, color: off ? p.textMuted : p.ink)),
                    ],
                  ),
                  if (on && !inline)
                    GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onTap: () {},
                      child: Padding(
                        padding: const EdgeInsets.fromLTRB(32, 7, 0, 0),
                        child: Row(children: [stepper, const Spacer(), refund]),
                      ),
                    ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}

class _Tick extends StatelessWidget {
  const _Tick({required this.on});
  final bool on;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      width: 22,
      height: 22,
      decoration: BoxDecoration(
        color: on ? p.primary : Colors.transparent,
        borderRadius: BorderRadius.circular(7),
        border: Border.all(color: on ? p.primary : p.hairline, width: 1.5),
      ),
      child: on ? const Icon(Icons.check_rounded, size: 14, color: Colors.white) : null,
    );
  }
}

/// A finger-sized stepper — the phone's [QtyStepper] keys are 28pt.
class _Stepper extends StatelessWidget {
  const _Stepper({required this.qty, this.onMinus, this.onPlus});
  final String qty;
  final VoidCallback? onMinus;
  final VoidCallback? onPlus;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    Widget key(IconData icon, VoidCallback? onTap) => GestureDetector(
          onTap: onTap,
          behavior: HitTestBehavior.opaque,
          child: SizedBox(
            width: 36,
            height: 34,
            child: Icon(icon, size: 17, color: onTap == null ? p.textMuted.withValues(alpha: 0.4) : p.primary),
          ),
        );
    return Container(
      decoration: BoxDecoration(
        color: p.cardSolid,
        borderRadius: BorderRadius.circular(11),
        border: Border.all(color: p.primary.withValues(alpha: 0.6)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          key(Icons.remove, onMinus),
          Container(
            constraints: const BoxConstraints(minWidth: 30),
            height: 34,
            alignment: Alignment.center,
            decoration: BoxDecoration(border: Border.symmetric(vertical: BorderSide(color: p.hairline))),
            child: Text(qty, style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
          ),
          key(Icons.add, onPlus),
        ],
      ),
    );
  }
}

/// The arrow between the papers: what moves across, and how much of it.
class _Bridge extends StatelessWidget {
  const _Bridge({required this.count});
  final int count;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: p.cardSolid,
            shape: BoxShape.circle,
            border: Border.all(color: p.hairline),
            boxShadow: context.astraTheme.softShadow,
          ),
          child: Icon(Icons.arrow_forward_rounded, size: 18, color: p.primary),
        ),
        const SizedBox(height: 7),
        Text('$count item${count == 1 ? '' : 's'}',
            textAlign: TextAlign.center, style: ui(size: 10, weight: FontWeight.w800, color: p.primary)),
      ],
    );
  }
}

/// The return as it builds: the lines coming back and the refund total. While
/// a split is open the totals block folds away ([compact]) — the headline
/// already carries the total, and the split panel needs the height.
class _ReturnSlip extends StatelessWidget {
  const _ReturnSlip({required this.draft, required this.compact});
  final ReturnDraftCubit draft;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final lines = draft.returningLines;

    return _Paper(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _PaperHead(
            caption: draft.isEditing ? 'RETURN SLIP · EDITING' : 'RETURN SLIP · DRAFT',
            title: Money.of(draft.total),
            subtitle: 'Refund to ${draft.customerName}',
            actions: [
              if (!draft.isEmpty) _HeadButton(icon: Icons.clear_all_rounded, label: 'Clear', onTap: draft.clearQtys),
              _HeadButton(icon: Icons.close, tooltip: 'Discard', onTap: () => NewSaleReturnScreen.close(context)),
            ],
          ),
          Expanded(
            child: lines.isEmpty
                ? Center(
                    child: SingleChildScrollView(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          IconChip(icon: Icons.checklist_rounded, size: 40, radius: 12),
                          const SizedBox(height: 8),
                          Text('Tick lines on the invoice\nto move them here.',
                              textAlign: TextAlign.center,
                              style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
                        ],
                      ),
                    ),
                  )
                : ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 4),
                    itemCount: lines.length,
                    separatorBuilder: (_, __) => Divider(height: 1, color: p.hairline),
                    itemBuilder: (_, i) => _SlipLine(line: lines[i]),
                  ),
          ),
          if (!compact)
            Container(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 9),
              decoration: BoxDecoration(border: Border(top: BorderSide(color: p.hairline))),
              child: Column(
                children: [
                  _SumRow(label: 'Subtotal', value: Money.of(draft.subtotal)),
                  if (draft.totalDiscount > 0) _SumRow(label: 'Discount (prorated)', value: '− ${Money.of(draft.totalDiscount)}'),
                  if (draft.taxTotal > 0) _SumRow(label: 'Tax', value: Money.of(draft.taxTotal)),
                  Row(
                    children: [
                      Expanded(child: Text('Refund total', style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink))),
                      Text(Money.of(draft.total), style: serif(size: 19, color: p.ink)),
                    ],
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _SlipLine extends StatelessWidget {
  const _SlipLine({required this.line});
  final ReturnLine line;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(6)),
            child: Text('${_qty(line.returnQty)}×', style: ui(size: 10.5, weight: FontWeight.w800, color: p.primary)),
          ),
          const SizedBox(width: 9),
          Expanded(
            child: Text(line.name, maxLines: 1, overflow: TextOverflow.ellipsis,
                style: ui(size: 12, weight: FontWeight.w600, color: p.ink)),
          ),
          const SizedBox(width: 9),
          Text(Money.of(line.total), style: ui(size: 12, weight: FontWeight.w800, color: p.ink)),
        ],
      ),
    );
  }
}

class _SumRow extends StatelessWidget {
  const _SumRow({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.only(bottom: 3),
      child: Row(
        children: [
          Expanded(child: Text(label, style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary))),
          Text(value, style: ui(size: 12, weight: FontWeight.w800, color: p.ink)),
        ],
      ),
    );
  }
}

/// Cash · Card · To account · Split — one tap applies (Split opens the split).
/// Four across when the column allows, two by two when it doesn't.
class _RefundMethods extends StatelessWidget {
  const _RefundMethods({required this.draft, required this.onSplit});
  final ReturnDraftCubit draft;
  final VoidCallback onSplit;

  @override
  Widget build(BuildContext context) {
    final tiles = [
      for (final (mode, icon, label) in const [
        (PayMode.cash, Icons.payments_outlined, 'Cash'),
        (PayMode.card, Icons.credit_card, 'Card'),
        (PayMode.credit, Icons.description_outlined, 'To account'),
        (PayMode.custom, Icons.call_split_rounded, 'Split'),
      ])
        if (mode == PayMode.custom)
          _SplitTile(onTap: onSplit)
        else
          _MethodTile(icon: icon, label: label, on: draft.payMode == mode, onTap: () => draft.setPayMode(mode)),
    ];
    Widget row(List<Widget> items) => Row(
          children: [
            for (final (i, t) in items.indexed) ...[if (i > 0) const SizedBox(width: 6), Expanded(child: t)],
          ],
        );
    return LayoutBuilder(
      builder: (context, c) => c.maxWidth >= 440
          ? row(tiles)
          : Column(children: [row(tiles.sublist(0, 2)), const SizedBox(height: 6), row(tiles.sublist(2))]),
    );
  }
}

class _MethodTile extends StatelessWidget {
  const _MethodTile({required this.icon, required this.label, required this.on, required this.onTap});
  final IconData icon;
  final String label;
  final bool on;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        height: 42,
        padding: const EdgeInsets.symmetric(horizontal: 8),
        decoration: BoxDecoration(
          color: on ? p.tint : p.cardSolid,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: on ? p.primary : p.hairline, width: on ? 1.5 : 1),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 16, color: on ? p.primary : p.textSecondary),
            const SizedBox(width: 6),
            Flexible(
              child: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: ui(size: 12, weight: FontWeight.w800, color: on ? p.primary : p.ink)),
            ),
          ],
        ),
      ),
    );
  }
}

/// Split, set apart from the single methods: it opens a choice rather than
/// making one, so it wears the accent — a gold gradient edge, the split mark in
/// a gold coin, and what it does ("2+ ways") under the name.
class _SplitTile extends StatelessWidget {
  const _SplitTile({required this.onTap});
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        height: 42,
        padding: const EdgeInsets.all(1.5),
        decoration: BoxDecoration(
          gradient: p.accentGradient,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [BoxShadow(color: p.accent.withValues(alpha: 0.28), blurRadius: 14, offset: const Offset(0, 5))],
        ),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 7),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(10.5),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color.alphaBlend(p.accent.withValues(alpha: 0.14), p.cardSolid), p.cardSolid],
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 24,
                height: 24,
                decoration: BoxDecoration(gradient: p.accentGradient, shape: BoxShape.circle),
                child: Icon(Icons.call_split_rounded, size: 14, color: p.primaryDark),
              ),
              const SizedBox(width: 7),
              Flexible(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Split', maxLines: 1, style: ui(size: 12, weight: FontWeight.w800, color: p.ink, height: 1.1)),
                    Text('2+ ways', maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: ui(size: 9, weight: FontWeight.w700, color: p.goldText, height: 1.1)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Where the money goes, in one line, for a single-method refund.
class _RefundStatus extends StatelessWidget {
  const _RefundStatus({required this.draft});
  final ReturnDraftCubit draft;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final toAccount = draft.payMode == PayMode.credit;
    final (Color tint, Color color, IconData icon, String text) = toAccount
        ? (p.warnTint, p.warnText, Icons.account_balance_wallet_outlined, 'To ${draft.customerName}\'s balance · nothing paid out')
        : (p.successTint, AstraPalette.success, Icons.check_circle_rounded, draft.payMode == PayMode.card ? 'Back to card' : 'Cash from the till');
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8),
      decoration: BoxDecoration(color: tint, borderRadius: BorderRadius.circular(11)),
      child: Row(
        children: [
          Icon(icon, size: 15, color: color),
          const SizedBox(width: 8),
          Expanded(
            child: Text(text, maxLines: 1, overflow: TextOverflow.ellipsis,
                style: ui(size: 11.5, weight: FontWeight.w800, color: color)),
          ),
          const SizedBox(width: 8),
          Text(Money.of(draft.total), style: ui(size: 12.5, weight: FontWeight.w800, color: color)),
        ],
      ),
    );
  }
}

/// Receipt paper: rounded top corners, round bites torn along the bottom.
class _TornBottomClipper extends CustomClipper<Path> {
  const _TornBottomClipper();

  static const _bite = 6.0;
  static const _period = 16.0;
  static const _corner = 14.0;

  @override
  Path getClip(Size size) {
    final h = size.height;
    final path = Path()
      ..moveTo(0, _corner)
      ..quadraticBezierTo(0, 0, _corner, 0)
      ..lineTo(size.width - _corner, 0)
      ..quadraticBezierTo(size.width, 0, size.width, _corner)
      ..lineTo(size.width, h);
    for (var x = size.width; x - _period >= 0; x -= _period) {
      final mid = x - _period / 2;
      path
        ..lineTo(mid + _bite, h)
        ..arcToPoint(Offset(mid - _bite, h), radius: const Radius.circular(_bite), clockwise: false);
    }
    return path
      ..lineTo(0, h)
      ..close();
  }

  @override
  bool shouldReclip(covariant CustomClipper<Path> oldClipper) => false;
}

import 'dart:ui' show ImageFilter;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Step 2 of a return: pick which sold lines (and how many) to return. Each
/// stepper is capped at the line's remaining returnable quantity.
class NewSaleReturnScreen extends StatelessWidget {
  const NewSaleReturnScreen({super.key});

  Future<void> _close(BuildContext context) async {
    HapticFeedback.selectionClick();
    final draft = context.read<ReturnDraftCubit>();
    if (!draft.isEmpty) {
      final discard = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Discard this return?'),
          content: const Text('The current return will be cleared.'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Keep')),
            TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Discard')),
          ],
        ),
      );
      if (discard != true) return;
    }
    if (!context.mounted) return;
    draft.clear();
    context.go('/sales-returns');
  }

  @override
  Widget build(BuildContext context) {
    final draft = context.watch<ReturnDraftCubit>();

    if (!draft.isSeeded) {
      return Scaffold(
        body: AstraBackground(
          child: Column(
            children: [
              EmeraldHeader(
                leading: HeaderIconButton(
                  icon: Icons.chevron_left,
                  onTap: () => context.canPop() ? context.pop() : context.go('/sale-return/pick'),
                ),
                title: 'New Return',
              ),
              Expanded(
                child: EmptyState(
                  icon: Icons.assignment_return_outlined,
                  title: 'No invoice selected',
                  message: 'Choose a paid invoice to return against.',
                  action: AstraButton(label: 'Pick invoice', icon: Icons.search, expand: false, onTap: () => context.pushReplacement('/sale-return/pick')),
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(
                icon: Icons.chevron_left,
                onTap: () => context.canPop() ? context.pop() : context.go('/sale-return/pick'),
              ),
              titleWidget: Row(
                children: [
                  Expanded(child: Text(draft.isEditing ? 'Edit Return' : 'New Return', style: serif(size: 23, color: Colors.white))),
                  HeaderIconButton(icon: Icons.close, onTap: () => _close(context)),
                ],
              ),
              bottom: Row(
                children: [
                  Expanded(child: _selector(context, Icons.receipt_long, 'INVOICE', draft.invoiceNo.isEmpty ? '—' : draft.invoiceNo)),
                  const SizedBox(width: 9),
                  Expanded(child: _selector(context, Icons.person_outline, 'CLIENT', draft.customerName)),
                ],
              ),
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 640,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 130),
                  children: [
                    _note(context, draft),
                    const SizedBox(height: 13),
                    for (final line in draft.lines) _lineCard(context, draft, line),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: draft.isEmpty
          ? null
          : SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 12),
                child: MaxWidthBox(maxWidth: 640, child: _refundBar(context, draft)),
              ),
            ),
    );
  }

  Widget _note(BuildContext context, ReturnDraftCubit draft) {
    final p = context.astra;
    final date = Dates.human(draft.saleDate);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 11),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
      child: Row(
        children: [
          Icon(Icons.info_outline_rounded, size: 16, color: p.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Returning against ${draft.invoiceNo}${date.isEmpty ? '' : ' · $date'}',
              style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary),
            ),
          ),
        ],
      ),
    );
  }

  Widget _lineCard(BuildContext context, ReturnDraftCubit draft, ReturnLine line) {
    final p = context.astra;
    final isService = line.type.toLowerCase().startsWith('serv');
    final off = !line.isReturning;
    final maxed = line.returnQty >= line.returnable;
    final disabled = line.fullyReturned;
    final soldLabel = 'SOLD ${_qty(line.sold)}';

    return Padding(
      padding: const EdgeInsets.only(bottom: 11),
      child: Opacity(
        opacity: off ? 0.6 : 1,
        child: AstraCard(
          radius: 18,
          padding: const EdgeInsets.fromLTRB(13, 13, 13, 11),
          child: Column(
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  IconChip(icon: isService ? Icons.content_cut : Icons.shopping_bag_outlined, size: 46, radius: 14),
                  const SizedBox(width: 11),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(line.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 15, color: p.ink)),
                        const SizedBox(height: 5),
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                              decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(8)),
                              child: Text(soldLabel, style: ui(size: 8.5, weight: FontWeight.w800, color: p.textSecondary, letterSpacing: 0.3)),
                            ),
                            if (line.source.returnedQuantity > 0) ...[
                              const SizedBox(width: 6),
                              Text('${_qty(line.source.returnedQuantity)} returned',
                                  style: ui(size: 9.5, weight: FontWeight.w700, color: p.textMuted)),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(line.isReturning ? '− ${Money.of(line.total)}' : '—',
                          style: serif(size: 16, color: line.isReturning ? p.goldText : p.textMuted)),
                      const SizedBox(height: 2),
                      Text('${Money.of(line.unitPrice)} / unit', style: ui(size: 9.5, weight: FontWeight.w600, color: p.textMuted)),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 11),
              Container(height: 1, color: p.hairline),
              const SizedBox(height: 11),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  QtyStepper(
                    qty: _qty(line.returnQty),
                    onMinus: (disabled || line.returnQty <= 0)
                        ? null
                        : () {
                            HapticFeedback.selectionClick();
                            draft.changeQty(line, -1);
                          },
                    onPlus: (disabled || maxed)
                        ? null
                        : () {
                            HapticFeedback.selectionClick();
                            draft.changeQty(line, 1);
                          },
                  ),
                  Text(
                    disabled ? 'fully returned' : 'of ${_qty(line.returnable)} returnable',
                    style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _refundBar(BuildContext context, ReturnDraftCubit draft) {
    final p = context.astra;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        context.push('/sale-return/review');
      },
      child: ClipRRect(
        borderRadius: BorderRadius.circular(22),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
          child: Container(
            margin: const EdgeInsets.symmetric(horizontal: 2),
            padding: const EdgeInsets.fromLTRB(13, 13, 15, 13),
            decoration: BoxDecoration(
              gradient: LinearGradient(colors: [
                p.primaryDark.withValues(alpha: 0.74),
                Color.lerp(p.primaryDark, Colors.black, 0.25)!.withValues(alpha: 0.8),
              ]),
              borderRadius: BorderRadius.circular(22),
              border: Border.all(color: p.accent.withValues(alpha: 0.3)),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.3), blurRadius: 30, offset: const Offset(0, 14))],
            ),
            child: Row(
              children: [
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(13),
                      ),
                      child: Icon(Icons.assignment_return_outlined, color: p.accent, size: 18),
                    ),
                    Positioned(
                      top: -6,
                      right: -6,
                      child: Container(
                        padding: const EdgeInsets.all(4),
                        constraints: const BoxConstraints(minWidth: 20, minHeight: 20),
                        decoration: BoxDecoration(gradient: p.accentGradient, shape: BoxShape.circle),
                        alignment: Alignment.center,
                        child: Text('${draft.count}', style: ui(size: 10.5, weight: FontWeight.w800, color: p.primaryDark)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(width: 13),
                Expanded(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('${draft.count} item${draft.count == 1 ? '' : 's'} · Refund',
                          style: ui(size: 10.5, weight: FontWeight.w600, color: Colors.white70)),
                      Text(Money.of(draft.total), style: serif(size: 21, color: Colors.white)),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
                  decoration: BoxDecoration(gradient: p.accentGradient, borderRadius: BorderRadius.circular(14)),
                  child: Row(
                    children: [
                      Text('Review', style: ui(size: 13.5, weight: FontWeight.w800, color: p.primaryDark)),
                      const SizedBox(width: 7),
                      Icon(Icons.arrow_forward, size: 15, color: p.primaryDark),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _selector(BuildContext context, IconData icon, String label, String value) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.13),
        borderRadius: BorderRadius.circular(13),
        border: Border.all(color: Colors.white.withValues(alpha: 0.18)),
      ),
      child: Row(
        children: [
          Container(
            width: 26,
            height: 26,
            decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.16), borderRadius: BorderRadius.circular(8)),
            child: Icon(icon, size: 13, color: context.astra.accent),
          ),
          const SizedBox(width: 9),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: ui(size: 9, weight: FontWeight.w700, color: Colors.white70, letterSpacing: 0.6)),
                Text(value, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12, weight: FontWeight.w700, color: Colors.white)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _qty(double v) => v.toStringAsFixed(v % 1 == 0 ? 0 : 2);
}

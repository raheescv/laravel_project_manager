import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/models/currency.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// Click-and-go currency picker: tapping a row applies it instantly app-wide
/// and closes the sheet. Premium, theme-var styled to match the app.
Future<void> showCurrencySheet(BuildContext context) {
  final p = context.astra;
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    builder: (sheetContext) {
      final controller = sheetContext.watch<CurrencyCubit>();
      final current = controller.currency;
      final baseCode = controller.base?.code;
      return Container(
        decoration: BoxDecoration(
          color: p.canvas,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 10),
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3)),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 6),
                child: Row(
                  children: [
                    Icon(Icons.payments_outlined, size: 18, color: p.primary),
                    const SizedBox(width: 9),
                    Flexible(
                      child: Text('Currency', maxLines: 1, overflow: TextOverflow.ellipsis,
                          style: serif(size: 20, color: p.ink)),
                    ),
                    const SizedBox(width: 9),
                    if (controller.isCached) _offlineChip(sheetContext),
                    const Spacer(),
                    GestureDetector(
                      onTap: () => Navigator.of(sheetContext).pop(),
                      child: Icon(Icons.close, size: 20, color: p.textMuted),
                    ),
                  ],
                ),
              ),
              Flexible(
                child: ListView(
                  shrinkWrap: true,
                  padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
                  children: [
                    for (final c in controller.available)
                      _row(sheetContext, c, c.code == current.code, c.code == baseCode),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
    },
  );
}

Widget _offlineChip(BuildContext context) {
  final p = context.astra;
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
    decoration: BoxDecoration(
      color: p.tint,
      borderRadius: BorderRadius.circular(999),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(Icons.bolt, size: 11, color: p.primaryDark),
        const SizedBox(width: 3),
        Text('Offline', style: ui(size: 9.5, weight: FontWeight.w800, color: p.primaryDark, letterSpacing: 0.4)),
      ],
    ),
  );
}

Widget _row(BuildContext context, Currency c, bool active, bool isBase) {
  final p = context.astra;
  return GestureDetector(
    onTap: () {
      context.read<CurrencyCubit>().setCurrency(c);
      Navigator.of(context).pop();
    },
    behavior: HitTestBehavior.opaque,
    child: Container(
      margin: const EdgeInsets.symmetric(vertical: 4, horizontal: 4),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(14),
        boxShadow: context.astraTheme.softShadow,
        border: Border.all(color: active ? p.primary : Colors.transparent, width: 1.5),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12)),
            child: Text(c.symbol.trim(), style: serif(size: 18, color: p.primaryDark)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(c.name, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                Text(c.code, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted, letterSpacing: 0.6)),
              ],
            ),
          ),
          if (isBase)
            Container(
              margin: const EdgeInsets.only(right: 10),
              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
              decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(6)),
              child: Text('BASE', style: ui(size: 9, weight: FontWeight.w800, color: p.primaryDark, letterSpacing: 0.5)),
            )
          else
            Padding(
              padding: const EdgeInsets.only(right: 10),
              child: Text(c.rateToBase.toStringAsFixed(4),
                  style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
            ),
          Container(
            width: 24,
            height: 24,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: active ? p.primaryGradient : null,
              border: active ? null : Border.all(color: p.hairline, width: 1.5),
            ),
            child: active ? const Icon(Icons.check, size: 13, color: Colors.white) : null,
          ),
        ],
      ),
    ),
  );
}

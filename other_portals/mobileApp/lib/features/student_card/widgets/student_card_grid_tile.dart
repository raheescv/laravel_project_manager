import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import 'card_status.dart';

/// Compact grid card for [StudentCard] — photo/initial, name, class, balance
/// and a status pill, all in a fixed-height tile. The row equivalent of
/// [StudentCardTile], for the Link Card screen's grid view.
class StudentCardGridTile extends StatelessWidget {
  const StudentCardGridTile({super.key, required this.card, this.onTap});

  final StudentCard card;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    final headers = context.read<AuthCubit>().config.assetHeaders;
    final status = cardStatusOf(card, p);

    final initial = Container(
      width: 44,
      height: 44,
      alignment: Alignment.center,
      decoration: BoxDecoration(shape: BoxShape.circle, color: p.tint),
      child: Text(card.name.isEmpty ? '?' : card.name.characters.first.toUpperCase(),
          style: serif(size: 18, color: p.primary)),
    );

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(t.rTile),
          border: p.isEditorial ? Border.all(color: p.cardBorder) : null,
          boxShadow: t.softShadow,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                card.imageUrl.startsWith('http')
                    ? ClipOval(
                        child: Image.network(
                          card.imageUrl,
                          headers: headers,
                          width: 44,
                          height: 44,
                          fit: BoxFit.cover,
                          cacheWidth: decodeWidthFor(context, 44),
                          errorBuilder: (_, __, ___) => initial,
                        ),
                      )
                    : initial,
                const Spacer(),
                Container(
                  width: 10,
                  height: 10,
                  decoration: BoxDecoration(shape: BoxShape.circle, color: status.fg),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(card.name,
                maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
            const SizedBox(height: 2),
            Text(card.classLabel,
                maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
            const Spacer(),
            Text('BALANCE', style: ui(size: 9, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
            Text(Money.of(card.balance),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: serif(size: 15, color: card.balance < 0 ? AstraPalette.danger : p.ink)),
            const SizedBox(height: 8),
            StatusPill(label: status.shortLabel, bg: status.bg, fg: status.fg, icon: status.icon),
          ],
        ),
      ),
    );
  }
}

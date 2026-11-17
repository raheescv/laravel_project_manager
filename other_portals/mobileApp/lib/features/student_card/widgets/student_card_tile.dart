import 'package:flutter/material.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:provider/provider.dart';

/// Photo, name, class and balance — what the cashier checks the card against.
class StudentCardTile extends StatelessWidget {
  const StudentCardTile({super.key, required this.card, this.trailing, this.showBalance = true});

  final StudentCard card;
  final Widget? trailing;
  final bool showBalance;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final headers = context.read<AuthCubit>().config.assetHeaders;
    final initial = Container(
      width: 52,
      height: 52,
      alignment: Alignment.center,
      decoration: BoxDecoration(shape: BoxShape.circle, color: p.tint),
      child: Text(card.name.isEmpty ? '?' : card.name.characters.first.toUpperCase(),
          style: serif(size: 22, color: p.primary)),
    );

    return Row(
      children: [
        card.imageUrl.startsWith('http')
            ? ClipOval(
                child: Image.network(
                  card.imageUrl,
                  headers: headers,
                  width: 52,
                  height: 52,
                  fit: BoxFit.cover,
                  cacheWidth: decodeWidthFor(context, 52),
                  errorBuilder: (_, __, ___) => initial,
                ),
              )
            : initial,
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(card.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 15, weight: FontWeight.w800, color: p.ink)),
              const SizedBox(height: 2),
              Text(card.classLabel, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
              if (card.isBlocked) ...[
                const SizedBox(height: 3),
                Text('Card blocked', style: ui(size: 11, weight: FontWeight.w800, color: AstraPalette.danger)),
              ],
            ],
          ),
        ),
        if (showBalance)
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text('BALANCE', style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
              Text(Money.of(card.balance),
                  style: serif(size: 18, color: card.balance < 0 ? AstraPalette.danger : p.ink)),
            ],
          ),
        if (trailing != null) ...[const SizedBox(width: 8), trailing!],
      ],
    );
  }
}

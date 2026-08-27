import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../features/catalog/logic/funnel_cubit/funnel_cubit.dart';
import '../../domain/helpers/responsive.dart';
import '../../logic/branch_cubit/branch_cubit.dart';
import '../../utils/components/theme/pearl_theme.dart';
import '../../utils/router/routes.dart';
import '../branch_picker.dart';
import '../pearl_widgets.dart';

/// The bar that stays put across the funnel: wordmark, search, stock, branch,
/// scan.
///
/// Search and branch being here rather than only on the first screen is the
/// point — on a shop floor the question changes mid-browse. "In stock" sits
/// beside the branch for the same reason and because it means nothing without
/// one: both answer "what can I actually put in this customer's hands today".
class AppTopBar extends StatelessWidget {
  const AppTopBar({super.key, this.leading, this.title});

  /// Replaces the wordmark on inner screens (a back control, usually).
  final Widget? leading;

  /// Breadcrumb or page title shown between the leading control and search.
  final Widget? title;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final tablet = context.isTablet;
    return Container(
      padding: EdgeInsets.symmetric(horizontal: tablet ? 20 : 14, vertical: 12),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: p.line)),
      ),
      child: Row(
        children: [
          if (leading != null) ...[leading!, const SizedBox(width: 12)],
          if (leading == null)
            Text(
              'SIZERUN',
              style: PearlText.section.copyWith(fontSize: 13, color: p.ink),
            ),
          if (title != null) ...[
            const SizedBox(width: 14),
            Expanded(child: title!),
          ] else if (tablet) ...[
            const SizedBox(width: 18),
            Expanded(child: _SearchField(onTap: () => context.push(Routes.search))),
          ] else
            const Spacer(),
          const SizedBox(width: 12),
          const StockPill(),
          const SizedBox(width: 8),
          const BranchPill(),
          if (tablet) ...[
            const SizedBox(width: 8),
            IconSquare(
              Icons.qr_code_scanner_outlined,
              size: 38,
              onTap: () => context.push(Routes.scan),
            ),
          ] else ...[
            const SizedBox(width: 8),
            IconSquare(Icons.search, size: 38, onTap: () => context.push(Routes.search)),
          ],
        ],
      ),
    );
  }
}

class _SearchField extends StatelessWidget {
  const _SearchField({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return InkWell(
      onTap: onTap,
      child: Container(
        height: 38,
        padding: const EdgeInsets.symmetric(horizontal: 13),
        decoration: BoxDecoration(color: p.surface, border: Border.all(color: p.line)),
        child: Row(
          children: [
            Icon(Icons.search, size: 15, color: p.faint),
            const SizedBox(width: 10),
            Text(
              'Search by name, code or barcode',
              style: PearlText.body(11.5).copyWith(color: p.faint),
            ),
          ],
        ),
      ),
    );
  }
}

/// "Only what is on the shelf here", on by default.
///
/// It lives in the bar rather than in the results filter panel because it is
/// not a refinement of one screen — it scopes the brand list, the results and
/// every count the funnel shows. A customer who has said they want stock has
/// said it for the whole visit.
class StockPill extends StatelessWidget {
  const StockPill({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final funnel = context.watch<FunnelCubit>();
    final on = funnel.state.inStockOnly;
    final tablet = context.isTablet;
    return InkWell(
      onTap: () => funnel.setInStockOnly(!on),
      child: Container(
        height: 38,
        padding: EdgeInsets.symmetric(horizontal: tablet ? 12 : 10),
        decoration: BoxDecoration(
          color: on ? p.accent : p.surface,
          border: Border.all(color: on ? p.accent : p.line),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              on ? Icons.check_box_outlined : Icons.check_box_outline_blank,
              size: 15,
              color: on ? p.accentInk : p.faint,
            ),
            if (tablet) ...[
              const SizedBox(width: 8),
              Text(
                'In stock',
                style: PearlText.label.copyWith(
                  color: on ? p.accentInk : p.muted,
                  fontSize: 11.5,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// The active shop. Tapping it opens the picker; every stock number on screen
/// is relative to whatever is shown here.
class BranchPill extends StatelessWidget {
  const BranchPill({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final branch = context.watch<BranchCubit>().state.selected;
    return InkWell(
      onTap: () => showBranchPicker(context),
      child: Container(
        height: 38,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(color: p.surface, border: Border.all(color: p.line)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.place_outlined, size: 15, color: p.ink),
            const SizedBox(width: 8),
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 170),
              child: Text(
                branch?.label ?? 'Choose a store',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: PearlText.label.copyWith(color: p.ink, fontSize: 11.5),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

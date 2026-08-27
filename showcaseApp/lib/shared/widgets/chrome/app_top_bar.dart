import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../features/catalog/logic/funnel_cubit/funnel_cubit.dart';
import '../../domain/helpers/responsive.dart';
import '../../logic/branch_cubit/branch_cubit.dart';
import '../../utils/components/theme/pearl_theme.dart';
import '../../utils/router/routes.dart';
import '../brand_mark.dart';
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
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              if (leading != null) ...[leading!, const SizedBox(width: 12)],
              if (leading == null) const BrandMark(height: 30),
              // Only the tablet bar is wide enough to hold the breadcrumbs
              // inline; on a phone they get their own row below.
              if (tablet && title != null) ...[
                const SizedBox(width: 14),
                Expanded(child: title!),
              ] else if (tablet) ...[
                const SizedBox(width: 18),
                Expanded(child: _SearchField(onTap: () => context.push(Routes.search))),
              ] else
                // A small flex against the branch pill's large one: the gap
                // gives way first, so the shop name keeps its room.
                const Spacer(),
              const SizedBox(width: 12),
              const StockPill(),
              const SizedBox(width: 8),
              // Flexible so a long shop name shortens the pill instead of
              // overflowing the row — the phone bar has no spare width.
              Flexible(flex: tablet ? 1 : 6, child: const BranchPill()),
              if (tablet) ...[
                const SizedBox(width: 8),
                IconSquare(
                  Icons.qr_code_scanner_outlined,
                  size: 38,
                  onTap: () => context.push(Routes.scan),
                ),
              ] else ...[
                // Three separate icons do not fit beside a branch name on a
                // 393pt phone — together they are wider than the bar. They
                // fold into one menu, which also gets the phone the scanner it
                // never had.
                const SizedBox(width: 8),
                const _OverflowMenu(),
              ],
            ],
          ),
          if (!tablet && title != null) ...[
            const SizedBox(height: 10),
            title!,
          ],
        ],
      ),
    );
  }
}

/// Phone only: search, scan and appearance behind one control.
class _OverflowMenu extends StatelessWidget {
  const _OverflowMenu();

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return PopupMenuButton<String>(
      tooltip: 'More',
      position: PopupMenuPosition.under,
      color: p.bg,
      // Pearl has no corner radius; the menu inherits that rather than
      // arriving as the one rounded surface in the app.
      shape: RoundedRectangleBorder(side: BorderSide(color: p.line)),
      padding: EdgeInsets.zero,
      onSelected: (value) => context.push(value),
      itemBuilder: (context) => [
        _item(context, Routes.search, Icons.search, 'Search'),
        _item(context, Routes.scan, Icons.qr_code_scanner_outlined, 'Scan a barcode'),
        _item(context, Routes.settings, Icons.tune, 'Appearance'),
      ],
      child: Container(
        width: 38,
        height: 38,
        alignment: Alignment.center,
        decoration: BoxDecoration(border: Border.all(color: p.line)),
        child: Icon(Icons.more_horiz, size: 16, color: p.ink),
      ),
    );
  }

  PopupMenuItem<String> _item(
    BuildContext context,
    String route,
    IconData icon,
    String label,
  ) {
    final p = context.pearl;
    return PopupMenuItem<String>(
      value: route,
      height: 44,
      child: Row(
        children: [
          Icon(icon, size: 16, color: p.muted),
          const SizedBox(width: 12),
          Text(label, style: PearlText.label.copyWith(color: p.ink, fontSize: 12)),
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
            // Flexible because the placeholder is long: it fits at the tablet
            // widths this was drawn at, but a narrower split, a larger text
            // scale or a fallback font all push it past the field.
            Flexible(
              child: Text(
                'Search by name, code or barcode',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: PearlText.body(11.5).copyWith(color: p.faint),
              ),
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
            // Flexible as well as capped: the cap stops a long shop name
            // dominating a wide bar, but only the flex lets it give way on a
            // narrow one. A ConstrainedBox alone still demands its 170.
            Flexible(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 170),
                child: Text(
                  branch?.label ?? 'Choose a store',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: PearlText.label.copyWith(color: p.ink, fontSize: 11.5),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

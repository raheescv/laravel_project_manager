import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../features/catalog/logic/funnel_cubit/funnel_cubit.dart';
import '../../domain/helpers/responsive.dart';
import '../../logic/branch_cubit/branch_cubit.dart';
import '../../logic/locale_cubit/locale_cubit.dart';
import '../../utils/components/theme/pearl_theme.dart';
import '../../utils/router/routes.dart';
import '../brand_mark.dart';
import '../branch_picker.dart';
import '../pearl_widgets.dart';
import '../../../l10n/app_localizations.dart';

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
    // The bar does not mirror.
    //
    // Everything below it does — Arabic flips the funnel, the grids and the
    // panels, which is right. But the chrome is furniture: the mark, the shop,
    // the language switch and the back control stay where the hand already
    // knows to find them, so switching language does not move the controls out
    // from under a customer mid-tap. Arabic *text* inside still shapes and
    // reads right-to-left — this fixes the order of the boxes, not the words.
    return Directionality(
      textDirection: TextDirection.ltr,
      child: Container(
        decoration: BoxDecoration(
          border: Border(bottom: BorderSide(color: p.line)),
        ),
        child: context.isTablet ? _tabletBar(context) : _phoneBar(context),
      ),
    );
  }

  /// One row. The tablet has the width for everything inline, and the rail
  /// beside it already carries the mark — a brand row here would be the second
  /// one on screen and would cost height the tablet has no reason to spend.
  Widget _tabletBar(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              if (leading != null) ...[leading!, const SizedBox(width: _group)],
              if (title != null)
                Expanded(child: title!)
              else
                Expanded(child: _SearchField(onTap: () => context.push(Routes.search))),
              const SizedBox(width: _group),
              const StockPill(),
              const SizedBox(width: _gap),
              const LanguagePill(),
              const SizedBox(width: _gap),
              const Flexible(child: BranchPill()),
              const SizedBox(width: _gap),
              IconSquare(
                Icons.qr_code_scanner_outlined,
                size: _control,
                onTap: () => context.push(Routes.scan),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// Two rows: who we are and where you are, then what you can press.
  ///
  /// One row could not hold a mark, a back control, a shop name and three
  /// actions at 320pt — the branch name was squeezed to a couple of characters
  /// and the breadcrumbs to a sliver. Split, the mark can be set half again as
  /// large, the shop name never competes for width, and the breadcrumbs get the
  /// whole line they need. It costs about 50pt of height, which is the trade.
  Widget _phoneBar(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 10, 14, 11),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Row(
            children: [
              BrandMark(height: 42),
              SizedBox(width: _group),
              LanguagePill(),
              SizedBox(width: _gap),
              // Expanded + Align rather than a Spacer beside a Flexible: two
              // flex children split the slack between them, so the pill ended
              // up sitting in the middle of the row with empty space to its
              // right. One flex child that fills and aligns its contents puts
              // the shop hard against the edge and still lets a long name
              // shorten inside it.
              Expanded(
                child: Align(
                  alignment: Alignment.centerRight,
                  child: BranchPill(),
                ),
              ),
            ],
          ),
          const SizedBox(height: _gap),
          Row(
            children: [
              if (leading != null) ...[leading!, const SizedBox(width: _gap)],
              Expanded(
                child: _SearchField(
                  onTap: () => context.push(Routes.search),
                  // The full prompt does not survive a 320pt row once the
                  // stock label and the back control have taken their share.
                  compact: true,
                ),
              ),
              const SizedBox(width: _gap),
              const StockPill(),
              const SizedBox(width: _gap),
              const _OverflowMenu(),
            ],
          ),
          // The funnel's answers, on a line of their own. They used to share
          // the control row, which left them about 90pt — enough for "42" and
          // an ellipsis.
          if (title != null) ...[
            const SizedBox(height: 9),
            title!,
          ],
        ],
      ),
    );
  }
}

/// One height and one gap, so the bar has a rhythm rather than a set of
/// one-off numbers. [_group] separates the two halves of a row; [_gap]
/// separates siblings inside one.
const double _control = 38;
const double _gap = 8;
const double _group = 14;

/// Phone only: search, scan and appearance behind one control.
class _OverflowMenu extends StatelessWidget {
  const _OverflowMenu();

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return PopupMenuButton<String>(
      tooltip: L.of(context).more,
      position: PopupMenuPosition.under,
      color: p.bg,
      // Pearl has no corner radius; the menu inherits that rather than
      // arriving as the one rounded surface in the app.
      shape: RoundedRectangleBorder(side: BorderSide(color: p.line)),
      padding: EdgeInsets.zero,
      onSelected: (value) => context.push(value),
      itemBuilder: (context) => [
        // Search left the menu when it got its own field in the control row.
        _item(context, Routes.scan, Icons.qr_code_scanner_outlined, L.of(context).scanBarcode),
        _item(context, Routes.settings, Icons.tune, L.of(context).appearance),
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
  const _SearchField({required this.onTap, this.compact = false});

  final VoidCallback onTap;

  /// Shortens the prompt for the phone's control row.
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return InkWell(
      onTap: onTap,
      child: Container(
        height: _control,
        decoration: BoxDecoration(color: p.surface, border: Border.all(color: p.line)),
        child: LayoutBuilder(
          builder: (context, constraints) {
            // Below this there is not room for the icon, its gap and a legible
            // word, so the field becomes the icon rather than overflowing by a
            // few pixels. It is the most elastic thing in the row — the back
            // control, the stock label and the menu all have to stay whole.
            final iconOnly = constraints.maxWidth < 76;
            return Padding(
              padding: EdgeInsets.symmetric(horizontal: iconOnly ? 0 : 12),
              child: Row(
                mainAxisAlignment:
                    iconOnly ? MainAxisAlignment.center : MainAxisAlignment.start,
                children: [
                  Icon(Icons.search, size: 15, color: p.faint),
                  if (!iconOnly) ...[
                    const SizedBox(width: 9),
                    // Flexible because the placeholder is long: it fits at the
                    // tablet widths this was drawn at, but a narrower split, a
                    // larger text scale or a fallback font all push it past
                    // the field.
                    Flexible(
                      child: Text(
                        compact ? L.of(context).search : L.of(context).searchLong,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: PearlText.body(11.5).copyWith(color: p.faint),
                      ),
                    ),
                  ],
                ],
              ),
            );
          },
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
    return InkWell(
      onTap: () => funnel.setInStockOnly(!on),
      child: Container(
        height: _control,
        padding: const EdgeInsets.symmetric(horizontal: 11),
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
            // Labelled everywhere. A lone checkbox in a bar is a control
            // nobody can name, and this one silently scopes every count on
            // every screen — it has to say what it does.
            const SizedBox(width: 7),
            Text(
              L.of(context).inStock,
              style: PearlText.label.copyWith(
                color: on ? p.accentInk : p.muted,
                fontSize: 11.5,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// One tap between English and Arabic.
///
/// It sits in the bar beside the shop rather than in Settings because it is not
/// a setting — it is the first thing a customer needs when the tablet is handed
/// to them, and a member of staff should not have to go and find a screen. It
/// is labelled with the language you would get, not the one you are in, which
/// is the only version that reads as a button.
class LanguagePill extends StatelessWidget {
  const LanguagePill({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final arabic = Localizations.localeOf(context).languageCode == 'ar';
    final t = L.of(context);
    return InkWell(
      onTap: () => context
          .read<LocaleCubit>()
          .set(Locale(arabic ? 'en' : 'ar')),
      child: Container(
        height: _control,
        padding: const EdgeInsets.symmetric(horizontal: 11),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: p.surface,
          border: Border.all(color: p.line),
        ),
        child: Text(
          // The other language, in its own script — the one word a speaker of
          // it will recognise without reading the rest of the screen.
          arabic ? t.english : t.arabic,
          style: PearlText.label.copyWith(color: p.ink, fontSize: 11.5),
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
                  branch?.label ?? L.of(context).chooseStore,
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

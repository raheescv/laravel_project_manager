import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../logic/branch_cubit/branch_cubit.dart';
import '../../logic/funnel_cubit/funnel_cubit.dart';
import '../../logic/locale_cubit/locale_cubit.dart';
import '../../utils/components/theme/pearl_theme.dart';
import '../../utils/router/routes.dart';
import '../branch_picker.dart';
import '../brand_mark.dart';
import '../pearl_widgets.dart';
import '../../../l10n/app_localizations.dart';

/// The bar that stays put across the funnel: wordmark, language, shop, search,
/// stock, settings.
///
/// Search and branch being here rather than only on the first screen is the
/// point — on a shop floor the question changes mid-browse. "In stock" sits
/// beside the branch for the same reason and because it means nothing without
/// one: both answer "what can I actually put in this customer's hands today".
class AppTopBar extends StatelessWidget {
  const AppTopBar({
    super.key,
    this.leading,
    this.title,
    this.atSettings = false,
  });

  /// Replaces the wordmark on inner screens (a back control, usually).
  final Widget? leading;

  /// Breadcrumb or page title shown between the leading control and search.
  final Widget? title;

  /// Set by Settings itself, so its own square stops pointing at it.
  ///
  /// Passed in rather than read off the router: the bar is drawn on the screens
  /// underneath the one on top as well, and the router only knows which route
  /// is topmost — so a rebuild while Settings is open (changing the palette
  /// does exactly that) would leave the results screen holding a dead square
  /// once you came back to it.
  final bool atSettings;

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
    return MediaQuery.withClampedTextScaling(
      // The bar stops growing before the catalogue does.
      //
      // "Text size" exists so a customer can read a price at arm's length, and
      // that is the content — the bar is furniture you tap. Letting its labels
      // run to the largest step pushed a 320pt phone past its width, and the
      // thing that would have had to give was the in-stock label, which is the
      // one control on screen nobody can name without it.
      maxScaleFactor: _chromeMaxScale,
      child: Directionality(
        textDirection: TextDirection.ltr,
        child: Container(
          decoration: BoxDecoration(
            // Its own ground, not the Scaffold's showing through.
            //
            // The bar drew nothing but its hairline and let the page's colour
            // stand in for its fill, which is the same colour and looks
            // identical — until something under it paints above that colour.
            // A Material paints its ink features itself, over its own ground
            // and outside whatever clip its children sit in, so an `Ink` in a
            // list scrolling under this bar was drawn straight across it. An
            // opaque bar is the wall that stops it, whatever a screen below
            // does with its ink.
            color: p.bg,
            border: Border(bottom: BorderSide(color: p.line)),
          ),
          child: _bar(context),
        ),
      ),
    );
  }

  /// Two rows: who we are and where you are, then what you can press.
  ///
  /// One row could not hold a mark, a back control, a shop name and three
  /// actions at the width this layout is capped to — the branch name was
  /// squeezed to a couple of characters and the breadcrumbs to a sliver. Split,
  /// the mark can be set half again as large, the shop name never competes for
  /// width, and the breadcrumbs get the whole line they need. It costs about
  /// 50pt of height, which is the trade.
  Widget _bar(BuildContext context) {
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
          LayoutBuilder(
            builder: (context, row) => Row(
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
                _ShrinkTo(
                  width: _stockCap(row.maxWidth, leading != null),
                  child: const InStockToggle(),
                ),
                const SizedBox(width: _gap),
                // Straight to Settings. It sat behind an overflow menu while
                // the scanner shared that menu with it; a menu you open to find
                // a single item is a tap that buys nothing.
                //
                // Inert on Settings itself, where it used to push a second copy
                // of the screen you were already looking at — a tap that
                // changed nothing on screen and left a back control that had to
                // be pressed twice. Dimmed and left in place rather than
                // removed: the row's widths are measured against a square that
                // is always there, so taking it out would reflow the whole bar
                // every time Settings opened.
                Opacity(
                  opacity: atSettings ? .38 : 1,
                  child: IconSquare(
                    Icons.tune,
                    size: _control,
                    onTap: atSettings ? null : () => context.push(Routes.settings),
                  ),
                ),
              ],
            ),
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

/// A ceiling, not a share.
///
/// The stock toggle and the branch pill used to sit in the row as `Flexible`,
/// which allots a child half the free space and lets it keep whatever it does
/// not use — so on anything wider than about a 400pt phone the pair took their
/// natural width and left the remainder as a hole between the last control and
/// the edge of the bar. A 1366pt tablet ended its row 378pt short.
///
/// As an ordinary child with a maximum instead, each one takes exactly the
/// width its label needs, the search field beside it absorbs the rest, and the
/// row reaches the edge at every size. The cap is what the `Flexible` was
/// really there for: a long shop name, or an Arabic label half again as long
/// as its English, shortens rather than pushing the row past its width.
class _ShrinkTo extends StatelessWidget {
  const _ShrinkTo({required this.width, required this.child});

  final double width;
  final Widget child;

  @override
  Widget build(BuildContext context) => ConstrainedBox(
        constraints: BoxConstraints(maxWidth: width),
        child: child,
      );
}

/// What the stock toggle may take: everything the row has left once the back
/// control, the settings square and the gaps are paid for, less what the field
/// needs to keep its word. Below that the two share what there is, which is the
/// even split the narrowest screen already had.
double _stockCap(double row, bool hasLeading) {
  final fixed = (hasLeading ? _control + _gap : 0) + _gap * 2 + _control;
  return math.max(_searchComfort, row - fixed - _searchComfort);
}

/// One height and one gap, so the bar has a rhythm rather than a set of
/// one-off numbers. [_group] separates the two halves of a row; [_gap]
/// separates siblings inside one.
const double _control = 38;

/// Under this the search field is an icon: there is no room for the glass, its
/// gap and a legible word.
const double _searchMin = 76;

/// And under this the word is there but ellipsing. What shares the field's row
/// is capped so the field is not pushed below it while the row still has space
/// to give.
const double _searchComfort = 96;

/// How far the bar's own labels will grow, whatever the text-size setting says.
const double _chromeMaxScale = 1.25;
const double _gap = 8;
const double _group = 14;

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
            final iconOnly = constraints.maxWidth < _searchMin;
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
/// Named a toggle rather than a pill because there is a `StockPill` in
/// `pearl_widgets` that only reports stock — this one changes it.
///
/// It lives in the bar rather than in the results filter panel because it is
/// not a refinement of one screen — it scopes the brand list, the results and
/// every count the funnel shows. A customer who has said they want stock has
/// said it for the whole visit.
class InStockToggle extends StatelessWidget {
  const InStockToggle({super.key});

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
            // Flexible: "In stock" is eight characters and its Arabic is
            // thirteen, so a label that cannot give way turns a bar that fits
            // in one language into one that overflows in the other.
            Flexible(
              child: Text(
                L.of(context).inStock,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: PearlText.label.copyWith(
                  color: on ? p.accentInk : p.muted,
                  fontSize: 11.5,
                ),
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
///
/// The globe says what the word cannot. "العربية" to someone who does not read
/// Arabic — or "English" to someone who does not read Latin — is a label in a
/// bar full of labels, and nothing about it announces that it is the control
/// that changes the language. The icon carries that, the word answers "into
/// which", and neither has to do both jobs.
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
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Same size and gap as the shop pill beside it: the two read as one
            // family of controls rather than two designs sharing a row.
            Icon(Icons.language, size: 15, color: p.ink),
            const SizedBox(width: 8),
            // Flexible for the same reason the stock label is: the pill now
            // has two things in it, and the word has to be the one that gives
            // way if the row it sits in ever runs out of width.
            Flexible(
              // The other language, in its own script — the one word a speaker
              // of it will recognise without reading the rest of the screen.
              child: Text(
                arabic ? t.english : t.arabic,
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

/// The active shop. Tapping it opens the picker; every stock number on screen
/// is relative to whatever is shown here.
class BranchPill extends StatelessWidget {
  const BranchPill({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final state = context.watch<BranchCubit>().state;
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
                  state.showingAll
                    ? L.of(context).allStores
                    : state.selected?.label ?? L.of(context).chooseStore,
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

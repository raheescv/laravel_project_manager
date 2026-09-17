import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/qloud_logo.dart';

/// Which branch this session works as. The router holds a user assigned to more
/// than one branch here after every sign-in — and after an unlock, unless the
/// till turned that off in Settings. Click-and-go: the tap sets the branch (the
/// stock sold from, and where sales are booked) and the router carries on to the
/// start screen. Only the user's own assigned branches are offered.
///
/// "Welcome split" (docs/mobile-branch-picker-preview.html, direction A): the
/// login gradient carries on as a brand panel and the branches sit on the canvas.
/// A landscape tablet splits the screen, a portrait tablet stacks the panel as a
/// header, and a phone keeps the gradient top with the list as a rising sheet.
/// On a tablet the cards stretch to fill the screen.
class BranchSelectScreen extends StatefulWidget {
  const BranchSelectScreen({super.key});

  @override
  State<BranchSelectScreen> createState() => _BranchSelectScreenState();
}

/// More than this and the list outgrows a tablet screen, so a search field
/// appears.
const int _searchAfter = 6;

class _BranchSelectScreenState extends State<BranchSelectScreen> {
  final _search = TextEditingController();
  String _query = '';

  /// The branch being applied, so a double tap can't pick two.
  int? _choosing;

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<void> _choose(Branch b) async {
    if (_choosing != null) return;
    final branch = context.read<BranchCubit>();
    final auth = context.read<AuthCubit>();
    setState(() => _choosing = b.id);
    try {
      await branch.setBranch(b);
      await auth.confirmBranch();
    } finally {
      if (mounted) setState(() => _choosing = null);
    }
  }

  List<Branch> _filtered(List<Branch> all) {
    final q = _query.trim().toLowerCase();
    if (q.isEmpty) return all;
    return all.where((b) => '${b.name} ${b.code} ${b.location}'.toLowerCase().contains(q)).toList();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final user = context.watch<AuthCubit>().user;
    final branch = context.watch<BranchCubit>();
    final all = branch.branches;

    Widget pane({required int columns, bool sheet = false, bool splitTop = false}) => _BranchPane(
          total: all.length,
          columns: columns,
          sheet: sheet,
          splitTop: splitTop,
          firstName: _firstName(user),
          search: all.length > _searchAfter
              ? _SearchField(controller: _search, onChanged: (v) => setState(() => _query = v))
              : null,
          grid: _BranchGrid(
            branches: _filtered(all),
            columns: columns,
            selectedId: branch.selected?.id,
            choosingId: _choosing,
            onChoose: _choose,
          ),
        );

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: p.canvas,
        body: LayoutBuilder(
          builder: (context, c) {
            if (context.isTablet && c.maxWidth >= 900) {
              return Row(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  SizedBox(width: c.maxWidth * 0.38, child: _WelcomeHero(user: user, layout: _HeroLayout.side)),
                  Expanded(child: pane(columns: 2, splitTop: true)),
                ],
              );
            }
            if (context.isTablet) {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _WelcomeHero(user: user, layout: _HeroLayout.banner),
                  Expanded(child: pane(columns: 2)),
                ],
              );
            }
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _WelcomeHero(user: user, layout: _HeroLayout.compact),
                // The list rises over the bottom of the gradient as a sheet.
                Expanded(
                  child: Transform.translate(
                    offset: const Offset(0, -_sheetOverlap),
                    child: pane(columns: 1, sheet: true),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

const double _sheetOverlap = 28;

String _firstName(ApiUser? user) {
  final name = (user?.name ?? '').trim();
  return name.isEmpty ? '' : name.split(RegExp(r'\s+')).first;
}

enum _HeroLayout { side, banner, compact }

/// The login gradient, carried on: brand, date, greeting and who is signed in.
class _WelcomeHero extends StatelessWidget {
  const _WelcomeHero({required this.user, required this.layout});

  final ApiUser? user;
  final _HeroLayout layout;

  String get _greeting {
    final h = DateTime.now().hour;
    return h < 12 ? 'Good morning' : (h < 17 ? 'Good afternoon' : 'Good evening');
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final inset = MediaQuery.viewPaddingOf(context);
    final first = _firstName(user);
    final date = DateFormat('EEEE d MMMM').format(DateTime.now()).toUpperCase();
    final side = layout == _HeroLayout.side;
    final compact = layout == _HeroLayout.compact;

    final brand = Row(
      children: [
        QloudLogomark(height: compact ? 22 : 26, color: Colors.white),
        const SizedBox(width: 10),
        Text('QLOUD', style: ui(size: 11.5, weight: FontWeight.w800, color: Colors.white, letterSpacing: 3.2)),
        const SizedBox(width: 6),
        Text('POS', style: ui(size: 10, weight: FontWeight.w700, color: Colors.white54, letterSpacing: 2.8)),
      ],
    );

    final copy = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(date, style: ui(size: compact ? 10 : 11, weight: FontWeight.w800, color: p.accent, letterSpacing: 1.8)),
        SizedBox(height: compact ? 8 : 12),
        Text(
          first.isEmpty ? '$_greeting!' : (layout == _HeroLayout.banner ? '$_greeting, $first' : '$_greeting,\n$first'),
          style: serif(size: side ? 44 : (compact ? 29 : 36), color: Colors.white, height: 1.08),
        ),
        SizedBox(height: compact ? 8 : 12),
        ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 360),
          child: Text(
            'Which branch are you working at? Your stock, prices and the sales you ring up all follow it.',
            style: ui(size: compact ? 12.5 : 14, weight: FontWeight.w500, color: Colors.white70, height: 1.5),
          ),
        ),
      ],
    );

    final Widget body;
    switch (layout) {
      case _HeroLayout.side:
        body = Padding(
          padding: EdgeInsets.fromLTRB(44, inset.top + 30, 40, inset.bottom + 30),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [brand, const Spacer(), copy, const Spacer(), _SignedInCard(user: user)],
          ),
        );
      case _HeroLayout.banner:
        body = Padding(
          padding: EdgeInsets.fromLTRB(40, inset.top + 24, 40, 30),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              brand,
              const SizedBox(height: 26),
              Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(child: copy),
                  const SizedBox(width: 24),
                  SizedBox(width: 300, child: _SignedInCard(user: user)),
                ],
              ),
            ],
          ),
        );
      case _HeroLayout.compact:
        body = Padding(
          padding: EdgeInsets.fromLTRB(22, inset.top + 16, 22, 26 + _sheetOverlap),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [brand, const SizedBox(height: 22), copy],
          ),
        );
    }

    return Container(
      clipBehavior: Clip.hardEdge,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [p.primary, p.primaryDark, Color.lerp(p.primaryDark, Colors.black, 0.22)!],
        ),
      ),
      child: Stack(
        children: [
          Positioned(
            right: -90,
            top: -80,
            child: Container(
              width: 300,
              height: 300,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(colors: [p.accent.withValues(alpha: 0.35), Colors.transparent]),
              ),
            ),
          ),
          Positioned(
            right: side ? -70 : -40,
            bottom: side ? -50 : -30,
            child: Opacity(
              opacity: 0.06,
              child: QloudLogomark(height: side ? 320 : (compact ? 190 : 240), color: Colors.white),
            ),
          ),
          body,
        ],
      ),
    );
  }
}

/// Who is signed in, and the way out — a shared till gets handed over.
class _SignedInCard extends StatelessWidget {
  const _SignedInCard({required this.user});

  final ApiUser? user;

  @override
  Widget build(BuildContext context) {
    final cfg = context.read<AuthCubit>().config;
    final u = user;
    final subtitle = (u?.designation.isNotEmpty ?? false)
        ? u!.designation
        : ((u?.role.isNotEmpty ?? false) ? u!.role : 'Signed in');
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.white.withValues(alpha: 0.14)),
      ),
      child: Row(
        children: [
          ProfileAvatar(
            letter: u?.initial ?? '?',
            imageUrl: (u?.hasPhoto ?? false) ? cfg.assetUrl(u!.photoUrl) : null,
            headers: cfg.assetHeaders,
            size: 40,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(u?.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis,
                    style: ui(size: 14, weight: FontWeight.w700, color: Colors.white)),
                const SizedBox(height: 2),
                Text(subtitle, maxLines: 1, overflow: TextOverflow.ellipsis,
                    style: ui(size: 11.5, weight: FontWeight.w500, color: Colors.white60)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: () => context.read<AuthCubit>().logout(),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(11),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.logout, size: 15, color: Colors.white),
                  const SizedBox(width: 6),
                  Text('Sign out', style: ui(size: 12, weight: FontWeight.w700, color: Colors.white)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// The canvas side: heading, search when the list is long, and the branches.
class _BranchPane extends StatelessWidget {
  const _BranchPane({
    required this.total,
    required this.columns,
    required this.grid,
    required this.firstName,
    this.search,
    this.sheet = false,
    this.splitTop = false,
  });

  final int total;
  final int columns;
  final Widget grid;
  final Widget? search;
  final String firstName;

  /// Phone: drawn as a sheet rising over the hero, with the sign-out link below.
  final bool sheet;

  /// Beside the hero on a landscape tablet, so it clears the status bar itself.
  final bool splitTop;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final inset = MediaQuery.viewPaddingOf(context);
    final heading = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text('Your branches', style: serif(size: sheet ? 21 : 26, color: p.ink)),
        const SizedBox(height: 4),
        Text('$total assigned to you · tap one to start',
            style: ui(size: sheet ? 12 : 13, weight: FontWeight.w500, color: p.textSecondary)),
      ],
    );
    final side = sheet ? 16.0 : (splitTop ? 40.0 : 32.0);
    return Container(
      decoration: BoxDecoration(
        color: p.canvas,
        borderRadius: sheet ? const BorderRadius.vertical(top: Radius.circular(28)) : null,
      ),
      padding: EdgeInsets.fromLTRB(
        side,
        sheet ? 22 : (splitTop ? inset.top + 36 : 28),
        side,
        sheet ? inset.bottom + _sheetOverlap + 8 : inset.bottom + 28,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (sheet || search == null)
            heading
          else
            Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [Expanded(child: heading), const SizedBox(width: 16), SizedBox(width: 260, child: search)],
            ),
          if (sheet && search != null) ...[const SizedBox(height: 14), search!],
          SizedBox(height: sheet ? 14 : 20),
          Expanded(child: grid),
          if (sheet)
            Padding(
              padding: const EdgeInsets.only(top: 10),
              child: Center(
                child: GestureDetector(
                  onTap: () => context.read<AuthCubit>().logout(),
                  child: Text(firstName.isEmpty ? 'Sign out' : 'Not $firstName? Sign out',
                      style: ui(size: 12, weight: FontWeight.w700, color: p.textSecondary)),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// A phone lists the cards at their natural height; a tablet stretches them to
/// fill the space (capped, so a short list doesn't become slabs) and scrolls
/// only once they no longer fit.
class _BranchGrid extends StatelessWidget {
  const _BranchGrid({
    required this.branches,
    required this.columns,
    required this.selectedId,
    required this.choosingId,
    required this.onChoose,
  });

  final List<Branch> branches;
  final int columns;
  final int? selectedId;
  final int? choosingId;
  final ValueChanged<Branch> onChoose;

  static const double _gap = 14;
  // Fits the worst card: a two-line name, the Current badge on its own run and
  // a two-line address. Scaled with the device text size below.
  static const double _minRow = 136;
  static const double _maxRow = 240;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    if (branches.isEmpty) {
      return Center(
        child: Text('No branch matches that search',
            style: ui(size: 13, weight: FontWeight.w600, color: p.textMuted)),
      );
    }

    Widget card(int i, {required bool compact}) => _BranchCard(
          branch: branches[i],
          active: branches[i].id == selectedId,
          busy: branches[i].id == choosingId,
          compact: compact,
          onTap: () => onChoose(branches[i]),
        );

    if (columns == 1) {
      return ListView.separated(
        padding: const EdgeInsets.only(bottom: 6),
        itemCount: branches.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (_, i) => card(i, compact: true),
      );
    }

    return LayoutBuilder(
      builder: (context, c) {
        final rows = (branches.length / columns).ceil();
        final fit = (c.maxHeight - _gap * (rows - 1)) / rows;
        final minRow = MediaQuery.textScalerOf(context).scale(_minRow);
        final rowHeight = fit.clamp(minRow, _maxRow < minRow ? minRow : _maxRow);
        final used = rows * rowHeight + _gap * (rows - 1);
        return GridView.builder(
          padding: EdgeInsets.only(top: used < c.maxHeight ? (c.maxHeight - used) / 2 : 0, bottom: 4),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: columns,
            mainAxisSpacing: _gap,
            crossAxisSpacing: _gap,
            mainAxisExtent: rowHeight,
          ),
          itemCount: branches.length,
          itemBuilder: (_, i) => card(i, compact: false),
        );
      },
    );
  }
}

class _BranchCard extends StatelessWidget {
  const _BranchCard({
    required this.branch,
    required this.active,
    required this.busy,
    required this.compact,
    required this.onTap,
  });

  final Branch branch;
  final bool active;
  final bool busy;
  final bool compact;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    // `Branch.fromJson` falls back to the name for an empty location.
    final location = branch.location.trim() == branch.name.trim() ? '' : branch.location.trim();
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: EdgeInsets.fromLTRB(compact ? 13 : 18, compact ? 13 : 16, compact ? 12 : 16, compact ? 13 : 16),
        decoration: BoxDecoration(
          color: active ? Color.alphaBlend(p.primary.withValues(alpha: 0.07), p.card) : p.card,
          borderRadius: BorderRadius.circular(compact ? 18 : 20),
          border: Border.all(color: active ? p.primary : Colors.transparent, width: 1.5),
          boxShadow: context.astraTheme.softShadow,
        ),
        child: Row(
          children: [
            _Monogram(branch: branch, size: compact ? 46 : 56),
            SizedBox(width: compact ? 12 : 16),
            Expanded(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Wrap(
                    spacing: 8,
                    runSpacing: 4,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      Text(branch.name,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: ui(size: compact ? 14.5 : 17, weight: FontWeight.w700, color: p.ink)),
                      if (active) const _CurrentBadge(),
                    ],
                  ),
                  if (location.isNotEmpty) ...[
                    SizedBox(height: compact ? 3 : 6),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: const EdgeInsets.only(top: 1),
                          child: Icon(Icons.place_outlined, size: compact ? 13 : 14, color: p.textMuted),
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(location,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: ui(
                                  size: compact ? 11.5 : 12.5,
                                  weight: FontWeight.w500,
                                  color: p.textSecondary,
                                  height: 1.35)),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(width: 10),
            _Trail(active: active, busy: busy, size: compact ? 30 : 34),
          ],
        ),
      ),
    );
  }
}

/// The branch code (HM, MC, TVM) — initials would give "Main Store" and
/// "Market City" the same M. Falls back to initials when there is no code.
class _Monogram extends StatelessWidget {
  const _Monogram({required this.branch, required this.size});

  final Branch branch;
  final double size;

  String get _label {
    final code = branch.code.trim().toUpperCase();
    if (code.isNotEmpty) return code.length > 4 ? code.substring(0, 4) : code;
    final words = branch.name.trim().split(RegExp(r'\s+')).where((w) => w.isNotEmpty);
    return words.take(2).map((w) => w[0].toUpperCase()).join();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      width: size,
      height: size,
      padding: EdgeInsets.all(size * 0.16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [p.primary, p.primaryDark],
        ),
        borderRadius: BorderRadius.circular(size * 0.3),
        boxShadow: [
          BoxShadow(color: p.primary.withValues(alpha: 0.35), blurRadius: 14, offset: const Offset(0, 6), spreadRadius: -6),
        ],
      ),
      alignment: Alignment.center,
      child: FittedBox(
        fit: BoxFit.scaleDown,
        child: Text(_label, style: ui(size: size * 0.27, weight: FontWeight.w800, color: Colors.white, letterSpacing: 0.4)),
      ),
    );
  }
}

class _CurrentBadge extends StatelessWidget {
  const _CurrentBadge();

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final onAccent =
        ThemeData.estimateBrightnessForColor(p.accent) == Brightness.dark ? Colors.white : const Color(0xFF2B2006);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(color: p.accent, borderRadius: BorderRadius.circular(999)),
      child: Text('CURRENT', style: ui(size: 9, weight: FontWeight.w800, color: onAccent, letterSpacing: 0.8)),
    );
  }
}

class _Trail extends StatelessWidget {
  const _Trail({required this.active, required this.busy, required this.size});

  final bool active;
  final bool busy;
  final double size;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    if (busy) {
      return SizedBox(
        width: size,
        height: size,
        child: Padding(
          padding: EdgeInsets.all(size * 0.2),
          child: CircularProgressIndicator(strokeWidth: 2.4, color: p.primary),
        ),
      );
    }
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: active ? p.primaryGradient : null,
        color: active ? null : p.tint,
      ),
      child: Icon(active ? Icons.check : Icons.arrow_forward,
          size: size * 0.47, color: active ? Colors.white : p.primary),
    );
  }
}

class _SearchField extends StatelessWidget {
  const _SearchField({required this.controller, required this.onChanged});

  final TextEditingController controller;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return SizedBox(
      height: 44,
      child: TextField(
        controller: controller,
        onChanged: onChanged,
        textInputAction: TextInputAction.search,
        style: ui(size: 13.5, weight: FontWeight.w600, color: p.ink),
        decoration: InputDecoration(
          hintText: 'Search branches',
          hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
          prefixIcon: Icon(Icons.search, size: 18, color: p.textMuted),
          filled: true,
          fillColor: p.card,
          contentPadding: EdgeInsets.zero,
          enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(14), borderSide: BorderSide(color: p.hairline)),
          focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(14), borderSide: BorderSide(color: p.primary, width: 1.5)),
        ),
      ),
    );
  }
}

import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/logic/student_card_cubit/student_card_cubit.dart';
import 'package:invo/features/student_card/widgets/card_status.dart';
import 'package:invo/features/student_card/widgets/student_card_grid_tile.dart';
import 'package:invo/features/student_card/widgets/student_card_tile.dart';
import 'package:invo/features/student_card/widgets/tap_card_sheet.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// How the search results render — a compact row list or a photo-forward
/// grid. Persisted per device, the same way New Sale remembers its catalog
/// view (`LocalStorageService.studentCardView`).
enum _CardView { grid, list }

/// Narrows the current search results to who still needs a card. Scoped to
/// this search session only — unlike [_CardView] it isn't a device setting.
enum _CardFilter { all, unlinked, linked }

/// Link (or replace) a student's NFC card from the till.
///
/// The recommended way to enrol cards: the UID is read by the same device family
/// that reads it at the canteen, so what is stored is exactly what the till will
/// see. Gated on `student card.assign` (router + API).
///
/// Unlike most screens this one has no gradient hero: it is a working surface
/// the user scans for a name, so the chrome is a single dense command bar and
/// every pixel below it goes to results.
class LinkCardScreen extends StatefulWidget {
  const LinkCardScreen({super.key});

  @override
  State<LinkCardScreen> createState() => _LinkCardScreenState();
}

class _LinkCardScreenState extends State<LinkCardScreen> {
  late final StudentCardCubit _cubit = serviceLocator<StudentCardCubit>();
  late final TextEditingController _search = TextEditingController();
  Timer? _debounce;

  _CardView _view = serviceLocator<LocalStorageService>().studentCardView == 'grid'
      ? _CardView.grid
      : _CardView.list;
  _CardFilter _filter = _CardFilter.all;

  @override
  void initState() {
    super.initState();
    _cubit.search('');
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    _cubit.close();
    super.dispose();
  }

  void _onSearch(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () => _cubit.search(value));
  }

  void _setView(_CardView mode) {
    if (_view == mode) return;
    HapticFeedback.selectionClick();
    setState(() => _view = mode);
    serviceLocator<LocalStorageService>().setStudentCardView(mode == _CardView.grid ? 'grid' : 'list');
  }

  Future<void> _link(int accountId, String name) async {
    final uid = await showReadCardUidSheet(context, studentName: name);
    if (uid == null || !mounted) return;
    final card = await _cubit.link(accountId, uid);
    if (!mounted) return;
    if (card != null) {
      AstraSnack.success(context, 'Card $uid linked to $name');
    } else {
      AstraSnack.error(context, _cubit.state.errorMessage ?? 'Could not link the card.');
    }
  }

  /// A recessed fill for the controls sunk into the command bar — neutral so
  /// it reads the same on every preset, light or dark.
  Color get _softFill {
    final p = context.astra;
    return p.isDark ? Colors.white.withValues(alpha: 0.07) : Colors.black.withValues(alpha: 0.045);
  }

  Widget _searchField(AstraPalette p, {required double height}) {
    return Container(
      height: height,
      padding: const EdgeInsets.only(left: 14, right: 12),
      decoration: BoxDecoration(color: _softFill, borderRadius: BorderRadius.circular(12)),
      child: Row(
        children: [
          Icon(Icons.search, color: p.textSecondary, size: 19),
          const SizedBox(width: 9),
          Expanded(
            child: TextField(
              controller: _search,
              onChanged: _onSearch,
              style: ui(size: 13.5, weight: FontWeight.w600, color: p.ink),
              decoration: InputDecoration(
                isDense: true,
                hintText: 'Name, admission no or parent mobile',
                hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
                border: InputBorder.none,
                contentPadding: EdgeInsets.zero,
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Grid/list switcher, sunk into the command bar beside the search field.
  Widget _viewToggle() {
    final p = context.astra;
    Widget btn(IconData icon, _CardView mode) {
      final active = _view == mode;
      return GestureDetector(
        onTap: () => _setView(mode),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          width: 32,
          height: 28,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            gradient: active ? p.primaryGradient : null,
            borderRadius: BorderRadius.circular(9),
          ),
          child: Icon(icon, size: 16, color: active ? Colors.white : p.textMuted),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: _softFill, borderRadius: BorderRadius.circular(12)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          btn(Icons.view_agenda_outlined, _CardView.list),
          const SizedBox(width: 2),
          btn(Icons.grid_view_rounded, _CardView.grid),
        ],
      ),
    );
  }

  /// Title, search and the view switcher on one line — a flat bar closed by a
  /// hairline, so the results start as high up the screen as they can.
  Widget _commandBar(AstraPalette p, {required bool tablet}) {
    final search = _searchField(p, height: tablet ? 40 : 46);
    final title = Text(
      'Link Student Card',
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      style: serif(size: tablet ? 20 : 21, color: p.ink),
    );

    // The search is capped and right-aligned so it sits next to the switcher
    // instead of stretching the width of a desktop monitor; on a narrow tablet
    // it simply takes whatever is left, so the row can never overflow.
    final Widget inner = tablet
        ? Row(
            children: [
              Flexible(child: title),
              const SizedBox(width: 20),
              Expanded(
                child: Align(
                  alignment: Alignment.centerRight,
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 420),
                    child: search,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              _viewToggle(),
            ],
          )
        : Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        title,
                        const SizedBox(height: 2),
                        Text(
                          'Find the student, then tap their card',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 10),
                  _viewToggle(),
                ],
              ),
              const SizedBox(height: 12),
              search,
            ],
          );

    return Container(
      decoration: BoxDecoration(
        color: p.cardSolid,
        border: Border(bottom: BorderSide(color: p.hairline)),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(tablet ? 24 : 16, 12, tablet ? 24 : 16, tablet ? 12 : 14),
          child: inner,
        ),
      ),
    );
  }

  /// All / Not linked / Linked, each labelled with its count against the
  /// current search. [compact] sizes each segment to its label instead of
  /// splitting the row evenly, so it can sit as a quiet cluster under the
  /// command bar on a wide screen rather than stretching across it.
  Widget _filterSegmented(List<StudentCard> results, {bool compact = false}) {
    final linked = results.where((s) => s.hasCard).length;
    final options = <(_CardFilter, String, int)>[
      (_CardFilter.all, 'All', results.length),
      (_CardFilter.unlinked, 'Not linked', results.length - linked),
      (_CardFilter.linked, 'Linked', linked),
    ];
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(color: _softFill, borderRadius: BorderRadius.circular(13)),
      child: Row(
        mainAxisSize: compact ? MainAxisSize.min : MainAxisSize.max,
        children: [for (final (f, label, count) in options) _filterSeg(f, label, count, compact)],
      ),
    );
  }

  Widget _filterSeg(_CardFilter f, String label, int count, bool compact) {
    final p = context.astra;
    final active = _filter == f;
    final seg = GestureDetector(
      onTap: () {
        if (_filter == f) return;
        HapticFeedback.selectionClick();
        setState(() => _filter = f);
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: EdgeInsets.symmetric(vertical: 7, horizontal: compact ? 13 : 0),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: active ? p.cardSolid : Colors.transparent,
          borderRadius: BorderRadius.circular(9),
        ),
        child: Text(
          '$label · $count',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: ui(size: 11.5, weight: FontWeight.w700, color: active ? p.primary : p.textSecondary),
        ),
      ),
    );
    return compact ? seg : Expanded(child: seg);
  }

  Widget _listRow(AstraPalette p, StudentCard student, {required bool busy}) {
    final status = cardStatusOf(student, p);
    return AstraCard(
      onTap: busy ? null : () => _link(student.accountId, student.name),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          StudentCardTile(
            card: student,
            trailing: IconChip(icon: Icons.nfc_rounded, size: 34, radius: 11, bg: p.tint, fg: p.primary),
          ),
          const SizedBox(height: 10),
          StatusPill(label: status.label, bg: status.bg, fg: status.fg, icon: status.icon),
        ],
      ),
    );
  }

  /// Search results, filtered and laid out. On tablet/desktop this fills the
  /// full width — no [MaxWidthBox] cap, and a list view wraps into columns
  /// instead of staying a single edge-to-edge row.
  Widget _resultsBody(AstraPalette p, StudentCardState state, {required bool tablet}) {
    final hPad = tablet ? 24.0 : 16.0;
    if (state.isBusy && state.results.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }
    if (state.results.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text(state.errorMessage ?? 'No students found.',
              textAlign: TextAlign.center, style: ui(size: 13, weight: FontWeight.w600, color: p.textMuted)),
        ),
      );
    }
    final filtered = switch (_filter) {
      _CardFilter.all => state.results,
      _CardFilter.linked => state.results.where((s) => s.hasCard).toList(),
      _CardFilter.unlinked => state.results.where((s) => !s.hasCard).toList(),
    };
    if (filtered.isEmpty) {
      return Center(
        child: Text('No students match this filter.', style: ui(size: 13, weight: FontWeight.w600, color: p.textMuted)),
      );
    }
    final content = _view == _CardView.list
        ? (tablet
            ? GridView.builder(
                padding: EdgeInsets.fromLTRB(hPad, 12, hPad, 24),
                gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                  maxCrossAxisExtent: 460,
                  mainAxisExtent: 140,
                  crossAxisSpacing: 14,
                  mainAxisSpacing: 12,
                ),
                itemCount: filtered.length,
                itemBuilder: (_, i) => _listRow(p, filtered[i], busy: state.isBusy),
              )
            : ListView.separated(
                padding: EdgeInsets.fromLTRB(hPad, 12, hPad, 24),
                itemCount: filtered.length,
                separatorBuilder: (_, __) => const SizedBox(height: 9),
                itemBuilder: (_, i) => _listRow(p, filtered[i], busy: state.isBusy),
              ))
        : GridView.builder(
            padding: EdgeInsets.fromLTRB(hPad, 12, hPad, 24),
            gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
              maxCrossAxisExtent: 190,
              mainAxisExtent: 210,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
            ),
            itemCount: filtered.length,
            itemBuilder: (_, i) {
              final student = filtered[i];
              return StudentCardGridTile(
                card: student,
                onTap: state.isBusy ? null : () => _link(student.accountId, student.name),
              );
            },
          );
    return tablet ? content : MaxWidthBox(maxWidth: 640, child: content);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final tablet = context.isTablet;
    final screen = Scaffold(
      body: AstraBackground(
        child: BlocBuilder<StudentCardCubit, StudentCardState>(
          bloc: _cubit,
          builder: (context, state) => Column(
            children: [
              _commandBar(p, tablet: tablet),
              Padding(
                padding: EdgeInsets.fromLTRB(tablet ? 24 : 16, tablet ? 14 : 12, tablet ? 24 : 16, 0),
                child: tablet
                    ? Align(
                        alignment: Alignment.centerLeft,
                        child: _filterSegmented(state.results, compact: true),
                      )
                    : _filterSegmented(state.results),
              ),
              Expanded(child: _resultsBody(p, state, tablet: tablet)),
            ],
          ),
        ),
      ),
    );
    // On tablet the side rail sets the status-bar icons from its own strip.
    // On phone there is no rail, and this screen's top is now a light surface
    // rather than the emerald hero, so it has to ask for dark icons itself.
    if (tablet) return screen;
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: p.isDark ? SystemUiOverlayStyle.light : SystemUiOverlayStyle.dark,
      child: screen,
    );
  }
}

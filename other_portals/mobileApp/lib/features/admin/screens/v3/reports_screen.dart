import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/admin/screens/v3/report_preview_screen.dart';
import 'package:invo/features/admin/widgets/report_sort_sheet.dart';
import 'package:invo/features/admin/widgets/report_picker_sheet.dart';
import 'package:invo/features/admin/widgets/report_staff_sheet.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/charts.dart';
import 'package:invo/shared/widgets/you_badge.dart';
import 'package:invo/shared/widgets/astra_range_picker.dart';

part 'reports_overview_sections.dart';

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});
  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  final _scrollCtl = ScrollController();
  StreamSubscription<int>? _branchSub;
  StreamSubscription<AuthState>? _authSub;

  /// The two reports this page holds: 0 = Sales Overview (performance,
  /// payments, per-day trend), 1 = Breakdown (By Item / By Category / By Staff
  /// ranking, and the Commission lines).
  /// The date range is shared, so it stays on top of both.
  /// Opens on the tab this user last left it on (saved per user, on device).
  late int _tab = _savedTab;

  /// The keys the two reports are saved under, by index.
  static const _tabKeys = ['overview', 'breakdown'];

  /// Breakdown rankings a saved choice may restore — anything else (an older
  /// build's key) leaves the cubit's default.
  static const _breakdownTypes = {'itemwise', 'categorywise', 'employeewise', 'commission'};

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();
  String? get _userId => context.read<AuthCubit>().user?.id;

  int get _savedTab => _tabKeys.indexOf(_storage.reportsTabFor(_userId) ?? '').clamp(0, 1);

  String? get _savedBreakdown {
    final saved = _storage.reportsBreakdownFor(_userId);
    return _breakdownTypes.contains(saved) ? saved : null;
  }

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(_onScroll);
    // Skip the report fetches entirely when the user can't view reports —
    // the endpoint would 403 (see EnsureMobilePermission on /admin/reports).
    if (!context.read<AuthCubit>().hasPermission(PermissionSlug.report)) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AdminCubit>()
        ..reanchorRange()
        ..loadReports(type: _savedBreakdown, force: true)
        ..loadOverview();
    });
    // The shell keeps this screen alive, so reload the current range for the
    // new branch when the active branch changes. `force` because the range is
    // the same but nothing cached for the old branch survives it.
    // Opening or closing the day moves "today"; a preset range follows it.
    _authSub = context.read<AuthCubit>().stream.listen((_) {
      if (!mounted) return;
      final admin = context.read<AdminCubit>();
      if (!admin.reanchorRange()) return;
      admin
        ..loadReports()
        ..loadOverview();
    });
    _branchSub = context.read<BranchCubit>().onBranchChanged.listen((_) {
      if (!mounted) return;
      context.read<AdminCubit>()
        ..loadReports(force: true)
        ..loadOverview();
    });
  }

  @override
  void dispose() {
    _scrollCtl.dispose();
    _branchSub?.cancel();
    _authSub?.cancel();
    super.dispose();
  }

  /// Switch report. The two reports have unrelated lengths, so the shared
  /// scroll position is reset instead of carrying over.
  void _setTab(int i) {
    if (_tab == i) return;
    setState(() => _tab = i);
    if (_scrollCtl.hasClients) _scrollCtl.jumpTo(0);
    unawaited(_storage.setReportsTabFor(_userId, _tabKeys[i]));
  }

  /// Switch the Breakdown ranking, remembered for this user's next visit.
  void _setBreakdown(AdminCubit admin, String type) {
    admin.setReportType(type);
    unawaited(_storage.setReportsBreakdownFor(_userId, type));
  }

  /// Infinite scroll: pull the next page of the breakdown once near the bottom.
  /// Only the breakdown report paginates.
  void _onScroll() {
    if (_tab != 1) return;
    if (!context.read<AuthCubit>().hasPermission(PermissionSlug.report)) return;
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 500) {
      context.read<AdminCubit>().loadMoreReport();
    }
  }

  @override
  Widget build(BuildContext context) {
    final admin = context.watch<AdminCubit>();
    final canView = context.read<AuthCubit>().hasPermission(PermissionSlug.report);

    // Tablet has no header band — the page head carries the title and the range
    // controls in one toolbar row (the preview's `.pagehead`).
    if (context.isTablet) {
      return Scaffold(
        backgroundColor: Colors.transparent,
        body: AstraBackground(
          child: SafeArea(
            bottom: false,
            child: canView
                ? Column(
                    children: [
                      _commandBar(admin),
                      Expanded(child: MaxWidthBox(maxWidth: 1120, child: _tabletReports(admin))),
                    ],
                  )
                : _restricted(),
          ),
        ),
      );
    }
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            // The report switcher rides in the header's title row, beside the
            // print button, so the body starts straight at the date range.
            EmeraldHeader(
              title: 'Reports',
              subtitle: canView ? null : 'Every angle on your sales',
              trailing: canView
                  ? Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        _reportTabs(),
                        const SizedBox(width: 8),
                        HeaderIconButton(icon: Icons.print_outlined, gold: true, onTap: () => unawaited(_openExport())),
                      ],
                    )
                  : null,
            ),
            Expanded(
              child: canView
                  ? MaxWidthBox(
                maxWidth: 820,
                child: _tab == 1
                    // Breakdown: the card fills what's left and scrolls its own
                    // rows, so they are built lazily as they come into view.
                    // Controls and the grand total stay put, which is also the
                    // better read on a long list.
                    ? Padding(
                        padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
                        child: Column(
                          children: [
                            _dateFilter(admin),
                            const SizedBox(height: 8),
                            Expanded(child: _breakdownCard(admin)),
                          ],
                        ),
                      )
                    : ListView(
                  controller: _scrollCtl,
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 110),
                  children: [
                    // One gap between every section keeps spacing uniform & DRY;
                    // null sections (e.g. the trend on a single-day range) drop out
                    // entirely so they don't leave an empty gap.
                    for (final section in <Widget?>[
                      _dateFilter(admin),
                      ..._tabSections(admin),
                    ])
                      if (section != null) Padding(padding: const EdgeInsets.only(bottom: 8), child: section),
                  ],
                ),
              )
                  : _restricted(),
            ),
          ],
        ),
      ),
    );
  }

  /// Sections of the Overview report (the date range is rendered separately,
  /// above them, since both reports share it). The Breakdown report is one
  /// card that fills the page, so it is laid out by [build] instead.
  List<Widget?> _tabSections(AdminCubit admin) =>
      [_salesPerformance(admin), _paymentOverview(admin), _byDay(admin)];

  /// A recessed fill for controls sunk into the command bar — neutral so it
  /// reads the same on every preset, light or dark. Same value as Link Card's.
  Color get _softFill {
    final p = context.astra;
    return p.isDark ? Colors.white.withValues(alpha: 0.07) : Colors.black.withValues(alpha: 0.045);
  }

  /// Report switcher — picks which of the two reports the page shows.
  ///
  /// On phone it sits in the header's title row, so it is drawn on the hero:
  /// a translucent track with a white thumb, labels only (no icons) so it
  /// leaves the title room on a 360-wide phone.
  ///
  /// [quiet] draws it the way the command bar draws its presets: a segmented
  /// cluster sunk into a recessed track, sized to its labels. Used on tablet,
  /// where it sits in the command bar beside the presets.
  Widget _reportTabs({bool quiet = false}) {
    final p = context.astra;
    Widget tab(String label, int index, IconData icon) {
      final active = _tab == index;
      final heroColor = active ? p.primary : Colors.white.withValues(alpha: 0.82);
      return GestureDetector(
        onTap: () {
          if (_tab == index) return;
          HapticFeedback.selectionClick();
          _setTab(index);
        },
        behavior: HitTestBehavior.opaque,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          curve: Curves.easeOut,
          padding: EdgeInsets.symmetric(vertical: 7, horizontal: quiet ? 14 : 11),
          alignment: Alignment.center,
          decoration: quiet
              ? _thumb(active)
              : BoxDecoration(color: active ? Colors.white : Colors.transparent, borderRadius: BorderRadius.circular(9)),
          child: quiet
              ? Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(icon, size: 14, color: active ? p.primary : p.textMuted),
                    const SizedBox(width: 6),
                    Text(label,
                        maxLines: 1,
                        style: ui(
                            size: 11.5,
                            weight: active ? FontWeight.w800 : FontWeight.w600,
                            color: active ? p.primary : p.textSecondary)),
                  ],
                )
              : Text(label,
                  maxLines: 1,
                  style: ui(size: 11.5, weight: active ? FontWeight.w800 : FontWeight.w700, color: heroColor)),
        ),
      );
    }

    final tabs = [tab('Overview', 0, Icons.insights_rounded), tab('Breakdown', 1, Icons.leaderboard_rounded)];
    if (quiet) return _sunkGroup(children: tabs);
    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withValues(alpha: 0.20)),
      ),
      child: Row(mainAxisSize: MainAxisSize.min, children: tabs),
    );
  }

  /// The report list, the one on screen ticked; a report opens its preview.
  Future<void> _openExport() => showReportPicker(context, current: _reportOnScreen);

  ExportReport get _reportOnScreen {
    if (_tab == 0) return ExportReport.overview;
    return switch (context.read<AdminCubit>().reportType) {
      'itemwise' => ExportReport.items,
      'categorywise' => ExportReport.categories,
      'commission' => ExportReport.commission,
      _ => ExportReport.staff,
    };
  }

  /// Whether the signed-in account reads every staff member's figures. A
  /// rank-and-file employee reads only their own — the server pins every
  /// report to them — so the commission report drops its staff filter and
  /// column for them.
  bool get _seesAllRecords => context.read<AuthCubit>().user?.seesAllRecords ?? true;

  Widget _restricted() => const Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: EmptyState(
            icon: Icons.lock_outline,
            title: 'Reports restricted',
            message: "You don't have permission to view sales reports. "
                'Ask an administrator to grant access.',
          ),
        ),
      );

  /// Tablet command bar — the title, the range it is showing, the report
  /// switcher and the whole range control on one flat bar closed by a hairline (the same chrome as
  /// Link Card): every control is sunk into a recessed group instead of
  /// floating as its own tinted pill, so the reports below start the page.
  ///
  /// The title side is [Expanded], not [Flexible], on purpose: the range line
  /// changes length as presets are picked ("Today" vs "1 – 23 Sep 2026"), and a
  /// loose fit would let that reflow the whole bar under the pointer. A tight
  /// flex fixes both halves, so tapping a preset never moves the controls.
  Widget _commandBar(AdminCubit admin) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(
        color: p.cardSolid,
        border: Border(bottom: BorderSide(color: p.hairline)),
      ),
      padding: const EdgeInsets.fromLTRB(24, 12, 24, 13),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text('Reports', maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 20, color: p.ink)),
                const SizedBox(height: 5),
                _rangeChip(admin),
              ],
            ),
          ),
          const SizedBox(width: 20),
          // Wrap, not Row: on a narrow tablet the groups drop to a second
          // line instead of overflowing the bar.
          Expanded(
            flex: 8,
            child: Wrap(
              alignment: WrapAlignment.end,
              crossAxisAlignment: WrapCrossAlignment.center,
              spacing: 10,
              runSpacing: 8,
              children: [_reportTabs(quiet: true), _presetSegmented(admin), _barActions(admin)],
            ),
          ),
        ],
      ),
    );
  }

  /// The range being reported on, as a tinted chip under the title — the one
  /// piece of live data in the bar, so it reads as data rather than a caption.
  Widget _rangeChip(AdminCubit admin) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.fromLTRB(8, 4, 10, 4),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(8)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.event_rounded, size: 12.5, color: p.primary),
          const SizedBox(width: 6),
          Flexible(
            child: Text(Dates.range(admin.startDate, admin.endDate),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(size: 11, weight: FontWeight.w700, color: p.primary)),
          ),
        ],
      ),
    );
  }

  /// The recessed shell both control groups share — a sunk track with a
  /// hairline edge, so each group reads as one machined control rather than a
  /// row of loose buttons.
  Widget _sunkGroup({required List<Widget> children, bool expand = false}) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: _softFill,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: p.hairline),
      ),
      child: Row(
        mainAxisSize: expand ? MainAxisSize.max : MainAxisSize.min,
        children: expand ? [for (final c in children) Expanded(child: c)] : children,
      ),
    );
  }

  /// The raised thumb that marks the live segment in a sunk group.
  BoxDecoration _thumb(bool active, {double radius = 10}) {
    final p = context.astra;
    return BoxDecoration(
      color: active ? p.cardSolid : Colors.transparent,
      borderRadius: BorderRadius.circular(radius),
      border: Border.all(color: active ? p.hairline : Colors.transparent),
      boxShadow: active ? context.astraTheme.softShadow : null,
    );
  }

  /// The date presets, in order: the open session's own day (only while it
  /// isn't the calendar date, labelled with that date), then Today /
  /// Yesterday / 7 Days / Month.
  List<(String, String)> get _presets {
    final session = context.select<AuthCubit, DateTime?>((a) => a.sessionDay);
    return [
      if (session != null) (Dates.dayMonth(session), 'session'),
      ('Today', 'today'),
      ('Yesterday', 'yesterday'),
      ('7 Days', '7d'),
      ('Month', 'month'),
    ];
  }

  /// The presets as one segmented cluster — the quiet sibling of the phone's
  /// gradient preset chips.
  Widget _presetSegmented(AdminCubit admin) {
    final presets = _presets;
    // Scales down rather than overflowing where a portrait tablet's bar is
    // narrower than the five segments.
    return FittedBox(
      fit: BoxFit.scaleDown,
      child: _sunkGroup(
        children: [for (final (label, id) in presets) _presetSegment(admin, label, id)],
      ),
    );
  }

  Widget _presetSegment(AdminCubit admin, String label, String id) {
    final p = context.astra;
    final active = admin.rangePreset == id;
    return GestureDetector(
      onTap: () {
        if (active) return;
        HapticFeedback.selectionClick();
        admin.setPreset(id);
      },
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        curve: Curves.easeOut,
        padding: const EdgeInsets.symmetric(vertical: 7, horizontal: 11),
        alignment: Alignment.center,
        decoration: _thumb(active),
        child: Text(label,
            maxLines: 1,
            style: ui(
              size: 11.5,
              weight: active ? FontWeight.w800 : FontWeight.w600,
              color: active ? p.primary : p.textSecondary,
            )),
      ),
    );
  }

  /// Custom range, refresh and print — a second sunk group beside the presets,
  /// so the bar carries two machined controls rather than five loose squares.
  Widget _barActions(AdminCubit admin) {
    final busy = admin.reportLoading || admin.overviewLoading;
    return _sunkGroup(
      children: [
        _barAction(Icons.date_range_rounded, 'Custom range',
            () => _pickCustom(admin), active: admin.rangePreset == 'custom'),
        _barAction(Icons.refresh_rounded, 'Refresh',
            busy ? null : () => unawaited(admin.refreshReports()), busy: busy),
        _barAction(Icons.print_rounded, 'Print or export', () => unawaited(_openExport())),
      ],
    );
  }

  Widget _barAction(IconData icon, String tooltip, VoidCallback? onTap, {bool active = false, bool busy = false}) {
    final p = context.astra;
    return Tooltip(
      message: tooltip,
      waitDuration: const Duration(milliseconds: 500),
      child: GestureDetector(
        onTap: onTap == null
            ? null
            : () {
                HapticFeedback.selectionClick();
                onTap();
              },
        behavior: HitTestBehavior.opaque,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          curve: Curves.easeOut,
          width: 36,
          height: 30,
          alignment: Alignment.center,
          decoration: active
              ? BoxDecoration(
                  gradient: p.primaryGradient,
                  borderRadius: BorderRadius.circular(10),
                  boxShadow: context.astraTheme.floatShadow(p.primary),
                )
              : _thumb(false),
          child: busy
              ? SizedBox(width: 15, height: 15, child: CircularProgressIndicator(strokeWidth: 2.2, color: p.primary))
              : Icon(icon, size: 16.5, color: active ? Colors.white : p.textSecondary),
        ),
      ),
    );
  }

  /// Tablet: the date filter spans the top as a toolbar (the page head); the
  /// switcher picks the report and the Overview's sections split into two
  /// columns to use the width. The split only kicks in once each column would
  /// still be at least phone-width — on a small tablet in portrait two columns
  /// would squeeze every chart and donut, so those stack instead. The Breakdown
  /// report is a single ranked table, so it stays one column.
  Widget _tabletReports(AdminCubit admin) {
    Widget col(List<Widget?> items) => Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            for (final s in items)
              if (s != null) Padding(padding: const EdgeInsets.only(bottom: 12), child: s),
          ],
        );
    final left = <Widget?>[_salesPerformance(admin)];
    final right = <Widget?>[_paymentOverview(admin), _byDay(admin)];

    // Breakdown fills the pane and scrolls its own rows — see the phone body.
    if (_tab == 1) {
      return Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
        child: _breakdownCard(admin),
      );
    }

    return ListView(
      controller: _scrollCtl,
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 40),
      children: [
        LayoutBuilder(
          builder: (ctx, c) {
            if (c.maxWidth < 820) return col([...left, ...right]);
            return Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(child: col(left)),
                const SizedBox(width: 16),
                Expanded(child: col(right)),
              ],
            );
          },
        ),
      ],
    );
  }

  // ---- Premium date-range filter ----

  Widget _dateFilter(AdminCubit admin) {
    final p = context.astra;
    final custom = admin.rangePreset == 'custom';
    return AstraCard(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              IconChip(icon: Icons.event_note, size: 40, radius: 12, fg: p.goldText),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('DATE RANGE',
                        style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.9)),
                    const SizedBox(height: 2),
                    Text(Dates.range(admin.startDate, admin.endDate), style: serif(size: 16.5, color: p.ink)),
                  ],
                ),
              ),
              GestureDetector(
                onTap: () => _pickCustom(admin),
                child: Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    gradient: custom ? p.accentGradient : null,
                    color: custom ? null : p.tint,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(Icons.edit_calendar,
                      size: 18, color: custom ? p.primaryDark : p.primary),
                ),
              ),
              const SizedBox(width: 8),
              _refreshButton(admin),
            ],
          ),
          const SizedBox(height: 11),
          Row(
            children: [
              for (final (i, (label, id)) in _presets.indexed) ...[
                if (i > 0) const SizedBox(width: 7),
                _presetChip(admin, label, id, flex: id == 'yesterday' ? 5 : 4),
              ],
            ],
          ),
        ],
      ),
    );
  }

  /// [flex] is out of 4 — a long label like "Yesterday" takes a wider share.
  Widget _presetChip(AdminCubit admin, String label, String id, {int flex = 4}) {
    final p = context.astra;
    final active = admin.rangePreset == id;
    return Expanded(
      flex: flex,
      child: GestureDetector(
        onTap: () => admin.setPreset(id),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 9),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            gradient: active ? p.primaryGradient : null,
            color: active ? null : p.tint,
            borderRadius: BorderRadius.circular(10),
            boxShadow: active ? context.astraTheme.floatShadow(p.primary) : null,
          ),
          child: Text(label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 11, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary)),
        ),
      ),
    );
  }

  /// Force refresh beside the range control: re-fetches the reports for the
  /// range on screen from the API, bypassing the cubit's cached breakdowns.
  /// Spins while either request is in flight and ignores taps until it settles,
  /// so a double tap can't stack requests.
  Widget _refreshButton(AdminCubit admin) {
    const size = 40.0, radius = 12.0, iconSize = 18.0;
    final p = context.astra;
    final busy = admin.reportLoading || admin.overviewLoading;
    return GestureDetector(
      onTap: busy ? null : () => unawaited(admin.refreshReports()),
      child: Container(
        width: size,
        height: size,
        alignment: Alignment.center,
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(radius)),
        child: busy
            ? SizedBox(
                width: iconSize - 1,
                height: iconSize - 1,
                child: CircularProgressIndicator(strokeWidth: 2.2, color: p.primary))
            : Icon(Icons.refresh_rounded, size: iconSize, color: p.primary),
      ),
    );
  }

  Future<void> _pickCustom(AdminCubit admin) async {
    final now = DateTime.now();
    final picked = await showAstraDateRangePicker(
      context,
      title: 'Report range',
      firstDate: DateTime(now.year - 3),
      lastDate: DateTime(now.year, now.month, now.day),
      initialDateRange: DateTimeRange(start: admin.startDate, end: admin.endDate),
    );
    if (picked != null) admin.setCustomRange(picked.start, picked.end);
  }

  // ---- Sales Overview dashboard (type=overview) ------------------------------
  // Status colours kept local so the card reads the same across all skins.
  static const _good = Color(0xFF1F9D63);
  static const _warn = Color(0xFFD9890C);
  static const _bad = Color(0xFFD4546A);

  // ---- Shared overview helpers ----

  Widget _pill(String text, Color bg, Color fg) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(999)),
        child: Text(text, style: ui(size: 9.5, weight: FontWeight.w800, color: fg, letterSpacing: 0.3)),
      );

  Color _sliceColor(int i, AstraPalette p) {
    final palette = [p.primary, _good, _warn, p.accent, p.primaryDark];
    return palette[i % palette.length];
  }

  IconData _methodIcon(String name) {
    switch (name.toLowerCase()) {
      case 'cash':
        return Icons.payments_rounded;
      case 'card':
        return Icons.credit_card_rounded;
      case 'bank':
        return Icons.account_balance_rounded;
      case 'credit':
        return Icons.account_balance_wallet_rounded;
      case 'mobile money':
      case 'mobile':
        return Icons.smartphone_rounded;
      default:
        return Icons.credit_card_rounded;
    }
  }

  Color _methodColor(String name, AstraPalette p) {
    switch (name.toLowerCase()) {
      case 'cash':
        return _good;
      case 'card':
        return p.primary;
      case 'bank':
        return _warn;
      case 'credit':
        return p.accent;
      default:
        return p.primary;
    }
  }

  // ---- Per-day trend ----

  Widget? _byDay(AdminCubit admin) {
    final p = context.astra;
    final pts = admin.reportTrendPoints;
    final labels = admin.reportTrendLabels;
    if (pts.length < 2) return null;
    final maxV = pts.reduce((a, b) => a > b ? a : b);
    final total = pts.fold<double>(0, (a, b) => a + b);
    var peakTaken = false; // one peak only, even when two days tie
    final data = <BarDatum>[];
    for (var i = 0; i < pts.length; i++) {
      final iso = labels.length > i ? labels[i] : '';
      final isPeak = maxV > 0 && pts[i] == maxV && !peakTaken;
      if (isPeak) peakTaken = true;
      data.add(BarDatum(_dayLabel(iso, pts.length), pts[i], peak: isPeak, detail: _dayDetail(iso)));
    }
    // The trend rides on loadReports (via _applyRangeSummary), not the overview
    // call, so it reports that request's progress.
    final busy = admin.reportLoading;
    return AstraCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.bar_chart_rounded, 'Gross sales by day',
              busy: busy, trailing: _pill('Total ${Money.compact(total)}', p.tint, p.primary)),
          const SizedBox(height: 12),
          _refreshing(
            busy,
            BarChart(
              data: data,
              // Tall enough for the value labels to clear the bars; long ranges
              // scroll sideways rather than shrink (see BarChart.minSlot).
              height: 150,
              showReadout: true,
            ),
          ),
        ],
      ),
    );
  }

  String _dayLabel(String iso, int count) {
    final d = DateTime.tryParse(iso);
    if (d == null) return '';
    if (count <= 8) {
      const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
      return days[d.weekday - 1];
    }
    return '${d.day}/${d.month}';
  }

  /// Full date for the chart readout, e.g. `Sat, 21 Jun 2026`.
  String _dayDetail(String iso) {
    final d = DateTime.tryParse(iso);
    return d == null ? '' : Dates.weekday(d);
  }

  // ---- By Item / By Category / By Staff breakdown (toolbar + ledger) --------

  /// Column widths the ledger head and its rows share, so the numbers line up
  /// down the page. Phone drops Qty and Bills (see [_ledgerRow]).
  static const double _colRank = 28;
  static const double _colGap = 16;

  /// Between the rank and the name — tighter than a column gutter, because the
  /// numeral belongs to the row it labels.
  static const double _rankGap = 12;
  static const double _colQty = 62;
  static const double _colBills = 58;
  static const double _colShare = 122;
  static const double _colAmount = 128;

  /// The commission table's Net sales and Rate columns.
  static const double _colNet = 110;
  static const double _colRate = 64;

  /// The Breakdown report: one quiet toolbar (which breakdown, item type, and
  /// the sort — which opens the filter sheet), the filters in force as chips,
  /// then the ranked ledger table closed by a grand-total bar.
  Widget _breakdownCard(AdminCubit admin) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // A wide screen carries the filter chips in the toolbar row itself;
        // anything narrower gives them a line of their own beneath it.
        LayoutBuilder(builder: (context, box) {
          final inline = context.isTablet && box.maxWidth >= _inlineChipsWidth;
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _breakdownToolbar(admin, inlineChips: inline),
              if (!inline) _appliedChips(admin),
            ],
          );
        }),
        const SizedBox(height: 10),
        // The rows are the only unbounded part of the card, so they get the
        // remaining height and scroll inside it — built lazily as they come
        // into view. Keeping the card a plain box (rather than slivers) means
        // it still renders identically under all five skins, including Glass,
        // whose BackdropFilter has no sliver equivalent.
        Expanded(
          child: AstraCard(
            padding: EdgeInsets.zero,
            // The head and total bars are full-bleed fills, so they are
            // clipped to the card's radius — otherwise their square corners
            // sit proud of it.
            child: ClipRRect(
              borderRadius: BorderRadius.circular(context.astraTheme.rCard),
              child: _breakdownBody(admin),
            ),
          ),
        ),
      ],
    );
  }

  /// Breakdown width from which the applied-filter chips join the toolbar row
  /// — a landscape tablet or the till (1097dp wide leaves the pane ~965dp
  /// beside the nav rail). Below it (a portrait tablet, ~700dp) the segments
  /// and the type group leave no room, so the chips keep a line of their own.
  static const double _inlineChipsWidth = 860;

  /// Which breakdown · which item type (or, on Commission, whose) · the sort.
  /// One line on tablet, with the filters in force beside the segments when
  /// [inlineChips]; two on phone, where the breakdown segments take the full
  /// width under shorter labels.
  Widget _breakdownToolbar(AdminCubit admin, {bool inlineChips = false}) {
    final wide = context.isTablet;
    final segments = _sunkGroup(expand: !wide, children: [
      _breakdownSegment(admin, wide ? 'By Item' : 'Item', 'itemwise', Icons.inventory_2_rounded, compact: !wide),
      _breakdownSegment(admin, wide ? 'By Category' : 'Category', 'categorywise', Icons.category_rounded,
          compact: !wide),
      _breakdownSegment(admin, wide ? 'By Staff' : 'Staff', 'employeewise', Icons.people_alt_rounded,
          compact: !wide),
      _breakdownSegment(admin, 'Commission', 'commission', Icons.percent_rounded, compact: !wide),
    ]);
    final commission = AdminCubit.isCommission(admin.reportType);
    final type = AdminCubit.ranksProducts(admin.reportType)
        ? _typeGroup(admin, expand: !wide)
        : commission
            ? (_seesAllRecords ? _staffButton(admin) : _pill('Only your sales', context.astra.primary.withValues(alpha: 0.12), context.astra.primary))
            : null;

    if (wide) {
      // A big screen needs no filter button: the column heads sort, the type
      // sits here, and the command bar holds the range. On a wide screen the
      // applied-filter chips share the segments' line — a Wrap, so a long run
      // of them drops beneath the segments instead of overflowing.
      final chips = inlineChips ? _appliedChipList(admin, inline: true) : const <Widget>[];
      return Row(
        // Top, so the type group stays level with the segments if chips wrap.
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Wrap(
              spacing: 7,
              runSpacing: 8,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                // The segments keep their natural width and scroll sideways on
                // a narrow tablet rather than squeezing their labels to nothing.
                SingleChildScrollView(scrollDirection: Axis.horizontal, child: segments),
                ...chips,
              ],
            ),
          ),
          const SizedBox(width: 12),
          if (type != null) type,
        ],
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        segments,
        const SizedBox(height: 8),
        Row(
          children: [
            // The self-scoped pill is a label, not a control, so it keeps its
            // own width rather than stretching like the type and staff ones.
            if (type != null) ...[
              commission && !_seesAllRecords ? type : Expanded(child: type),
              const SizedBox(width: 8),
            ],
            if (commission && !_seesAllRecords) const Spacer(),
            _sortButton(admin),
          ],
        ),
      ],
    );
  }

  Widget _breakdownSegment(AdminCubit admin, String label, String type, IconData icon,
      {bool compact = false}) {
    final p = context.astra;
    final active = admin.reportType == type;
    return GestureDetector(
      onTap: () {
        if (admin.reportType == type) return;
        HapticFeedback.selectionClick();
        _setBreakdown(admin, type);
      },
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        curve: Curves.easeOut,
        padding: EdgeInsets.symmetric(vertical: 7, horizontal: compact ? 6 : 14),
        alignment: Alignment.center,
        decoration: _thumb(active),
        // Four segments share a phone's width, so a long label shrinks a
        // touch rather than losing its end to an ellipsis.
        child: FittedBox(
          fit: BoxFit.scaleDown,
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 14, color: active ? p.primary : p.textMuted),
              SizedBox(width: compact ? 4 : 6),
              Text(label,
                  maxLines: 1,
                  style: ui(
                      size: 11.5,
                      weight: active ? FontWeight.w800 : FontWeight.w600,
                      color: active ? p.primary : p.textSecondary)),
            ],
          ),
        ),
      ),
    );
  }

  /// Whose commission the report shows — everyone, or the one staff member
  /// picked from the sheet.
  Widget _staffButton(AdminCubit admin) {
    final p = context.astra;
    final name = admin.commissionStaffName;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        unawaited(showReportStaffPicker(context));
      },
      behavior: HitTestBehavior.opaque,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8),
        decoration: BoxDecoration(
          color: p.cardSolid,
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: p.hairline),
          boxShadow: context.astraTheme.softShadow,
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.badge_outlined, size: 14, color: p.textSecondary),
            const SizedBox(width: 7),
            // One run, so a long name gives way before the label does — the
            // phone shares this row with the sort button.
            Flexible(
              child: Text.rich(
                TextSpan(
                  text: 'Staff · ',
                  style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary),
                  children: [
                    TextSpan(
                        text: name.isEmpty ? 'All' : name,
                        style: ui(size: 11.5, weight: FontWeight.w800, color: p.ink)),
                  ],
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            const SizedBox(width: 3),
            Icon(Icons.expand_more_rounded, size: 15, color: p.primary),
          ],
        ),
      ),
    );
  }

  /// All / Product / Service — the item and category reports' type filter,
  /// mirroring the web report's `product_type`.
  Widget _typeGroup(AdminCubit admin, {bool expand = false}) {
    final p = context.astra;
    Widget seg(String label, String? id) {
      final active = admin.itemProductType == id;
      return GestureDetector(
        onTap: () {
          if (active) return;
          HapticFeedback.selectionClick();
          admin.setItemProductType(id);
        },
        behavior: HitTestBehavior.opaque,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          curve: Curves.easeOut,
          padding: const EdgeInsets.symmetric(vertical: 7, horizontal: 12),
          alignment: Alignment.center,
          decoration: _thumb(active),
          child: Text(label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(
                  size: 11.5,
                  weight: active ? FontWeight.w800 : FontWeight.w600,
                  color: active ? p.primary : p.textSecondary)),
        ),
      );
    }

    return _sunkGroup(
      expand: expand,
      children: [seg('All', null), seg('Product', 'product'), seg('Service', 'service')],
    );
  }

  /// The phone's way into the sort sheet, carrying the sort it is on.
  Widget _sortButton(AdminCubit admin) {
    final p = context.astra;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        unawaited(showReportSort(context));
      },
      behavior: HitTestBehavior.opaque,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8),
        decoration: BoxDecoration(
          color: p.cardSolid,
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: p.hairline),
          boxShadow: context.astraTheme.softShadow,
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.swap_vert_rounded, size: 14, color: p.textSecondary),
            const SizedBox(width: 7),
            Text('Sort · ', style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary)),
            Text(sortChipLabel(admin.sortKey, admin.reportType),
                style: ui(size: 11.5, weight: FontWeight.w800, color: p.ink)),
            const SizedBox(width: 3),
            Icon(admin.sortAscending ? Icons.arrow_upward_rounded : Icons.arrow_downward_rounded,
                size: 13, color: p.primary),
            if (_filtersOn(admin)) ...[
              const SizedBox(width: 8),
              Container(
                  width: 6,
                  height: 6,
                  decoration: BoxDecoration(color: p.primary, shape: BoxShape.circle)),
            ],
          ],
        ),
      ),
    );
  }

  /// Whether anything is set away from the report's defaults — its default
  /// day, ranked by amount, every item type.
  bool _filtersOn(AdminCubit admin) =>
      admin.rangePreset != admin.defaultRangePreset ||
      admin.sortKey != 'amount' ||
      admin.sortAscending ||
      admin.itemProductType != null ||
      admin.commissionStaffId != null;

  /// What the list is filtered to, as chips you can take off one at a time —
  /// so the figures below are never a mystery. A line under the toolbar; a
  /// wide screen puts [_appliedChipList] in the toolbar row instead.
  Widget _appliedChips(AdminCubit admin) {
    final chips = _appliedChipList(admin);
    if (chips.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 9),
      child: Wrap(spacing: 7, runSpacing: 7, children: chips),
    );
  }

  /// One chip per filter set away from its default, then Reset — or none.
  /// [inline] leaves out the item-type and staff chips: in the toolbar row
  /// they would sit beside the very controls that already show them.
  List<Widget> _appliedChipList(AdminCubit admin, {bool inline = false}) {
    if (!_filtersOn(admin)) return const [];
    final sorted = admin.sortKey != 'amount' || admin.sortAscending;
    return [
      if (admin.rangePreset != admin.defaultRangePreset)
        _filterChip(Dates.range(admin.startDate, admin.endDate), () => admin.setPreset(admin.defaultRangePreset)),
      if (sorted)
        _filterChip(
            '${sortChipLabel(admin.sortKey, admin.reportType)} · '
            '${sortDirectionLabel(admin.sortKey, admin.sortAscending)}',
            () => admin.setSort('amount', ascending: false)),
      if (admin.itemProductType != null && !inline)
        _filterChip(admin.itemProductType == 'product' ? 'Products only' : 'Services only',
            () => admin.setItemProductType(null)),
      if (admin.commissionStaffId != null && !inline)
        _filterChip('Staff · ${admin.commissionStaffName}', () => admin.setCommissionStaff(null)),
      _resetChip(admin),
    ];
  }

  Widget _filterChip(String label, VoidCallback onClear) {
    final p = context.astra;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        onClear();
      },
      behavior: HitTestBehavior.opaque,
      child: Container(
        padding: const EdgeInsets.fromLTRB(10, 5, 7, 5),
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(11)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(label, style: ui(size: 10.5, weight: FontWeight.w800, color: p.primary)),
            const SizedBox(width: 5),
            Icon(Icons.close_rounded, size: 12, color: p.primary.withValues(alpha: 0.7)),
          ],
        ),
      ),
    );
  }

  Widget _resetChip(AdminCubit admin) {
    final p = context.astra;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        admin.resetReportFilters();
      },
      behavior: HitTestBehavior.opaque,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: _softFill,
          borderRadius: BorderRadius.circular(11),
          border: Border.all(color: p.hairline),
        ),
        child: Text('Reset', style: ui(size: 10.5, weight: FontWeight.w700, color: p.textSecondary)),
      ),
    );
  }

  /// The ranked ledger: a column head, the rows, and the grand-total bar.
  /// Lives inside the breakdown card so the toolbar stays put across the
  /// loading, error and empty states.
  Widget _breakdownBody(AdminCubit admin) {
    if (admin.reportLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (admin.reportError != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: EmptyState(
              icon: Icons.wifi_off, title: 'Report unavailable', message: admin.reportError),
        ),
      );
    }
    if (admin.reportRows.isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(20),
          child: EmptyState(icon: Icons.bar_chart, title: 'No data for this period'),
        ),
      );
    }

    if (AdminCubit.isCommission(admin.reportType)) return _commissionTable(admin);

    final isStaff = !AdminCubit.ranksProducts(admin.reportType);
    final maxAmount = admin.reportRows.fold<double>(0, (a, r) => r.amount > a ? r.amount : a);
    // One trailing slot for the load-more footer while further pages remain.
    final extra = admin.reportHasMore ? 1 : 0;

    // The signed-in person's row on the staff list, and their rank among the
    // rows loaded so far (the list is server-ranked, so position is rank).
    final meId = context.select<AuthCubit, String?>((c) => c.state.user?.id) ?? '';
    bool isMe(ReportRow r) => isStaff && meId.isNotEmpty && r.id == meId;
    final myIndex = isStaff ? admin.reportRows.indexWhere(isMe) : -1;

    return LayoutBuilder(
      builder: (context, box) {
        // Below this the Qty and Bills columns would squeeze the name to a few
        // characters, so the phone layout carries them in the row's caption.
        final wide = box.maxWidth >= 560;
        return Column(
          children: [
            _ledgerHead(admin, wide: wide, isStaff: isStaff),
            Expanded(
              child: ListView.builder(
                controller: _scrollCtl,
                padding: EdgeInsets.zero,
                itemCount: admin.reportRows.length + extra,
                itemBuilder: (_, i) {
                  // Infinite-scroll footer: a spinner while the next page
                  // streams in, plus a tap-to-load fallback in case the scroll
                  // trigger is missed. New pages arrive via [_onScroll].
                  if (i == admin.reportRows.length) return _loadMoreFooter(admin);
                  final r = admin.reportRows[i];
                  return _ledgerRow(admin, r, i, maxAmount,
                      wide: wide, isMe: isMe(r));
                },
              ),
            ),
            _ledgerFoot(admin, wide: wide, isStaff: isStaff, myIndex: myIndex),
          ],
        );
      },
    );
  }

  /// The column head. On a big screen every label is also its sort: tap one to
  /// rank the list by that column, tap the live one to flip the direction. The
  /// live column is tinted and carries the arrow. A phone head is labels only —
  /// its columns are too narrow to aim at, so the filter sheet does the sorting.
  Widget _ledgerHead(AdminCubit admin, {required bool wide, required bool isStaff}) {
    final p = context.astra;
    final noun = switch (admin.reportType) {
      'itemwise' => 'Item',
      'categorywise' => 'Category',
      _ => 'Staff',
    };

    Widget cell(String label, String sortKey, {double? width, bool right = false}) =>
        _headCell(admin, label, sortKey, wide: wide, width: width, right: right);

    return Container(
      padding: EdgeInsets.fromLTRB(wide ? 18 : 13, 11, wide ? 18 : 13, 11),
      decoration: BoxDecoration(
        color: _softFill,
        border: Border(bottom: BorderSide(color: p.hairline)),
      ),
      child: Row(
        children: [
          SizedBox(
            width: wide ? _colRank : 26,
            child: Text('#',
                style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.9)),
          ),
          const SizedBox(width: _rankGap),
          Expanded(child: cell(noun.toUpperCase(), 'name')),
          if (wide) ...[
            const SizedBox(width: _colGap),
            cell(isStaff ? 'ITEMS' : 'QTY', 'quantity', width: _colQty, right: true),
            const SizedBox(width: _colGap),
            cell('BILLS', 'bills', width: _colBills, right: true),
            const SizedBox(width: _colGap),
            SizedBox(
              width: _colShare,
              child: Text('SHARE',
                  style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.9)),
            ),
          ],
          const SizedBox(width: _colGap),
          // The currency rides on the head only where the column is wide
          // enough to spell it out; a phone would clip it to `REVENUE (Q…`,
          // so there the grand-total bar states it instead.
          cell(
              wide
                  ? _withCurrency(isStaff ? 'REVENUE' : 'AMOUNT')
                  : (isStaff ? 'REVENUE' : 'AMOUNT'),
              'amount',
              width: wide ? _colAmount : 96,
              right: true),
        ],
      ),
    );
  }

  /// One column head. On a big screen a head with a [sortKey] is also its
  /// sort: tap it to rank the list by that column, tap the live one to flip the
  /// direction. The live column is tinted and carries the arrow.
  Widget _headCell(AdminCubit admin, String label, String? sortKey,
      {required bool wide, double? width, bool right = false}) {
    final p = context.astra;
    final active = sortKey != null && admin.sortKey == sortKey;
    final text = Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (active) ...[
          Icon(admin.sortAscending ? Icons.arrow_upward_rounded : Icons.arrow_downward_rounded,
              size: 11, color: p.primary),
          const SizedBox(width: 3),
        ],
        Flexible(
          child: Text(label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(
                  size: 9.5,
                  weight: FontWeight.w800,
                  color: active ? p.primary : p.textMuted,
                  letterSpacing: 0.9)),
        ),
      ],
    );
    final cellChild = Align(
      alignment: right ? Alignment.centerRight : Alignment.centerLeft,
      child: text,
    );
    final sized = width == null ? cellChild : SizedBox(width: width, child: cellChild);
    if (!wide || sortKey == null) return sized;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        admin.setSort(sortKey);
      },
      behavior: HitTestBehavior.opaque,
      child: sized,
    );
  }

  /// One ledger line. [isMe] marks the signed-in person's own staff row with a
  /// primary tint and a YOU badge; rank 1 carries a small gold numeral rather
  /// than a card of its own.
  Widget _ledgerRow(AdminCubit admin, ReportRow r, int index, double maxAmount,
      {required bool wide, bool isMe = false}) {
    final p = context.astra;
    final frac = admin.reportTotal > 0 ? r.amount / admin.reportTotal : 0.0;
    final pct = frac * 100;
    final isTop = maxAmount > 0 && r.amount >= maxAmount;

    final rank = Container(
      width: 26,
      height: 26,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        gradient: isTop ? p.accentGradient : null,
        color: isTop ? null : _softFill,
        borderRadius: BorderRadius.circular(9),
        boxShadow: isTop ? context.astraTheme.floatShadow(p.accent) : null,
      ),
      child: Text('${index + 1}',
          style: ui(
              size: 11,
              weight: FontWeight.w800,
              color: isTop ? p.primaryDark : p.textSecondary)),
    );

    final spent = r.amount == 0;
    final name = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          children: [
            Flexible(
              child: Text(r.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(
                      size: 12.5,
                      weight: FontWeight.w800,
                      color: spent ? p.textSecondary : p.ink)),
            ),
            if (isMe) ...[const SizedBox(width: 6), const YouBadge()],
          ],
        ),
        // On a wide screen Qty and Bills have columns of their own, so the
        // caption would only say them twice.
        if (!wide) ...[
          const SizedBox(height: 2),
          Text(r.subtitle,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
        ],
      ],
    );

    final share = Row(
      children: [
        Expanded(child: ProgressBar(fraction: frac)),
        const SizedBox(width: 8),
        SizedBox(
          width: 32,
          child: Text('${pct.toStringAsFixed(pct >= 10 ? 0 : 1)}%',
              textAlign: TextAlign.right,
              style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
        ),
      ],
    );

    final amount = Text(Money.plain(r.amount),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        textAlign: TextAlign.right,
        style: ui(
            size: 13,
            weight: spent ? FontWeight.w600 : FontWeight.w800,
            color: spent ? p.textMuted : p.ink));

    Widget figure(String text) => Text(text,
        textAlign: TextAlign.right,
        style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary));

    return Container(
      padding: EdgeInsets.fromLTRB(wide ? 18 : 13, wide ? 11 : 9, wide ? 18 : 13, wide ? 11 : 9),
      decoration: BoxDecoration(
        color: isMe
            ? p.primary.withValues(alpha: p.isDark ? 0.16 : 0.07)
            : (isTop ? p.tint.withValues(alpha: p.isDark ? 0.5 : 0.55) : null),
        border: Border(bottom: BorderSide(color: p.hairline)),
      ),
      child: wide
          ? Row(
              children: [
                SizedBox(width: _colRank, child: Align(alignment: Alignment.centerLeft, child: rank)),
                const SizedBox(width: _rankGap),
                Expanded(child: name),
                const SizedBox(width: _colGap),
                SizedBox(width: _colQty, child: figure(qtyLabel(r.quantity))),
                const SizedBox(width: _colGap),
                SizedBox(width: _colBills, child: figure('${r.bills}')),
                const SizedBox(width: _colGap),
                SizedBox(width: _colShare, child: share),
                const SizedBox(width: _colGap),
                SizedBox(width: _colAmount, child: amount),
              ],
            )
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    SizedBox(width: 26, child: Align(alignment: Alignment.centerLeft, child: rank)),
                    const SizedBox(width: _rankGap),
                    Expanded(child: name),
                    const SizedBox(width: _colGap),
                    SizedBox(width: 96, child: amount),
                  ],
                ),
                const SizedBox(height: 7),
                // Indented to the name, so the bar reads as part of that row.
                Padding(padding: const EdgeInsets.only(left: 26 + _rankGap), child: share),
              ],
            ),
    );
  }

  /// The bar that closes the table: what the list adds up to, how much of it is
  /// loaded, and — on the staff list — where the signed-in person ranks. On a
  /// big screen it stands on the table's own grid, so each total sits under the
  /// column it totals.
  Widget _ledgerFoot(AdminCubit admin,
      {required bool wide, required bool isStaff, required int myIndex}) {
    final p = context.astra;
    final noun = switch (admin.reportType) {
      'itemwise' => 'items',
      'categorywise' => admin.reportRowCount == 1 ? 'category' : 'categories',
      _ => 'staff',
    };
    final loaded = admin.reportRows.length < admin.reportRowCount
        ? '${admin.reportRows.length} of ${admin.reportRowCount} $noun'
        : '${admin.reportRowCount} $noun';
    return Container(
      padding: EdgeInsets.fromLTRB(wide ? 18 : 13, 12, wide ? 18 : 13, 13),
      decoration: BoxDecoration(
        color: _softFill,
        border: Border(top: BorderSide(color: p.hairline)),
      ),
      child: wide
          ? Row(
              children: [
                const SizedBox(width: _colRank + _rankGap),
                Expanded(
                  child: Row(
                    children: [
                      Text('Grand total', style: ui(size: 12, weight: FontWeight.w800, color: p.ink)),
                      const SizedBox(width: 10),
                      if (myIndex >= 0) ...[
                        _pill('You · #${myIndex + 1}', p.primary.withValues(alpha: 0.12), p.primary),
                        const SizedBox(width: 6),
                      ],
                      Flexible(
                        child: FittedBox(
                          alignment: Alignment.centerLeft,
                          fit: BoxFit.scaleDown,
                          child: _pill(loaded, p.tint, p.textSecondary),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: _colGap),
                SizedBox(
                  width: _colQty,
                  child: Text(admin.reportQuantityText,
                      textAlign: TextAlign.right,
                      style: ui(size: 12, weight: FontWeight.w800, color: p.textSecondary)),
                ),
                // Bills are counted per row, not summed across the range, and
                // the share of the whole is always 100% — both left blank so
                // the eye lands on the two totals that mean something.
                const SizedBox(width: _colGap + _colBills + _colGap + _colShare + _colGap),
                SizedBox(
                  width: _colAmount,
                  child: FittedBox(
                    alignment: Alignment.centerRight,
                    fit: BoxFit.scaleDown,
                    child: Text(admin.reportTotalText, style: serif(size: 17, color: p.primaryDark)),
                  ),
                ),
              ],
            )
          : Row(
              children: [
                Text('Grand total', style: ui(size: 12, weight: FontWeight.w800, color: p.ink)),
                const SizedBox(width: 10),
                if (myIndex >= 0) ...[
                  _pill('You · #${myIndex + 1}', p.primary.withValues(alpha: 0.12), p.primary),
                  const SizedBox(width: 6),
                ],
                _pill(loaded, p.tint, p.textSecondary),
                const Spacer(),
                const SizedBox(width: 10),
                Flexible(
                  child: FittedBox(
                    alignment: Alignment.centerRight,
                    fit: BoxFit.scaleDown,
                    child: Text(admin.reportTotalText, style: serif(size: 17, color: p.primaryDark)),
                  ),
                ),
              ],
            ),
    );
  }

  // ---- Commission (staff × product lines) ---------------------------------

  /// The commission report: one line per staff member × product, net of
  /// returns, at the rate set for that pair — the web Employee Report's Sales
  /// Details table. Ranked by commission; a big screen re-ranks from the heads.
  Widget _commissionTable(AdminCubit admin) {
    // A Staff column only while the lines can belong to more than one person:
    // a self-scoped employee, or a list narrowed to one name, would repeat it
    // down every row.
    final showStaff = _seesAllRecords && admin.commissionStaffId == null;
    final meId = context.select<AuthCubit, String?>((c) => c.state.user?.id) ?? '';
    final extra = admin.reportHasMore ? 1 : 0;

    return LayoutBuilder(
      builder: (context, box) {
        final wide = box.maxWidth >= 560;
        return Column(
          children: [
            _commissionHead(admin, wide: wide, showStaff: showStaff),
            Expanded(
              child: ListView.builder(
                controller: _scrollCtl,
                padding: EdgeInsets.zero,
                itemCount: admin.reportRows.length + extra,
                itemBuilder: (_, i) {
                  if (i == admin.reportRows.length) return _loadMoreFooter(admin);
                  final r = admin.reportRows[i];
                  return _commissionRow(r,
                      wide: wide, showStaff: showStaff, isMe: showStaff && meId.isNotEmpty && r.id == meId);
                },
              ),
            ),
            _commissionFoot(admin, wide: wide, showStaff: showStaff),
          ],
        );
      },
    );
  }

  Widget _commissionHead(AdminCubit admin, {required bool wide, required bool showStaff}) {
    final p = context.astra;
    Widget cell(String label, String? sortKey, {double? width, bool right = false}) =>
        _headCell(admin, label, sortKey, wide: wide, width: width, right: right);
    return Container(
      padding: EdgeInsets.fromLTRB(wide ? 18 : 13, 11, wide ? 18 : 13, 11),
      decoration: BoxDecoration(
        color: _softFill,
        border: Border(bottom: BorderSide(color: p.hairline)),
      ),
      child: Row(
        children: [
          if (wide && showStaff) ...[
            Expanded(child: cell('STAFF', null)),
            const SizedBox(width: _colGap),
          ],
          Expanded(child: cell('PRODUCT', 'name')),
          if (wide) ...[
            const SizedBox(width: _colGap),
            cell('QTY', 'quantity', width: _colQty, right: true),
            const SizedBox(width: _colGap),
            cell('NET SALES', 'net', width: _colNet, right: true),
            const SizedBox(width: _colGap),
            cell('RATE', null, width: _colRate, right: true),
          ],
          const SizedBox(width: _colGap),
          cell(wide ? _withCurrency('COMMISSION') : 'COMMISSION', 'amount',
              width: wide ? _colAmount : 96, right: true),
        ],
      ),
    );
  }

  /// The rate set for a staff member × product pair, as a small gold chip; a
  /// dash where none is set, so a line that earns nothing says why.
  Widget _rateChip(double rate) {
    final p = context.astra;
    final none = rate == 0;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
      decoration: BoxDecoration(
        color: none ? _softFill : p.accent.withValues(alpha: p.isDark ? 0.2 : 0.16),
        borderRadius: BorderRadius.circular(7),
      ),
      child: Text(none ? '—' : pctLabel(rate),
          style: ui(size: 10.5, weight: FontWeight.w800, color: none ? p.textMuted : p.goldText)),
    );
  }

  Widget _commissionRow(ReportRow r, {required bool wide, required bool showStaff, bool isMe = false}) {
    final p = context.astra;
    final zero = r.amount == 0;

    final product = Text(r.title,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: ui(size: 12.5, weight: zero ? FontWeight.w600 : FontWeight.w800, color: zero ? p.textSecondary : p.ink));

    final commission = Text(zero ? '—' : Money.plain(r.amount),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        textAlign: TextAlign.right,
        style: ui(
            size: 13,
            weight: zero ? FontWeight.w600 : FontWeight.w800,
            color: zero ? p.textMuted : p.primaryDark));

    Widget figure(String text) => Text(text,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        textAlign: TextAlign.right,
        style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary));

    final Widget body;
    if (wide) {
      body = Row(
        children: [
          if (showStaff) ...[
            Expanded(child: _staffCell(r.staff, isMe: isMe)),
            const SizedBox(width: _colGap),
          ],
          Expanded(child: product),
          const SizedBox(width: _colGap),
          SizedBox(width: _colQty, child: figure(qtyLabel(r.quantity))),
          const SizedBox(width: _colGap),
          SizedBox(width: _colNet, child: figure(Money.plain(r.net))),
          const SizedBox(width: _colGap),
          SizedBox(width: _colRate, child: Align(alignment: Alignment.centerRight, child: _rateChip(r.rate))),
          const SizedBox(width: _colGap),
          SizedBox(width: _colAmount, child: commission),
        ],
      );
    } else {
      // The phone keeps product and commission on the line; who, how many and
      // the sum behind it ride in the caption.
      final caption = [
        if (showStaff) r.staff,
        '${qtyLabel(r.quantity)} sold',
        '${Money.plain(r.net)} × ${r.rate == 0 ? 'no rate' : pctLabel(r.rate)}',
      ].join(' · ');
      body = Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  children: [
                    Flexible(child: product),
                    if (isMe) ...[const SizedBox(width: 6), const YouBadge()],
                  ],
                ),
                const SizedBox(height: 2),
                Text(caption,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          const SizedBox(width: _colGap),
          SizedBox(width: 96, child: commission),
        ],
      );
    }

    return Container(
      padding: EdgeInsets.fromLTRB(wide ? 18 : 13, wide ? 11 : 9, wide ? 18 : 13, wide ? 11 : 9),
      decoration: BoxDecoration(
        color: isMe ? p.primary.withValues(alpha: p.isDark ? 0.16 : 0.07) : null,
        border: Border(bottom: BorderSide(color: p.hairline)),
      ),
      child: body,
    );
  }

  /// Who earned a line: their initials on a small brand tile, then the name.
  Widget _staffCell(String name, {bool isMe = false}) {
    final p = context.astra;
    final initials = name.trim().split(RegExp(r'\s+')).where((w) => w.isNotEmpty).take(2).map((w) => w[0]).join();
    return Row(
      children: [
        Container(
          width: 24,
          height: 24,
          alignment: Alignment.center,
          decoration: BoxDecoration(gradient: p.primaryGradient, borderRadius: BorderRadius.circular(8)),
          child: Text(initials.toUpperCase(), style: ui(size: 9.5, weight: FontWeight.w800, color: Colors.white)),
        ),
        const SizedBox(width: 8),
        Flexible(
          child: Text(name,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 12, weight: FontWeight.w700, color: p.ink)),
        ),
        if (isMe) ...[const SizedBox(width: 6), const YouBadge()],
      ],
    );
  }

  /// The grand-total bar on the table's own grid: quantity under Qty, net sales
  /// under Net sales, the blended rate under Rate, commission under Commission.
  Widget _commissionFoot(AdminCubit admin, {required bool wide, required bool showStaff}) {
    final p = context.astra;
    final count = admin.reportRowCount;
    final lines = admin.reportRows.length < count
        ? '${admin.reportRows.length} of $count lines'
        : '$count line${count == 1 ? '' : 's'}';
    final loaded = showStaff && admin.reportStaffCount > 0 ? '$lines · ${admin.reportStaffCount} staff' : lines;
    final total = FittedBox(
      alignment: Alignment.centerRight,
      fit: BoxFit.scaleDown,
      child: Text(admin.reportTotalText, style: serif(size: 17, color: p.primaryDark)),
    );
    final title = [
      Text('Grand total', style: ui(size: 12, weight: FontWeight.w800, color: p.ink)),
      const SizedBox(width: 10),
      Flexible(
        child: FittedBox(
          alignment: Alignment.centerLeft,
          fit: BoxFit.scaleDown,
          child: _pill(loaded, p.tint, p.textSecondary),
        ),
      ),
    ];
    final net = admin.reportNetTotal;

    return Container(
      padding: EdgeInsets.fromLTRB(wide ? 18 : 13, 12, wide ? 18 : 13, 13),
      decoration: BoxDecoration(
        color: _softFill,
        border: Border(top: BorderSide(color: p.hairline)),
      ),
      child: wide
          ? Row(
              children: [
                Expanded(child: Row(children: title)),
                const SizedBox(width: _colGap),
                SizedBox(
                  width: _colQty,
                  child: Text(admin.reportQuantityText,
                      textAlign: TextAlign.right,
                      style: ui(size: 12, weight: FontWeight.w800, color: p.textSecondary)),
                ),
                const SizedBox(width: _colGap),
                SizedBox(
                  width: _colNet,
                  child: Text(Money.plain(net),
                      textAlign: TextAlign.right,
                      style: ui(size: 12, weight: FontWeight.w800, color: p.textSecondary)),
                ),
                const SizedBox(width: _colGap),
                SizedBox(
                  width: _colRate,
                  child: Align(
                    alignment: Alignment.centerRight,
                    child: _rateChip(net > 0 ? admin.reportTotal / net * 100 : 0),
                  ),
                ),
                const SizedBox(width: _colGap),
                SizedBox(width: _colAmount, child: total),
              ],
            )
          : Row(
              children: [
                ...title,
                const SizedBox(width: 10),
                Expanded(child: total),
              ],
            ),
    );
  }

  /// Footer under the loaded rows while more pages remain: a spinner during a
  /// fetch, otherwise a tap-to-load cue showing how many of the total are on
  /// screen. Scrolling near the bottom auto-loads the next page via [_onScroll].
  Widget _loadMoreFooter(AdminCubit admin) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Center(
        child: admin.reportLoadingMore
            ? Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  SizedBox(
                      width: 15,
                      height: 15,
                      child: CircularProgressIndicator(strokeWidth: 2.2, color: p.primary)),
                  const SizedBox(width: 9),
                  Text('Loading more…', style: ui(size: 11.5, weight: FontWeight.w700, color: p.textMuted)),
                ],
              )
            : GestureDetector(
                onTap: admin.loadMoreReport,
                behavior: HitTestBehavior.opaque,
                child: Text('Showing ${admin.reportRows.length} of ${admin.reportRowCount} · tap to load more',
                    style: ui(size: 11, weight: FontWeight.w700, color: p.primary)),
              ),
      ),
    );
  }
}

/// Column label carrying the currency, e.g. `AMOUNT (QAR)` — the cells under it
/// then print plain grouped numbers, so the symbol is stated once rather than
/// repeated down every row.
String _withCurrency(String label) {
  final symbol = Money.symbol.trim();
  return symbol.isEmpty ? label : '$label ($symbol)';
}

import 'dart:async';

import 'package:flutter/material.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale_return/screens/v3/return_receipt_screen.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';
import 'package:invo/features/sales_returns/logic/sales_returns_cubit/sales_returns_cubit.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';
import 'package:invo/shared/widgets/nav_hide.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_range_picker.dart';

part 'sales_returns_list_controls.dart';

class SalesReturnListScreen extends StatefulWidget {
  const SalesReturnListScreen({super.key});
  @override
  State<SalesReturnListScreen> createState() => _SalesReturnListScreenState();
}

/// One sort choice: a label plus the `sort_by` / `sort_direction` the API expects.
class _SortOption {
  const _SortOption(this.label, this.by, this.dir, this.icon);
  final String label;
  final String by;
  final String dir;
  final IconData icon;
}

const _sortOptions = <_SortOption>[
  _SortOption('Newest first', 'date', 'desc', Icons.schedule),
  _SortOption('Oldest first', 'date', 'asc', Icons.history),
  _SortOption('Refund: high to low', 'paid', 'desc', Icons.trending_down),
  _SortOption('Refund: low to high', 'paid', 'asc', Icons.trending_up),
  _SortOption('Reference no', 'reference_no', 'desc', Icons.tag),
];

class _SalesReturnListScreenState extends State<SalesReturnListScreen> {
  /// Owns the repositories (§10) — the screen never resolves one itself.
  final _returns = SalesReturnsCubit();

  /// Owns fetch/pagination/error state — see [PaginatedListCubit]. The screen
  /// keeps only the filter values it drives the fetcher with, plus the tablet
  /// drawer's selection.
  late final PaginatedListCubit _list = PaginatedListCubit(
    fetch: _fetchPage,
    errorMessage: 'Could not load returns.',
  );
  final _scrollCtl = ScrollController();
  StreamSubscription<int>? _branchSub;
  StreamSubscription<AuthState>? _authSub;

  String? _status; // null = all
  int? _methodId; // null = all payment methods
  List<PaymentMethod> _methods = [];
  String _sortBy = 'date';
  String _sortDir = 'desc';

  // Date range — preset drives [_startDate]/[_endDate]. Opens on the open
  // session's day while it trails the calendar, else today (see
  // AuthCubit.defaultDatePreset).
  String _datePreset = 'today'; // session | today | yesterday | 7d | month | custom

  /// The session day the date filter was last anchored on — see [_onAuthChanged].
  DateTime? _anchoredSession;
  DateTime? _startDate;
  DateTime? _endDate;

  // Tablet drawer: the row whose return is shown over the table's right side.
  // Untouched on phones, which keep pushing the /return-receipt route on tap.
  Map<String, dynamic>? _selectedRow;
  SaleReturn? _selectedReturn;
  bool _detailLoading = false;
  String? _detailError;

  @override
  void initState() {
    super.initState();
    _openOnDefaultDay();
    _scrollCtl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _load();
      _loadMethods();
    });
    // Reload for the new branch if it changes while this screen is alive.
    _authSub = context.read<AuthCubit>().stream.listen(_onAuthChanged);
    _branchSub = context.read<BranchCubit>().onBranchChanged.listen((_) {
      if (!mounted) return;
      _load();
      _loadMethods();
    });
  }

  @override
  void dispose() {
    _scrollCtl.dispose();
    _branchSub?.cancel();
    _authSub?.cancel();
    unawaited(_list.close());
    super.dispose();
  }

  /// Infinite scroll: pull the next page once the user nears the bottom.
  void _onScroll() {
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 500) unawaited(_list.loadMore());
  }

  Future<PageResult> _fetchPage(int page) => _returns.fetchPage(
        page: page,
        status: _status,
        paymentMethodId: _methodId,
        fromDate: _startDate == null ? null : Dates.iso(_startDate!),
        toDate: _endDate == null ? null : Dates.iso(_endDate!),
        sortBy: _sortBy,
        sortDirection: _sortDir,
      );

  /// (Re)load from page 1 for the current filters, then re-sync the tablet
  /// drawer against the new rows.
  Future<void> _load() async {
    await _list.load();
    if (mounted) _syncSelection();
  }

  /// Payment methods power the in-card payment selector; a failure just leaves
  /// it showing "All methods".
  Future<void> _loadMethods() async {
    try {
      final m = await _returns.paymentMethods();
      if (mounted) setState(() => _methods = m);
    } catch (_) {/* keep the list usable without the method filter */}
  }

  // ---- filter setters (click-and-go: apply on tap) ----

  void _setStatus(String? status) {
    if (_status == status) return;
    setState(() => _status = status);
    _load();
  }

  void _setMethod(int? id) {
    if (_methodId == id) return;
    setState(() => _methodId = id);
    _load();
  }

  /// The open session's day while it trails the calendar, else today.
  void _openOnDefaultDay() {
    final auth = context.read<AuthCubit>();
    final range = auth.datePresetRange(auth.defaultDatePreset)!;
    _datePreset = auth.defaultDatePreset;
    _anchoredSession = auth.sessionDay;
    _startDate = range.start;
    _endDate = range.end;
  }

  void _setDatePreset(String id) {
    final range = context.read<AuthCubit>().datePresetRange(id);
    setState(() {
      _datePreset = id;
      if (range != null) {
        _startDate = range.start;
        _endDate = range.end;
      }
    });
    _load();
  }

  /// The shell keeps this screen alive, so when the day session opens or
  /// closes a filter on its default day follows to the new default, and a
  /// preset range follows the calendar; a custom range is the user's own and
  /// stays put.
  void _onAuthChanged(AuthState _) {
    if (!mounted || _datePreset == 'custom') return;
    final auth = context.read<AuthCubit>();
    final preset = auth.reanchorDatePreset(_datePreset, _anchoredSession);
    _anchoredSession = auth.sessionDay;
    final range = auth.datePresetRange(preset);
    if (range == null || (preset == _datePreset && range.start == _startDate && range.end == _endDate)) return;
    _setDatePreset(preset);
  }

  Future<void> _pickCustomDate() async {
    final now = DateTime.now();
    final picked = await showAstraDateRangePicker(
      context,
      title: 'Returns range',
      firstDate: DateTime(now.year - 3),
      lastDate: DateTime(now.year, now.month, now.day),
      initialDateRange: _startDate != null && _endDate != null
          ? DateTimeRange(start: _startDate!, end: _endDate!)
          : null,
    );
    if (picked != null) {
      setState(() {
        _datePreset = 'custom';
        _startDate = DateTime(picked.start.year, picked.start.month, picked.start.day);
        _endDate = DateTime(picked.end.year, picked.end.month, picked.end.day);
      });
      unawaited(_load());
    }
  }

  // ---- labels ----

  String get _dateLabel => switch (_datePreset) {
        'session' when _startDate != null => Dates.dayMonth(_startDate!),
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        '7d' => 'Last 7 days',
        'month' => 'This month',
        _ => (_startDate != null && _endDate != null) ? Dates.range(_startDate!, _endDate!) : 'Custom',
      };

  String get _methodLabel =>
      _methodId == null ? 'All methods' : _methods.firstWhere((m) => m.id == _methodId, orElse: () => PaymentMethod(id: 0, name: 'Method')).name;

  String get _sortLabel => _sortOptions
      .firstWhere((o) => o.by == _sortBy && o.dir == _sortDir, orElse: () => _sortOptions.first)
      .label;

  /// Pop when there's a stack to pop; otherwise fall back to Sales — this screen
  /// is also reached via `context.go` (from a completed return), which leaves
  /// nothing to pop.
  void _back() {
    if (context.canPop()) {
      context.pop();
    } else {
      context.go(Routes.sales);
    }
  }

  /// Returns lives under the Sales section, so the bottom-nav tabs re-enter the
  /// home shell at the tapped section (Sales shows highlighted here).
  void _onNavTap(int i) => context.go(Routes.homeTab(i));

  String get _statusLabel => switch (_status) {
        'completed' => 'Completed',
        'draft' => 'Draft',
        _ => 'All statuses',
      };

  String get _subtitle {
    final s = _list.state;
    if (s.isLoading && s.items.isEmpty) return 'Loading…';
    return '${s.total} return${s.total == 1 ? '' : 's'} found';
  }

  void _newReturn() => context.push(Routes.saleReturnPick);

  @override
  Widget build(BuildContext context) {
    final canCreate = context.read<AuthCubit>().hasPermission(PermissionSlug.saleReturnCreate);
    final body = Scaffold(
      backgroundColor: Colors.transparent,
      extendBody: true,
      body: AstraBackground(
        child: BlocBuilder<PaginatedListCubit, PaginatedListState>(
          bloc: _list,
          builder: (context, _) => context.isTablet
              // Tablet has no header band: the page names itself and carries the
              // back + new-return actions.
              ? SafeArea(bottom: false, child: _tabletBody(canCreate))
              : Column(
                  children: [
                    EmeraldHeader(
                      leading: HeaderIconButton(icon: Icons.chevron_left, onTap: _back),
                      title: 'Sales Returns',
                      subtitle: _subtitle,
                      trailing: canCreate ? HeaderIconButton(icon: Icons.add, gold: true, onTap: _newReturn) : null,
                    ),
                    Expanded(child: _phoneList(canCreate)),
                  ],
                ),
        ),
      ),
      // Tablet routes navigate via the side-rail + the page head's back button,
      // so the bottom nav + docked FAB are phone-only.
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: (canCreate && !context.isTablet) ? AstraNavFab(onTap: _newReturn) : null,
      bottomNavigationBar: context.isTablet ? null : AstraNavBar(activeIndex: 1, onTap: _onNavTap),
    );
    // Phone only — the tablet has no bottom bar to get out of the way.
    return context.isTablet ? body : NavHide(child: body);
  }

  // ---- phone ----

  Widget _phoneList(bool canCreate) {
    return RefreshIndicator(
      onRefresh: _load,
      child: MaxWidthBox(
        maxWidth: 720,
        // Slivers, not ListView(children:) — the rows grow by a page on every
        // scroll, so the eager form would rebuild every accumulated row.
        child: CustomScrollView(
          controller: _scrollCtl,
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
              sliver: SliverToBoxAdapter(child: _toolsCard()),
            ),
            SliverPadding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              sliver: SliverMainAxisGroup(slivers: _listSlivers(_list.state, canCreate)),
            ),
            const SliverToBoxAdapter(child: SizedBox(height: 120)),
          ],
        ),
      ),
    );
  }

  /// Phone rows sit in one card: hairline-separated, rounded at the ends of
  /// what is loaded so far, so the list reads as a single ledger.
  Widget _cardRow(int index, int count, Widget row) {
    final p = context.astra;
    const r = Radius.circular(18);
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.vertical(top: index == 0 ? r : Radius.zero, bottom: index == count - 1 ? r : Radius.zero),
      ),
      child: row,
    );
  }

  List<Widget> _listSlivers(PaginatedListState state, bool canCreate) {
    if (state.isLoading) {
      return const [
        SliverToBoxAdapter(
          child: Padding(padding: EdgeInsets.symmetric(vertical: 48), child: Center(child: CircularProgressIndicator())),
        ),
      ];
    }
    if (state.hasFailed || state.items.isEmpty) {
      return [SliverToBoxAdapter(child: Padding(padding: const EdgeInsets.all(16), child: _emptyState(state, canCreate)))];
    }
    return [
      SliverList.builder(
        itemCount: state.items.length,
        itemBuilder: (_, i) => _cardRow(i, state.items.length, _returnRow(state.items[i])),
      ),
      if (state.loadingMore) const SliverToBoxAdapter(child: _MoreSpinner()),
    ];
  }

  Widget _emptyState(PaginatedListState state, bool canCreate) {
    if (state.hasFailed) {
      return EmptyState(
        icon: Icons.wifi_off,
        title: 'Returns unavailable',
        message: state.errorMessage,
        action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load),
      );
    }
    return EmptyState(
      icon: Icons.assignment_return_outlined,
      title: 'No returns found',
      message: canCreate ? 'Try a wider date range, or start a return from a paid invoice.' : 'Try a wider date range.',
      action: canCreate ? AstraButton(label: 'New return', icon: Icons.add, expand: false, onTap: _newReturn) : null,
    );
  }

  Widget _returnRow(Map<String, dynamic> r) {
    final p = context.astra;
    final d = _rowData(r);
    return TabletListRow(
      selected: false,
      onTap: () => _open(asStr(r['id'])),
      padding: const EdgeInsets.fromLTRB(16, 11, 16, 11),
      child: Row(
        children: [
          IconChip(icon: Icons.assignment_return_outlined, size: 36, radius: 11),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(d.ref,
                          maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                    ),
                    if (d.status.isNotEmpty) ...[
                      const SizedBox(width: 7),
                      StatusPill(label: d.status.toUpperCase(), bg: d.bg, fg: d.fg),
                    ],
                  ],
                ),
                const SizedBox(height: 3),
                Row(
                  children: [
                    Flexible(
                      child: Text('${d.who}${d.date.isEmpty ? '' : ' · ${d.date}'}',
                          maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                    ),
                    if (d.method.isNotEmpty) ...[const SizedBox(width: 7), Flexible(child: _methodTag(d.method))],
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text('− ${Money.of(d.amount)}', style: serif(size: 15, color: AstraPalette.danger)),
        ],
      ),
    );
  }

  Widget _methodTag(String method) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(6)),
      child: Text(method,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: ui(size: 8.5, weight: FontWeight.w800, color: p.textSecondary, letterSpacing: 0.3)),
    );
  }

  // ---- tablet: full-width ledger table + return drawer ----

  Widget _tabletBody(bool canCreate) {
    // Sized from this screen's own width, not the window's — beside the
    // side-rail those differ by ~106pt, which matters on a small tablet.
    return LayoutBuilder(
      builder: (ctx, c) {
        final drawerWidth = (c.maxWidth * 0.46).clamp(340.0, 460.0);
        final tableWidth = c.maxWidth - 40;
        return Stack(
          children: [
            Column(
              children: [
                TabletPageHead(
                  title: 'Sales Returns',
                  subtitle: _subtitle,
                  padding: const EdgeInsets.fromLTRB(20, 14, 20, 14),
                  leading: context.canPop() ? TabletIconButton(icon: Icons.chevron_left, tooltip: 'Back', onTap: _back) : null,
                  actions: [
                    if (canCreate) TabletActionButton(label: 'New return', icon: Icons.add, primary: true, onTap: _newReturn),
                  ],
                ),
                Padding(padding: const EdgeInsets.fromLTRB(20, 16, 20, 14), child: _tabletTools(tableWidth)),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                    child: AstraCard(
                      radius: 18,
                      padding: EdgeInsets.zero,
                      child: ClipRRect(borderRadius: BorderRadius.circular(18), child: _table(tableWidth, canCreate)),
                    ),
                  ),
                ),
              ],
            ),
            if (_selectedRow != null) Positioned(top: 0, right: 0, bottom: 0, width: drawerWidth, child: _drawer()),
          ],
        );
      },
    );
  }

  Widget _table(double width, bool canCreate) {
    final p = context.astra;
    final state = _list.state;
    final cols = _columns(width);
    Widget body;
    if (state.isLoading) {
      body = const Center(child: CircularProgressIndicator());
    } else if (state.hasFailed || state.items.isEmpty) {
      // Still a scrollable, so pull-to-refresh works on an empty table.
      body = ListView(
        controller: _scrollCtl,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(24),
        children: [_emptyState(state, canCreate)],
      );
    } else {
      body = ListView.builder(
        controller: _scrollCtl,
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount: state.items.length + (state.loadingMore ? 1 : 0),
        itemBuilder: (_, i) => i >= state.items.length ? const _MoreSpinner() : _tableRow(cols, state.items[i]),
      );
    }
    return Column(
      children: [
        _tableLine(
          cols,
          [
            for (final c in cols)
              Text(c.label.toUpperCase(),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1)),
          ],
          padding: const EdgeInsets.fromLTRB(20, 10, 20, 10),
          border: true,
        ),
        Expanded(child: RefreshIndicator(onRefresh: _load, child: body)),
        if (state.items.isNotEmpty) _tableFooter(cols, state),
      ],
    );
  }

  /// Which columns fit: the optional ones drop out as the table narrows (a
  /// small tablet in portrait, or the drawer's share of the width).
  List<_Col> _columns(double w) => [
        const _Col('ref', 'Reference', 3),
        const _Col('date', 'Date', 3),
        const _Col('customer', 'Customer', 4),
        if (w >= 700) const _Col('payment', 'Payment', 3),
        const _Col('status', 'Status', 3),
        const _Col('amount', 'Refund', 3, end: true),
      ];

  Widget _tableLine(List<_Col> cols, List<Widget> cells, {EdgeInsets? padding, bool border = false, Color? color}) {
    final p = context.astra;
    return Container(
      padding: padding,
      decoration: BoxDecoration(
        color: color,
        border: border ? Border(bottom: BorderSide(color: p.hairline)) : null,
      ),
      child: Row(
        children: [
          for (var i = 0; i < cols.length; i++)
            Expanded(
              flex: cols[i].flex,
              child: Padding(
                padding: EdgeInsets.only(left: i == 0 ? 0 : 10),
                child: Align(alignment: cols[i].end ? Alignment.centerRight : Alignment.centerLeft, child: cells[i]),
              ),
            ),
        ],
      ),
    );
  }

  Widget _cellText(String text, {bool strong = false, Color? color, bool amount = false}) {
    final p = context.astra;
    return Text(text,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: amount
            ? serif(size: 14, color: color ?? p.ink)
            : ui(size: 12, weight: strong ? FontWeight.w800 : FontWeight.w600, color: color ?? (strong ? p.ink : p.textSecondary)));
  }

  Widget _tableRow(List<_Col> cols, Map<String, dynamic> r) {
    final d = _rowData(r);
    final selected = _selectedRow != null && asStr(_selectedRow!['id']) == asStr(r['id']);
    Widget cell(String id) => switch (id) {
          'ref' => _cellText(d.ref, strong: true),
          'date' => _cellText(d.date),
          'customer' => _cellText(d.who),
          'payment' => _cellText(d.method.isEmpty ? '—' : d.method),
          // Scales down rather than overflowing a narrow column.
          'status' => FittedBox(
              fit: BoxFit.scaleDown,
              child: d.status.isEmpty ? const SizedBox.shrink() : StatusPill(label: d.status.toUpperCase(), bg: d.bg, fg: d.fg)),
          'amount' => _cellText('− ${Money.of(d.amount)}', amount: true, color: AstraPalette.danger),
          _ => const SizedBox.shrink(),
        };
    return TabletListRow(
      selected: selected,
      onTap: () => _selectRow(r),
      padding: const EdgeInsets.fromLTRB(20, 11, 20, 11),
      child: _tableLine(cols, [for (final c in cols) cell(c.id)]),
    );
  }

  /// Whole-set totals under the table — what the filters match, not just the
  /// rows scrolled into view so far.
  Widget _tableFooter(List<_Col> cols, PaginatedListState state) {
    final p = context.astra;
    Widget cell(String id) {
      final style = ui(size: 12, weight: FontWeight.w800, color: p.ink);
      return switch (id) {
        'ref' => Text('${state.items.length} of ${state.total}', maxLines: 1, overflow: TextOverflow.ellipsis, style: style),
        'amount' => Text('− ${Money.of(state.totalPaid)}',
            maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 14, color: AstraPalette.danger)),
        _ => const SizedBox.shrink(),
      };
    }

    return _tableLine(
      cols,
      [for (final c in cols) cell(c.id)],
      padding: const EdgeInsets.fromLTRB(20, 11, 20, 11),
      color: p.tint,
    );
  }

  /// The selected return, sliding over the table's right side. The table stays
  /// live beside it, so tapping another row just swaps what the drawer shows.
  Widget _drawer() {
    final p = context.astra;
    return DecoratedBox(
      decoration: BoxDecoration(
        color: p.canvas,
        border: Border(left: BorderSide(color: p.hairline)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: p.isDark ? 0.40 : 0.12), blurRadius: 30, offset: const Offset(-12, 0)),
        ],
      ),
      child: ClipRect(child: _detailPane()),
    );
  }

  /// The drawer's content — the selected return, embedded (its close button
  /// clears the selection).
  Widget _detailPane() {
    if (_detailLoading) return const Center(child: CircularProgressIndicator());
    if (_detailError != null || _selectedReturn == null) {
      return EmptyState(
        icon: Icons.wifi_off,
        title: 'Return unavailable',
        message: _detailError ?? 'Could not open return.',
        action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: () => _selectRow(_selectedRow!)),
      );
    }
    final ret = _selectedReturn!;
    return ReturnReceiptScreen(
      key: ValueKey('return-${ret.id}'),
      saleReturn: ret,
      onClose: () {
        _clearSelection();
        _load();
      },
    );
  }

  void _clearSelection() {
    setState(() {
      _selectedRow = null;
      _selectedReturn = null;
      _detailError = null;
      _detailLoading = false;
    });
  }

  /// Load a return into the drawer (tablet). Guarded against out-of-order taps
  /// so only the last-tapped return is shown.
  Future<void> _selectRow(Map<String, dynamic> r) async {
    final id = asStr(r['id']);
    if (id.isEmpty) return;
    setState(() {
      _selectedRow = r;
      _selectedReturn = null;
      _detailLoading = true;
      _detailError = null;
    });
    try {
      final ret = await _returns.saleReturnById(id);
      if (!mounted || asStr(_selectedRow?['id']) != id) return;
      setState(() {
        _selectedReturn = ret;
        _detailLoading = false;
      });
    } catch (e) {
      if (!mounted || asStr(_selectedRow?['id']) != id) return;
      setState(() {
        _detailError = 'Could not open return.';
        _detailLoading = false;
      });
    }
  }

  /// Tablet only: close the drawer when the row it was opened from is no longer
  /// listed (after a filter change, say). Nothing is preselected — the table is
  /// the page, and the drawer opens on a tap.
  void _syncSelection() {
    if (!mounted || !context.isTablet || _selectedRow == null) return;
    final selId = asStr(_selectedRow!['id']);
    if (!_list.state.items.any((r) => asStr(r['id']) == selId)) _clearSelection();
  }

  // ---- row data ----

  /// The display fields of one list row, shared by the phone row and the
  /// tablet table so both read the payload the same way.
  ({String ref, num amount, String who, String status, String date, String method, Color bg, Color fg}) _rowData(
      Map<String, dynamic> r) {
    final p = context.astra;
    final summary = r['summary'] is Map ? r['summary'] as Map : const {};
    final customer = r['customer'] is Map ? r['customer'] as Map : const {};
    final status = asStr(r['status']);
    final (bg, fg) = switch (status) {
      'completed' => (p.successTint, AstraPalette.success),
      _ => (p.warnTint, p.goldText),
    };
    return (
      ref: asStr(r['reference_no']).isEmpty
          ? (asStr(r['invoice_no']).isEmpty ? '#${asStr(r['id'])}' : asStr(r['invoice_no']))
          : asStr(r['reference_no']),
      amount: asNum(summary['paid'] ?? summary['grand_total'] ?? summary['total'] ?? r['paid']),
      who: asStr(customer['name']).isEmpty ? AppStrings.walkInCustomer : asStr(customer['name']),
      status: status,
      date: Dates.human(asStr(r['date'])),
      method: asStr(r['payment_methods']),
      bg: bg,
      fg: fg,
    );
  }

  void _openStatusSheet() {
    const statuses = <(String, String?, IconData)>[
      ('All statuses', null, Icons.all_inclusive_rounded),
      ('Completed', 'completed', Icons.task_alt_rounded),
      ('Draft', 'draft', Icons.edit_note_rounded),
    ];
    _optionSheet('Status', [
      for (final (label, status, icon) in statuses)
        _optTile(
          label: label,
          icon: icon,
          active: _status == status,
          onTap: () {
            Navigator.pop(context);
            _setStatus(status);
          },
        ),
    ]);
  }

  // ---- option sheets (apply on tap) ----

  Future<void> _optionSheet(String title, List<Widget> tiles) {
    final p = context.astra;
    return showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => ConstrainedBox(
        constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(ctx).height * 0.85),
        child: Container(
          decoration: BoxDecoration(
            color: p.cardSolid,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 14),
                  decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(4)),
                ),
              ),
              Padding(
                padding: const EdgeInsets.only(left: 4, bottom: 10),
                child: Text(title, style: serif(size: 17, color: p.ink)),
              ),
              Flexible(
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: tiles,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _openDateSheet() {
    final session = context.read<AuthCubit>().sessionDay;
    final presets = [
      if (session != null) ('Session day · ${Dates.dayMonth(session)}', 'session', Icons.event_available),
      ('Today', 'today', Icons.today),
      ('Yesterday', 'yesterday', Icons.history),
      ('Last 7 days', '7d', Icons.date_range),
      ('This month', 'month', Icons.calendar_month),
    ];
    _optionSheet('Date range', [
      for (final o in presets)
        _optTile(
          label: o.$1,
          icon: o.$3,
          active: _datePreset == o.$2,
          onTap: () {
            Navigator.pop(context);
            _setDatePreset(o.$2);
          },
        ),
      _optTile(
        label: 'Custom range…',
        icon: Icons.edit_calendar,
        active: _datePreset == 'custom',
        trailing: _datePreset == 'custom' && _startDate != null && _endDate != null ? Dates.range(_startDate!, _endDate!) : null,
        onTap: () {
          Navigator.pop(context);
          _pickCustomDate();
        },
      ),
    ]);
  }

  void _openPaymentSheet() {
    _optionSheet('Payment method', [
      _optTile(
        label: 'All methods',
        icon: Icons.account_balance_wallet_outlined,
        active: _methodId == null,
        onTap: () {
          Navigator.pop(context);
          _setMethod(null);
        },
      ),
      for (final m in _methods)
        _optTile(
          label: m.name,
          icon: Icons.payments_outlined,
          active: _methodId == m.id,
          onTap: () {
            Navigator.pop(context);
            _setMethod(m.id);
          },
        ),
    ]);
  }

  void _openSort() {
    _optionSheet('Sort returns', [
      for (final o in _sortOptions)
        _optTile(
          label: o.label,
          icon: o.icon,
          active: _sortBy == o.by && _sortDir == o.dir,
          onTap: () {
            Navigator.pop(context);
            if (_sortBy == o.by && _sortDir == o.dir) return;
            setState(() {
              _sortBy = o.by;
              _sortDir = o.dir;
            });
            _load();
          },
        ),
    ]);
  }

  Future<void> _open(String id) async {
    if (id.isEmpty) return;
    unawaited(showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator())));
    try {
      final ret = await _returns.saleReturnById(id);
      if (mounted) Navigator.pop(context);
      if (mounted) unawaited(context.push(Routes.returnReceipt, extra: ret));
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) AstraSnack.error(context, 'Could not open return');
    }
  }
}

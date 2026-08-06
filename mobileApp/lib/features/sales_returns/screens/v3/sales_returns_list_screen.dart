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
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

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
  /// master-detail selection.
  late final PaginatedListCubit _list = PaginatedListCubit(
    fetch: _fetchPage,
    errorMessage: 'Could not load returns.',
  );
  final _scrollCtl = ScrollController();
  StreamSubscription<int>? _branchSub;

  String? _status; // null = all
  int? _methodId; // null = all payment methods
  List<PaymentMethod> _methods = [];
  String _sortBy = 'date';
  String _sortDir = 'desc';

  // Date range — preset drives [_startDate]/[_endDate]; defaults to today.
  String _datePreset = 'today'; // today | 7d | 30d | month | custom
  DateTime? _startDate;
  DateTime? _endDate;

  // Tablet master–detail: the row whose return is shown in the right pane.
  // Untouched on phones, which keep pushing the /return-receipt route on tap.
  Map<String, dynamic>? _selectedRow;
  SaleReturn? _selectedReturn;
  bool _detailLoading = false;
  String? _detailError;

  @override
  void initState() {
    super.initState();
    // Default the list to today's returns.
    final now = DateTime.now();
    _startDate = _endDate = DateTime(now.year, now.month, now.day);
    _scrollCtl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _load();
      _loadMethods();
    });
    // Reload for the new branch if it changes while this screen is alive.
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
  /// detail pane against the new rows.
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

  void _setDatePreset(String id) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    setState(() {
      _datePreset = id;
      switch (id) {
        case 'today':
          _startDate = today;
          _endDate = today;
        case '7d':
          _startDate = today.subtract(const Duration(days: 6));
          _endDate = today;
        case '30d':
          _startDate = today.subtract(const Duration(days: 29));
          _endDate = today;
        case 'month':
          _startDate = DateTime(now.year, now.month, 1);
          _endDate = today;
      }
    });
    _load();
  }

  Future<void> _pickCustomDate() async {
    final p = context.astra;
    final now = DateTime.now();
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 3),
      lastDate: DateTime(now.year, now.month, now.day),
      initialDateRange: _startDate != null && _endDate != null
          ? DateTimeRange(start: _startDate!, end: _endDate!)
          : null,
      helpText: 'Select returns range',
      builder: (ctx, child) => Theme(
        data: Theme.of(ctx).copyWith(
          colorScheme: (p.isDark ? const ColorScheme.dark() : const ColorScheme.light()).copyWith(
            primary: p.primary,
            onPrimary: Colors.white,
            surface: p.cardSolid,
            onSurface: p.ink,
            secondary: p.accent,
          ),
        ),
        child: child!,
      ),
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
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
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

  @override
  Widget build(BuildContext context) {
    final st = _list.state;
    final sub = st.isLoading && st.items.isEmpty ? 'Loading…' : '${st.total} return${st.total == 1 ? '' : 's'} found';
    final canCreate = context.read<AuthCubit>().hasPermission(PermissionSlug.saleReturnCreate);
    return Scaffold(
      backgroundColor: Colors.transparent,
      extendBody: true,
      body: AstraBackground(
        // Tablet drops the header band (see the preview): the pane names itself
        // and carries the back + new-return actions, since this is a pushed
        // route with no side-rail of its own to navigate from.
        child: context.isTablet
            ? SafeArea(bottom: false, child: _tabletBody(canCreate))
            : Column(
                children: [
                  EmeraldHeader(
                    leading: HeaderIconButton(icon: Icons.chevron_left, onTap: _back),
                    title: 'Sales Returns',
                    subtitle: sub,
                    trailing: canCreate
                        ? HeaderIconButton(icon: Icons.add, gold: true, onTap: () => context.push(Routes.saleReturnPick))
                        : null,
                  ),
                  Expanded(child: _body(canCreate)),
                ],
              ),
      ),
      // Tablet routes navigate via the shell side-rail + the header back button,
      // so the bottom nav + docked FAB are phone-only.
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: (canCreate && !context.isTablet) ? AstraNavFab(onTap: () => context.push(Routes.saleReturnPick)) : null,
      bottomNavigationBar: context.isTablet ? null : AstraNavBar(activeIndex: 1, onTap: _onNavTap),
    );
  }

  Widget _body(bool canCreate) {
    return RefreshIndicator(
      onRefresh: _load,
      child: MaxWidthBox(
        maxWidth: 720,
        child: _listView(canCreate, const EdgeInsets.fromLTRB(16, 14, 16, 120)),
      ),
    );
  }

  /// Tablet: a surfaced master pane (head + flat rows) beside the selected
  /// return — the same `.listcol` / `.detail` split Sales uses.
  Widget _tabletBody(bool canCreate) {
    return LayoutBuilder(
      builder: (ctx, c) => Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TabletPane(
            width: TabletMetrics.forWidth(c.maxWidth).listColumn,
            child: Column(
              children: [
                _paneHead(canCreate),
                Expanded(child: RefreshIndicator(onRefresh: _load, child: _tabletList(canCreate))),
              ],
            ),
          ),
          Expanded(child: _detailPane()),
        ],
      ),
    );
  }

  /// The master pane's head: refunded total as the headline, then the bento's
  /// filters as chips (a control card inside a pane reads as a card-in-a-card).
  Widget _paneHead(bool canCreate) {
    const statuses = <(String, String?)>[
      ('All', null),
      ('Completed', 'completed'),
      ('Draft', 'draft'),
    ];
    return TabletPaneHead(
      title: 'Sales Returns',
      subtitle: _list.state.isLoading && _list.state.items.isEmpty
          ? 'Loading…'
          : '${Money.of(_list.state.totalPaid)} refunded · ${_list.state.total} return${_list.state.total == 1 ? '' : 's'}',
      leading: context.canPop()
          ? TabletIconButton(icon: Icons.chevron_left, tooltip: 'Back', onTap: _back)
          : null,
      trailing: canCreate
          ? TabletActionButton(
              label: 'New', icon: Icons.add, primary: true, onTap: () => context.push(Routes.saleReturnPick))
          : null,
      children: [
        const SizedBox(height: 13),
        Wrap(
          spacing: 7,
          runSpacing: 7,
          children: [
            for (final (label, status) in statuses)
              TabletFilterChip(label: label, active: _status == status, onTap: () => _setStatus(status)),
          ],
        ),
        const SizedBox(height: 8),
        Wrap(
          spacing: 7,
          runSpacing: 7,
          children: [
            TabletFilterChip(
                label: _dateLabel,
                active: false,
                icon: Icons.event_rounded,
                trailingIcon: Icons.keyboard_arrow_down_rounded,
                onTap: _openDateSheet),
            TabletFilterChip(
                label: _methodLabel,
                active: false,
                icon: Icons.account_balance_wallet_outlined,
                trailingIcon: Icons.keyboard_arrow_down_rounded,
                onTap: _openPaymentSheet),
            TabletFilterChip(
                label: _sortLabel,
                active: false,
                icon: Icons.swap_vert_rounded,
                trailingIcon: Icons.keyboard_arrow_down_rounded,
                onTap: _openSort),
          ],
        ),
      ],
    );
  }

  Widget _tabletList(bool canCreate) {
    return BlocBuilder<PaginatedListCubit, PaginatedListState>(
      bloc: _list,
      builder: (context, state) {
        return CustomScrollView(
          controller: _scrollCtl,
          slivers: [
            if (state.isLoading)
              const SliverToBoxAdapter(
                child: Padding(padding: EdgeInsets.symmetric(vertical: 60), child: Center(child: CircularProgressIndicator())),
              )
            else if (state.hasFailed)
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 24),
                  child: EmptyState(
                      icon: Icons.wifi_off,
                      title: 'Returns unavailable',
                      message: state.errorMessage,
                      action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load)),
                ),
              )
            else if (state.items.isEmpty)
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 24),
                  child: EmptyState(
                    icon: Icons.assignment_return_outlined,
                    title: 'No returns found',
                    message: canCreate
                        ? 'Try a wider date range, or start a return from a paid invoice.'
                        : 'Try a wider date range.',
                    action: canCreate
                        ? AstraButton(label: 'New return', icon: Icons.add, expand: false, onTap: () => context.push(Routes.saleReturnPick))
                        : null,
                  ),
                ),
              )
            else
              SliverList.builder(
                itemCount: state.items.length,
                itemBuilder: (_, i) => _tabletRow(state.items[i]),
              ),
            if (state.loadingMore)
              const SliverToBoxAdapter(
                child: Padding(
                  padding: EdgeInsets.symmetric(vertical: 16),
                  child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4))),
                ),
              ),
            const SliverToBoxAdapter(child: SizedBox(height: 24)),
          ],
        );
      },
    );
  }

  /// One flat pane row — reference + refund on the first line, who/when/how and
  /// the status badge on the second.
  Widget _tabletRow(Map<String, dynamic> r) {
    final p = context.astra;
    final d = _rowData(r);
    final selected = _selectedRow != null && asStr(_selectedRow!['id']) == asStr(r['id']);
    final sub = [d.who, if (d.date.isNotEmpty) d.date, if (d.method.isNotEmpty) d.method].join(' · ');
    return TabletListRow(
      selected: selected,
      onTap: () => _selectRow(r),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(d.ref,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
              ),
              const SizedBox(width: 8),
              Text('− ${Money.of(d.amount)}', style: serif(size: 15, color: AstraPalette.danger)),
            ],
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              Expanded(
                child: Text(sub,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ),
              if (d.status.isNotEmpty) ...[
                const SizedBox(width: 8),
                StatusPill(label: d.status.toUpperCase(), bg: d.bg, fg: d.fg),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _listView(bool canCreate, EdgeInsets padding) {
    return BlocBuilder<PaginatedListCubit, PaginatedListState>(
      bloc: _list,
      builder: (context, state) {
        // Slivers, not ListView(children:) — the rows grow by a page on every
        // scroll, so the eager form would rebuild every accumulated row.
        return CustomScrollView(
          controller: _scrollCtl,
          slivers: [
            SliverPadding(
              padding: padding.copyWith(bottom: 0),
              sliver: SliverList.list(children: [_bento(), _resultLine()]),
            ),
            SliverPadding(
              padding: EdgeInsets.symmetric(horizontal: padding.left),
              sliver: state.isLoading
                  ? const SliverToBoxAdapter(
                      child: Padding(padding: EdgeInsets.symmetric(vertical: 48), child: Center(child: CircularProgressIndicator())),
                    )
                  : state.hasFailed
                      ? SliverToBoxAdapter(
                          child: EmptyState(icon: Icons.wifi_off, title: 'Returns unavailable', message: state.errorMessage, action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load)),
                        )
                      : state.items.isEmpty
                          ? SliverToBoxAdapter(
                              child: EmptyState(
                                icon: Icons.assignment_return_outlined,
                                title: 'No returns found',
                                message: canCreate
                                    ? 'Try a wider date range, or start a return from a paid invoice.'
                                    : 'Try a wider date range.',
                                action: canCreate
                                    ? AstraButton(label: 'New return', icon: Icons.add, expand: false, onTap: () => context.push(Routes.saleReturnPick))
                                    : null,
                              ),
                            )
                          : SliverList.separated(
                              itemCount: state.items.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 9),
                              itemBuilder: (_, i) => _row(state.items[i]),
                            ),
            ),
            if (state.loadingMore)
              const SliverToBoxAdapter(
                child: Padding(
                  padding: EdgeInsets.symmetric(vertical: 16),
                  child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4))),
                ),
              ),
            SliverToBoxAdapter(child: SizedBox(height: padding.bottom)),
          ],
        );
      },
    );
  }

  /// Tablet detail pane — the selected return, embedded (its close button clears
  /// the selection).
  Widget _detailPane() {
    if (_selectedRow == null) {
      return const EmptyState(
        icon: Icons.assignment_return_outlined,
        title: 'No return selected',
        message: 'Pick a return from the list to see its details here.',
      );
    }
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
        setState(() {
          _selectedRow = null;
          _selectedReturn = null;
          _detailError = null;
        });
        _load();
      },
    );
  }

  /// Load a return into the detail pane (tablet). Guarded against out-of-order
  /// taps so only the last-tapped return is shown.
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

  /// Tablet only: keep the detail pane in step with the list — drop a stale
  /// selection and preselect the first return so the pane is never empty.
  void _syncSelection() {
    if (!mounted || !context.isTablet) return;
    final rows = _list.state.items;
    final selId = _selectedRow == null ? '' : asStr(_selectedRow!['id']);
    final stillThere = selId.isNotEmpty && rows.any((r) => asStr(r['id']) == selId);
    if (!stillThere) {
      if (rows.isEmpty) {
        setState(() {
          _selectedRow = null;
          _selectedReturn = null;
          _detailError = null;
          _detailLoading = false;
        });
      } else {
        _selectRow(rows.first);
      }
    }
  }

  // ---- Bento control card ----

  // ---- return row ----

  /// The display fields of one list row, shared by the phone card and the
  /// tablet pane row so both read the payload the same way.
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

  Widget _row(Map<String, dynamic> r) {
    final p = context.astra;
    final d = _rowData(r);
    final ref = d.ref;
    final amount = d.amount;
    final who = d.who;
    final status = d.status;
    final date = d.date;
    final method = d.method;
    final (bg, fg) = (d.bg, d.fg);
    return AstraCard(
      radius: 14,
      padding: const EdgeInsets.all(12),
      onTap: () => _open(asStr(r['id'])),
      child: Row(
        children: [
          IconChip(icon: Icons.assignment_return_outlined, size: 40, radius: 12),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(child: Text(ref, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink))),
                    if (status.isNotEmpty) ...[
                      const SizedBox(width: 7),
                      StatusPill(label: status.toUpperCase(), bg: bg, fg: fg),
                    ],
                  ],
                ),
                const SizedBox(height: 3),
                Row(
                  children: [
                    Flexible(
                      child: Text('$who${date.isEmpty ? '' : ' · $date'}',
                          maxLines: 1, overflow: TextOverflow.ellipsis,
                          style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                    ),
                    if (method.isNotEmpty) ...[
                      const SizedBox(width: 7),
                      Flexible(
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(6)),
                          child: Text(method,
                              maxLines: 1, overflow: TextOverflow.ellipsis,
                              style: ui(size: 8.5, weight: FontWeight.w800, color: p.textSecondary, letterSpacing: 0.3)),
                        ),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text('− ${Money.of(amount)}', style: serif(size: 15, color: AstraPalette.danger)),
        ],
      ),
    );
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
    const presets = [
      ('Today', 'today', Icons.today),
      ('Last 7 days', '7d', Icons.date_range),
      ('Last 30 days', '30d', Icons.calendar_view_week),
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
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not open return')));
    }
  }
}

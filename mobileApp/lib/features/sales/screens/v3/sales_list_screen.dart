import 'dart:async';

import 'package:flutter/material.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/screens/v3/invoice_screen.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';
import 'package:invo/features/sales/logic/sales_cubit/sales_cubit.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';

part 'sales_list_controls.dart';

class SalesListScreen extends StatefulWidget {
  const SalesListScreen({super.key, this.onSelectTab});

  /// Switches the shell to a destination. Injected by [HomeShell] so the
  /// Returns link switches tabs on a tablet (where Returns is a shell
  /// destination) instead of pushing a second copy over the shell. Null when
  /// this screen is opened as its own route.
  final ValueChanged<int>? onSelectTab;
  @override
  State<SalesListScreen> createState() => _SalesListScreenState();
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
  _SortOption('Amount: high to low', 'paid', 'desc', Icons.trending_down),
  _SortOption('Amount: low to high', 'paid', 'asc', Icons.trending_up),
  _SortOption('Invoice no', 'invoice_no', 'desc', Icons.tag),
];

class _SalesListScreenState extends State<SalesListScreen> {
  /// Owns the repositories (§10) — the screen never resolves one itself.
  final _sales = SalesCubit();

  /// Owns fetch/pagination/error state — see [PaginatedListCubit]. The screen
  /// keeps only the filter values it drives the fetcher with, plus the tablet
  /// master-detail selection.
  late final PaginatedListCubit _list = PaginatedListCubit(
    fetch: _fetchPage,
    errorMessage: 'Could not load sales.',
  );
  final _scrollCtl = ScrollController();
  StreamSubscription<int>? _branchSub;

  String? _status; // null = all
  String _search = '';
  final _searchCtl = TextEditingController();
  Timer? _searchDebounce;
  int? _methodId; // null = all payment methods
  List<PaymentMethod> _methods = [];
  String _sortBy = 'date';
  String _sortDir = 'desc';

  // Date range — preset drives [_startDate]/[_endDate]. Defaults to today.
  String _datePreset = 'today'; // today | 7d | 30d | month | custom
  DateTime? _startDate;
  DateTime? _endDate;

  /// Returns is a shell destination on tablet — switch to it rather than
  /// pushing, so this link behaves like every other tablet nav link.
  void _openReturns() => context.isTablet && widget.onSelectTab != null
      ? widget.onSelectTab!(kReturnsTab)
      : context.push(Routes.salesReturns);

  // Tablet master–detail: the row whose invoice is shown in the right pane.
  // Untouched on phones, which keep pushing the /invoice route on tap.
  Map<String, dynamic>? _selectedRow;
  Sale? _selectedSale;
  bool _detailLoading = false;
  String? _detailError;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _startDate = DateTime(now.year, now.month, now.day);
    _endDate = _startDate;
    _scrollCtl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _load();
      _loadMethods();
    });
    // The shell keeps this screen alive, so reload the list (and branch-scoped
    // payment methods) when the active branch changes.
    _branchSub = context.read<BranchCubit>().onBranchChanged.listen((_) {
      if (!mounted) return;
      _load();
      _loadMethods();
    });
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _searchCtl.dispose();
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

  Future<PageResult> _fetchPage(int page) => _sales.fetchPage(
        page: page,
        status: _status,
        search: _search.isEmpty ? null : _search,
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

  /// Tablet only: keep the detail pane in step with the list — drop a selection
  /// whose row no longer exists (e.g. after a filter change or a delete), and
  /// preselect the first invoice so the pane is never awkwardly empty.
  void _syncSelection() {
    if (!mounted || !context.isTablet) return;
    final rows = _list.state.items;
    final selId = _selectedRow == null ? '' : asStr(_selectedRow!['id']);
    final stillThere = selId.isNotEmpty && rows.any((r) => asStr(r['id']) == selId);
    if (!stillThere) {
      if (rows.isEmpty) {
        setState(() {
          _selectedRow = null;
          _selectedSale = null;
          _detailError = null;
          _detailLoading = false;
        });
      } else {
        _selectRow(rows.first);
      }
    }
  }

  /// Payment methods power the in-card payment selector; a failure just leaves
  /// it showing "All methods".
  Future<void> _loadMethods() async {
    try {
      final m = await _sales.paymentMethods();
      if (mounted) setState(() => _methods = m);
    } catch (_) {/* keep the Sales list usable without the method filter */}
  }

  // ---- filter setters (click-and-go: apply on tap) ----

  void _setStatus(String? status) {
    if (_status == status) return;
    setState(() => _status = status);
    _load();
  }

  /// Debounced: the list reloads from page 1 on every change, and firing per
  /// keystroke would queue a page request for each letter of a customer's name.
  void _setSearch(String value) {
    final term = value.trim();
    // setState regardless, so the clear button appears as soon as there is text.
    setState(() => _search = term);
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 350), () {
      if (mounted) _load();
    });
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
      helpText: 'Select sales range',
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

  @override
  Widget build(BuildContext context) {
    final st = _list.state;
    final sub = st.isLoading && st.items.isEmpty ? 'Loading…' : '${st.total} invoice${st.total == 1 ? '' : 's'} found';
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        // Tablet has no header band: the shell's side-rail is the chrome and the
        // list pane names itself (the preview's `.lc-head`). A green band on top
        // of a two-pane layout is the phone screen showing through.
        child: context.isTablet
            ? SafeArea(bottom: false, child: _tabletBody())
            : Column(
                children: [
                  EmeraldHeader(title: 'Sales', subtitle: sub, trailing: _returnsAction()),
                  Expanded(child: _body()),
                ],
              ),
      ),
    );
  }

  /// Entry point into the Sales Return module — a compact translucent pill in the
  /// header so it doesn't crowd the 4-tab bottom nav. Hidden when the user
  /// can't view returns.
  Widget? _returnsAction() {
    if (!context.read<AuthCubit>().hasPermission(PermissionSlug.saleReturnView)) {
      return null;
    }
    final p = context.astra;
    return GestureDetector(
      onTap: _openReturns,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.14),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.white.withValues(alpha: 0.20)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.assignment_return_outlined, size: 15, color: p.accent),
            const SizedBox(width: 7),
            Text('Returns', style: ui(size: 12, weight: FontWeight.w800, color: Colors.white)),
          ],
        ),
      ),
    );
  }

  Widget _body() {
    return BlocBuilder<PaginatedListCubit, PaginatedListState>(
      bloc: _list,
      builder: (context, state) {
        // Slivers, not ListView(children:) — the rows grow by a page on every
        // scroll, and the eager form would rebuild and lay out every
        // accumulated row on each emit instead of recycling what is off-screen.
        return RefreshIndicator(
          onRefresh: _load,
          child: MaxWidthBox(
            maxWidth: 720,
            child: CustomScrollView(
              controller: _scrollCtl,
              slivers: [
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 0),
                  sliver: SliverList.list(children: [_bento(), _resultLine()]),
                ),
                if (state.isLoading)
                  const SliverToBoxAdapter(
                    child: Padding(padding: EdgeInsets.symmetric(vertical: 48), child: Center(child: CircularProgressIndicator())),
                  )
                else if (state.hasFailed)
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: EmptyState(icon: Icons.wifi_off, title: 'Sales unavailable', message: state.errorMessage, action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load)),
                    ),
                  )
                else if (state.items.isEmpty)
                  const SliverToBoxAdapter(
                    child: Padding(
                      padding: EdgeInsets.symmetric(horizontal: 16),
                      child: EmptyState(icon: Icons.receipt_long, title: 'No sales found', message: 'Try a wider date range or clearing the filters.'),
                    ),
                  )
                else
                  SliverPadding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    sliver: SliverList.separated(
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
                const SliverToBoxAdapter(child: SizedBox(height: 120)),
              ],
            ),
          ),
        );
      },
    );
  }

  /// Tablet: a surfaced master pane — head (collected total + filter chips) over
  /// flat hairline-separated rows — beside the selected invoice. This is the
  /// `.listcol` / `.detail` split from the approved preview; the phone's bento
  /// control card and floating row cards would read as cards-inside-a-card here.
  Widget _tabletBody() {
    // Sized from this screen's own width, not the window's — beside the shell's
    // side-rail those differ by ~106pt, which matters on a small tablet.
    return LayoutBuilder(
      builder: (ctx, c) => Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TabletPane(
            width: TabletMetrics.forWidth(c.maxWidth).listColumn,
            child: Column(
              children: [
                _paneHead(),
                Expanded(child: RefreshIndicator(onRefresh: _load, child: _tabletList())),
              ],
            ),
          ),
          Expanded(child: _detailPane()),
        ],
      ),
    );
  }

  /// The master pane's head: the collected total as the headline, the invoice
  /// count under it, then every filter the bento card holds — as chips, which
  /// stay legible at pane width where stacked labelled boxes do not.
  Widget _paneHead() {
    const statuses = <(String, String?)>[
      ('All', null),
      ('Completed', 'completed'),
      ('Draft', 'draft'),
      ('Cancelled', 'cancelled'),
    ];
    final canSeeReturns = context.read<AuthCubit>().hasPermission(PermissionSlug.saleReturnView);
    return TabletPaneHead(
      title: 'Sales',
      subtitle: _list.state.isLoading && _list.state.items.isEmpty
          ? 'Loading…'
          : '${Money.of(_list.state.totalPaid)} collected · ${_list.state.total} invoice${_list.state.total == 1 ? '' : 's'}',
      trailing: canSeeReturns
          ? TabletActionButton(
              label: 'Returns',
              icon: Icons.assignment_return_outlined,
              onTap: _openReturns)
          : null,
      children: [
        const SizedBox(height: 13),
        // Same search as the phone card — a tablet till has the same person with
        // the same receipt standing at it.
        _searchBox(),
        const SizedBox(height: 10),
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

  Widget _tabletList() {
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
                      title: 'Sales unavailable',
                      message: state.errorMessage,
                      action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load)),
                ),
              )
            else if (state.items.isEmpty)
              const SliverToBoxAdapter(
                child: Padding(
                  padding: EdgeInsets.symmetric(horizontal: 14, vertical: 24),
                  child: EmptyState(
                      icon: Icons.receipt_long,
                      title: 'No sales found',
                      message: 'Try a wider date range or clearing the filters.'),
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

  /// One flat pane row — invoice + amount on the first line, who/when/how and
  /// the status badge on the second.
  Widget _tabletRow(Map<String, dynamic> r) {
    final p = context.astra;
    final d = _rowData(r);
    final selected = _selectedRow != null && asStr(_selectedRow!['id']) == asStr(r['id']);
    final sub = [
      d.who,
      if (d.date.isNotEmpty) d.date,
      if (d.method.isNotEmpty) d.method,
      if (d.offlineRef.isNotEmpty) d.offlineRef,
    ].join(' · ');
    return TabletListRow(
      selected: selected,
      onTap: () => _selectRow(r),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(d.invoice,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
              ),
              const SizedBox(width: 8),
              Text(Money.of(d.amount), style: serif(size: 15, color: p.ink)),
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

  /// Tablet detail pane — the selected invoice, embedded (its close button
  /// clears the selection; a delete drops the row and reloads the list).
  Widget _detailPane() {
    if (_selectedRow == null) {
      return const EmptyState(
        icon: Icons.receipt_long_outlined,
        title: 'No invoice selected',
        message: 'Pick an invoice from the list to see its details here.',
      );
    }
    if (_detailLoading) return const Center(child: CircularProgressIndicator());
    if (_detailError != null || _selectedSale == null) {
      return EmptyState(
        icon: Icons.wifi_off,
        title: 'Invoice unavailable',
        message: _detailError ?? 'Could not open invoice.',
        action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: () => _selectRow(_selectedRow!)),
      );
    }
    final sale = _selectedSale!;
    return InvoiceScreen(
      key: ValueKey('invoice-${sale.id}'),
      sale: sale,
      onClose: () {
        setState(() {
          _selectedRow = null;
          _selectedSale = null;
          _detailError = null;
        });
        _load();
      },
    );
  }

  /// Load a sale into the detail pane (tablet). Guarded so a fast succession of
  /// taps only ever shows the last-tapped invoice.
  Future<void> _selectRow(Map<String, dynamic> r) async {
    // A held row is its own detail — no fetch, and none possible.
    if (_isHeld(r)) {
      setState(() {
        _selectedRow = r;
        _selectedSale = Sale.fromJson(r);
        _detailLoading = false;
        _detailError = null;
      });
      return;
    }

    final id = asStr(r['id']);
    if (id.isEmpty) return;
    setState(() {
      _selectedRow = r;
      _selectedSale = null;
      _detailLoading = true;
      _detailError = null;
    });
    try {
      final sale = await _sales.saleById(id);
      if (!mounted || asStr(_selectedRow?['id']) != id) return;
      setState(() {
        _selectedSale = sale;
        _detailLoading = false;
      });
    } catch (e) {
      if (!mounted || asStr(_selectedRow?['id']) != id) return;
      setState(() {
        _detailError = 'Could not open invoice.';
        _detailLoading = false;
      });
    }
  }

  // ---- Bento control card ----

  // ---- sale row ----

  /// The display fields of one list row, shared by the phone card and the
  /// tablet pane row so both read the payload the same way.
  ({
    String invoice,
    String offlineRef,
    num amount,
    String who,
    String status,
    String date,
    String method,
    Color bg,
    Color fg
  }) _rowData(Map<String, dynamic> r) {
    final p = context.astra;
    final invoice = asStr(r['invoice_no']).isEmpty ? '#${asStr(r['id'])}' : asStr(r['invoice_no']);
    // `reference_no` holds the reference printed on the receipt when the sale was
    // rung up offline — but only when `client_uuid` proves it came from a queued
    // ticket, since the same field holds free text typed in the back office.
    // Shown so that searching the number on a customer's receipt returns a row
    // that visibly carries it, rather than an invoice number they've never seen.
    final offlineRef = asStr(r['client_uuid']).isEmpty ? '' : asStr(r['reference_no']);
    // Amount lives under `summary` in SaleListResource; keep flat keys as a fallback.
    final summary = r['summary'] is Map ? r['summary'] as Map : const {};
    final customer = r['customer'] is Map ? r['customer'] as Map : const {};
    final status = asStr(r['status']);
    final (bg, fg) = switch (status) {
      'completed' => (p.successTint, AstraPalette.success),
      'cancelled' => (p.dangerTint, AstraPalette.danger),
      _ => (p.warnTint, p.goldText),
    };
    return (
      invoice: invoice,
      offlineRef: offlineRef == invoice ? '' : offlineRef,
      amount: asNum(summary['paid'] ?? summary['gross_amount'] ?? r['paid'] ?? r['gross_amount'] ?? r['amount']),
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
    final invoice = d.invoice;
    final amount = d.amount;
    final who = d.who;
    final status = d.status;
    final date = d.date;
    final method = d.method;
    final (bg, fg) = (d.bg, d.fg);
    final held = _isHeld(r);
    return AstraCard(
      radius: 14,
      padding: const EdgeInsets.all(12),
      onTap: () => _open(r),
      child: Row(
        children: [
          // A held sale reads as a different kind of thing at a glance, because it
          // is one: it has not reached the server, so its reference is provisional
          // and its actions are different.
          IconChip(
            icon: held ? Icons.cloud_off_rounded : Icons.shopping_bag_outlined,
            size: 40,
            radius: 12,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(invoice,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                    ),
                    if (held) ...[
                      const SizedBox(width: 7),
                      StatusPill(label: 'HELD', bg: p.warnTint, fg: p.goldText),
                    ] else if (status.isNotEmpty) ...[
                      const SizedBox(width: 7),
                      StatusPill(label: status.toUpperCase(), bg: bg, fg: fg),
                    ],
                  ],
                ),
                const SizedBox(height: 3),
                Row(
                  children: [
                    Flexible(
                      child: Text(
                          '$who${date.isEmpty ? '' : ' · $date'}'
                          '${d.offlineRef.isEmpty ? '' : ' · ${d.offlineRef}'}',
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
          Text(Money.of(amount), style: serif(size: 15, color: p.ink)),
          // Edit sits on the row only for a held sale. A committed one is edited
          // from its invoice, where the permission gate and the Return/Delete
          // actions live; a held one has none of those, so there is nothing to go
          // to the receipt for.
          if (held) ...[
            const SizedBox(width: 4),
            IconButton(
              onPressed: () => _editHeld(r),
              icon: Icon(Icons.edit_outlined, size: 18, color: p.goldText),
              visualDensity: VisualDensity.compact,
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
              tooltip: 'Edit held sale',
            ),
          ],
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
    _optionSheet('Sort sales', [
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

  Future<void> _open(Map<String, dynamic> r) async {
    // A held row needs no fetch — and could not do one. It has no server id yet,
    // and the row itself IS the receipt: the outbox stores the sale in exactly the
    // shape the invoice screen renders.
    if (_isHeld(r)) {
      final reloaded = await context.push<bool>(Routes.invoice, extra: Sale.fromJson(r));
      if (reloaded == true && mounted) unawaited(_load());
      return;
    }

    final id = asStr(r['id']);
    if (id.isEmpty) return;
    unawaited(showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator())));
    try {
      final sale = await _sales.saleById(id);
      if (mounted) Navigator.pop(context);
      if (!mounted) return;
      // The invoice view returns `true` after deleting the sale — reload so the
      // deleted row drops off the list.
      final deleted = await context.push<bool>(Routes.invoice, extra: sale);
      if (deleted == true && mounted) unawaited(_load());
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not open invoice')));
    }
  }

  /// Whether this row is a sale still held on this device.
  ///
  /// Held rows come off the outbox rather than the server (see `SalesCubit`), so
  /// they have no id, cannot be fetched, and are edited through the queue.
  bool _isHeld(Map<String, dynamic> r) => r['pending'] == true;

  /// Correct a held sale straight from the list.
  ///
  /// Offered here as well as on the invoice screen because this is where a cashier
  /// goes looking for the sale they have just rung up — making them open the receipt
  /// first to fix a wrong quantity is a step for nothing.
  ///
  /// The correction rewrites the outbox row it is already captured in, under the
  /// same idempotency key, so the server still only ever hears about one sale.
  Future<void> _editHeld(Map<String, dynamic> r) async {
    final uuid = asStr(r['client_uuid']);
    if (uuid.isEmpty || !serviceLocator.isRegistered<OutboxRepository>()) return;

    final row = await serviceLocator<OutboxRepository>().byUuid(uuid);
    if (!mounted) return;
    if (row == null) {
      // Gone from the queue between the tap and here means it synced, which is the
      // good outcome — it is an ordinary sale now. Reload so the row shows as one.
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('This sale has synced — reopen it to edit.')),
      );
      unawaited(_load());
      return;
    }

    context.read<CartCubit>().seedFromPendingSale(row);
    await context.push(Routes.sale);
    // The correction may have changed the figures, or synced the row away.
    if (mounted) unawaited(_load());
  }
}

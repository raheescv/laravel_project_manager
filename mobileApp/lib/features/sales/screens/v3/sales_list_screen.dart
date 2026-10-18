import 'dart:async';

import 'package:flutter/material.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/logic/stylist_cubit/stylist_cubit.dart';
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
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_range_picker.dart';

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

/// The Items tab sorts by the line's own figures (`ItemIndexRequest`).
const _itemSortOptions = <_SortOption>[
  _SortOption('Newest first', 'date', 'desc', Icons.schedule),
  _SortOption('Oldest first', 'date', 'asc', Icons.history),
  _SortOption('Amount: high to low', 'total', 'desc', Icons.trending_down),
  _SortOption('Amount: low to high', 'total', 'asc', Icons.trending_up),
  _SortOption('Quantity: high to low', 'quantity', 'desc', Icons.stacked_bar_chart),
];

class _SalesListScreenState extends State<SalesListScreen> {
  /// Owns the repositories (§10) — the screen never resolves one itself.
  final _sales = SalesCubit();

  /// Owns fetch/pagination/error state — see [PaginatedListCubit]. The screen
  /// keeps only the filter values it drives the fetchers with, plus the tablet
  /// drawer's selection.
  late final PaginatedListCubit _list = PaginatedListCubit(
    fetch: _fetchPage,
    errorMessage: 'Could not load sales.',
  );

  /// The Items tab: every sold line, under the same filters.
  late final PaginatedListCubit _itemList = PaginatedListCubit(
    fetch: _fetchItemsPage,
    errorMessage: 'Could not load items.',
  );

  String _tab = 'invoices'; // invoices | items
  bool get _isItems => _tab == 'items';
  PaginatedListCubit get _active => _isItems ? _itemList : _list;

  // A filter change reloads only the tab on screen; the other is marked stale
  // and reloads when it is next shown.
  bool _invoicesStale = false;
  bool _itemsStale = true;
  final _scrollCtl = ScrollController();
  StreamSubscription<int>? _branchSub;

  String? _status; // null = all
  String _search = '';
  final _searchCtl = TextEditingController();
  Timer? _searchDebounce;
  int? _methodId; // null = all payment methods
  List<PaymentMethod> _methods = [];

  // Staff filter — who rang the sale up (`created_by`). Admin-only: everyone
  // else already sees nothing but their own sales, so the control would be a
  // filter with one possible answer.
  int? _staffId; // null = all staff
  String _staffName = '';
  String _sortBy = 'date';
  String _sortDir = 'desc';
  String _itemSortBy = 'date';
  String _itemSortDir = 'desc';

  // Date range — preset drives [_startDate]/[_endDate]. Defaults to today.
  String _datePreset = 'today'; // today | 7d | 30d | month | custom
  DateTime? _startDate;
  DateTime? _endDate;

  /// Returns is a shell destination on tablet — switch to it rather than
  /// pushing, so this link behaves like every other tablet nav link.
  void _openReturns() => context.isTablet && widget.onSelectTab != null
      ? widget.onSelectTab!(kReturnsTab)
      : context.push(Routes.salesReturns);

  // Tablet drawer: the row whose invoice is shown over the table's right side
  // (for an Items-tab line, its invoice plus the line to pick out). Untouched on
  // phones, which keep pushing the /invoice route on tap.
  Map<String, dynamic>? _selectedRow;
  String? _selectedItemId;
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
      // Warm the staff list so the filter sheet opens instantly — only for the
      // accounts that get the filter at all.
      if (mounted && _seesAllSales) unawaited(context.read<StylistCubit>().loadIfNeeded());
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
    unawaited(_itemList.close());
    super.dispose();
  }

  /// Infinite scroll: pull the next page once the user nears the bottom.
  void _onScroll() {
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 500) unawaited(_active.loadMore());
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
        staffId: _staffId,
      );

  Future<PageResult> _fetchItemsPage(int page) => _sales.fetchItemsPage(
        page: page,
        status: _status,
        search: _search.isEmpty ? null : _search,
        paymentMethodId: _methodId,
        fromDate: _startDate == null ? null : Dates.iso(_startDate!),
        toDate: _endDate == null ? null : Dates.iso(_endDate!),
        sortBy: _itemSortBy,
        sortDirection: _itemSortDir,
        staffId: _staffId,
      );

  /// Whether this user sees the whole till's sales — and so has anything to
  /// filter by staff, or any reason to be told who rang a sale up.
  ///
  /// The scope the API applies, not the admin flag: a back-office 'user'
  /// account is handed every sale even without `is_admin`, so hiding the staff
  /// filter from it would leave a list of everyone's sales with no way to tell
  /// them apart. Only a rank-and-file employee is self-scoped.
  bool get _seesAllSales => context.read<AuthCubit>().user?.seesAllRecords ?? false;

  /// (Re)load the tab on screen from page 1 for the current filters, mark the
  /// other one stale, then re-sync the tablet drawer against the new rows.
  Future<void> _load() async {
    if (_isItems) {
      _itemsStale = false;
      _invoicesStale = true;
    } else {
      _invoicesStale = false;
      _itemsStale = true;
    }
    await _active.load();
    if (mounted) _syncSelection();
  }

  void _switchTab(String tab) {
    if (_tab == tab) return;
    setState(() {
      _tab = tab;
      // The drawer belongs to the tab it was opened from.
      _selectedRow = null;
      _selectedItemId = null;
      _selectedSale = null;
      _detailError = null;
      _detailLoading = false;
    });
    if (_scrollCtl.hasClients) _scrollCtl.jumpTo(0);
    if (_isItems ? _itemsStale : _invoicesStale) unawaited(_load());
  }

  /// Tablet only: close the drawer when the row it was opened from is no longer
  /// listed (e.g. after a filter change or a delete). Nothing is preselected —
  /// the table is the page, and the drawer opens on a tap.
  void _syncSelection() {
    if (!mounted || !context.isTablet || _selectedRow == null) return;
    final rows = _active.state.items;
    final stillThere = _isItems
        ? rows.any((r) => asStr(r['id']) == _selectedItemId)
        : rows.any((r) => asStr(r['id']) == asStr(_selectedRow!['id']));
    if (!stillThere) _clearSelection();
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

  void _setStaff(int? id, String name) {
    if (_staffId == id) return;
    setState(() {
      _staffId = id;
      _staffName = id == null ? '' : name;
    });
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
    final now = DateTime.now();
    final picked = await showAstraDateRangePicker(
      context,
      title: 'Sales range',
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
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'month' => 'This month',
        _ => (_startDate != null && _endDate != null) ? Dates.range(_startDate!, _endDate!) : 'Custom',
      };

  String get _methodLabel =>
      _methodId == null ? 'All methods' : _methods.firstWhere((m) => m.id == _methodId, orElse: () => PaymentMethod(id: 0, name: 'Method')).name;

  List<_SortOption> get _activeSortOptions => _isItems ? _itemSortOptions : _sortOptions;
  String get _activeSortBy => _isItems ? _itemSortBy : _sortBy;
  String get _activeSortDir => _isItems ? _itemSortDir : _sortDir;

  String get _sortLabel => _activeSortOptions
      .firstWhere((o) => o.by == _activeSortBy && o.dir == _activeSortDir, orElse: () => _activeSortOptions.first)
      .label;

  String get _statusLabel => switch (_status) {
        'completed' => 'Completed',
        'draft' => 'Draft',
        'cancelled' => 'Cancelled',
        _ => 'All statuses',
      };

  String get _staffLabel => _staffId == null ? 'All staff' : _staffName;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        // Rebuilds on the active tab's list; a tab switch swaps the bloc, which
        // BlocBuilder re-subscribes to.
        child: BlocBuilder<PaginatedListCubit, PaginatedListState>(
          bloc: _active,
          builder: (context, _) => context.isTablet
              // Tablet has no header band: the shell's side-rail is the chrome
              // and the page names itself.
              ? SafeArea(bottom: false, child: _tabletBody())
              : Column(
                  children: [
                    EmeraldHeader(title: 'Sales', subtitle: _subtitle, trailing: _returnsPill(), bottom: _tabSeg(onHero: true)),
                    Expanded(child: _phoneList()),
                  ],
                ),
        ),
      ),
    );
  }

  bool get _canSeeReturns => context.read<AuthCubit>().hasPermission(PermissionSlug.saleReturnView);

  String get _subtitle {
    final s = _active.state;
    if (s.isLoading && s.items.isEmpty) return 'Loading…';
    if (_isItems) return '${s.total} line${s.total == 1 ? '' : 's'} sold';
    return '${s.total} invoice${s.total == 1 ? '' : 's'} found';
  }

  // ---- phone ----

  Widget _phoneList() {
    return RefreshIndicator(
      onRefresh: _load,
      child: MaxWidthBox(
        maxWidth: 720,
        // Slivers, not ListView(children:) — the rows grow by a page on every
        // scroll, and the eager form would lay out every accumulated row on each
        // emit instead of recycling what is off-screen.
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
              sliver: SliverMainAxisGroup(slivers: _listSlivers(_active.state)),
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

  List<Widget> _listSlivers(PaginatedListState state) {
    if (state.isLoading) {
      return const [
        SliverToBoxAdapter(
          child: Padding(padding: EdgeInsets.symmetric(vertical: 48), child: Center(child: CircularProgressIndicator())),
        ),
      ];
    }
    if (state.hasFailed || state.items.isEmpty) {
      return [SliverToBoxAdapter(child: Padding(padding: const EdgeInsets.all(16), child: _emptyState(state)))];
    }
    return [
      SliverList.builder(
        itemCount: state.items.length,
        itemBuilder: (_, i) =>
            _cardRow(i, state.items.length, _isItems ? _itemRow(state.items[i]) : _invoiceRow(state.items[i])),
      ),
      if (state.loadingMore) const SliverToBoxAdapter(child: _MoreSpinner()),
    ];
  }

  Widget _emptyState(PaginatedListState state) {
    if (state.hasFailed) {
      return EmptyState(
        icon: Icons.wifi_off,
        title: _isItems ? 'Items unavailable' : 'Sales unavailable',
        message: state.errorMessage,
        action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load),
      );
    }
    return EmptyState(
      icon: _isItems ? Icons.inventory_2_outlined : Icons.receipt_long,
      title: _isItems ? 'No items sold' : 'No sales found',
      message: 'Try a wider date range or clearing the filters.',
    );
  }

  Widget _invoiceRow(Map<String, dynamic> r) {
    final p = context.astra;
    final d = _rowData(r);
    final held = _isHeld(r);
    final sub = '${d.who}${d.date.isEmpty ? '' : ' · ${d.date}'}'
        // Only someone who sees the whole till is told who rang it up: everyone
        // else is looking at a list of nothing but their own sales.
        '${_seesAllSales && d.staff.isNotEmpty ? ' · ${d.staff}' : ''}'
        '${d.offlineRef.isEmpty ? '' : ' · ${d.offlineRef}'}';
    return TabletListRow(
      selected: false,
      onTap: () => _open(r),
      padding: const EdgeInsets.fromLTRB(16, 11, 16, 11),
      child: Row(
        children: [
          // A held sale reads as a different kind of thing at a glance, because it
          // is one: it has not reached the server, so its reference is provisional
          // and its actions are different.
          IconChip(
            icon: held ? Icons.cloud_off_rounded : Icons.receipt_outlined,
            size: 36,
            radius: 11,
            bg: held ? p.warnTint : null,
            fg: held ? p.goldText : null,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(d.invoice,
                          maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                    ),
                    if (held) ...[
                      const SizedBox(width: 7),
                      StatusPill(label: 'HELD', bg: p.warnTint, fg: p.goldText),
                    ] else if (d.status.isNotEmpty) ...[
                      const SizedBox(width: 7),
                      StatusPill(label: d.status.toUpperCase(), bg: d.bg, fg: d.fg),
                    ],
                  ],
                ),
                const SizedBox(height: 3),
                Row(
                  children: [
                    Flexible(
                      child: Text(sub,
                          maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                    ),
                    if (d.method.isNotEmpty) ...[const SizedBox(width: 7), Flexible(child: _methodTag(d.method))],
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text(Money.of(d.amount), style: serif(size: 15, color: p.ink)),
          // Edit sits on the row only for a held sale. A committed one is edited
          // from its invoice, where the permission gate and the Return/Delete
          // actions live; a held one has none of those.
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

  Widget _itemRow(Map<String, dynamic> r) {
    final p = context.astra;
    final d = _itemData(r);
    return TabletListRow(
      selected: false,
      onTap: () => _openItem(r),
      padding: const EdgeInsets.fromLTRB(16, 11, 16, 11),
      child: Row(
        children: [
          _qtyChip(d.qty),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(d.product, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                const SizedBox(height: 3),
                Text('${d.invoice} · ${d.customer}${d.date.isEmpty ? '' : ' · ${d.date}'}',
                    maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(Money.of(d.total), style: serif(size: 15, color: p.ink)),
              const SizedBox(height: 2),
              Text(d.discount > 0 ? '− ${Money.of(d.discount)} disc' : '${qtyLabel(d.qty)} × ${Money.of(d.price)}',
                  style: ui(size: 9.5, weight: FontWeight.w700, color: d.discount > 0 ? AstraPalette.danger : p.textMuted)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _qtyChip(num qty) {
    final p = context.astra;
    return Container(
      width: 36,
      height: 36,
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(11)),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(qtyLabel(qty), style: ui(size: 12.5, weight: FontWeight.w800, color: p.primary, height: 1)),
          ),
          const SizedBox(height: 2),
          Text('QTY', style: ui(size: 7, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6, height: 1)),
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

  // ---- tablet: full-width ledger table + invoice drawer ----

  Widget _tabletBody() {
    // Sized from this screen's own width, not the window's — beside the shell's
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
                  title: 'Sales',
                  subtitle: _subtitle,
                  padding: const EdgeInsets.fromLTRB(20, 14, 20, 14),
                  actions: [
                    SizedBox(width: 260, child: _tabSeg()),
                    if (_canSeeReturns)
                      TabletActionButton(label: 'Returns', icon: Icons.assignment_return_outlined, onTap: _openReturns),
                  ],
                ),
                Padding(padding: const EdgeInsets.fromLTRB(20, 16, 20, 14), child: _tabletTools(tableWidth)),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                    child: AstraCard(
                      radius: 18,
                      padding: EdgeInsets.zero,
                      child: ClipRRect(borderRadius: BorderRadius.circular(18), child: _table(tableWidth)),
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

  Widget _table(double width) {
    final p = context.astra;
    final state = _active.state;
    final cols = _isItems ? _itemColumns(width) : _invoiceColumns(width);
    Widget body;
    if (state.isLoading) {
      body = const Center(child: CircularProgressIndicator());
    } else if (state.hasFailed || state.items.isEmpty) {
      // Still a scrollable, so pull-to-refresh works on an empty table.
      body = ListView(
        controller: _scrollCtl,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(24),
        children: [_emptyState(state)],
      );
    } else {
      body = ListView.builder(
        controller: _scrollCtl,
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount: state.items.length + (state.loadingMore ? 1 : 0),
        itemBuilder: (_, i) {
          if (i >= state.items.length) return const _MoreSpinner();
          final r = state.items[i];
          return _isItems ? _itemTableRow(cols, r) : _invoiceTableRow(cols, r);
        },
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
  /// small tablet in portrait, or the invoice drawer's share of the width).
  List<_Col> _invoiceColumns(double w) => [
        const _Col('invoice', 'Invoice', 3),
        const _Col('date', 'Date', 3),
        const _Col('customer', 'Customer', 4),
        if (_seesAllSales && w >= 700) const _Col('staff', 'Staff', 3),
        if (w >= 760) const _Col('payment', 'Payment', 3),
        const _Col('status', 'Status', 3),
        const _Col('amount', 'Amount', 3, end: true),
      ];

  List<_Col> _itemColumns(double w) => [
        const _Col('product', 'Product', 5),
        if (w >= 900) const _Col('code', 'Code', 2),
        const _Col('invoice', 'Invoice', 3),
        if (w >= 760) const _Col('customer', 'Customer', 3),
        const _Col('qty', 'Qty', 2, end: true),
        if (w >= 700) const _Col('price', 'Price', 3, end: true),
        if (w >= 820) const _Col('discount', 'Disc', 2, end: true),
        const _Col('total', 'Total', 3, end: true),
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

  Widget _invoiceTableRow(List<_Col> cols, Map<String, dynamic> r) {
    final p = context.astra;
    final d = _rowData(r);
    final held = _isHeld(r);
    final selected = !_isItems && _selectedRow != null && asStr(_selectedRow!['id']) == asStr(r['id']);
    Widget cell(String id) => switch (id) {
          'invoice' => _cellText(d.invoice, strong: true),
          'date' => _cellText(d.date),
          'customer' => _cellText(d.who),
          'staff' => _cellText(d.staff.isEmpty ? '—' : d.staff),
          'payment' => _cellText(d.method.isEmpty ? '—' : d.method),
          // Scales down rather than overflowing a narrow column.
          'status' => FittedBox(
              fit: BoxFit.scaleDown,
              child: held
                  ? StatusPill(label: 'HELD', bg: p.warnTint, fg: p.goldText)
                  : (d.status.isEmpty ? const SizedBox.shrink() : StatusPill(label: d.status.toUpperCase(), bg: d.bg, fg: d.fg))),
          'amount' => _cellText(Money.of(d.amount), amount: true),
          _ => const SizedBox.shrink(),
        };
    return TabletListRow(
      selected: selected,
      onTap: () => _selectRow(r),
      padding: const EdgeInsets.fromLTRB(20, 11, 20, 11),
      child: _tableLine(cols, [for (final c in cols) cell(c.id)]),
    );
  }

  Widget _itemTableRow(List<_Col> cols, Map<String, dynamic> r) {
    final d = _itemData(r);
    final selected = _isItems && _selectedItemId == d.id;
    Widget cell(String id) => switch (id) {
          'product' => _cellText(d.product, strong: true),
          'code' => _cellText(d.code.isEmpty ? '—' : d.code),
          'invoice' => _cellText(d.invoice),
          'customer' => _cellText(d.customer),
          'qty' => _cellText(qtyLabel(d.qty), strong: true),
          'price' => _cellText(Money.of(d.price)),
          'discount' => _cellText(d.discount > 0 ? Money.of(d.discount) : '—', color: d.discount > 0 ? AstraPalette.danger : null),
          'total' => _cellText(Money.of(d.total), amount: true),
          _ => const SizedBox.shrink(),
        };
    return TabletListRow(
      selected: selected,
      onTap: () => _openItem(r),
      padding: const EdgeInsets.fromLTRB(20, 11, 20, 11),
      child: _tableLine(cols, [for (final c in cols) cell(c.id)]),
    );
  }

  /// Whole-set totals under the table — what the filters match, not just the
  /// rows scrolled into view so far.
  Widget _tableFooter(List<_Col> cols, PaginatedListState state) {
    final p = context.astra;
    final loaded = state.items.length;
    Widget cell(String id) {
      final style = ui(size: 12, weight: FontWeight.w800, color: p.ink);
      return switch (id) {
        'invoice' when !_isItems => Text('$loaded of ${state.total}', maxLines: 1, overflow: TextOverflow.ellipsis, style: style),
        'product' => Text('$loaded of ${state.total} lines', maxLines: 1, overflow: TextOverflow.ellipsis, style: style),
        'qty' => Text(qtyLabel(state.summary['quantity'] ?? 0), style: style),
        'discount' => Text(Money.of(state.summary['discount'] ?? 0), maxLines: 1, overflow: TextOverflow.ellipsis, style: style),
        'amount' || 'total' => Text(Money.of(state.totalPaid), maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 14, color: p.ink)),
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

  /// The selected invoice, sliding over the table's right side. The table stays
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

  /// The drawer's content — the selected invoice, embedded (its close button
  /// clears the selection; a delete drops the row and reloads the list).
  Widget _detailPane() {
    if (_detailLoading) return const Center(child: CircularProgressIndicator());
    if (_detailError != null || _selectedSale == null) {
      return EmptyState(
        icon: Icons.wifi_off,
        title: 'Invoice unavailable',
        message: _detailError ?? 'Could not open invoice.',
        action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: () => _selectRow(_selectedRow!, itemId: _selectedItemId)),
      );
    }
    final sale = _selectedSale!;
    return InvoiceScreen(
      key: ValueKey('invoice-${sale.id}'),
      sale: sale,
      highlightItemId: int.tryParse(_selectedItemId ?? ''),
      onClose: () {
        _clearSelection();
        _load();
      },
    );
  }

  void _clearSelection() {
    setState(() {
      _selectedRow = null;
      _selectedSale = null;
      _selectedItemId = null;
      _detailError = null;
      _detailLoading = false;
    });
  }

  /// Open an Items-tab line: its invoice, with the line picked out — pushed on a
  /// phone, in the drawer on a tablet.
  void _openItem(Map<String, dynamic> r) {
    final d = _itemData(r);
    if (d.saleId.isEmpty) return;
    if (context.isTablet) {
      _selectRow({'id': d.saleId}, itemId: d.id);
    } else {
      _open({'id': d.saleId}, lineId: int.tryParse(d.id));
    }
  }

  /// Load a sale into the drawer (tablet). Guarded so a fast succession of taps
  /// only ever shows the last-tapped invoice.
  Future<void> _selectRow(Map<String, dynamic> r, {String? itemId}) async {
    // A held row is its own detail — no fetch, and none possible.
    if (_isHeld(r)) {
      setState(() {
        _selectedRow = r;
        _selectedItemId = null;
        _selectedSale = Sale.fromJson(r);
        _detailLoading = false;
        _detailError = null;
      });
      return;
    }

    final id = asStr(r['id']);
    if (id.isEmpty) return;
    // Another line of the invoice already open: no refetch, just move the mark.
    if (_selectedSale != null && asStr(_selectedRow?['id']) == id && !_detailLoading) {
      setState(() => _selectedItemId = itemId);
      return;
    }
    setState(() {
      _selectedRow = r;
      _selectedItemId = itemId;
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

  // ---- row data ----

  /// The display fields of one invoice row, shared by the phone row and the
  /// tablet table so both read the payload the same way.
  ({
    String invoice,
    String offlineRef,
    num amount,
    String who,
    String staff,
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
      // Who rang it up. `SaleListResource` sends the name, and a held row carries
      // the same key — the offline ticket stamps it at capture — so a queued sale
      // names its cashier exactly as the committed one will.
      staff: asStr(r['created_by']),
      status: status,
      date: Dates.human(asStr(r['date'])),
      method: asStr(r['payment_methods']),
      bg: bg,
      fg: fg,
    );
  }

  /// The display fields of one Items-tab line (`SaleItemListResource`).
  ({
    String id,
    String saleId,
    String product,
    String code,
    String invoice,
    String customer,
    String date,
    num qty,
    num price,
    num discount,
    num total
  }) _itemData(Map<String, dynamic> r) {
    final product = r['product'] is Map ? r['product'] as Map : const {};
    final saleId = asStr(r['sale_id']);
    return (
      id: asStr(r['id']),
      saleId: saleId,
      product: asStr(product['name']).isEmpty ? 'Item' : asStr(product['name']),
      code: asStr(product['code']),
      invoice: asStr(r['invoice_no']).isEmpty ? '#$saleId' : asStr(r['invoice_no']),
      customer: asStr(r['customer']).isEmpty ? AppStrings.walkInCustomer : asStr(r['customer']),
      date: Dates.human(asStr(r['date'])),
      qty: asNum(r['quantity']),
      price: asNum(r['unit_price']),
      discount: asNum(r['discount']),
      total: asNum(r['total']),
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

  Future<void> _openPaymentSheet() async {
    // Retried here because the one load at screen init can have failed — the app
    // may have started during an outage before the snapshot was readable, and the
    // shell keeps this screen alive, so nothing would ask again for the rest of
    // the session. Offline this is a local read of the cached list.
    if (_methods.isEmpty) await _loadMethods();
    if (!mounted) return;
    unawaited(_optionSheet('Payment method', [
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
    ]));
  }

  /// Who rang the sale up. Fed by [StylistCubit], which is the same staff list
  /// the New Sale picker uses and is served from the offline snapshot when there
  /// is no network — so the filter keeps working on a till that has dropped off.
  Future<void> _openStaffSheet() async {
    final staff = context.read<StylistCubit>();
    // Served from the offline snapshot when there is no network, so this is a
    // local read on a till that has provisioned — but it can still be the first
    // call of the session, and the sheet is built from a fixed list of tiles.
    await staff.loadIfNeeded();
    if (!mounted) return;
    unawaited(_optionSheet('Staff', [
      _optTile(
        label: 'All staff',
        icon: Icons.groups_outlined,
        active: _staffId == null,
        onTap: () {
          Navigator.pop(context);
          _setStaff(null, '');
        },
      ),
      // The whole staff list, not the assignable one ([StylistCubit.all]) —
      // filtering by who rang a sale is a view, open only to see-all users.
      for (final e in staff.state.items)
        _optTile(
          label: e.name,
          icon: Icons.person_outline,
          active: _staffId == e.id,
          onTap: () {
            Navigator.pop(context);
            _setStaff(e.id, e.name);
          },
        ),
    ]));
  }

  void _openStatusSheet() {
    const statuses = <(String, String?, IconData)>[
      ('All statuses', null, Icons.all_inclusive_rounded),
      ('Completed', 'completed', Icons.task_alt_rounded),
      ('Draft', 'draft', Icons.edit_note_rounded),
      ('Cancelled', 'cancelled', Icons.cancel_outlined),
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

  void _openSort() {
    _optionSheet(_isItems ? 'Sort items' : 'Sort sales', [
      for (final o in _activeSortOptions)
        _optTile(
          label: o.label,
          icon: o.icon,
          active: _activeSortBy == o.by && _activeSortDir == o.dir,
          onTap: () {
            Navigator.pop(context);
            if (_activeSortBy == o.by && _activeSortDir == o.dir) return;
            setState(() {
              if (_isItems) {
                _itemSortBy = o.by;
                _itemSortDir = o.dir;
              } else {
                _sortBy = o.by;
                _sortDir = o.dir;
              }
            });
            _load();
          },
        ),
    ]);
  }

  /// Push the invoice for [r]. [lineId] picks out the sale line an Items-tab row
  /// was opened from.
  Future<void> _open(Map<String, dynamic> r, {int? lineId}) async {
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
      final deleted = await context.push<bool>(lineId == null ? Routes.invoice : '${Routes.invoice}?line=$lineId', extra: sale);
      if (deleted == true && mounted) unawaited(_load());
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) AstraSnack.error(context, 'Could not open invoice');
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
      AstraSnack.show(context, 'This sale has synced — reopen it to edit.');
      unawaited(_load());
      return;
    }

    context.read<CartCubit>().seedFromPendingSale(row);
    await context.push(Routes.sale);
    // The correction may have changed the figures, or synced the row away.
    if (mounted) unawaited(_load());
  }
}

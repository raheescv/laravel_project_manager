import 'dart:async';

import 'package:flutter/material.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/features/sale/screens/v3/invoice_screen.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';

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
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  List<Map<String, dynamic>> _rows = [];
  int _total = 0;
  double _totalPaid = 0;
  int _page = 1;
  int _lastPage = 1;
  int _reqId = 0; // guards against out-of-order / superseded responses
  final _scrollCtl = ScrollController();
  StreamSubscription<int>? _branchSub;

  bool get _hasMore => _page < _lastPage;

  String? _status; // null = all
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
      : context.push('/sales-returns');

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
    _scrollCtl.dispose();
    _branchSub?.cancel();
    super.dispose();
  }

  /// Infinite scroll: pull the next page once the user nears the bottom.
  void _onScroll() {
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 500) _loadMore();
  }

  Future<SalesPage> _fetch(int page) => serviceLocator<SaleRepository>().sales(
        status: _status,
        paymentMethodId: _methodId,
        fromDate: _startDate == null ? null : Dates.iso(_startDate!),
        toDate: _endDate == null ? null : Dates.iso(_endDate!),
        sortBy: _sortBy,
        sortDirection: _sortDir,
        page: page,
      );

  /// (Re)load from page 1 for the current filters — any in-flight loadMore for
  /// an older filter set is voided by the request id.
  Future<void> _load() async {
    final req = ++_reqId;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _fetch(1);
      if (!mounted || req != _reqId) return;
      setState(() {
        _rows = res.rows;
        _total = res.total;
        _totalPaid = res.totalPaid;
        _page = res.currentPage;
        _lastPage = res.lastPage;
      });
      _syncSelection();
    } catch (e) {
      if (mounted && req == _reqId) setState(() => _error = 'Could not load sales.');
    }
    if (mounted && req == _reqId) setState(() => _loading = false);
  }

  /// Tablet only: keep the detail pane in step with the list — drop a selection
  /// whose row no longer exists (e.g. after a filter change or a delete), and
  /// preselect the first invoice so the pane is never awkwardly empty.
  void _syncSelection() {
    if (!mounted || !context.isTablet) return;
    final selId = _selectedRow == null ? '' : asStr(_selectedRow!['id']);
    final stillThere = selId.isNotEmpty && _rows.any((r) => asStr(r['id']) == selId);
    if (!stillThere) {
      if (_rows.isEmpty) {
        setState(() {
          _selectedRow = null;
          _selectedSale = null;
          _detailError = null;
          _detailLoading = false;
        });
      } else {
        _selectRow(_rows.first);
      }
    }
  }

  /// Fetch the next page and append it. No-op while a load is running or the
  /// last page has been reached.
  Future<void> _loadMore() async {
    if (_loadingMore || _loading || !_hasMore) return;
    final req = _reqId; // tie to the current filters; bail if they change
    setState(() => _loadingMore = true);
    try {
      final res = await _fetch(_page + 1);
      if (!mounted || req != _reqId) return;
      setState(() {
        _rows = [..._rows, ...res.rows];
        _page = res.currentPage;
        _lastPage = res.lastPage;
        _total = res.total;
        _totalPaid = res.totalPaid;
      });
    } catch (_) {
      // Keep what we have; the next scroll can retry the same page.
    }
    if (mounted && req == _reqId) setState(() => _loadingMore = false);
  }

  /// Payment methods power the in-card payment selector; a failure just leaves
  /// it showing "All methods".
  Future<void> _loadMethods() async {
    try {
      final m = await serviceLocator<LookupRepository>().paymentMethods();
      if (mounted) setState(() => _methods = m);
    } catch (_) {/* keep the Sales list usable without the method filter */}
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
      _load();
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
    final sub = _loading && _rows.isEmpty ? 'Loading…' : '$_total invoice${_total == 1 ? '' : 's'} found';
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
    return RefreshIndicator(
      onRefresh: _load,
      child: MaxWidthBox(
        maxWidth: 720,
        child: ListView(
          controller: _scrollCtl,
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 120),
          children: [
            _bento(),
            _resultLine(),
            if (_loading)
              const Padding(padding: EdgeInsets.symmetric(vertical: 48), child: Center(child: CircularProgressIndicator()))
            else if (_error != null)
              EmptyState(icon: Icons.wifi_off, title: 'Sales unavailable', message: _error, action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load))
            else if (_rows.isEmpty)
              EmptyState(icon: Icons.receipt_long, title: 'No sales found', message: 'Try a wider date range or clearing the filters.')
            else ...[
              for (final r in _rows)
                Padding(padding: const EdgeInsets.only(bottom: 9), child: _row(r)),
              if (_loadingMore)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 16),
                  child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4))),
                ),
            ],
          ],
        ),
      ),
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
      subtitle: _loading && _rows.isEmpty
          ? 'Loading…'
          : '${Money.of(_totalPaid)} collected · $_total invoice${_total == 1 ? '' : 's'}',
      trailing: canSeeReturns
          ? TabletActionButton(
              label: 'Returns',
              icon: Icons.assignment_return_outlined,
              onTap: _openReturns)
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

  Widget _tabletList() {
    return ListView(
      controller: _scrollCtl,
      padding: const EdgeInsets.only(bottom: 24),
      children: [
        if (_loading)
          const Padding(padding: EdgeInsets.symmetric(vertical: 60), child: Center(child: CircularProgressIndicator()))
        else if (_error != null)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 24),
            child: EmptyState(
                icon: Icons.wifi_off,
                title: 'Sales unavailable',
                message: _error,
                action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load)),
          )
        else if (_rows.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 14, vertical: 24),
            child: EmptyState(
                icon: Icons.receipt_long,
                title: 'No sales found',
                message: 'Try a wider date range or clearing the filters.'),
          )
        else ...[
          for (final r in _rows) _tabletRow(r),
          if (_loadingMore)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4))),
            ),
        ],
      ],
    );
  }

  /// One flat pane row — invoice + amount on the first line, who/when/how and
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
    final id = asStr(r['id']);
    if (id.isEmpty) return;
    setState(() {
      _selectedRow = r;
      _selectedSale = null;
      _detailLoading = true;
      _detailError = null;
    });
    try {
      final sale = await serviceLocator<SaleRepository>().saleById(id);
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

  Widget _bento() {
    return AstraCard(
      radius: 20,
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _fieldLabel('DATE RANGE'),
          _dateBox(),
          const SizedBox(height: 13),
          _fieldLabel('STATUS'),
          _statusSeg(),
          const SizedBox(height: 13),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(child: _field('PAYMENT', _selBox(_methodLabel, Icons.account_balance_wallet_outlined, _openPaymentSheet))),
              const SizedBox(width: 10),
              Expanded(child: _field('SORT', _selBox(_sortLabel, Icons.swap_vert_rounded, _openSort))),
            ],
          ),
        ],
      ),
    );
  }

  Widget _field(String label, Widget child) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [_fieldLabel(label), child],
      );

  Widget _fieldLabel(String t) => Padding(
        padding: const EdgeInsets.only(left: 2, bottom: 6),
        child: Text(t, style: ui(size: 8.5, weight: FontWeight.w800, color: context.astra.textMuted, letterSpacing: 1.1)),
      );

  Widget _dateBox() {
    final p = context.astra;
    return GestureDetector(
      onTap: _openDateSheet,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 12),
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
        child: Row(
          children: [
            Icon(Icons.event_rounded, size: 17, color: p.primary),
            const SizedBox(width: 10),
            Expanded(child: Text(_dateLabel, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 15, color: p.ink))),
            Icon(Icons.keyboard_arrow_down_rounded, size: 18, color: p.textMuted),
          ],
        ),
      ),
    );
  }

  Widget _statusSeg() {
    final p = context.astra;
    Widget seg(String label, String? status) {
      final active = _status == status;
      return Expanded(
        child: GestureDetector(
          onTap: () => _setStatus(status),
          behavior: HitTestBehavior.opaque,
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 8),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: active ? p.card : Colors.transparent,
              borderRadius: BorderRadius.circular(10),
              boxShadow: active ? context.astraTheme.softShadow : null,
            ),
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(label,
                  style: ui(size: 11, weight: active ? FontWeight.w800 : FontWeight.w700, color: active ? p.primary : p.textSecondary)),
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
      child: Row(children: [
        seg('All', null),
        seg('Completed', 'completed'),
        seg('Draft', 'draft'),
        seg('Cancelled', 'cancelled'),
      ]),
    );
  }

  Widget _selBox(String value, IconData icon, VoidCallback onTap) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12)),
        child: Row(
          children: [
            Icon(icon, size: 15, color: p.primary),
            const SizedBox(width: 8),
            Expanded(child: Text(value, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12, weight: FontWeight.w700, color: p.ink))),
            Icon(Icons.keyboard_arrow_down_rounded, size: 16, color: p.textMuted),
          ],
        ),
      ),
    );
  }

  Widget _resultLine() {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 16, 4, 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Flexible(
            child: Text('$_total invoice${_total == 1 ? '' : 's'}',
                maxLines: 1, overflow: TextOverflow.ellipsis,
                style: ui(size: 11.5, weight: FontWeight.w700, color: p.textMuted)),
          ),
          const SizedBox(width: 8),
          Flexible(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerRight,
              child: Text(Money.of(_totalPaid), style: serif(size: 16, color: p.goldText)),
            ),
          ),
        ],
      ),
    );
  }

  // ---- sale row ----

  /// The display fields of one list row, shared by the phone card and the
  /// tablet pane row so both read the payload the same way.
  ({String invoice, num amount, String who, String status, String date, String method, Color bg, Color fg}) _rowData(
      Map<String, dynamic> r) {
    final p = context.astra;
    final invoice = asStr(r['invoice_no']).isEmpty ? '#${asStr(r['id'])}' : asStr(r['invoice_no']);
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
      amount: asNum(summary['paid'] ?? summary['gross_amount'] ?? r['paid'] ?? r['gross_amount'] ?? r['amount']),
      who: asStr(customer['name']).isEmpty ? 'Walk-in' : asStr(customer['name']),
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
    return AstraCard(
      radius: 14,
      padding: const EdgeInsets.all(12),
      onTap: () => _open(asStr(r['id'])),
      child: Row(
        children: [
          IconChip(icon: Icons.shopping_bag_outlined, size: 40, radius: 12),
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
          Text(Money.of(amount), style: serif(size: 15, color: p.ink)),
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

  Widget _optTile({
    required String label,
    required IconData icon,
    required bool active,
    required VoidCallback onTap,
    String? trailing,
  }) {
    final p = context.astra;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 6),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        decoration: BoxDecoration(color: active ? p.tint : Colors.transparent, borderRadius: BorderRadius.circular(13)),
        child: Row(
          children: [
            Icon(icon, size: 18, color: active ? p.primary : p.textSecondary),
            const SizedBox(width: 12),
            Expanded(
              child: Text(label, style: ui(size: 13, weight: active ? FontWeight.w800 : FontWeight.w600, color: active ? p.ink : p.textSecondary)),
            ),
            if (trailing != null)
              Padding(
                padding: const EdgeInsets.only(right: 6),
                child: Text(trailing, style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
              ),
            if (active) Icon(Icons.check_circle_rounded, size: 18, color: p.primary),
          ],
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

  Future<void> _open(String id) async {
    if (id.isEmpty) return;
    showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));
    try {
      final sale = await serviceLocator<SaleRepository>().saleById(id);
      if (mounted) Navigator.pop(context);
      if (!mounted) return;
      // The invoice view returns `true` after deleting the sale — reload so the
      // deleted row drops off the list.
      final deleted = await context.push<bool>('/invoice', extra: sale);
      if (deleted == true && mounted) _load();
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not open invoice')));
    }
  }
}

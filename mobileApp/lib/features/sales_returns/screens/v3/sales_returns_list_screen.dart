import 'package:flutter/material.dart';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

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

  bool get _hasMore => _page < _lastPage;

  String? _status; // null = all
  int? _methodId; // null = all payment methods
  List<PaymentMethod> _methods = [];
  String _sortBy = 'date';
  String _sortDir = 'desc';

  // Date range — preset drives [_startDate]/[_endDate]; defaults to today.
  String _datePreset = 'today'; // today | 7d | 30d | month | custom
  DateTime? _startDate;
  DateTime? _endDate;

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
  }

  @override
  void dispose() {
    _scrollCtl.dispose();
    super.dispose();
  }

  /// Infinite scroll: pull the next page once the user nears the bottom.
  void _onScroll() {
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 500) _loadMore();
  }

  Future<SaleReturnsPage> _fetch(int page) => serviceLocator<SaleReturnRepository>().saleReturns(
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
    } catch (e) {
      if (mounted && req == _reqId) setState(() => _error = 'Could not load returns.');
    }
    if (mounted && req == _reqId) setState(() => _loading = false);
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

  /// Pop when there's a stack to pop; otherwise fall back to Sales — this screen
  /// is also reached via `context.go` (from a completed return), which leaves
  /// nothing to pop.
  void _back() {
    if (context.canPop()) {
      context.pop();
    } else {
      context.go('/sales');
    }
  }

  /// Returns lives under the Sales section, so the bottom-nav tabs re-enter the
  /// home shell at the tapped section (Sales shows highlighted here).
  void _onNavTap(int i) => context.go('/home?tab=$i');

  @override
  Widget build(BuildContext context) {
    final sub = _loading && _rows.isEmpty ? 'Loading…' : '$_total return${_total == 1 ? '' : 's'} found';
    return Scaffold(
      backgroundColor: Colors.transparent,
      extendBody: true,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.chevron_left, onTap: _back),
              title: 'Sales Returns',
              subtitle: sub,
              trailing: HeaderIconButton(icon: Icons.add, gold: true, onTap: () => context.push('/sale-return/pick')),
            ),
            Expanded(child: _body()),
          ],
        ),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: AstraNavFab(onTap: () => context.push('/sale-return/pick')),
      bottomNavigationBar: AstraNavBar(activeIndex: 1, onTap: _onNavTap),
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
              EmptyState(icon: Icons.wifi_off, title: 'Returns unavailable', message: _error, action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load))
            else if (_rows.isEmpty)
              EmptyState(
                icon: Icons.assignment_return_outlined,
                title: 'No returns found',
                message: 'Try a wider date range, or start a return from a paid invoice.',
                action: AstraButton(label: 'New return', icon: Icons.add, expand: false, onTap: () => context.push('/sale-return/pick')),
              )
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
          Text('$_total return${_total == 1 ? '' : 's'}', style: ui(size: 11.5, weight: FontWeight.w700, color: p.textMuted)),
          Text('− ${Money.of(_totalPaid)}', style: serif(size: 16, color: p.goldText)),
        ],
      ),
    );
  }

  // ---- return row ----

  Widget _row(Map<String, dynamic> r) {
    final p = context.astra;
    final ref = asStr(r['reference_no']).isEmpty
        ? (asStr(r['invoice_no']).isEmpty ? '#${asStr(r['id'])}' : asStr(r['invoice_no']))
        : asStr(r['reference_no']);
    final summary = r['summary'] is Map ? r['summary'] as Map : const {};
    final amount = asNum(summary['paid'] ?? summary['grand_total'] ?? summary['total'] ?? r['paid']);
    final customer = r['customer'] is Map ? r['customer'] as Map : const {};
    final who = asStr(customer['name']).isEmpty ? 'Walk-in' : asStr(customer['name']);
    final status = asStr(r['status']);
    final date = Dates.human(asStr(r['date']));
    final method = asStr(r['payment_methods']);
    final (bg, fg) = switch (status) {
      'completed' => (p.successTint, AstraPalette.success),
      _ => (p.warnTint, p.goldText),
    };
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
      builder: (ctx) => Container(
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
            ...tiles,
          ],
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
    showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));
    try {
      final ret = await serviceLocator<SaleReturnRepository>().saleReturnById(id);
      if (mounted) Navigator.pop(context);
      if (mounted) context.push('/return-receipt', extra: ret);
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not open return')));
    }
  }
}

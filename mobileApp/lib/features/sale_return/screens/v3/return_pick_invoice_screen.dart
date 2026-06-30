import 'dart:async';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Step 1 of a return: find and select the paid invoice to return against.
/// Reuses the Sales list (status = completed) so only billable sales appear.
class ReturnPickInvoiceScreen extends StatefulWidget {
  const ReturnPickInvoiceScreen({super.key});
  @override
  State<ReturnPickInvoiceScreen> createState() => _ReturnPickInvoiceScreenState();
}

class _ReturnPickInvoiceScreenState extends State<ReturnPickInvoiceScreen> {
  final _searchCtl = TextEditingController();
  final _scrollCtl = ScrollController();
  Timer? _debounce;

  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  List<Map<String, dynamic>> _rows = [];
  int _page = 1;
  int _lastPage = 1;
  int _reqId = 0;

  String _search = '';
  String _datePreset = '30d'; // 30d | month | all
  DateTime? _startDate;
  DateTime? _endDate;

  bool get _hasMore => _page < _lastPage;

  @override
  void initState() {
    super.initState();
    _applyPreset('30d', reload: false);
    _scrollCtl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtl.dispose();
    _scrollCtl.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 500) _loadMore();
  }

  void _applyPreset(String id, {bool reload = true}) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    _datePreset = id;
    switch (id) {
      case '30d':
        _startDate = today.subtract(const Duration(days: 29));
        _endDate = today;
      case 'month':
        _startDate = DateTime(now.year, now.month, 1);
        _endDate = today;
      case 'all':
        _startDate = null;
        _endDate = null;
    }
    if (reload) {
      setState(() {});
      _load();
    }
  }

  void _onSearchChanged(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () {
      _search = v.trim();
      _load();
    });
  }

  Future<SalesPage> _fetch(int page) => serviceLocator<SaleRepository>().sales(
        status: 'completed',
        search: _search.isEmpty ? null : _search,
        fromDate: _startDate == null ? null : Dates.iso(_startDate!),
        toDate: _endDate == null ? null : Dates.iso(_endDate!),
        page: page,
      );

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
        _page = res.currentPage;
        _lastPage = res.lastPage;
      });
    } catch (e) {
      if (mounted && req == _reqId) setState(() => _error = 'Could not load invoices.');
    }
    if (mounted && req == _reqId) setState(() => _loading = false);
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _loading || !_hasMore) return;
    final req = _reqId;
    setState(() => _loadingMore = true);
    try {
      final res = await _fetch(_page + 1);
      if (!mounted || req != _reqId) return;
      setState(() {
        _rows = [..._rows, ...res.rows];
        _page = res.currentPage;
        _lastPage = res.lastPage;
      });
    } catch (_) {}
    if (mounted && req == _reqId) setState(() => _loadingMore = false);
  }

  /// Load the returnable lines for the chosen sale, seed the draft, and move on
  /// to the New Return screen.
  Future<void> _pick(String id) async {
    if (id.isEmpty) return;
    showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));
    try {
      final returnable = await serviceLocator<SaleReturnRepository>().returnableSale(id);
      if (!mounted) return;
      Navigator.pop(context); // close the loader
      final hasReturnable = returnable.lines.any((l) => l.returnableQuantity > 0);
      if (!hasReturnable) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Every item on this invoice has already been returned.')),
        );
        return;
      }
      context.read<ReturnDraftCubit>().seed(returnable);
      context.push('/sale-return');
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not open invoice')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(
                icon: Icons.chevron_left,
                onTap: () => context.canPop() ? context.pop() : context.go('/sales-returns'),
              ),
              title: 'Return against',
              subtitle: 'Select a paid invoice',
            ),
            Expanded(child: _body()),
          ],
        ),
      ),
    );
  }

  Widget _body() {
    return MaxWidthBox(
      maxWidth: 720,
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 8),
            child: Column(
              children: [
                _searchField(),
                const SizedBox(height: 12),
                _dateSeg(),
              ],
            ),
          ),
          Expanded(child: _list()),
        ],
      ),
    );
  }

  Widget _searchField() {
    final p = context.astra;
    final t = context.astraTheme;
    return Container(
      decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(14), boxShadow: t.softShadow),
      child: TextField(
        controller: _searchCtl,
        onChanged: _onSearchChanged,
        style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
        decoration: InputDecoration(
          isDense: true,
          hintText: 'Search invoice no, customer, mobile',
          hintStyle: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted),
          prefixIcon: Icon(Icons.search, color: p.textMuted, size: 20),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 14),
        ),
      ),
    );
  }

  Widget _dateSeg() {
    final p = context.astra;
    Widget seg(String label, String id) {
      final active = _datePreset == id;
      return Expanded(
        child: GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: () => _applyPreset(id),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 9),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: active ? p.card : Colors.transparent,
              borderRadius: BorderRadius.circular(10),
              boxShadow: active ? context.astraTheme.softShadow : null,
            ),
            child: Text(label, style: ui(size: 11.5, weight: active ? FontWeight.w800 : FontWeight.w700, color: active ? p.primary : p.textSecondary)),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
      child: Row(children: [seg('Last 30 days', '30d'), seg('This month', 'month'), seg('All time', 'all')]),
    );
  }

  Widget _list() {
    if (_loading && _rows.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null && _rows.isEmpty) {
      return EmptyState(icon: Icons.wifi_off, title: 'Invoices unavailable', message: _error, action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load));
    }
    if (_rows.isEmpty) {
      return EmptyState(icon: Icons.receipt_long, title: 'No paid invoices', message: 'Try a wider date range or a different search.');
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        controller: _scrollCtl,
        padding: const EdgeInsets.fromLTRB(16, 6, 16, 120),
        children: [
          for (final r in _rows)
            Padding(padding: const EdgeInsets.only(bottom: 9), child: _row(r)),
          if (_loadingMore)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4))),
            ),
        ],
      ),
    );
  }

  Widget _row(Map<String, dynamic> r) {
    final p = context.astra;
    final invoice = asStr(r['invoice_no']).isEmpty ? '#${asStr(r['id'])}' : asStr(r['invoice_no']);
    final summary = r['summary'] is Map ? r['summary'] as Map : const {};
    final amount = asNum(summary['paid'] ?? summary['gross_amount'] ?? r['paid']);
    final customer = r['customer'] is Map ? r['customer'] as Map : const {};
    final who = asStr(customer['name']).isEmpty ? 'Walk-in' : asStr(customer['name']);
    final date = Dates.human(asStr(r['date']));
    final items = asNum(r['items_count']).toInt();
    return AstraCard(
      radius: 14,
      padding: const EdgeInsets.all(12),
      onTap: () => _pick(asStr(r['id'])),
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
                    Flexible(child: Text(invoice, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink))),
                    const SizedBox(width: 7),
                    StatusPill(label: 'PAID', bg: p.successTint, fg: AstraPalette.success),
                  ],
                ),
                const SizedBox(height: 3),
                Text(
                  '$who${date.isEmpty ? '' : ' · $date'}${items > 0 ? ' · $items item${items == 1 ? '' : 's'}' : ''}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text(Money.of(amount), style: serif(size: 15, color: p.ink)),
          const SizedBox(width: 6),
          Icon(Icons.chevron_right_rounded, size: 18, color: p.textMuted),
        ],
      ),
    );
  }
}

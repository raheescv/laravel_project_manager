import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/stock_check_models.dart';
import '../../domain/repository/stock_check_repository.dart';
import 'stock_check_scan_screen.dart';

/// One filter chip → the API `status` / `difference_condition` it maps to.
class _Filter {
  const _Filter(this.key, this.label, {this.status, this.diff});
  final String key;
  final String label;
  final String? status;
  final String? diff;
}

const _filters = <_Filter>[
  _Filter('all', 'All'),
  _Filter('pending', 'Pending', status: 'pending'),
  _Filter('counted', 'Counted', status: 'completed'),
  _Filter('variance', 'Variance', diff: 'variance'),
];

class StockCheckCountScreen extends StatefulWidget {
  const StockCheckCountScreen({super.key, required this.detail});
  final StockCheckDetail detail;
  @override
  State<StockCheckCountScreen> createState() => _StockCheckCountScreenState();
}

class _StockCheckCountScreenState extends State<StockCheckCountScreen> {
  late StockCheckDetail _stats = widget.detail;

  bool _loading = true;
  bool _loadingMore = false;
  bool _saving = false;
  String? _error;

  List<StockCheckItem> _items = [];
  final Set<int> _dirty = {};
  int _page = 1;
  int _lastPage = 1;
  int _reqId = 0;

  String _filter = 'all';
  String _search = '';
  Timer? _debounce;
  final _searchCtl = TextEditingController();
  final _scrollCtl = ScrollController();

  bool get _hasMore => _page < _lastPage;
  _Filter get _activeFilter => _filters.firstWhere((f) => f.key == _filter);

  StockCheckRepository get _repo => serviceLocator<StockCheckRepository>();

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _refreshStats();
      _reload();
    });
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
    if (pos.pixels >= pos.maxScrollExtent - 400) _loadMore();
  }

  Future<void> _refreshStats() async {
    try {
      final d = await _repo.show(widget.detail.id);
      if (mounted) setState(() => _stats = d);
    } catch (_) {/* keep last stats */}
  }

  Future<void> _reload() async {
    final req = ++_reqId;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final f = _activeFilter;
      final res = await _repo.items(widget.detail.id, status: f.status, differenceCondition: f.diff, search: _search, page: 1);
      if (!mounted || req != _reqId) return;
      setState(() {
        _items = res.rows;
        _dirty.clear();
        _page = res.currentPage;
        _lastPage = res.lastPage;
      });
    } catch (_) {
      if (mounted && req == _reqId) setState(() => _error = 'Could not load items.');
    }
    if (mounted && req == _reqId) setState(() => _loading = false);
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _loading || !_hasMore) return;
    final req = _reqId;
    setState(() => _loadingMore = true);
    try {
      final f = _activeFilter;
      final res = await _repo.items(widget.detail.id, status: f.status, differenceCondition: f.diff, search: _search, page: _page + 1);
      if (!mounted || req != _reqId) return;
      setState(() {
        _items = [..._items, ...res.rows];
        _page = res.currentPage;
        _lastPage = res.lastPage;
      });
    } catch (_) {/* retry on next scroll */}
    if (mounted && req == _reqId) setState(() => _loadingMore = false);
  }

  // ---- edits ----

  void _mutate(int id, {double? physical, String? status}) {
    final idx = _items.indexWhere((e) => e.id == id);
    if (idx == -1) return;
    setState(() {
      _items[idx] = _items[idx].copyWith(physical: physical, status: status);
      _dirty.add(id);
    });
  }

  void _adjust(StockCheckItem it, double delta) {
    final next = (it.physical + delta).clamp(0, double.infinity).toDouble();
    if (next == it.physical) return;
    HapticFeedback.selectionClick();
    _mutate(it.id, physical: next);
  }

  void _toggleDone(StockCheckItem it) {
    _mutate(it.id, status: it.isCompleted ? 'pending' : 'completed');
  }

  Future<void> _typeQty(StockCheckItem it) async {
    final ctl = TextEditingController(text: qtyLabel(it.physical));
    final p = context.astra;
    final v = await showDialog<double>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: p.cardSolid,
        title: Text('Counted qty', style: serif(size: 18, color: p.ink)),
        content: TextField(
          controller: ctl,
          autofocus: true,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          style: ui(size: 18, weight: FontWeight.w800, color: p.ink),
          decoration: InputDecoration(hintText: it.productName),
          onSubmitted: (s) => Navigator.pop(ctx, double.tryParse(s.trim())),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: Text('Cancel', style: ui(size: 13, weight: FontWeight.w700, color: p.textSecondary))),
          TextButton(onPressed: () => Navigator.pop(ctx, double.tryParse(ctl.text.trim())), child: Text('Set', style: ui(size: 13, weight: FontWeight.w800, color: p.primary))),
        ],
      ),
    );
    if (v != null && v >= 0) _mutate(it.id, physical: v);
  }

  // ---- persistence ----

  /// Persist any pending edits. Returns true when the working copy is in sync
  /// with the server (nothing to save, or the save succeeded).
  Future<bool> _ensureSaved({bool feedback = false}) async {
    if (_dirty.isEmpty) return true;
    final payload = _items
        .where((e) => _dirty.contains(e.id))
        .map((e) => {'id': e.id, 'physical_quantity': e.physical, 'status': e.status})
        .toList();
    setState(() => _saving = true);
    var ok = false;
    try {
      await _repo.saveCounts(widget.detail.id, payload);
      _dirty.clear();
      ok = true;
      if (feedback && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Saved ${payload.length} item${payload.length == 1 ? '' : 's'}')));
      }
    } catch (_) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not save counts.')));
    }
    if (mounted) setState(() => _saving = false);
    return ok;
  }

  Future<void> _save() async {
    if (_dirty.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nothing to save yet.')));
      return;
    }
    final toReconcile = _items.where((e) => _dirty.contains(e.id) && e.isCompleted).length;
    final confirmed = await _confirmSave(_dirty.length, toReconcile);
    if (confirmed != true) return;
    if (await _ensureSaved(feedback: true)) {
      await _reload();
      await _refreshStats();
    }
  }

  Future<bool?> _confirmSave(int count, int reconcile) {
    final p = context.astra;
    return showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        decoration: BoxDecoration(color: p.cardSolid, borderRadius: const BorderRadius.vertical(top: Radius.circular(24))),
        padding: const EdgeInsets.fromLTRB(18, 12, 18, 26),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(child: Container(width: 40, height: 4, margin: const EdgeInsets.only(bottom: 16), decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(4)))),
            Text('Save counts', style: serif(size: 19, color: p.ink)),
            const SizedBox(height: 8),
            Text(
              reconcile > 0
                  ? '$count item${count == 1 ? '' : 's'} will be saved. $reconcile marked completed will reconcile real inventory to the counted quantity.'
                  : '$count item${count == 1 ? '' : 's'} will be saved as counts. None are marked completed, so inventory is not changed yet.',
              style: ui(size: 12.5, weight: FontWeight.w600, color: p.textSecondary, height: 1.5),
            ),
            const SizedBox(height: 18),
            Row(
              children: [
                Expanded(
                  child: GestureDetector(
                    onTap: () => Navigator.pop(ctx, false),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      alignment: Alignment.center,
                      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(14)),
                      child: Text('Cancel', style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  flex: 2,
                  child: GestureDetector(
                    onTap: () => Navigator.pop(ctx, true),
                    child: AstraButton(label: 'Save', icon: Icons.check_rounded, onTap: () => Navigator.pop(ctx, true)),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openScan() async {
    // Persist local edits first so scanning (which mutates server-side) can't
    // clobber them on the reload that follows.
    if (!await _ensureSaved()) return;
    if (!mounted) return;
    await Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => StockCheckScanScreen(detail: widget.detail),
    ));
    if (!mounted) return;
    await _reload();
    await _refreshStats();
  }

  void _onSearch(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () async {
      if (!mounted) return;
      if (!await _ensureSaved()) return;
      if (!mounted) return;
      setState(() => _search = v.trim());
      _reload();
    });
  }

  Future<void> _setFilter(String key) async {
    if (_filter == key) return;
    if (!await _ensureSaved()) return;
    if (!mounted) return;
    setState(() => _filter = key);
    _reload();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            _hero(),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 720,
                child: Stack(
                  children: [
                    _list(),
                    Positioned(left: 0, right: 0, bottom: 0, child: _dock()),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ---- hero with progress ----

  Widget _hero() {
    final p = context.astra;
    final s = _stats;
    final pct = (s.progress * 100).round();
    final net = s.netDifference;
    final netColor = net < 0 ? const Color(0xFFFFC2CC) : (net > 0 ? p.accent : Colors.white);
    return Container(
      decoration: BoxDecoration(gradient: p.heroGradient, borderRadius: const BorderRadius.vertical(bottom: Radius.circular(30))),
      child: Stack(
        children: [
          Positioned(right: -40, top: -46, child: Container(width: 180, height: 180, decoration: BoxDecoration(shape: BoxShape.circle, gradient: RadialGradient(colors: [p.accent.withValues(alpha: 0.20), Colors.transparent])))),
          SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 6, 16, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      HeaderIconButton(icon: Icons.arrow_back_ios_new, onTap: () => context.pop()),
                      const SizedBox(width: 11),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('STOCK CHECK · #${s.id}', style: ui(size: 9.5, weight: FontWeight.w800, color: p.accent, letterSpacing: 1.6)),
                            const SizedBox(height: 2),
                            Text(s.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 21, color: Colors.white)),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      RichText(
                        text: TextSpan(children: [
                          TextSpan(text: '${s.itemsCounted}', style: serif(size: 25, color: Colors.white)),
                          TextSpan(text: ' / ${s.itemsTotal}', style: serif(size: 14, color: Colors.white.withValues(alpha: 0.7))),
                        ]),
                      ),
                      Text('$pct% counted', style: ui(size: 12, weight: FontWeight.w800, color: p.accent)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: LinearProgressIndicator(
                      value: s.progress.clamp(0, 1),
                      minHeight: 8,
                      backgroundColor: Colors.white.withValues(alpha: 0.18),
                      valueColor: AlwaysStoppedAnimation(p.accent),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      _heroStat('Counted', '${s.itemsCounted}', Colors.white),
                      const SizedBox(width: 9),
                      _heroStat('Variances', '${s.varianceCount}', p.accent),
                      const SizedBox(width: 9),
                      _heroStat('Net diff', net == 0 ? '0' : '${net > 0 ? '+' : ''}${qtyLabel(net)}', netColor),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _heroStat(String label, String value, Color valueColor) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 9),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label.toUpperCase(), style: ui(size: 8.5, weight: FontWeight.w800, color: Colors.white.withValues(alpha: 0.66), letterSpacing: 0.6)),
            const SizedBox(height: 3),
            Text(value, style: serif(size: 17, color: valueColor)),
          ],
        ),
      ),
    );
  }

  // ---- list ----

  Widget _list() {
    return RefreshIndicator(
      onRefresh: () async {
        await _ensureSaved();
        await _reload();
        await _refreshStats();
      },
      child: ListView(
        controller: _scrollCtl,
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 150),
        children: [
          _searchBox(),
          const SizedBox(height: 12),
          _filterChips(),
          const SizedBox(height: 12),
          if (_loading && _items.isEmpty)
            const Padding(padding: EdgeInsets.symmetric(vertical: 50), child: Center(child: CircularProgressIndicator()))
          else if (_error != null && _items.isEmpty)
            EmptyState(icon: Icons.wifi_off, title: 'Unavailable', message: _error, action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _reload))
          else if (_items.isEmpty)
            EmptyState(icon: Icons.inventory_2_outlined, title: 'No items', message: 'No items match this filter or search.')
          else ...[
            for (final it in _items) Padding(padding: const EdgeInsets.only(bottom: 10), child: _itemCard(it)),
            if (_loadingMore)
              const Padding(padding: EdgeInsets.symmetric(vertical: 16), child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4)))),
          ],
        ],
      ),
    );
  }

  Widget _searchBox() {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
      decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(13), boxShadow: context.astraTheme.softShadow, border: Border.all(color: p.cardBorder)),
      child: Row(
        children: [
          Icon(Icons.search, size: 16, color: p.textMuted),
          const SizedBox(width: 8),
          Expanded(
            child: TextField(
              controller: _searchCtl,
              onChanged: _onSearch,
              style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink),
              decoration: InputDecoration(isDense: true, border: InputBorder.none, hintText: 'Product, code or barcode…', hintStyle: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted)),
            ),
          ),
          if (_searchCtl.text.isNotEmpty)
            GestureDetector(
              onTap: () {
                _searchCtl.clear();
                _onSearch('');
              },
              child: Icon(Icons.close, size: 16, color: p.textMuted),
            ),
        ],
      ),
    );
  }

  Widget _filterChips() {
    final s = _stats;
    int? badge(String key) => switch (key) {
          'all' => s.itemsTotal,
          'pending' => s.itemsTotal - s.itemsCounted,
          'counted' => s.itemsCounted,
          'variance' => s.varianceCount,
          _ => null,
        };
    return SizedBox(
      height: 34,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: _filters.length,
        separatorBuilder: (_, __) => const SizedBox(width: 7),
        itemBuilder: (_, i) {
          final f = _filters[i];
          return _chip(f.label, badge(f.key), _filter == f.key, () => _setFilter(f.key));
        },
      ),
    );
  }

  Widget _chip(String label, int? badge, bool active, VoidCallback onTap) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 13),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          gradient: active ? p.heroGradient : null,
          color: active ? null : p.card,
          borderRadius: BorderRadius.circular(20),
          boxShadow: active ? null : context.astraTheme.softShadow,
          border: Border.all(color: active ? Colors.transparent : p.cardBorder),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(label, style: ui(size: 11.5, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary)),
            if (badge != null) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                decoration: BoxDecoration(color: active ? Colors.white.withValues(alpha: 0.22) : p.tint, borderRadius: BorderRadius.circular(10)),
                child: Text('$badge', style: ui(size: 9.5, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary)),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _itemCard(StockCheckItem it) {
    final p = context.astra;
    final done = it.isCompleted;
    final diff = it.difference;
    final (dbg, dfg, dlabel) = diff < 0
        ? (p.dangerTint, AstraPalette.danger, 'Short')
        : (diff > 0 ? (p.warnTint, p.warnText, 'Over') : (p.successTint, AstraPalette.success, 'Match'));
    return AstraCard(
      radius: 18,
      padding: const EdgeInsets.all(13),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              IconChip(icon: Icons.inventory_2_outlined, size: 44, radius: 12),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(it.productName.isEmpty ? '—' : it.productName, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 14.5, color: p.ink)),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        if (it.productCode.isNotEmpty) ...[
                          Icon(Icons.qr_code_2, size: 11, color: p.textMuted),
                          const SizedBox(width: 3),
                          Flexible(child: Text(it.productCode, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 9.5, weight: FontWeight.w700, color: p.textMuted))),
                        ],
                        if (it.barcode.isNotEmpty) ...[
                          const SizedBox(width: 8),
                          Icon(Icons.barcode_reader, size: 11, color: p.textMuted),
                          const SizedBox(width: 3),
                          Flexible(child: Text(it.barcode, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 9.5, weight: FontWeight.w700, color: p.textMuted))),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              GestureDetector(
                onTap: () => _toggleDone(it),
                child: StatusPill(
                  label: (done ? 'Done' : 'Pending').toUpperCase(),
                  bg: done ? p.successTint : p.tint,
                  fg: done ? AstraPalette.success : p.textSecondary,
                  icon: done ? Icons.check_circle : Icons.radio_button_unchecked,
                ),
              ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 11),
            child: Divider(height: 1, color: p.hairline),
          ),
          Row(
            children: [
              Column(
                children: [
                  Text('SYSTEM', style: ui(size: 8, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
                  const SizedBox(height: 3),
                  Text(qtyLabel(it.recorded), style: serif(size: 18, color: p.textSecondary)),
                ],
              ),
              const Spacer(),
              _stepper(it),
              const Spacer(),
              Container(
                width: 56,
                padding: const EdgeInsets.symmetric(vertical: 6),
                decoration: BoxDecoration(color: dbg, borderRadius: BorderRadius.circular(11)),
                child: Column(
                  children: [
                    Text(diff == 0 ? '0' : '${diff > 0 ? '+' : ''}${qtyLabel(diff)}', style: serif(size: 16, color: dfg)),
                    Text(dlabel.toUpperCase(), style: ui(size: 7.5, weight: FontWeight.w800, color: dfg, letterSpacing: 0.4)),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _stepper(StockCheckItem it) {
    final p = context.astra;
    Widget btn(IconData i, Color bg, Color fg, VoidCallback on) => GestureDetector(
          onTap: on,
          child: Container(width: 32, height: 32, decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(9)), child: Icon(i, size: 14, color: fg)),
        );
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        btn(Icons.remove, p.tint, p.ink, () => _adjust(it, -1)),
        const SizedBox(width: 9),
        GestureDetector(
          onTap: () => _typeQty(it),
          child: Container(
            constraints: const BoxConstraints(minWidth: 48),
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
            decoration: BoxDecoration(color: p.cardSolid, borderRadius: BorderRadius.circular(10), border: Border.all(color: p.primary, width: 1.4)),
            child: Column(
              children: [
                Text(qtyLabel(it.physical), style: serif(size: 18, color: p.ink)),
                Text('COUNTED', style: ui(size: 6.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.4)),
              ],
            ),
          ),
        ),
        const SizedBox(width: 9),
        btn(Icons.add, p.primary, Colors.white, () => _adjust(it, 1)),
      ],
    );
  }

  // ---- dock ----

  Widget _dock() {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 22, 14, 16),
      decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [p.canvas.withValues(alpha: 0), p.canvas])),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (_dirty.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(bottom: 9),
              child: Text('${_dirty.length} unsaved change${_dirty.length == 1 ? '' : 's'}', style: ui(size: 11, weight: FontWeight.w800, color: p.warnText)),
            ),
          Row(
            children: [
              Expanded(
                child: GestureDetector(
                  onTap: _openScan,
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 15),
                    alignment: Alignment.center,
                    decoration: BoxDecoration(gradient: p.accentGradient, borderRadius: BorderRadius.circular(15), boxShadow: context.astraTheme.floatShadow(p.accent)),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.qr_code_scanner, size: 17, color: p.primaryDark),
                        const SizedBox(width: 8),
                        Text('Scan', style: ui(size: 14, weight: FontWeight.w800, color: p.primaryDark)),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                flex: 2,
                child: AstraButton(label: 'Save count', icon: Icons.check_rounded, busy: _saving, onTap: _save),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

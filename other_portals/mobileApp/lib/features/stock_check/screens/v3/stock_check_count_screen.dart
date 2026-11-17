import 'package:invo/features/stock_check/logic/stock_check_cubit/stock_check_cubit.dart';
import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/camera_permission.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/continuous_scanner_screen.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';
import 'package:invo/shared/widgets/astra_snack.dart';

import '../../domain/models/stock_check_models.dart';
import 'stock_check_status_sheet.dart';

part 'stock_check_count_views.dart';

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
  // Variance = a CONFIRMED discrepancy: a counted (completed) item whose physical
  // differs from system. Must match the `variance_count` KPI, which is also
  // scoped to completed — otherwise the filter would surface every uncounted
  // item (physical 0 → difference = −system) and dwarf the badge.
  _Filter('variance', 'Variance', status: 'completed', diff: 'variance'),
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

  /// Owns the repository and its error handling (§10).
  final _stock = StockCheckCubit();

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
    unawaited(_stock.close());
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
    final d = await _stock.show(widget.detail.id);
    if (d != null && mounted) setState(() => _stats = d);
    // A null result keeps the last stats — the reason is on the cubit state.
  }

  Future<void> _reload() async {
    final req = ++_reqId;
    setState(() {
      _loading = true;
      _error = null;
    });
    final f = _activeFilter;
    final res = await _stock.items(widget.detail.id,
        status: f.status, differenceCondition: f.diff, search: _search, page: 1);
    if (!mounted || req != _reqId) return;
    if (res != null) {
      setState(() {
        _items = res.rows;
        _dirty.clear();
        _page = res.currentPage;
        _lastPage = res.lastPage;
      });
    } else {
      setState(() => _error = _stock.state.errorMessage ?? 'Could not load items.');
    }
    setState(() => _loading = false);
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _loading || !_hasMore) return;
    final req = _reqId;
    setState(() => _loadingMore = true);
    final f = _activeFilter;
    final res = await _stock.items(widget.detail.id,
        status: f.status, differenceCondition: f.diff, search: _search, page: _page + 1);
    if (!mounted) return;
    if (res != null && req == _reqId) {
      setState(() {
        _items = [..._items, ...res.rows];
        _page = res.currentPage;
        _lastPage = res.lastPage;
      });
    }
    // Always clear the flag, even for a discarded page, or the guard on entry
    // blocks every later page for the life of the screen.
    setState(() => _loadingMore = false);
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
    ctl.dispose(); // owned by this method, not by the State
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
      await _stock.saveCounts(widget.detail.id, payload);
      _dirty.clear();
      ok = true;
      if (feedback && mounted) {
        AstraSnack.success(context, 'Saved ${payload.length} item${payload.length == 1 ? '' : 's'}');
      }
    } catch (_) {
      if (mounted) AstraSnack.error(context, 'Could not save counts.');
    }
    if (mounted) setState(() => _saving = false);
    return ok;
  }

  Future<void> _save() async {
    if (_dirty.isEmpty) {
      AstraSnack.show(context, 'Nothing to save yet.');
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

  /// Move the count's own status (pending / completed / cancelled). Applies on
  /// tap in the sheet; the header stats are re-read so the pill reflects what the
  /// server stored.
  Future<void> _changeStatus() async {
    final next = await pickStockCheckStatus(context, current: _stats.status);
    if (next == null || !mounted) return;
    final ok = await _stock.updateStatus(widget.detail.id, next);
    if (!mounted) return;
    if (ok) {
      await _refreshStats();
      if (!mounted) return;
      AstraSnack.success(context, 'Status changed to ${StockCheckStatus.label(next)}');
    } else {
      AstraSnack.error(context, _stock.state.errorMessage ?? 'Could not change the status.');
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

  /// Opens the shared continuous scanner: the camera stays open and every
  /// barcode +1's its item live (via [_scanOne]). On return, reload so the list
  /// and stats reflect everything counted in the session.
  Future<void> _openScan() async {
    // Gate on camera access first — re-prompts after an accidental deny, or
    // routes the user to Settings when the OS won't ask again.
    if (!await ensureCameraPermission(context)) return;
    if (!mounted) return;
    // Persist local edits first so scanning (which mutates server-side) can't
    // clobber them.
    if (!await _ensureSaved()) return;
    if (!mounted) return;

    final counted = await ContinuousScannerScreen.open(
      context,
      title: 'STOCK CHECK',
      tallyLabel: 'COUNTED THIS SESSION',
      onScan: _scanOne,
    );
    if (!mounted || counted == 0) return;
    await _reload();
    await _refreshStats();
  }

  /// Count one scanned barcode server-side (+1) and describe the result for the
  /// scanner's feed. Over-counts warn; the returned `undo` reverts the +1.
  Future<ScanFeedback> _scanOne(String code) async {
    final res = await _stock.scan(widget.detail.id, code);
    if (res == null) {
      return ScanFeedback.error(
          code, _stock.state.errorMessage ?? 'Scan failed — check your connection.');
    }
    final name = res.productName.isEmpty ? code : res.productName;
    // Revert target: physical before the +1, keeping the post-scan status so
    // nothing reconciles from an undo.
    final undoTarget = (res.physical - 1).clamp(0, double.infinity).toDouble();
    return ScanFeedback(
      title: name,
      detail: 'now ${qtyLabel(res.physical)} · system ${qtyLabel(res.recorded)}${res.isOver ? '  ·  OVER' : ''}',
      status: res.isOver ? ScanStatus.warn : ScanStatus.ok,
      undo: () async {
        final ok = await _stock.saveCounts(widget.detail.id, [
          {'id': res.id, 'physical_quantity': undoTarget, 'status': res.status},
        ]);
        if (!ok) return null;
        return ScanFeedback(title: name, detail: 'Undone · −1 → ${qtyLabel(undoTarget)}');
      },
    );
  }

  void _onSearch(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () async {
      if (!mounted) return;
      if (!await _ensureSaved()) return;
      if (!mounted) return;
      setState(() => _search = v.trim());
      unawaited(_reload());
    });
  }

  Future<void> _setFilter(String key) async {
    if (_filter == key) return;
    if (!await _ensureSaved()) return;
    if (!mounted) return;
    setState(() => _filter = key);
    unawaited(_reload());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            // Tablet swaps the gradient hero for a page-head toolbar — the
            // counted/variance recap it carries is already docked in the side
            // panel, so on a tablet the hero was only repeating itself.
            if (context.isTablet) SafeArea(bottom: false, child: _tabletPageHead()) else _hero(),
            Expanded(
              child: MaxWidthBox(
                maxWidth: context.isTablet ? 1120 : 720,
                // Tablet: the item grid sits on the left with a persistent
                // progress + Scan/Save panel docked on the right. Phones keep the
                // overlay dock floating above the list.
                child: context.isTablet
                    ? LayoutBuilder(
                        builder: (ctx, c) => Row(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Expanded(child: _list()),
                            _sidePanel(TabletMetrics.forWidth(c.maxWidth).sidePanel),
                          ],
                        ),
                      )
                    : Stack(
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

  // ---- list ----

  /// Slivers, not `ListView(children:)` — `_items` accumulates a page per scroll
  /// and a count run can hold the whole branch. Eager children rebuilt every card
  /// on every scan and every stepper tap; lazy ones build only what's on screen.
  Widget _list() {
    const hPad = EdgeInsets.symmetric(horizontal: 14);
    final empty = _items.isEmpty;

    return RefreshIndicator(
      onRefresh: () async {
        await _ensureSaved();
        await _reload();
        await _refreshStats();
      },
      child: CustomScrollView(
        controller: _scrollCtl,
        slivers: [
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
            sliver: SliverToBoxAdapter(
              child: Column(
                children: [
                  _searchBox(),
                  const SizedBox(height: 12),
                  _filterChips(),
                  const SizedBox(height: 12),
                ],
              ),
            ),
          ),
          if (_loading && empty)
            const SliverToBoxAdapter(
              child: Padding(padding: EdgeInsets.symmetric(vertical: 50), child: Center(child: CircularProgressIndicator())),
            )
          else if (_error != null && empty)
            SliverPadding(
              padding: hPad,
              sliver: SliverToBoxAdapter(
                child: EmptyState(icon: Icons.wifi_off, title: 'Unavailable', message: _error, action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _reload)),
              ),
            )
          else if (empty)
            SliverPadding(
              padding: hPad,
              sliver: const SliverToBoxAdapter(
                child: EmptyState(icon: Icons.inventory_2_outlined, title: 'No items', message: 'No items match this filter or search.'),
              ),
            )
          else
            SliverPadding(padding: hPad, sliver: context.isTablet ? _tabletRows() : _phoneRows()),
          if (_loadingMore)
            const SliverToBoxAdapter(
              child: Padding(padding: EdgeInsets.symmetric(vertical: 16), child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4)))),
            ),
          // On tablet the Scan/Save actions live in the side panel, so the list
          // doesn't need the tall bottom gap the phone's overlay dock requires.
          SliverToBoxAdapter(child: SizedBox(height: context.isTablet ? 24 : 150)),
        ],
      ),
    );
  }

  Widget _phoneRows() => SliverList.separated(
        itemCount: _items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (_, i) => _itemCard(_items[i]),
      );

  /// Tablet: count tiles auto-fill by width (the preview's
  /// `repeat(auto-fill, minmax(…))`), so an 11" portrait gets two columns and a
  /// 13" landscape three or four — never a stretched row.
  ///
  /// Chunked into rows rather than a SliverGrid: the cards are content-sized and
  /// a grid would force one aspect ratio on all of them. A Row per line keeps the
  /// old Wrap's look (run height = tallest tile) and still builds lazily.
  Widget _tabletRows() => SliverLayoutBuilder(
        builder: (_, constraints) {
          const gap = 12.0;
          const minTile = 300.0;
          final width = constraints.crossAxisExtent;
          final cols = ((width + gap) / (minTile + gap)).floor().clamp(1, 4);
          final colW = (width - gap * (cols - 1)) / cols;

          return SliverList.separated(
            itemCount: (_items.length / cols).ceil(),
            separatorBuilder: (_, __) => const SizedBox(height: gap),
            itemBuilder: (_, row) {
              final start = row * cols;
              final end = (start + cols) > _items.length ? _items.length : start + cols;
              return Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  for (var i = start; i < end; i++) ...[
                    if (i > start) const SizedBox(width: gap),
                    SizedBox(width: colW, child: _itemCard(_items[i])),
                  ],
                ],
              );
            },
          );
        },
      );

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
              Flexible(
                child: Column(
                  children: [
                    Text('SYSTEM', style: ui(size: 8, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
                    const SizedBox(height: 3),
                    FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Text(qtyLabel(it.recorded), style: serif(size: 18, color: p.textSecondary)),
                    ),
                  ],
                ),
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
                    FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Text(diff == 0 ? '0' : '${diff > 0 ? '+' : ''}${qtyLabel(diff)}', style: serif(size: 16, color: dfg)),
                    ),
                    Text(dlabel.toUpperCase(), maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 7.5, weight: FontWeight.w800, color: dfg, letterSpacing: 0.4)),
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
            constraints: const BoxConstraints(minWidth: 48, maxWidth: 84),
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
            decoration: BoxDecoration(color: p.cardSolid, borderRadius: BorderRadius.circular(10), border: Border.all(color: p.primary, width: 1.4)),
            child: Column(
              children: [
                FittedBox(
                  fit: BoxFit.scaleDown,
                  child: Text(qtyLabel(it.physical), style: serif(size: 18, color: p.ink)),
                ),
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

  // ---- tablet side panel (progress recap + persistent Scan / Save) ----

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

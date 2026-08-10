import 'package:invo/features/stock_check/logic/stock_check_cubit/stock_check_cubit.dart';
import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

import '../../domain/models/stock_check_models.dart';
import 'stock_check_status_sheet.dart';

/// Lists the branch's stock checks with per-check progress. The active branch is
/// app-wide (BranchCubit) — the header shows it and the list reloads on switch.
class StockCheckListScreen extends StatefulWidget {
  const StockCheckListScreen({super.key});
  @override
  State<StockCheckListScreen> createState() => _StockCheckListScreenState();
}

class _StockCheckListScreenState extends State<StockCheckListScreen> {
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  List<StockCheckSummary> _rows = [];
  int _total = 0;
  int _page = 1;
  int _lastPage = 1;
  int _reqId = 0;

  String? _status; // null = all
  String _search = '';
  Timer? _debounce;
  final _searchCtl = TextEditingController();
  final _scrollCtl = ScrollController();
  StreamSubscription<int>? _branchSub;

  bool get _hasMore => _page < _lastPage;

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
    _branchSub = context.read<BranchCubit>().onBranchChanged.listen((_) {
      if (mounted) _load();
    });
  }

  @override
  void dispose() {
    unawaited(_stock.close());
    _debounce?.cancel();
    _searchCtl.dispose();
    _scrollCtl.dispose();
    _branchSub?.cancel();
    super.dispose();
  }

  void _onScroll() {
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 400) _loadMore();
  }

  /// Owns the repository and its error handling (§10).
  final _stock = StockCheckCubit();

  Future<void> _load() async {
    final req = ++_reqId;
    setState(() {
      _loading = true;
      _error = null;
    });
    final res = await _stock.list(status: _status, search: _search, page: 1);
    if (!mounted || req != _reqId) return;
    if (res != null) {
      setState(() {
        _rows = res.rows;
        _total = res.total;
        _page = res.currentPage;
        _lastPage = res.lastPage;
      });
    } else {
      setState(() => _error = _stock.state.errorMessage ?? 'Could not load stock checks.');
    }
    setState(() => _loading = false);
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _loading || !_hasMore) return;
    final req = _reqId;
    setState(() => _loadingMore = true);
    final res = await _stock.list(status: _status, search: _search, page: _page + 1);
    if (!mounted) return;
    if (res != null && req == _reqId) {
      setState(() {
        _rows = [..._rows, ...res.rows];
        _page = res.currentPage;
        _lastPage = res.lastPage;
        _total = res.total;
      });
    }
    // Always clear the flag, even for a discarded page, or the guard on entry
    // blocks every later page for the life of the screen.
    setState(() => _loadingMore = false);
  }

  void _setStatus(String? s) {
    if (_status == s) return;
    setState(() => _status = s);
    _load();
  }

  void _onSearch(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () {
      if (!mounted) return;
      setState(() => _search = v.trim());
      _load();
    });
  }

  Future<void> _openCreate() async {
    final detail = await context.push<StockCheckDetail>(Routes.stockCheckNew);
    if (!mounted || detail == null) return;
    unawaited(_load());
    unawaited(_pushCount(detail));
  }

  void _openCount(StockCheckSummary s) {
    _pushCount(StockCheckDetail(
      id: s.id,
      title: s.title,
      date: s.date,
      status: s.status,
      branchId: s.branchId,
      branchName: s.branchName,
    ));
  }

  /// Move a count's own status straight from its card — the same click-and-go
  /// sheet the counting screen uses.
  Future<void> _changeStatus(StockCheckSummary r) async {
    final next = await pickStockCheckStatus(context, current: r.status);
    if (next == null || !mounted) return;
    final ok = await _stock.updateStatus(r.id, next);
    if (!mounted) return;
    if (ok) {
      unawaited(_load());
      ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Status changed to ${StockCheckStatus.label(next)}')));
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(_stock.state.errorMessage ?? 'Could not change the status.')));
    }
  }

  Future<void> _pushCount(StockCheckDetail d) async {
    await context.push(Routes.stockCheckCount, extra: d);
    if (mounted) unawaited(_load()); // reflect progress/status changes on return
  }

  @override
  Widget build(BuildContext context) {
    final branch = context.watch<BranchCubit>().selected?.name ?? 'All branches';
    final sub = _loading && _rows.isEmpty ? 'Loading…' : '$branch · $_total count${_total == 1 ? '' : 's'}';
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        // Tablet swaps the header band for a page-head toolbar, matching the
        // other tablet screens.
        child: context.isTablet
            ? SafeArea(
                bottom: false,
                child: Column(
                  children: [
                    TabletPageHead(
                      // Reachable two ways on a tablet: pushed (dashboard tile,
                      // drawer) or as a top-level rail destination. Only offer a
                      // back arrow when there is actually something to pop —
                      // otherwise the rail's Stock Check would show a dead
                      // control the other rail destinations don't have.
                      leading: context.canPop()
                          ? TabletIconButton(
                              icon: Icons.chevron_left, tooltip: 'Back', onTap: () => context.pop())
                          : null,
                      title: 'Stock Check',
                      subtitle: sub,
                      actions: [
                        TabletActionButton(
                            label: 'New count', icon: Icons.add, primary: true, onTap: _openCreate),
                      ],
                    ),
                    Expanded(child: _body()),
                  ],
                ),
              )
            : Column(
                children: [
                  EmeraldHeader(
                    title: 'Stock Check',
                    subtitle: sub,
                    leading: HeaderIconButton(icon: Icons.arrow_back_ios_new, onTap: () => context.pop()),
                    trailing: HeaderIconButton(icon: Icons.add, gold: true, onTap: _openCreate),
                  ),
                  Expanded(child: _body()),
                ],
              ),
      ),
    );
  }

  Widget _body() {
    // Slivers, not ListView(children:) — the rows accumulate a page per scroll.
    return RefreshIndicator(
      onRefresh: _load,
      child: MaxWidthBox(
        maxWidth: context.isTablet ? 1120 : 720,
        child: CustomScrollView(
          controller: _scrollCtl,
          slivers: [
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 0),
              sliver: SliverList.list(children: [
                if (context.isTablet) _tabletControls() else _controls(),
                const SizedBox(height: 4),
              ]),
            ),
            SliverPadding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              sliver: _rowsSliver(),
            ),
            if (_loadingMore)
              const SliverToBoxAdapter(
                child: Padding(
                  padding: EdgeInsets.symmetric(vertical: 16),
                  child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4))),
                ),
              ),
            SliverToBoxAdapter(child: SizedBox(height: context.isTablet ? 32 : 120)),
          ],
        ),
      ),
    );
  }

  Widget _rowsSliver() {
    if (_loading && _rows.isEmpty) {
      return const SliverToBoxAdapter(
        child: Padding(padding: EdgeInsets.symmetric(vertical: 60), child: Center(child: CircularProgressIndicator())),
      );
    }
    if (_error != null && _rows.isEmpty) {
      return SliverToBoxAdapter(
        child: EmptyState(
          icon: Icons.wifi_off,
          title: 'Unavailable',
          message: _error,
          action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load),
        ),
      );
    }
    if (_rows.isEmpty) {
      return SliverToBoxAdapter(
        child: EmptyState(
          icon: Icons.fact_check_outlined,
          title: 'No stock checks',
          message: 'Start a new count to snapshot this branch\u2019s stock.',
          action: AstraButton(label: 'New count', icon: Icons.add, expand: false, onTap: _openCreate),
        ),
      );
    }
    if (!context.isTablet) {
      return SliverList.separated(
        itemCount: _rows.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (_, i) => _card(_rows[i]),
      );
    }
    // Tablet: count cards auto-fill by width instead of running as one long
    // thin column. Built as lazy rows-of-N rather than a Wrap — a Wrap has to
    // lay out every child, and these accumulate a page per scroll. Each row
    // still sizes to its tallest card, which is what a Wrap run did.
    return SliverLayoutBuilder(
      builder: (ctx, constraints) {
        const gap = 12.0;
        const minTile = 420.0;
        final width = constraints.crossAxisExtent;
        final cols = ((width + gap) / (minTile + gap)).floor().clamp(1, 3);
        final colW = (width - gap * (cols - 1)) / cols;
        final rowCount = (_rows.length + cols - 1) ~/ cols;
        return SliverList.separated(
          itemCount: rowCount,
          separatorBuilder: (_, __) => const SizedBox(height: gap),
          itemBuilder: (_, r) {
            final start = r * cols;
            return Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                for (var i = 0; i < cols; i++) ...[
                  if (i > 0) const SizedBox(width: gap),
                  SizedBox(
                    width: colW,
                    child: start + i < _rows.length ? _card(_rows[start + i]) : null,
                  ),
                ],
              ],
            );
          },
        );
      },
    );
  }

  /// Tablet: the phone's stacked control card gives the status segment an
  /// [Expanded] third each and the search the full width — at 1120pt that's
  /// three slabs over a 1100pt-wide text field. Chips plus a fixed search box
  /// keep both at a sane size.
  Widget _tabletControls() {
    final statuses = <(String, String?)>[
      ('All', null),
      for (final s in StockCheckStatus.all) (StockCheckStatus.label(s), s),
    ];
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        children: [
          for (final (label, s) in statuses) ...[
            TabletFilterChip(label: label, active: _status == s, onTap: () => _setStatus(s)),
            const SizedBox(width: 7),
          ],
          const Spacer(),
          SizedBox(width: 320, child: _searchBox()),
        ],
      ),
    );
  }

  Widget _controls() {
    return AstraCard(
      radius: 20,
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _label('STATUS'),
          _statusSeg(),
          const SizedBox(height: 13),
          _label('SEARCH'),
          _searchBox(),
        ],
      ),
    );
  }

  Widget _searchBox() {
    final p = context.astra;
    return Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12)),
            child: Row(
              children: [
                Icon(Icons.search, size: 16, color: p.primary),
                const SizedBox(width: 8),
                Expanded(
                  child: TextField(
                    controller: _searchCtl,
                    onChanged: _onSearch,
                    style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink),
                    decoration: InputDecoration(
                      isDense: true,
                      border: InputBorder.none,
                      hintText: 'Title or description…',
                      hintStyle: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted),
                    ),
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

  Widget _label(String t) => Padding(
        padding: const EdgeInsets.only(left: 2, bottom: 6),
        child: Text(t, style: ui(size: 8.5, weight: FontWeight.w800, color: context.astra.textMuted, letterSpacing: 1.1)),
      );

  Widget _statusSeg() {
    final p = context.astra;
    Widget seg(String label, String? s) {
      final active = _status == s;
      return Expanded(
        child: GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: () => _setStatus(s),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 8),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: active ? p.card : Colors.transparent,
              borderRadius: BorderRadius.circular(10),
              boxShadow: active ? context.astraTheme.softShadow : null,
            ),
            // Four segments on a narrow phone leave ~65pt each — scale the
            // longest labels down rather than letting them overflow.
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
        for (final s in StockCheckStatus.all) seg(StockCheckStatus.label(s), s),
      ]),
    );
  }

  Widget _card(StockCheckSummary r) {
    final p = context.astra;
    final done = r.isCompleted;
    final net = r.netDifference;
    final netColor = net < 0 ? AstraPalette.danger : (net > 0 ? p.warnText : AstraPalette.success);
    final netLabel = net == 0 ? 'balanced' : '${net > 0 ? '+' : ''}${qtyLabel(net)} net';
    final pct = (r.progress * 100).round();

    return AstraCard(
      radius: 18,
      padding: const EdgeInsets.all(14),
      onTap: () => _openCount(r),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              IconChip(icon: done ? Icons.check_circle_outline : Icons.assignment_outlined, size: 40, radius: 12),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(r.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 15.5, color: p.ink)),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(Icons.storefront_outlined, size: 11, color: p.textMuted),
                        const SizedBox(width: 4),
                        Flexible(
                          child: Text(r.branchName.isEmpty ? '—' : r.branchName,
                              maxLines: 1, overflow: TextOverflow.ellipsis,
                              style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
                        ),
                        const SizedBox(width: 8),
                        Icon(Icons.event_outlined, size: 11, color: p.textMuted),
                        const SizedBox(width: 4),
                        Text(Dates.human(r.date), style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              // Tap the pill to move the count's own status; tapping anywhere
              // else on the card still opens it for counting.
              StockCheckStatusPill(status: r.status, onTap: () => _changeStatus(r)),
            ],
          ),
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(5),
            child: LinearProgressIndicator(
              value: r.progress.clamp(0, 1),
              minHeight: 7,
              backgroundColor: p.tint,
              valueColor: AlwaysStoppedAnimation(done ? AstraPalette.success : p.primary),
            ),
          ),
          const SizedBox(height: 9),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Flexible(
                child: Text('${r.itemsCounted} / ${r.itemsTotal} counted · $pct%',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10.5, weight: FontWeight.w700, color: p.textSecondary)),
              ),
              const SizedBox(width: 8),
              Text(netLabel, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 11, weight: FontWeight.w800, color: netColor)),
            ],
          ),
        ],
      ),
    );
  }
}

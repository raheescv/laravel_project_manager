import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/stock_check_models.dart';
import '../../domain/repository/stock_check_repository.dart';

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

  StockCheckRepository get _repo => serviceLocator<StockCheckRepository>();

  Future<void> _load() async {
    final req = ++_reqId;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _repo.list(status: _status, search: _search, page: 1);
      if (!mounted || req != _reqId) return;
      setState(() {
        _rows = res.rows;
        _total = res.total;
        _page = res.currentPage;
        _lastPage = res.lastPage;
      });
    } catch (_) {
      if (mounted && req == _reqId) setState(() => _error = 'Could not load stock checks.');
    }
    if (mounted && req == _reqId) setState(() => _loading = false);
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _loading || !_hasMore) return;
    final req = _reqId;
    setState(() => _loadingMore = true);
    try {
      final res = await _repo.list(status: _status, search: _search, page: _page + 1);
      if (!mounted || req != _reqId) return;
      setState(() {
        _rows = [..._rows, ...res.rows];
        _page = res.currentPage;
        _lastPage = res.lastPage;
        _total = res.total;
      });
    } catch (_) {/* keep what we have */}
    if (mounted && req == _reqId) setState(() => _loadingMore = false);
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
    final detail = await context.push<StockCheckDetail>('/stock-check/new');
    if (!mounted || detail == null) return;
    _load();
    _pushCount(detail);
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

  Future<void> _pushCount(StockCheckDetail d) async {
    await context.push('/stock-check/count', extra: d);
    if (mounted) _load(); // reflect progress/status changes on return
  }

  @override
  Widget build(BuildContext context) {
    final branch = context.watch<BranchCubit>().selected?.name ?? 'All branches';
    final sub = _loading && _rows.isEmpty ? 'Loading…' : '$branch · $_total count${_total == 1 ? '' : 's'}';
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
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
    return RefreshIndicator(
      onRefresh: _load,
      child: MaxWidthBox(
        maxWidth: 720,
        child: ListView(
          controller: _scrollCtl,
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 120),
          children: [
            _controls(),
            const SizedBox(height: 4),
            if (_loading && _rows.isEmpty)
              const Padding(padding: EdgeInsets.symmetric(vertical: 60), child: Center(child: CircularProgressIndicator()))
            else if (_error != null && _rows.isEmpty)
              EmptyState(
                icon: Icons.wifi_off,
                title: 'Unavailable',
                message: _error,
                action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load),
              )
            else if (_rows.isEmpty)
              EmptyState(
                icon: Icons.fact_check_outlined,
                title: 'No stock checks',
                message: 'Start a new count to snapshot this branch’s stock.',
                action: AstraButton(label: 'New count', icon: Icons.add, expand: false, onTap: _openCreate),
              )
            else ...[
              for (final r in _rows)
                Padding(padding: const EdgeInsets.only(bottom: 10), child: _card(r)),
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

  Widget _controls() {
    final p = context.astra;
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
          Container(
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
            child: Text(label,
                style: ui(size: 11, weight: active ? FontWeight.w800 : FontWeight.w700, color: active ? p.primary : p.textSecondary)),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
      child: Row(children: [seg('All', null), seg('Pending', 'pending'), seg('Completed', 'completed')]),
    );
  }

  Widget _card(StockCheckSummary r) {
    final p = context.astra;
    final done = r.isCompleted;
    final (bg, fg) = done ? (p.successTint, AstraPalette.success) : (p.warnTint, p.warnText);
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
              StatusPill(
                label: (done ? 'Completed' : 'Pending').toUpperCase(),
                bg: bg,
                fg: fg,
                icon: done ? Icons.check : Icons.schedule,
              ),
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

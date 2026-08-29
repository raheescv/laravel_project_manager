import 'dart:async';
import 'package:invo/features/sale_return/logic/return_ops_cubit/return_ops_cubit.dart';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/astra_snack.dart';

/// Step 1 of a return: find and select the paid invoice to return against.
/// Reuses the Sales list (status = completed) so only billable sales appear.
class ReturnPickInvoiceScreen extends StatefulWidget {
  const ReturnPickInvoiceScreen({super.key});
  @override
  State<ReturnPickInvoiceScreen> createState() => _ReturnPickInvoiceScreenState();
}

class _ReturnPickInvoiceScreenState extends State<ReturnPickInvoiceScreen> {
  /// Repository access for this flow (§10).
  final _ops = ReturnOpsCubit();
  final _searchCtl = TextEditingController();
  final _scrollCtl = ScrollController();
  Timer? _debounce;

  /// Owns fetch/pagination/error state — see [PaginatedListCubit]. The screen
  /// keeps only the filter values it drives the fetcher with.
  late final PaginatedListCubit _list = PaginatedListCubit(
    fetch: _fetchPage,
    errorMessage: 'Could not load invoices.',
  );

  String _search = '';
  String _datePreset = '30d'; // 30d | month | all
  DateTime? _startDate;
  DateTime? _endDate;

  @override
  void initState() {
    super.initState();
    _applyPreset('30d', reload: false);
    _scrollCtl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) => unawaited(_list.load()));
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtl.dispose();
    _scrollCtl.dispose();
    unawaited(_list.close());
    super.dispose();
  }

  void _onScroll() {
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 500) unawaited(_list.loadMore());
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
      unawaited(_list.load());
    }
  }

  void _onSearchChanged(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () {
      _search = v.trim();
      unawaited(_list.load());
    });
  }

  Future<PageResult> _fetchPage(int page) => _ops.fetchPaidInvoices(
        page: page,
        search: _search.isEmpty ? null : _search,
        fromDate: _startDate == null ? null : Dates.iso(_startDate!),
        toDate: _endDate == null ? null : Dates.iso(_endDate!),
      );

  /// Load the returnable lines for the chosen sale, seed the draft, and move on
  /// to the New Return screen.
  Future<void> _pick(String id) async {
    if (id.isEmpty) return;
    unawaited(showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator())));
    try {
      final returnable = await _ops.returnableSale(id);
      if (!mounted) return;
      Navigator.pop(context); // close the loader
      final hasReturnable = returnable.lines.any((l) => l.returnableQuantity > 0);
      if (!hasReturnable) {
        AstraSnack.error(context, 'Every item on this invoice has already been returned.');
        return;
      }
      context.read<ReturnDraftCubit>().seed(returnable);
      unawaited(context.push(Routes.saleReturn));
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) AstraSnack.error(context, 'Could not open invoice');
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
                onTap: () => context.canPop() ? context.pop() : context.go(Routes.salesReturns),
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
          Expanded(child: _listView()),
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
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(label, maxLines: 1, style: ui(size: 11.5, weight: active ? FontWeight.w800 : FontWeight.w700, color: active ? p.primary : p.textSecondary)),
            ),
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

  Widget _listView() {
    return BlocBuilder<PaginatedListCubit, PaginatedListState>(
      bloc: _list,
      builder: (context, state) {
        if (state.isLoading && state.items.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }
        if (state.hasFailed && state.items.isEmpty) {
          return EmptyState(
              icon: Icons.wifi_off,
              title: 'Invoices unavailable',
              message: state.errorMessage,
              action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: () => unawaited(_list.load())));
        }
        if (state.items.isEmpty) {
          return const EmptyState(icon: Icons.receipt_long, title: 'No paid invoices', message: 'Try a wider date range or a different search.');
        }
        // Slivers, not ListView(children:) — the rows accumulate a page per scroll.
        return RefreshIndicator(
          onRefresh: _list.load,
          child: CustomScrollView(
            controller: _scrollCtl,
            slivers: [
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 6, 16, 0),
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
        );
      },
    );
  }

  Widget _row(Map<String, dynamic> r) {
    final p = context.astra;
    final invoice = asStr(r['invoice_no']).isEmpty ? '#${asStr(r['id'])}' : asStr(r['invoice_no']);
    final summary = r['summary'] is Map ? r['summary'] as Map : const {};
    final amount = asNum(summary['paid'] ?? summary['gross_amount'] ?? r['paid']);
    final customer = r['customer'] is Map ? r['customer'] as Map : const {};
    final who = asStr(customer['name']).isEmpty ? AppStrings.walkInCustomer : asStr(customer['name']);
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

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_service.dart';
import '../../core/formatters.dart';
import '../../core/responsive.dart';
import '../../theme/palette.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';

class SalesListScreen extends StatefulWidget {
  const SalesListScreen({super.key});
  @override
  State<SalesListScreen> createState() => _SalesListScreenState();
}

class _SalesListScreenState extends State<SalesListScreen> {
  bool _loading = true;
  String? _error;
  List<Map<String, dynamic>> _rows = [];
  String? _status; // null = all

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final rows = await context.read<ApiService>().sales(status: _status);
      if (mounted) setState(() => _rows = rows);
    } catch (e) {
      if (mounted) setState(() => _error = 'Could not load sales.');
    }
    if (mounted) setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              title: 'Sales',
              subtitle: '${_rows.length} recent invoices',
              bottom: SizedBox(
                height: 32,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: [
                    _filter('All', null),
                    const SizedBox(width: 7),
                    _filter('Completed', 'completed'),
                    const SizedBox(width: 7),
                    _filter('Draft', 'draft'),
                    const SizedBox(width: 7),
                    _filter('Cancelled', 'cancelled'),
                  ],
                ),
              ),
            ),
            Expanded(child: _body()),
          ],
        ),
      ),
    );
  }

  Widget _filter(String label, String? status) {
    final active = _status == status;
    return GestureDetector(
      onTap: () {
        setState(() => _status = status);
        _load();
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 6),
        decoration: BoxDecoration(
          color: active ? Colors.white.withValues(alpha: 0.18) : Colors.white.withValues(alpha: 0.07),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Text(label,
            style: ui(size: 11, weight: active ? FontWeight.w700 : FontWeight.w600, color: active ? Colors.white : Colors.white70)),
      ),
    );
  }

  Widget _body() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return EmptyState(icon: Icons.wifi_off, title: 'Sales unavailable', message: _error, action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: _load));
    }
    if (_rows.isEmpty) {
      return EmptyState(icon: Icons.receipt_long, title: 'No sales yet', message: 'Completed tickets will show up here.');
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: MaxWidthBox(
        maxWidth: 720,
        child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 110),
        itemCount: _rows.length,
        separatorBuilder: (_, __) => const SizedBox(height: 9),
        itemBuilder: (_, i) => _row(_rows[i]),
      ),
      ),
    );
  }

  Widget _row(Map<String, dynamic> r) {
    final p = context.astra;
    final invoice = asStr(r['invoice_no']).isEmpty ? '#${asStr(r['id'])}' : asStr(r['invoice_no']);
    final amount = asNum(r['paid'] ?? r['gross_amount'] ?? r['amount']);
    final who = asStr(r['customer_name']).isEmpty
        ? (r['account'] is Map ? asStr((r['account'] as Map)['name']) : 'Walk-in')
        : asStr(r['customer_name']);
    final status = asStr(r['status']);
    final date = Dates.human(asStr(r['date']));
    final (bg, fg) = switch (status) {
      'completed' => (AstraPalette.successTint, AstraPalette.success),
      'cancelled' => (AstraPalette.dangerTint, AstraPalette.danger),
      _ => (AstraPalette.warnTint, p.goldText),
    };
    return AstraCard(
      radius: 14,
      padding: const EdgeInsets.all(13),
      onTap: () => _open(asStr(r['id'])),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(invoice, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                    if (status.isNotEmpty) ...[
                      const SizedBox(width: 7),
                      StatusPill(label: status.toUpperCase(), bg: bg, fg: fg),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text('${who.isEmpty ? 'Walk-in' : who}${date.isEmpty ? '' : ' · $date'}',
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Text(Money.of(amount), style: serif(size: 15, color: p.ink)),
        ],
      ),
    );
  }

  Future<void> _open(String id) async {
    if (id.isEmpty) return;
    showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));
    try {
      final sale = await context.read<ApiService>().saleById(id);
      if (mounted) Navigator.pop(context);
      if (mounted) context.push('/invoice', extra: sale);
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not open invoice')));
    }
  }
}

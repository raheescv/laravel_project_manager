import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/stock_check_models.dart';
import '../../domain/repository/stock_check_repository.dart';

/// Create form for a new stock check. The item list is auto-snapshotted from the
/// selected branch's live stock server-side, so there is no product picking. On
/// success it pops the created [StockCheckDetail] so the list can jump into it.
class NewStockCheckScreen extends StatefulWidget {
  const NewStockCheckScreen({super.key});
  @override
  State<NewStockCheckScreen> createState() => _NewStockCheckScreenState();
}

class _NewStockCheckScreenState extends State<NewStockCheckScreen> {
  final _titleCtl = TextEditingController();
  final _descCtl = TextEditingController();
  final _titleFocus = FocusNode();
  DateTime _date = DateTime.now();
  Branch? _branch;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final b = context.read<BranchCubit>();
      setState(() => _branch = b.selected);
    });
  }

  @override
  void dispose() {
    _titleCtl.dispose();
    _descCtl.dispose();
    _titleFocus.dispose();
    super.dispose();
  }

  StockCheckRepository get _repo => serviceLocator<StockCheckRepository>();

  Future<void> _pickDate() async {
    final p = context.astra;
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(now.year - 2),
      lastDate: now,
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
    if (picked != null) setState(() => _date = picked);
  }

  void _pickBranch() {
    final branches = context.read<BranchCubit>().branches;
    if (branches.isEmpty) return;
    final p = context.astra;
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => ConstrainedBox(
        constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(ctx).height * 0.7),
        child: Container(
          decoration: BoxDecoration(color: p.cardSolid, borderRadius: const BorderRadius.vertical(top: Radius.circular(24))),
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(child: Container(width: 40, height: 4, margin: const EdgeInsets.only(bottom: 14), decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(4)))),
              Padding(padding: const EdgeInsets.only(left: 4, bottom: 10), child: Text('Branch', style: serif(size: 17, color: p.ink))),
              Flexible(
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      for (final b in branches)
                        GestureDetector(
                          behavior: HitTestBehavior.opaque,
                          onTap: () {
                            Navigator.pop(ctx);
                            setState(() => _branch = b);
                          },
                          child: Container(
                            margin: const EdgeInsets.only(bottom: 6),
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            decoration: BoxDecoration(color: _branch?.id == b.id ? p.tint : Colors.transparent, borderRadius: BorderRadius.circular(13)),
                            child: Row(
                              children: [
                                Icon(Icons.storefront_outlined, size: 18, color: _branch?.id == b.id ? p.primary : p.textSecondary),
                                const SizedBox(width: 12),
                                Expanded(child: Text(b.name, style: ui(size: 13, weight: _branch?.id == b.id ? FontWeight.w800 : FontWeight.w600, color: _branch?.id == b.id ? p.ink : p.textSecondary))),
                                if (_branch?.id == b.id) Icon(Icons.check_circle_rounded, size: 18, color: p.primary),
                              ],
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _create() async {
    final title = _titleCtl.text.trim();
    if (title.isEmpty) {
      _titleFocus.requestFocus();
      _snack('Enter a title for this count.');
      return;
    }
    if (_branch == null) {
      _snack('Select a branch.');
      return;
    }
    setState(() => _busy = true);
    try {
      final res = await _repo.create(
        branchId: _branch!.id,
        date: Dates.iso(_date),
        title: title,
        description: _descCtl.text.trim(),
      );
      if (!mounted) return;
      final detail = StockCheckDetail(
        id: res.id,
        title: title,
        date: Dates.iso(_date),
        status: 'pending',
        branchId: _branch!.id,
        branchName: _branch!.name,
      );
      context.pop(detail);
    } on ApiException catch (e) {
      if (mounted) _snack(e.message);
    } catch (_) {
      if (mounted) _snack('Could not create the stock check.');
    }
    if (mounted) setState(() => _busy = false);
  }

  void _snack(String m) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(m)));

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              title: 'New Stock Check',
              subtitle: 'Snapshot this branch’s stock',
              leading: HeaderIconButton(icon: Icons.arrow_back_ios_new, onTap: () => context.pop()),
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 620,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
                  children: [
                    const SectionLabel('Details'),
                    const SizedBox(height: 10),
                    _fieldLabel('TITLE'),
                    _inputBox(
                      TextField(
                        controller: _titleCtl,
                        focusNode: _titleFocus,
                        textCapitalization: TextCapitalization.sentences,
                        style: ui(size: 13, weight: FontWeight.w700, color: p.ink),
                        decoration: _dec('e.g. July Full Count', Icons.title_rounded),
                      ),
                    ),
                    const SizedBox(height: 13),
                    _fieldLabel('BRANCH'),
                    _tapBox(_branch?.name ?? 'Select branch', Icons.storefront_outlined, _pickBranch),
                    const SizedBox(height: 13),
                    _fieldLabel('COUNT DATE'),
                    _tapBox(Dates.human(Dates.iso(_date)), Icons.event_outlined, _pickDate),
                    const SizedBox(height: 13),
                    _fieldLabel('DESCRIPTION  (OPTIONAL)'),
                    _inputBox(
                      TextField(
                        controller: _descCtl,
                        maxLines: 3,
                        textCapitalization: TextCapitalization.sentences,
                        style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
                        decoration: _dec('Notes for this count…', Icons.notes_rounded),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(Icons.info_outline_rounded, size: 15, color: p.primary),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Every product currently in ${_branch?.name ?? 'the branch'} is snapshotted into this count with its system quantity. You then count against that list.',
                              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textSecondary, height: 1.5),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 22),
                    AstraButton(label: 'Create & start counting', icon: Icons.camera_alt_outlined, busy: _busy, onTap: _create),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  InputDecoration _dec(String hint, IconData icon) {
    final p = context.astra;
    return InputDecoration(
      isDense: true,
      border: InputBorder.none,
      prefixIcon: Icon(icon, size: 16, color: p.textMuted),
      prefixIconConstraints: const BoxConstraints(minWidth: 34),
      hintText: hint,
      hintStyle: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted),
    );
  }

  Widget _inputBox(Widget child) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
      decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(13), boxShadow: context.astraTheme.softShadow, border: Border.all(color: p.cardBorder)),
      child: child,
    );
  }

  Widget _tapBox(String value, IconData icon, VoidCallback onTap) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 13),
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
        child: Row(
          children: [
            Icon(icon, size: 16, color: p.primary),
            const SizedBox(width: 10),
            Expanded(child: Text(value, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink))),
            Icon(Icons.keyboard_arrow_down_rounded, size: 18, color: p.textMuted),
          ],
        ),
      ),
    );
  }

  Widget _fieldLabel(String t) => Padding(
        padding: const EdgeInsets.only(left: 2, bottom: 6),
        child: Text(t, style: ui(size: 8.5, weight: FontWeight.w800, color: context.astra.textMuted, letterSpacing: 1.1)),
      );
}

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/logic/stylist_cubit/stylist_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Click-and-go stylist picker. Tapping a stylist (or "Me") selects it and
/// closes immediately — no Save button. Returns the chosen [Employee], or
/// `null` if dismissed without a choice. The "Me" tile resolves to the
/// logged-in user so the ticket can stay assigned to whoever is ringing it up.
Future<Employee?> pickStylist(BuildContext context, {int? selectedId}) {
  return showModalBottomSheet<Employee>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    builder: (_) => _StylistSheet(selectedId: selectedId),
  );
}

class _StylistSheet extends StatefulWidget {
  const _StylistSheet({this.selectedId});
  final int? selectedId;
  @override
  State<_StylistSheet> createState() => _StylistSheetState();
}

class _StylistSheetState extends State<_StylistSheet> {
  final _searchCtl = TextEditingController();
  String _term = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<StylistCubit>().loadIfNeeded();
    });
  }

  @override
  void dispose() {
    _searchCtl.dispose();
    super.dispose();
  }

  /// The logged-in user expressed as an assignable stylist ("Me").
  Employee? get _me {
    final u = context.read<AuthCubit>().user;
    if (u == null) return null;
    return Employee(
      id: int.tryParse(u.id) ?? 0,
      name: u.name,
      code: u.code,
      mobile: u.mobile,
      designation: u.designation,
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final stylists = context.watch<StylistCubit>();
    final list = stylists.search(_term);
    final me = _me;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.85),
        decoration: BoxDecoration(color: p.canvas, borderRadius: const BorderRadius.vertical(top: Radius.circular(30))),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 10),
            Center(
              child: Container(width: 40, height: 4,
                  decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3))),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 16, 6),
              child: Row(
                children: [
                  Icon(Icons.brush, size: 18, color: p.primary),
                  const SizedBox(width: 9),
                  Expanded(child: Text('Select stylist', style: serif(size: 20, color: p.ink))),
                  GestureDetector(
                    onTap: () => Navigator.of(context).pop(),
                    child: Icon(Icons.close, size: 20, color: p.textMuted),
                  ),
                ],
              ),
            ),
            // Search
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 10),
              child: Container(
                decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(14), border: Border.all(color: p.hairline)),
                child: TextField(
                  controller: _searchCtl,
                  onChanged: (v) => setState(() => _term = v),
                  style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
                  decoration: InputDecoration(
                    isDense: true,
                    hintText: 'Search name or code',
                    hintStyle: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted),
                    prefixIcon: Icon(Icons.search, color: p.textMuted, size: 20),
                    border: InputBorder.none,
                    contentPadding: const EdgeInsets.symmetric(vertical: 13),
                  ),
                ),
              ),
            ),
            Flexible(child: _content(p, stylists, list, me)),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  Widget _content(AstraPalette p, StylistCubit stylists, List<Employee> list, Employee? me) {
    if (stylists.loading && list.isEmpty) {
      return const Padding(padding: EdgeInsets.symmetric(vertical: 40), child: Center(child: CircularProgressIndicator()));
    }
    if (stylists.error != null && list.isEmpty) {
      return Padding(
        padding: const EdgeInsets.fromLTRB(20, 10, 20, 30),
        child: EmptyState(
          icon: Icons.wifi_off,
          title: 'Couldn’t load stylists',
          message: stylists.error,
          action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: stylists.load),
        ),
      );
    }
    return ListView(
      shrinkWrap: true,
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      children: [
        // "Me" — only when not filtered out by the search term.
        if (me != null && (_term.isEmpty || me.name.toLowerCase().contains(_term.trim().toLowerCase())))
          _tile(p, me, label: 'Me · ${me.name}'),
        for (final e in list)
          if (me == null || e.id != me.id) _tile(p, e),
        if (list.isEmpty && (me == null))
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 26),
            child: Center(child: Text('No stylists found', style: ui(size: 12.5, weight: FontWeight.w600, color: p.textMuted))),
          ),
      ],
    );
  }

  Widget _tile(AstraPalette p, Employee e, {String? label}) {
    final selected = widget.selectedId != null && widget.selectedId == e.id;
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AstraCard(
        radius: 15,
        padding: const EdgeInsets.all(10),
        onTap: () => Navigator.of(context).pop(e),
        child: Row(
          children: [
            Monogram(letter: e.initial, size: 36),
            const SizedBox(width: 11),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label ?? e.name,
                      maxLines: 1, overflow: TextOverflow.ellipsis,
                      style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                  Text(
                    [e.code, if (e.designation.isNotEmpty) e.designation].where((s) => s.isNotEmpty).join(' · '),
                    maxLines: 1, overflow: TextOverflow.ellipsis,
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Icon(selected ? Icons.radio_button_checked : Icons.chevron_right,
                size: selected ? 20 : 22, color: selected ? p.primary : p.textMuted),
          ],
        ),
      ),
    );
  }
}

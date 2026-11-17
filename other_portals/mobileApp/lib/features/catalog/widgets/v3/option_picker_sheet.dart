import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

import '../../domain/models/catalog_models.dart';

/// What the picker returned: a choice, or "none / all" ([ref] null).
class OptionPick {
  const OptionPick(this.ref);

  final CatalogRef? ref;
}

/// Searchable single-choice sheet for departments, categories, units and
/// brands. Click-and-go: a tap applies and closes.
///
/// [clearLabel] adds a first row that clears the choice ("All departments",
/// "None"). [allowCreate] offers `Add "<typed>"` when nothing matches — saved
/// as a new option by the server, as the web form's pickers do.
Future<OptionPick?> showOptionPicker(
  BuildContext context, {
  required String title,
  required List<CatalogOption> options,
  int? selectedId,
  String? clearLabel,
  bool allowCreate = false,
}) {
  final p = context.astra;
  return showModalBottomSheet<OptionPick>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    backgroundColor: p.cardSolid,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
    constraints: const BoxConstraints(maxWidth: 560),
    builder: (_) => _OptionPickerBody(
      title: title,
      options: options,
      selectedId: selectedId,
      clearLabel: clearLabel,
      allowCreate: allowCreate,
    ),
  );
}

class _OptionPickerBody extends StatefulWidget {
  const _OptionPickerBody({required this.title, required this.options, this.selectedId, this.clearLabel, required this.allowCreate});

  final String title;
  final List<CatalogOption> options;
  final int? selectedId;
  final String? clearLabel;
  final bool allowCreate;

  @override
  State<_OptionPickerBody> createState() => _OptionPickerBodyState();
}

class _OptionPickerBodyState extends State<_OptionPickerBody> {
  final _search = TextEditingController();
  String _term = '';

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final term = _term.trim().toLowerCase();
    final matches = term.isEmpty ? widget.options : widget.options.where((o) => o.name.toLowerCase().contains(term)).toList();
    final exact = widget.options.any((o) => o.name.toLowerCase() == term);
    final canCreate = widget.allowCreate && term.isNotEmpty && !exact;
    final showSearch = widget.options.length > 7 || widget.allowCreate;
    final rows = <Widget>[
      if (widget.clearLabel != null && term.isEmpty)
        _row(context, widget.clearLabel!, widget.selectedId == null, () => Navigator.pop(context, const OptionPick(null)), muted: true),
      if (canCreate)
        _row(context, 'Add “${_term.trim()}”', false, () => Navigator.pop(context, OptionPick(CatalogRef.create(_term.trim()))),
            icon: Icons.add_circle_outline),
      for (final o in matches)
        _row(context, o.name, o.id == widget.selectedId, () => Navigator.pop(context, OptionPick(CatalogRef.existing(o.id, o.name)))),
    ];

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: ConstrainedBox(
        constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * 0.72),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 9),
            Container(width: 38, height: 4, decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3))),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 8),
              child: Align(alignment: Alignment.centerLeft, child: Text(widget.title, style: ui(size: 16, weight: FontWeight.w800, color: p.ink))),
            ),
            if (showSearch)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 6),
                child: TextField(
                  controller: _search,
                  autofocus: widget.options.length > 7,
                  onChanged: (v) => setState(() => _term = v),
                  style: ui(size: 14, weight: FontWeight.w600, color: p.ink),
                  decoration: InputDecoration(
                    hintText: widget.allowCreate ? 'Search or type a new name' : 'Search',
                    prefixIcon: Icon(Icons.search, color: p.textMuted, size: 20),
                    isDense: true,
                    filled: true,
                    fillColor: p.tint,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                  ),
                ),
              ),
            Flexible(
              child: rows.isEmpty
                  ? Padding(
                      padding: const EdgeInsets.all(24),
                      child: Text('Nothing matches', style: ui(size: 13, color: p.textMuted)),
                    )
                  : ListView.builder(
                      shrinkWrap: true,
                      padding: const EdgeInsets.fromLTRB(8, 4, 8, 16),
                      itemCount: rows.length,
                      itemBuilder: (_, i) => rows[i],
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _row(BuildContext context, String label, bool selected, VoidCallback onTap, {IconData? icon, bool muted = false}) {
    final p = context.astra;
    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 13),
        decoration: BoxDecoration(color: selected ? p.tint : null, borderRadius: BorderRadius.circular(12)),
        child: Row(
          children: [
            if (icon != null) ...[Icon(icon, size: 19, color: p.primary), const SizedBox(width: 10)],
            Expanded(
              child: Text(label,
                  style: ui(size: 14, weight: selected ? FontWeight.w800 : FontWeight.w600, color: icon != null ? p.primary : (muted ? p.textSecondary : p.ink))),
            ),
            if (selected) Icon(Icons.check_circle, size: 19, color: p.primary),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/catalog_models.dart';

/// The catalog as a full table: the header stays put while the body scrolls,
/// the name column stays pinned while the rest scrolls sideways, and only the
/// rows in view are built.
///
/// One vertical list carries every row (with an empty slot under the pinned
/// column); a pointer-transparent copy of the name column floats over that slot
/// and mirrors the list's offset — so taps and drags land on the real row,
/// while the names never scroll away.
class CatalogTable extends StatefulWidget {
  const CatalogTable({
    super.key,
    required this.type,
    required this.rows,
    required this.columns,
    required this.showImages,
    required this.query,
    required this.onSort,
    required this.onOpen,
    required this.onNearEnd,
    required this.footer,
    this.bottomInset = 0,
  });

  final CatalogType type;
  final List<Map<String, dynamic>> rows;

  /// Pinned name first, then the visible columns.
  final List<CatalogColumn> columns;
  final bool showImages;
  final CatalogQuery query;
  final ValueChanged<String> onSort;
  final ValueChanged<Map<String, dynamic>> onOpen;
  final VoidCallback onNearEnd;

  /// Under the last row — the count, or a spinner while the next page loads.
  final Widget footer;

  /// Room left under the last row for the floating bottom nav.
  final double bottomInset;

  @override
  State<CatalogTable> createState() => _CatalogTableState();
}

class _CatalogTableState extends State<CatalogTable> {
  static const _rowHeight = 52.0;
  static const _headerHeight = 42.0;
  static const _footerHeight = 52.0;

  final _vertical = ScrollController();
  final _pinnedVertical = ScrollController();
  final _horizontal = ScrollController();

  @override
  void initState() {
    super.initState();
    _vertical.addListener(_onVerticalScroll);
  }

  void _onVerticalScroll() {
    if (_pinnedVertical.hasClients) _pinnedVertical.jumpTo(_vertical.offset);
    if (_vertical.position.extentAfter < 500) widget.onNearEnd();
  }

  @override
  void dispose() {
    _vertical.dispose();
    _pinnedVertical.dispose();
    _horizontal.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return LayoutBuilder(builder: (context, box) {
      final pinned = widget.columns.first;
      final rest = widget.columns.skip(1).toList();
      final tablet = context.isTablet;
      final pinnedWidth = pinned.width + (tablet ? 70 : 0) + (widget.showImages ? 40 : 0);
      final natural = pinnedWidth + rest.fold<double>(0, (sum, c) => sum + c.width);
      // A table narrower than the screen stretches its columns rather than
      // leaving a blank strip down the right.
      final stretch = natural < box.maxWidth ? box.maxWidth / natural : 1.0;
      final widths = [pinnedWidth * stretch, for (final c in rest) c.width * stretch];
      final totalWidth = widths.fold<double>(0, (a, b) => a + b);
      final listPadding = EdgeInsets.only(bottom: widget.bottomInset);
      final itemCount = widget.rows.length + 1;

      return DecoratedBox(
        decoration: BoxDecoration(color: p.cardSolid, borderRadius: BorderRadius.circular(16), boxShadow: context.astraTheme.cardShadow),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(16),
          child: Stack(
            children: [
              SingleChildScrollView(
                controller: _horizontal,
                scrollDirection: Axis.horizontal,
                child: SizedBox(
                  width: totalWidth,
                  child: Column(
                    children: [
                      _HeaderRow(children: [
                        SizedBox(width: widths[0]),
                        for (var i = 0; i < rest.length; i++) _header(rest[i], widths[i + 1]),
                      ]),
                      Expanded(
                        child: ListView.builder(
                          controller: _vertical,
                          padding: listPadding,
                          itemCount: itemCount,
                          itemExtent: _rowHeight,
                          itemBuilder: (_, i) {
                            if (i == widget.rows.length) return const SizedBox(height: _footerHeight);
                            final row = widget.rows[i];
                            return _BodyRow(
                              onTap: () => widget.onOpen(row),
                              children: [
                                SizedBox(width: widths[0]),
                                for (var c = 0; c < rest.length; c++) _cell(rest[c], row, widths[c + 1]),
                              ],
                            );
                          },
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              // The pinned name column. Its body ignores the pointer so taps and
              // drags reach the row underneath; only its header sorts.
              Positioned(
                left: 0,
                top: 0,
                bottom: 0,
                width: widths[0],
                child: AnimatedBuilder(
                  animation: _horizontal,
                  builder: (context, child) => DecoratedBox(
                    decoration: BoxDecoration(
                      color: p.cardSolid,
                      boxShadow: _horizontal.hasClients && _horizontal.offset > 2
                          ? [BoxShadow(color: Colors.black.withValues(alpha: p.isDark ? 0.4 : 0.10), blurRadius: 10, offset: const Offset(3, 0))]
                          : null,
                    ),
                    child: child,
                  ),
                  child: Column(
                    children: [
                      _HeaderRow(children: [_header(pinned, widths[0])]),
                      Expanded(
                        child: IgnorePointer(
                          child: ListView.builder(
                            controller: _pinnedVertical,
                            physics: const NeverScrollableScrollPhysics(),
                            padding: listPadding,
                            itemCount: itemCount,
                            itemExtent: _rowHeight,
                            itemBuilder: (_, i) => i == widget.rows.length
                                ? Align(alignment: Alignment.centerLeft, child: Padding(padding: const EdgeInsets.only(left: 14), child: widget.footer))
                                : _BodyRow(children: [_nameCell(widget.rows[i], widths[0])]),
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
      );
    });
  }

  Widget _header(CatalogColumn c, double width) {
    final p = context.astra;
    final active = widget.query.sortKey == c.key;
    final arrow = active ? (widget.query.ascending ? Icons.arrow_upward : Icons.arrow_downward) : Icons.unfold_more;
    final label = Text(c.label.toUpperCase(),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: ui(size: 10, weight: FontWeight.w800, color: active ? p.primary : p.textMuted, letterSpacing: 0.6));
    final icon = Icon(arrow, size: 14, color: active ? p.primary : p.textMuted.withValues(alpha: 0.6));
    return InkWell(
      onTap: () => widget.onSort(c.key),
      child: Container(
        width: width,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        alignment: c.alignEnd ? Alignment.centerRight : Alignment.centerLeft,
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: c.alignEnd
              ? [icon, const SizedBox(width: 3), Flexible(child: label)]
              : [Flexible(child: label), const SizedBox(width: 3), icon],
        ),
      ),
    );
  }

  Widget _cell(CatalogColumn c, Map<String, dynamic> row, double width) {
    final p = context.astra;
    final disabled = row['status'] == 'disabled';
    final strong = c.kind == CellKind.price;
    var style = ui(size: 12.5, weight: strong ? FontWeight.w800 : FontWeight.w600, color: disabled ? p.textMuted : (strong ? p.ink : p.textSecondary));
    if (c.kind == CellKind.mono || c.alignEnd) style = style.copyWith(fontFeatures: const [FontFeature.tabularFigures()]);
    if (c.kind == CellKind.money) style = style.copyWith(color: p.textMuted);
    return Container(
      width: width,
      padding: const EdgeInsets.symmetric(horizontal: 12),
      alignment: c.alignEnd || c.kind == CellKind.rtl ? Alignment.centerRight : Alignment.centerLeft,
      child: Text(c.display(row),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          textDirection: c.kind == CellKind.rtl ? TextDirection.rtl : null,
          style: style),
    );
  }

  Widget _nameCell(Map<String, dynamic> row, double width) {
    final p = context.astra;
    final disabled = row['status'] == 'disabled';
    return Container(
      width: width,
      padding: const EdgeInsets.symmetric(horizontal: 12),
      child: Row(
        children: [
          if (widget.showImages) ...[
            ProductThumb(url: asStr(row['thumbnail']), fallbackIcon: widget.type.icon, size: 32, radius: 9),
            const SizedBox(width: 9),
          ],
          Flexible(
            child: Text(asStr(row['name']),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(size: 13, weight: FontWeight.w700, color: disabled ? p.textMuted : p.ink)),
          ),
          if (row['is_favorite'] == true) ...[const SizedBox(width: 5), const Icon(Icons.favorite, size: 13, color: AstraPalette.danger)],
          if (disabled) ...[
            const SizedBox(width: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(8)),
              child: Text('Disabled', style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted)),
            ),
          ],
        ],
      ),
    );
  }
}

class _HeaderRow extends StatelessWidget {
  const _HeaderRow({required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      height: _CatalogTableState._headerHeight,
      decoration: BoxDecoration(color: p.cardSolid, border: Border(bottom: BorderSide(color: p.hairline))),
      child: Row(children: children),
    );
  }
}

class _BodyRow extends StatelessWidget {
  const _BodyRow({required this.children, this.onTap});

  final List<Widget> children;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Material(
      color: p.cardSolid,
      child: InkWell(
        onTap: onTap,
        child: DecoratedBox(
          decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.hairline))),
          child: Row(children: children),
        ),
      ),
    );
  }
}

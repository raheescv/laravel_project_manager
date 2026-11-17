import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/catalog_models.dart';

/// The catalog as photo tiles — same rows, same order as the table.
class CatalogGallery extends StatefulWidget {
  const CatalogGallery({
    super.key,
    required this.type,
    required this.rows,
    required this.onOpen,
    required this.onNearEnd,
    required this.footer,
    this.bottomInset = 0,
  });

  final CatalogType type;
  final List<Map<String, dynamic>> rows;
  final ValueChanged<Map<String, dynamic>> onOpen;
  final VoidCallback onNearEnd;
  final Widget footer;
  final double bottomInset;

  @override
  State<CatalogGallery> createState() => _CatalogGalleryState();
}

class _CatalogGalleryState extends State<CatalogGallery> {
  static const _photoHeight = 112.0;
  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    _scroll.addListener(() {
      if (_scroll.position.extentAfter < 600) widget.onNearEnd();
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(builder: (context, box) {
      final columns = (box.maxWidth / 190).floor().clamp(2, 6);
      return CustomScrollView(
        controller: _scroll,
        slivers: [
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(0, 2, 0, 12),
            sliver: SliverGrid.builder(
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: columns,
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
                mainAxisExtent: _photoHeight + 74,
              ),
              itemCount: widget.rows.length,
              itemBuilder: (_, i) => _Tile(type: widget.type, row: widget.rows[i], onTap: () => widget.onOpen(widget.rows[i])),
            ),
          ),
          SliverToBoxAdapter(child: Center(child: widget.footer)),
          SliverToBoxAdapter(child: SizedBox(height: widget.bottomInset + 12)),
        ],
      );
    });
  }
}

class _Tile extends StatelessWidget {
  const _Tile({required this.type, required this.row, required this.onTap});

  final CatalogType type;
  final Map<String, dynamic> row;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final disabled = row['status'] == 'disabled';
    final badge = type.isProduct ? asStr(row['code']) : '${asNum(row['time']).round()} min';
    return GestureDetector(
      onTap: onTap,
      child: Container(
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(color: p.cardSolid, borderRadius: BorderRadius.circular(16), boxShadow: context.astraTheme.cardShadow),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            LayoutBuilder(
              builder: (context, box) => Stack(
                children: [
                  ProductThumb(
                    url: asStr(row['thumbnail']),
                    fallbackIcon: type.icon,
                    width: box.maxWidth,
                    height: _CatalogGalleryState._photoHeight,
                    radius: 0,
                  ),
                  if (badge.isNotEmpty) Positioned(left: 8, top: 8, child: _Badge(text: badge)),
                  if (row['is_favorite'] == true)
                    const Positioned(right: 8, top: 7, child: Icon(Icons.favorite, size: 17, color: Colors.white, shadows: [Shadow(blurRadius: 6, color: Colors.black38)])),
                  if (disabled) const Positioned(left: 8, bottom: 8, child: _Badge(text: 'Disabled', dark: true)),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(11, 9, 11, 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    height: 33,
                    child: Text(asStr(row['name']),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 13, weight: FontWeight.w700, color: disabled ? p.textMuted : p.ink, height: 1.25)),
                  ),
                  const SizedBox(height: 5),
                  Row(
                    children: [
                      Expanded(
                        child: Text(asStr(row['main_category']),
                            maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
                      ),
                      Text(Money.plain(asNum(row['mrp'])), style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  const _Badge({required this.text, this.dark = false});

  final String text;
  final bool dark;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      constraints: const BoxConstraints(maxWidth: 120),
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(
        color: dark ? Colors.black.withValues(alpha: 0.5) : p.cardSolid.withValues(alpha: 0.92),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(text,
          maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10, weight: FontWeight.w800, color: dark ? Colors.white : p.primary)),
    );
  }
}

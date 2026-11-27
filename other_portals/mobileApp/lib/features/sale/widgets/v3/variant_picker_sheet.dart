import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/offline_image.dart';

/// Design / size / colour picker for a family card on New Sale — the "Studio"
/// layout of the web POS (resources/js/components/VariantPickerModal.vue). One
/// side shows the product in the chosen colour with every colour as a photo
/// thumbnail; the other is a size keypad for that colour. Tapping a size returns
/// that item: click-and-go, no Add button. A size-only family skips the colours;
/// a colour-only one turns the keypad into colour keys. Returns null when
/// dismissed.
///
/// A family may have a first layer above size — a design / flavour / model
/// ([Product.design], named by [Product.optionName]). With sizes, the designs
/// sit in a row above the keypad and narrow it; without sizes (a drink in
/// flavours) the designs ARE the keys.
///
/// Three widths, as on the web: two roomy columns, two dense ones (tablet), and
/// on a phone one scrolling column under a pinned header, with the product as a
/// small photo beside its caption and the colours as a swipe strip.
Future<Product?> pickVariant(BuildContext context, Product family) {
  return showModalBottomSheet<Product>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    constraints: const BoxConstraints(maxWidth: 980),
    builder: (_) => _VariantSheet(family: family),
  );
}

/// One colour of the family: its stock and the first photo taken of it.
class _ColourOption {
  _ColourOption(this.value, this.sample);
  final String value;
  final Product sample;
  num stock = 0;
  String photo = '';

  String get name => sample.color.isEmpty ? 'No colour' : sample.colorName;
}

/// One design / flavour of the family and its stock.
class _DesignOption {
  _DesignOption(this.value);
  final String value;
  num stock = 0;
}

/// A key on the pad: a size (or design, or colour) and the item it adds, null
/// when this colour has none.
class _Key {
  const _Key(this.label, this.variant, {this.colour});
  final String label;
  final Product? variant;
  final Product? colour;
}

/// Sizes for one width tier — the same steps as the web modal's breakpoints.
class _Metrics {
  const _Metrics({
    required this.pad, required this.hero, required this.thumb, required this.key, required this.wideKey,
    required this.keyHeight, required this.keyRadius, required this.keyFont, required this.wideKeyFont,
    required this.noteFont, required this.close, required this.closeRadius, required this.title,
  });
  final double pad, hero, thumb, key, wideKey, keyHeight, keyRadius, keyFont, wideKeyFont, noteFont, close, closeRadius, title;

  static const wide = _Metrics(pad: 22, hero: 260, thumb: 92, key: 96, wideKey: 150, keyHeight: 84, keyRadius: 18,
      keyFont: 26, wideKeyFont: 17, noteFont: 11.5, close: 52, closeRadius: 16, title: 22);
  static const tablet = _Metrics(pad: 18, hero: 170, thumb: 76, key: 80, wideKey: 130, keyHeight: 72, keyRadius: 16,
      keyFont: 22, wideKeyFont: 17, noteFont: 11.5, close: 46, closeRadius: 16, title: 19);
  static const phone = _Metrics(pad: 14, hero: 84, thumb: 72, key: 68, wideKey: 120, keyHeight: 64, keyRadius: 14,
      keyFont: 20, wideKeyFont: 15, noteFont: 10.5, close: 44, closeRadius: 14, title: 18);
}

class _VariantSheet extends StatefulWidget {
  const _VariantSheet({required this.family});
  final Product family;
  @override
  State<_VariantSheet> createState() => _VariantSheetState();
}

class _VariantSheetState extends State<_VariantSheet> {
  static const double _gap = 10;
  static const double _phoneBelow = 760;
  static const double _tabletBelow = 1100;

  late final List<String> _sizes;
  late final List<_DesignOption> _designs;
  String? _design;
  String? _colour;

  /// The colours of the chosen design, each with its stock and first photo —
  /// rebuilt only when the design changes, not on every read during build.
  List<_ColourOption> _colours = const [];

  List<Product> get _variants => widget.family.variants;

  @override
  void initState() {
    super.initState();
    _sizes = sortedSizes(widget.family.familySizes);
    final byDesign = <String, _DesignOption>{};
    for (final v in _variants) {
      if (v.design.isEmpty) continue;
      byDesign.putIfAbsent(v.design, () => _DesignOption(v.design)).stock += v.totalStock;
    }
    _designs = byDesign.values.toList();
    // Open on the first design, then the first colour of it, that has stock.
    if (_designs.isNotEmpty) {
      _design = _designs.firstWhere((d) => d.stock > 0, orElse: () => _designs.first).value;
    }
    _colours = _coloursOfDesign();
    _resetColour();
  }

  void _resetColour() {
    final colours = _colours;
    _colour = colours.isEmpty ? null : colours.firstWhere((c) => c.stock > 0, orElse: () => colours.first).value;
  }

  /// A new design keeps the colour when it comes in it, else opens on its first colour in stock.
  void _pickDesign(String design) {
    setState(() {
      _design = design;
      _colours = _coloursOfDesign();
      if (!_colours.any((c) => c.value == _colour)) _resetColour();
    });
  }

  /// Sizes sit under the designs, so the designs get a row of their own.
  bool get _showDesignRow => _designs.isNotEmpty && _sizes.isNotEmpty;

  /// The items under the chosen design (all of them when designs are the keys or absent).
  List<Product> get _scoped =>
      !_showDesignRow || _design == null ? _variants : [for (final v in _variants) if (v.design == _design) v];

  List<_ColourOption> _coloursOfDesign() {
    if (!_variants.any((v) => v.color.isNotEmpty)) return const [];
    final byColour = <String, _ColourOption>{};
    for (final v in _scoped) {
      final option = byColour.putIfAbsent(v.color, () => _ColourOption(v.color, v));
      option.stock += v.totalStock;
      if (option.photo.isEmpty && v.thumbnail.isNotEmpty) option.photo = v.thumbnail;
    }
    return byColour.values.toList();
  }

  /// "Floral · Navy": what the stage is showing.
  String get _caption {
    final parts = [if (_showDesignRow && _design != null) _design!, if (_current != null) _current!.name];
    return parts.isEmpty ? widget.family.name : parts.join(' · ');
  }

  _ColourOption? get _current {
    for (final c in _colours) {
      if (c.value == _colour) return c;
    }
    return null;
  }

  /// The chosen colour's items; every item when the family has no colours.
  List<Product> get _inColour =>
      _current == null ? _scoped : [for (final v in _scoped) if (v.color == _colour) v];

  /// The chosen colour's own photo; else the family photo the catalog card
  /// shows (the head's, or the first variant that has one) — most colours are
  /// never photographed, and a blank stage reads as a broken image.
  String get _heroPhoto {
    final own = _current?.photo ?? '';
    if (own.isNotEmpty) return own;
    if (widget.family.thumbnail.isNotEmpty) return widget.family.thumbnail;
    for (final v in _variants) {
      if (v.thumbnail.isNotEmpty) return v.thumbnail;
    }
    return '';
  }

  /// Whether the stage is showing a photo of another colour (so it wears a
  /// swatch of the chosen one).
  bool get _borrowedPhoto => _current != null && _current!.photo.isEmpty && _heroPhoto.isNotEmpty;

  /// "flavours", "designs", "sizes": the option's name for a count above one.
  static String _plural(String word) {
    if (RegExp(r'[^aeiou]y$').hasMatch(word)) return '${word.substring(0, word.length - 1)}ies';
    if (RegExp(r'(s|x|z|ch|sh)$').hasMatch(word)) return '${word}es';
    return '${word}s';
  }

  /// Of several rows for one size and colour, the one holding the most stock.
  Product? _best(Iterable<Product> rows) {
    Product? top;
    for (final v in rows) {
      if (top == null || v.totalStock > top.totalStock) top = v;
    }
    return top;
  }

  bool get _sizeKeys => _sizes.isNotEmpty;
  bool get _designKeys => !_sizeKeys && _designs.isNotEmpty;
  bool get _showThumbs => _colours.isNotEmpty && (_sizeKeys || _designKeys);

  List<_Key> get _keys {
    if (_sizeKeys) {
      final rows = _inColour;
      return [for (final s in _sizes) _Key(s, _best(rows.where((v) => v.size.trim() == s)))];
    }
    if (_designKeys) {
      final rows = _inColour;
      return [for (final d in _designs) _Key(d.value, _best(rows.where((v) => v.design == d.value)))];
    }
    if (_colours.isNotEmpty) {
      return [for (final c in _colours) _Key(c.name, _best(_variants.where((v) => v.color == c.value)), colour: c.sample)];
    }
    return [for (final v in _variants) _Key(v.barcode.isEmpty ? v.name : v.barcode, v)];
  }

  ({double min, bool range}) get _price {
    final prices = [for (final v in _inColour) v.mrp];
    final min = prices.reduce((a, b) => a < b ? a : b);
    final max = prices.reduce((a, b) => a > b ? a : b);
    return (min: min, range: max > min);
  }

  String get _stockLine {
    final inColour = _inColour;
    final stock = inColour.fold<num>(0, (sum, v) => sum + v.totalStock);
    final sizes = {for (final v in inColour) if (v.size.trim().isNotEmpty) v.size.trim()}.length;
    final size = widget.family.sizeName.toLowerCase();
    if (sizes > 0) return '${qtyLabel(stock)} in stock across $sizes ${sizes == 1 ? size : _plural(size)}';
    final designs = {for (final v in inColour) if (v.design.isNotEmpty) v.design}.length;
    final option = widget.family.optionName.toLowerCase();
    return designs > 0 ? '${qtyLabel(stock)} in stock across $designs ${designs == 1 ? option : _plural(option)}' : '${qtyLabel(stock)} in stock';
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.92),
      decoration: BoxDecoration(color: p.canvas, borderRadius: const BorderRadius.vertical(top: Radius.circular(30))),
      clipBehavior: Clip.antiAlias,
      child: SafeArea(
        top: false,
        child: LayoutBuilder(
          builder: (context, constraints) {
            // The sheet is capped at 980 wide, so the dense / roomy step follows
            // the screen, as the web modal's media queries do.
            if (constraints.maxWidth < _phoneBelow) return _phoneLayout(p);
            final m = MediaQuery.sizeOf(context).width < _tabletBelow ? _Metrics.tablet : _Metrics.wide;
            // The photo side's tint runs the full height whichever side is
            // taller: a hard-stop gradient behind both columns.
            final split = _colours.isEmpty ? 0.4 : 0.5;
            return DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(colors: [p.tint, p.tint, p.canvas, p.canvas], stops: [0, split, split, 1]),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(flex: _colours.isEmpty ? 2 : 1, child: SingleChildScrollView(child: _stage(p, m))),
                  Expanded(flex: _colours.isEmpty ? 3 : 1, child: SingleChildScrollView(child: _panel(p, m))),
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  // ---- phone: one column under a pinned header ------------------------------

  Widget _phoneLayout(AstraPalette p) {
    const m = _Metrics.phone;
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        DecoratedBox(
          decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.hairline))),
          child: Padding(
            padding: EdgeInsets.fromLTRB(m.pad, 8, m.pad, 10),
            child: Column(children: [Center(child: _grabber(p)), _head(p, m)]),
          ),
        ),
        Flexible(
          child: SingleChildScrollView(
            padding: EdgeInsets.fromLTRB(m.pad, 12, m.pad, m.pad),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [_compactStage(p, m), const SizedBox(height: 14), ..._keypad(p, m)],
            ),
          ),
        ),
      ],
    );
  }

  /// The product as a small photo beside its caption, colours as a swipe strip.
  Widget _compactStage(AstraPalette p, _Metrics m) {
    final current = _current;
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(16)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              SizedBox(
                width: m.hero,
                height: m.hero,
                child: ClipRRect(borderRadius: BorderRadius.circular(12), child: Center(child: _hero(p, current, small: true))),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(_caption, maxLines: 2, overflow: TextOverflow.ellipsis,
                        style: ui(size: 17, weight: FontWeight.w900, color: p.ink, height: 1.15)),
                    const SizedBox(height: 3),
                    Text(_stockLine, style: ui(size: 12.5, weight: FontWeight.w600, color: p.textSecondary)),
                  ],
                ),
              ),
            ],
          ),
          if (_showThumbs) ...[
            const SizedBox(height: 10),
            SizedBox(
              height: m.thumb + 30,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: _colours.length,
                separatorBuilder: (_, __) => const SizedBox(width: 8),
                itemBuilder: (_, i) => _thumb(p, _colours[i], m.thumb, compact: true),
              ),
            ),
          ],
        ],
      ),
    );
  }

  // ---- tablet / wide: the product, in the chosen colour ---------------------

  Widget _stage(AstraPalette p, _Metrics m) {
    final current = _current;
    return Padding(
      padding: EdgeInsets.fromLTRB(m.pad, m.pad, m.pad, m.pad),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(child: _grabber(p)),
          SizedBox(height: m.hero, child: Center(child: _hero(p, current))),
          const SizedBox(height: 12),
          Text(_caption, textAlign: TextAlign.center, maxLines: 1, overflow: TextOverflow.ellipsis,
              style: ui(size: m.title, weight: FontWeight.w900, color: p.ink)),
          const SizedBox(height: 2),
          Text(_stockLine, textAlign: TextAlign.center, style: ui(size: 13, weight: FontWeight.w600, color: p.textSecondary)),
          if (_showThumbs) ...[
            const SizedBox(height: 16),
            LayoutBuilder(builder: (context, c) {
              final gap = m == _Metrics.wide ? _gap : 8.0;
              final cols = ((c.maxWidth + gap) / (m.thumb + gap)).floor().clamp(2, 6);
              final w = (c.maxWidth - gap * (cols - 1)) / cols;
              return Wrap(spacing: gap, runSpacing: gap, children: [for (final option in _colours) _thumb(p, option, w)]);
            }),
          ],
        ],
      ),
    );
  }

  Widget _grabber(AstraPalette p) => Container(
        width: 44,
        height: 5,
        margin: const EdgeInsets.only(bottom: 8),
        decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3)),
      );

  Widget _hero(AstraPalette p, _ColourOption? current, {bool small = false}) {
    final photo = _heroPhoto;
    final fallback = current != null
        ? _swatch(current.sample, small ? 60 : 130)
        : Icon(Icons.inventory_2_outlined, size: small ? 40 : 72, color: p.textMuted);
    if (photo.isEmpty) return fallback;
    final image = small
        ? SizedBox.expand(child: _photo(photo, fit: BoxFit.cover, fallback: fallback))
        : _photo(photo, fit: BoxFit.contain, fallback: fallback);
    if (!_borrowedPhoto) return image;
    // A family photo standing in for an unphotographed colour: the chosen
    // colour rides in the corner so the stage never shows the wrong one alone.
    return Stack(
      alignment: Alignment.center,
      children: [
        image,
        Positioned(
          right: small ? 4 : 8,
          bottom: small ? 4 : 8,
          child: Container(
            padding: const EdgeInsets.all(3),
            decoration: BoxDecoration(color: p.cardSolid, shape: BoxShape.circle, boxShadow: context.astraTheme.softShadow),
            child: _swatch(current!.sample, small ? 18 : 30),
          ),
        ),
      ],
    );
  }

  Widget _thumb(AstraPalette p, _ColourOption option, double width, {bool compact = false}) {
    final on = option.value == _colour;
    final out = option.stock <= 0;
    final radius = BorderRadius.circular(compact ? 12 : 16);
    return _Press(
      child: SizedBox(
        width: width,
        child: Material(
          color: Colors.transparent,
          child: Ink(
            decoration: BoxDecoration(
              color: p.cardSolid,
              borderRadius: radius,
              border: Border.all(color: on ? p.primary : Colors.transparent, width: 2),
              boxShadow: on ? context.astraTheme.floatShadow(p.primary) : null,
            ),
            child: InkWell(
              borderRadius: radius,
              onTap: () => setState(() => _colour = option.value),
              child: Padding(
                padding: compact ? const EdgeInsets.fromLTRB(4, 4, 4, 6) : const EdgeInsets.fromLTRB(6, 6, 6, 8),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    AspectRatio(
                      aspectRatio: 1,
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(compact ? 9 : 12),
                        child: option.photo.isNotEmpty
                            ? _photo(option.photo, fit: BoxFit.cover, fallback: Center(child: _swatch(option.sample, compact ? 30 : 40)))
                            : Center(child: _swatch(option.sample, compact ? 30 : 40)),
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(option.name, maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: ui(size: compact ? 11 : 12.5, weight: FontWeight.w800, color: out ? p.textMuted : p.ink)
                            .copyWith(decoration: out ? TextDecoration.lineThrough : null)),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  // ---- the keypad — one tap adds ---------------------------------------------

  Widget _head(AstraPalette p, _Metrics m) {
    final price = _price;
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(widget.family.name, maxLines: 2, overflow: TextOverflow.ellipsis,
                  style: ui(size: m.title, weight: FontWeight.w900, color: p.ink, height: 1.1)),
              const SizedBox(height: 4),
              Text.rich(TextSpan(children: [
                if (price.range)
                  TextSpan(text: 'FROM  ', style: ui(size: 11, weight: FontWeight.w700, color: p.textSecondary, letterSpacing: 0.6)),
                TextSpan(text: Money.plain(price.min), style: ui(size: 16, weight: FontWeight.w800, color: p.ink)),
                TextSpan(text: '  ·  ${_variants.length} items', style: ui(size: 13.5, weight: FontWeight.w600, color: p.textSecondary)),
              ])),
            ],
          ),
        ),
        const SizedBox(width: 12),
        _closeButton(p, m),
      ],
    );
  }

  Widget _closeButton(AstraPalette p, _Metrics m) {
    final radius = BorderRadius.circular(m.closeRadius);
    return SizedBox(
      width: m.close,
      height: m.close,
      child: Material(
        color: p.cardSolid,
        shape: RoundedRectangleBorder(borderRadius: radius, side: BorderSide(color: p.cardBorder)),
        child: InkWell(
          borderRadius: radius,
          onTap: () => Navigator.of(context).pop(),
          child: Icon(Icons.close_rounded, size: m.close * 0.42, color: p.ink),
        ),
      ),
    );
  }

  Widget _panel(AstraPalette p, _Metrics m) {
    return Padding(
      padding: EdgeInsets.all(m.pad),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [_head(p, m), const SizedBox(height: 14), ..._keypad(p, m)],
      ),
    );
  }

  /// The label, the keys and the scan hint — shared by every width.
  List<Widget> _keypad(AstraPalette p, _Metrics m) {
    final current = _current;
    final option = widget.family.optionName;
    final where = current == null ? '' : ' in ${current.name}';
    final label = _sizeKeys ? '${widget.family.sizeName}$where' : (_designKeys ? '$option$where' : (_colours.isNotEmpty ? 'Colour' : 'Items'));
    final keys = _keys;
    // Designs, colours and long size names ("Small", "Free size") need the wide keys.
    final wideKeys = !_sizeKeys || _sizes.any((s) => s.length > 4);
    final gap = m == _Metrics.wide ? _gap : 8.0;
    return [
      if (_showDesignRow) ...[
        Text(option.toUpperCase(), maxLines: 1, overflow: TextOverflow.ellipsis,
            style: ui(size: 12, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.9)),
        const SizedBox(height: 10),
        Wrap(spacing: 8, runSpacing: 8, children: [for (final d in _designs) _designChip(p, d)]),
        const SizedBox(height: 18),
      ],
      Row(
        children: [
          Expanded(
            child: Text(label.toUpperCase(), maxLines: 1, overflow: TextOverflow.ellipsis,
                style: ui(size: 12, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.9)),
          ),
          Text('Tap to add', style: ui(size: 12.5, weight: FontWeight.w600, color: p.textMuted)),
        ],
      ),
      const SizedBox(height: 10),
      LayoutBuilder(builder: (context, c) {
        final minKey = wideKeys ? m.wideKey : m.key;
        final cols = ((c.maxWidth + gap) / (minKey + gap)).floor().clamp(1, 8);
        final w = (c.maxWidth - gap * (cols - 1)) / cols;
        final lines = _labelLines(keys, w, _keyLabelStyle(p, m, wideKeys), m);
        return Wrap(spacing: gap, runSpacing: gap, children: [
          for (final key in keys) _keyTile(p, m, key, w, wide: wideKeys, lines: lines),
        ]);
      }),
      const SizedBox(height: 16),
      Divider(height: 1, color: p.hairline),
      const SizedBox(height: 12),
      Row(
        children: [
          Icon(Icons.qr_code_scanner_rounded, size: 18, color: p.textMuted),
          const SizedBox(width: 8),
          Expanded(
            child: Text('Scanning a barcode adds that item directly',
                style: ui(size: 12.5, weight: FontWeight.w600, color: p.textMuted)),
          ),
        ],
      ),
    ];
  }

  /// One design / flavour in the row above the size keypad; tapping it narrows the keys.
  Widget _designChip(AstraPalette p, _DesignOption design) {
    final on = design.value == _design;
    final fg = on ? Colors.white : (design.stock <= 0 ? p.textMuted : p.ink);
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(99),
        onTap: () => _pickDesign(design.value),
        child: Ink(
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 11),
          decoration: BoxDecoration(
            color: on ? p.primary : p.cardSolid,
            borderRadius: BorderRadius.circular(99),
            border: Border.all(color: on ? p.primary : p.cardBorder, width: 1.5),
          ),
          child: Text(design.value, style: ui(size: 15, weight: FontWeight.w800, color: fg)),
        ),
      ),
    );
  }

  TextStyle _keyLabelStyle(AstraPalette p, _Metrics m, bool wide) => ui(
        size: wide ? m.wideKeyFont : m.keyFont,
        weight: wide ? FontWeight.w800 : FontWeight.w900,
        color: p.ink,
        height: 1.05,
      );

  /// Room for a key's name: the tile less its padding and swatch.
  double _labelRoom(_Metrics m, _Key key, double width) =>
      width - 12 - (key.colour != null ? (m == _Metrics.phone ? 24 : 30) + 10 : 0);

  /// One line for every key's name, or two when any of them ("Mango Passion")
  /// will not fit on one — the whole pad grows together so the rows stay even,
  /// as the web keys wrap instead of cutting the name off.
  int _labelLines(List<_Key> keys, double width, TextStyle style, _Metrics m) {
    final scaler = MediaQuery.textScalerOf(context);
    for (final key in keys) {
      final painter = TextPainter(
        text: TextSpan(text: key.label, style: style),
        maxLines: 1,
        textDirection: Directionality.of(context),
        textScaler: scaler,
      )..layout(maxWidth: _labelRoom(m, key, width));
      final tooLong = painter.didExceedMaxLines;
      painter.dispose();
      if (tooLong) return 2;
    }
    return 1;
  }

  Widget _keyTile(AstraPalette p, _Metrics m, _Key key, double width, {required bool wide, int lines = 1}) {
    final variant = key.variant;
    final available = variant != null;
    final out = available && variant.totalStock <= 0;
    final current = _current;
    final String note;
    if (!available) {
      note = current == null ? '—' : 'Not in ${current.name}';
    } else if (out) {
      note = 'Out of stock';
    } else {
      note = '${qtyLabel(variant.totalStock)} left${_price.range ? ' · ${Money.plain(variant.mrp)}' : ''}';
    }
    final radius = BorderRadius.circular(m.keyRadius);
    final labelStyle = _keyLabelStyle(p, m, wide);
    final extraLine = lines > 1 ? MediaQuery.textScalerOf(context).scale(labelStyle.fontSize ?? 17) * 1.05 : 0.0;
    final tile = SizedBox(
      width: width,
      height: m.keyHeight + extraLine,
      child: Material(
        color: Colors.transparent,
        child: Ink(
          decoration: BoxDecoration(
            color: p.cardSolid,
            borderRadius: radius,
            // An unavailable size gets a dashed outline instead (painted below).
            border: available ? Border.all(color: p.cardBorder, width: 1.5) : null,
            boxShadow: available ? context.astraTheme.softShadow : null,
          ),
          child: InkWell(
            borderRadius: radius,
            onTap: available ? () => Navigator.of(context).pop(variant) : null,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 6),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  if (key.colour != null) ...[
                    _swatch(key.colour!, m == _Metrics.phone ? 24 : 30),
                    const SizedBox(width: 10),
                  ],
                  Flexible(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: key.colour != null ? CrossAxisAlignment.start : CrossAxisAlignment.center,
                      children: [
                        Text(key.label, maxLines: lines, overflow: TextOverflow.ellipsis,
                            textAlign: key.colour != null ? TextAlign.start : TextAlign.center, style: labelStyle),
                        const SizedBox(height: 3),
                        Text(note, maxLines: 1, overflow: TextOverflow.ellipsis, textAlign: TextAlign.center,
                            style: ui(
                              size: m.noteFont,
                              weight: FontWeight.w700,
                              color: !available ? p.textMuted : (out ? AstraPalette.danger : AstraPalette.success),
                            )),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
    if (available) return _Press(child: tile);
    return Opacity(
      opacity: 0.3,
      child: CustomPaint(foregroundPainter: _DashedOutline(color: p.textMuted, radius: m.keyRadius), child: tile),
    );
  }

  // ---- pieces ---------------------------------------------------------------

  /// A product photo, resolved onto the reachable server like every catalog image.
  Widget _photo(String url, {required BoxFit fit, required Widget fallback}) {
    final cfg = context.read<AuthCubit>().config;
    return Image(
      image: OfflineImage.provider(cfg.assetUrl(url), headers: cfg.assetHeaders, cacheWidth: decodeWidthFor(context, 320)),
      fit: fit,
      gaplessPlayback: true,
      errorBuilder: (_, __, ___) => fallback,
    );
  }

  /// A colour dot; "No colour" is an empty ring with a slash through it.
  Widget _swatch(Product v, double size) {
    if (v.color.isEmpty) {
      return SizedBox(width: size, height: size, child: CustomPaint(painter: _NoColour(context.astra.textMuted)));
    }
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: variantColour(v.colorHex) ?? const Color(0xFF9CA3AF),
        shape: BoxShape.circle,
        border: Border.all(color: Colors.black.withValues(alpha: 0.18)),
      ),
    );
  }
}

/// Shrinks a tile a little while it is held, like the web keys' `:active` state.
class _Press extends StatefulWidget {
  const _Press({required this.child});
  final Widget child;
  @override
  State<_Press> createState() => _PressState();
}

class _PressState extends State<_Press> {
  bool _down = false;

  void _set(bool down) {
    if (_down != down) setState(() => _down = down);
  }

  @override
  Widget build(BuildContext context) {
    return Listener(
      onPointerDown: (_) => _set(true),
      onPointerUp: (_) => _set(false),
      onPointerCancel: (_) => _set(false),
      child: AnimatedScale(scale: _down ? 0.97 : 1, duration: const Duration(milliseconds: 100), child: widget.child),
    );
  }
}

/// The dashed rounded outline of a size this colour does not come in.
class _DashedOutline extends CustomPainter {
  const _DashedOutline({required this.color, required this.radius});
  final Color color;
  final double radius;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.5;
    final path = Path()..addRRect(RRect.fromRectAndRadius(Offset.zero & size, Radius.circular(radius)).deflate(0.75));
    for (final metric in path.computeMetrics()) {
      for (double d = 0; d < metric.length; d += 9) {
        canvas.drawPath(metric.extractPath(d, d + 5), paint);
      }
    }
  }

  @override
  bool shouldRepaint(_DashedOutline old) => old.color != color || old.radius != radius;
}

/// "No colour": a ring with a diagonal slash, as on the web picker.
class _NoColour extends CustomPainter {
  const _NoColour(this.color);
  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final r = size.shortestSide / 2;
    final centre = size.center(Offset.zero);
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = (r * 0.09).clamp(1.5, 3);
    canvas.drawCircle(centre, r - paint.strokeWidth / 2, paint);
    final d = (r - paint.strokeWidth) * 0.7071;
    canvas.drawLine(centre + Offset(-d, d), centre + Offset(d, -d), paint..strokeWidth = r * 0.12);
  }

  @override
  bool shouldRepaint(_NoColour old) => old.color != color;
}

/// Sizes small to large: numbers by value, then letters and the rest.
List<String> sortedSizes(Iterable<String> sizes) {
  final numeric = RegExp(r'^\d+(\.\d+)?$');
  return sizes.toList()
    ..sort((a, b) {
      final na = numeric.hasMatch(a), nb = numeric.hasMatch(b);
      if (na && nb) return double.parse(a).compareTo(double.parse(b));
      if (na != nb) return na ? -1 : 1;
      return a.compareTo(b);
    });
}

/// A "#rrggbb" code as a [Color], or null when it is not one.
Color? variantColour(String hex) {
  final match = RegExp(r'^#([0-9a-fA-F]{6})$').firstMatch(hex.trim());
  return match == null ? null : Color(int.parse('ff${match.group(1)}', radix: 16));
}

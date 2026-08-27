import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../shared/domain/models/index.dart';
import '../../../shared/logic/product_list_cubit/product_list_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../l10n/app_localizations.dart';

/// The refinements that sit beside the grid on tablet and inside a sheet on
/// phone. Same widget both times — a filter that behaves differently depending
/// on where it is drawn is a filter people stop trusting.
class FilterPanel extends StatelessWidget {
  const FilterPanel({
    super.key,
    required this.state,
    required this.onChanged,
    this.showHeading = true,
  });

  final ProductListState state;
  final void Function(ProductFilters) onChanged;
  final bool showHeading;

  @override
  Widget build(BuildContext context) {
    final filters = state.filters;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (showHeading) ColumnHeading(L.of(context).refine),
        if (state.categories.isNotEmpty) ...[
          _MiniHeading(
            L.of(context).department,
            trailing: _selectedCategory(state)?.name,
            onClear: filters.mainCategoryId == null
                ? null
                : () => onChanged(filters.copyWith(clearCategory: true)),
          ),
          _CategoryRow(
            categories: state.categories,
            selectedId: filters.mainCategoryId,
            onTap: (id) => onChanged(
              id == filters.mainCategoryId
                  ? filters.copyWith(clearCategory: true)
                  : filters.copyWith(mainCategoryId: id),
            ),
          ),
          const SizedBox(height: 18),
        ],
        if (state.colors.isNotEmpty) ...[
          _MiniHeading(
            L.of(context).colour,
            trailing: filters.color,
            onClear: filters.color == null
                ? null
                : () => onChanged(filters.copyWith(clearColor: true)),
          ),
          _ColorRow(
            colors: state.colors.map((c) => c.color).toList(),
            selected: filters.color,
            onTap: (value) => onChanged(
              value == filters.color
                  ? filters.copyWith(clearColor: true)
                  : filters.copyWith(color: value),
            ),
          ),
          const SizedBox(height: 18),
        ],
        _MiniHeading(
          L.of(context).price,
          trailing: _priceLabel(filters),
          onClear: filters.minPrice == null && filters.maxPrice == null
              ? null
              : () => onChanged(filters.copyWith(clearPrice: true)),
        ),
        _PriceBands(
          filters: filters,
          onPick: (min, max) => onChanged(
            filters.minPrice == min && filters.maxPrice == max
                ? filters.copyWith(clearPrice: true)
                : ProductFilters(
                    mainCategoryId: filters.mainCategoryId,
                    brandId: filters.brandId,
                    size: filters.size,
                    color: filters.color,
                    search: filters.search,
                    minPrice: min,
                    maxPrice: max,
                    inStockOnly: filters.inStockOnly,
                    spinOnly: filters.spinOnly,
                    sortBy: filters.sortBy,
                    sortDirection: filters.sortDirection,
                  ),
          ),
        ),
        const SizedBox(height: 6),
        // "In stock at this store" is deliberately not here: it is global to
        // the whole funnel and lives in the top bar. Two controls for one
        // filter is how a filter ends up disagreeing with itself.
        _Toggle(
          label: L.of(context).has360,
          value: filters.spinOnly,
          onChanged: (v) => onChanged(filters.copyWith(spinOnly: v)),
        ),
      ],
    );
  }

  /// The chosen department, if it is still one of the offered ones — a size
  /// change can re-scope the list out from under a selection.
  static CategoryOption? _selectedCategory(ProductListState state) {
    final id = state.filters.mainCategoryId;
    if (id == null) return null;
    for (final c in state.categories) {
      if (c.id == id) return c;
    }
    return null;
  }

  static String? _priceLabel(ProductFilters f) {
    if (f.minPrice == null && f.maxPrice == null) return null;
    if (f.maxPrice == null) return '${f.minPrice!.round()}+';
    return '${f.minPrice?.round() ?? 0}–${f.maxPrice!.round()}';
  }
}

class _MiniHeading extends StatelessWidget {
  const _MiniHeading(this.text, {this.trailing, this.onClear});

  final String text;
  final String? trailing;
  final VoidCallback? onClear;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Expanded(
            child: Text(
              text.toUpperCase(),
              style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
            ),
          ),
          if (trailing != null)
            Text(
              trailing!.toUpperCase(),
              style: PearlText.micro.copyWith(fontSize: 8.5, color: p.ink),
            ),
          if (onClear != null) ...[
            const SizedBox(width: 8),
            InkWell(
              onTap: onClear,
              child: Icon(Icons.close, size: 13, color: p.faint),
            ),
          ],
        ],
      ),
    );
  }
}

/// Colour names, not swatches. The catalogue stores a free-text colour and
/// guessing a hex from "Photon Blue" would be a lie drawn in the brand's own
/// palette — worse than the word.
class _ColorRow extends StatelessWidget {
  const _ColorRow({required this.colors, required this.selected, required this.onTap});

  final List<String> colors;
  final String? selected;
  final void Function(String) onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Wrap(
      spacing: 7,
      runSpacing: 7,
      children: [
        for (final color in colors.take(12))
          InkWell(
            onTap: () => onTap(color),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
              decoration: BoxDecoration(
                color: color == selected ? p.accent : null,
                border: Border.all(color: color == selected ? p.accent : p.line),
              ),
              child: Text(
                color.toUpperCase(),
                style: PearlText.micro.copyWith(
                  fontSize: 8.5,
                  letterSpacing: 1.6,
                  color: color == selected ? p.accentInk : p.ink,
                ),
              ),
            ),
          ),
      ],
    );
  }
}

/// Departments as name-and-count chips.
///
/// The count comes along because it is the difference between a list of
/// departments and a list of departments worth tapping: the funnel already
/// narrowed to a size, so "Shoes 114" and "Apparel 2" are not equivalent
/// choices even though they look it.
class _CategoryRow extends StatelessWidget {
  const _CategoryRow({
    required this.categories,
    required this.selectedId,
    required this.onTap,
  });

  final List<CategoryOption> categories;
  final int? selectedId;
  final void Function(int) onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Wrap(
      spacing: 7,
      runSpacing: 7,
      children: [
        for (final category in categories)
          InkWell(
            onTap: () => onTap(category.id),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
              decoration: BoxDecoration(
                color: category.id == selectedId ? p.accent : null,
                border: Border.all(
                  color: category.id == selectedId ? p.accent : p.line,
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    category.name.toUpperCase(),
                    style: PearlText.micro.copyWith(
                      fontSize: 8.5,
                      letterSpacing: 1.6,
                      color: category.id == selectedId ? p.accentInk : p.ink,
                    ),
                  ),
                  const SizedBox(width: 7),
                  Text(
                    '${category.productCount}',
                    style: PearlText.micro.copyWith(
                      fontSize: 8.5,
                      letterSpacing: .4,
                      color: category.id == selectedId
                          ? p.accentInk.withValues(alpha: .7)
                          : p.faint,
                    ),
                  ),
                ],
              ),
            ),
          ),
      ],
    );
  }
}

class _PriceBands extends StatelessWidget {
  const _PriceBands({required this.filters, required this.onPick});

  final ProductFilters filters;
  final void Function(double? min, double? max) onPick;

  /// Bounds only — the label is built from them so it can be translated and
  /// so the numbers are never restated in two places.
  static const List<(double?, double?)> _bands = [
    (null, 300),
    (300, 600),
    (600, 900),
    (900, null),
  ];

  static String _bandLabel(L t, double? min, double? max) {
    if (min == null) return t.underAmount('${max!.round()}');
    if (max == null) return t.over('${min.round()}');
    return t.band('${min.round()}', '${max.round()}');
  }

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Wrap(
      spacing: 7,
      runSpacing: 7,
      children: [
        for (final band in _bands)
          InkWell(
            onTap: () => onPick(band.$1, band.$2),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8),
              decoration: BoxDecoration(
                color: filters.minPrice == band.$1 && filters.maxPrice == band.$2
                    ? p.accent
                    : null,
                border: Border.all(
                  color: filters.minPrice == band.$1 && filters.maxPrice == band.$2
                      ? p.accent
                      : p.line,
                ),
              ),
              child: Text(
                _bandLabel(L.of(context), band.$1, band.$2).toUpperCase(),
                style: PearlText.micro.copyWith(
                  fontSize: 8.5,
                  letterSpacing: 1.4,
                  color: filters.minPrice == band.$1 && filters.maxPrice == band.$2
                      ? p.accentInk
                      : p.ink,
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _Toggle extends StatelessWidget {
  const _Toggle({
    required this.label,
    required this.value,
    required this.onChanged,
  });

  final String label;
  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return InkWell(
      onTap: () => onChanged(!value),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 11),
        child: Row(
          children: [
            Expanded(
              child: Text(label,
                  style: PearlText.label.copyWith(color: p.ink, fontSize: 11.5)),
            ),
            const SizedBox(width: 10),
            Container(
              width: 36,
              height: 20,
              alignment: value ? Alignment.centerRight : Alignment.centerLeft,
              padding: const EdgeInsets.all(2.5),
              decoration: BoxDecoration(
                color: value ? p.accent : null,
                border: Border.all(color: value ? p.accent : p.line),
              ),
              child: Container(
                width: 14,
                height: 14,
                color: value ? p.accentInk : p.faint,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Phone: the same panel in a sheet.
Future<void> showFilterSheet(BuildContext context, ProductListCubit cubit) {
  cubit
    ..loadColors()
    ..loadCategories();
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: context.pearl.bg,
    builder: (sheetContext) => BlocProvider<ProductListCubit>.value(
      value: cubit,
      child: const _FilterSheet(),
    ),
  );
}

class _FilterSheet extends StatelessWidget {
  const _FilterSheet();

  @override
  Widget build(BuildContext context) {
    final cubit = context.read<ProductListCubit>();
    final state = context.watch<ProductListCubit>().state;
    return SafeArea(
      child: ConstrainedBox(
        constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * .8),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(PearlMetrics.pad),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            mainAxisSize: MainAxisSize.min,
            children: [
              SectionHeading(L.of(context).refine),
              FilterPanel(
                state: state,
                showHeading: false,
                onChanged: cubit.apply,
              ),
              const SizedBox(height: 22),
              PearlButton(
                label: L.of(context).showResults,
                onTap: () => Navigator.of(context).pop(),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

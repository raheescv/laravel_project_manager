import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

import '../../domain/models/catalog_models.dart';
import '../../logic/catalog_options_cubit/catalog_options_cubit.dart';
import '../../logic/catalog_view_cubit/catalog_view_cubit.dart';
import 'catalog_controls.dart';
import 'column_options.dart';
import 'option_picker_sheet.dart';

/// Search, the Columns button (table only) and the Table | Gallery switch.
/// Sorting lives on the column headers, not here.
class CatalogToolbar extends StatefulWidget {
  const CatalogToolbar({super.key});

  @override
  State<CatalogToolbar> createState() => _CatalogToolbarState();
}

class _CatalogToolbarState extends State<CatalogToolbar> {
  late final _search = TextEditingController(text: context.read<CatalogViewCubit>().state.query.search);

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final cubit = context.read<CatalogViewCubit>();
    final wide = context.isTablet;
    return BlocBuilder<CatalogViewCubit, CatalogViewState>(
      buildWhen: (a, b) => a.gallery != b.gallery || a.optionalOn != b.optionalOn,
      builder: (context, state) => Container(
        padding: const EdgeInsets.all(6),
        decoration: BoxDecoration(color: p.cardSolid, borderRadius: BorderRadius.circular(15), boxShadow: context.astraTheme.softShadow),
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: _search,
                onChanged: cubit.search,
                textInputAction: TextInputAction.search,
                style: ui(size: 13.5, weight: FontWeight.w600, color: p.ink),
                decoration: InputDecoration(
                  hintText: 'Search ${cubit.type.plural.toLowerCase()}',
                  hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
                  prefixIcon: Icon(Icons.search, size: 19, color: p.textMuted),
                  prefixIconConstraints: const BoxConstraints(minWidth: 38),
                  isDense: true,
                  filled: true,
                  fillColor: p.tint,
                  contentPadding: const EdgeInsets.symmetric(vertical: 10),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(11), borderSide: BorderSide.none),
                ),
              ),
            ),
            if (!state.gallery) ...[
              const SizedBox(width: 6),
              _ColumnsButton(count: state.optionalOn, showLabel: wide),
            ],
            const SizedBox(width: 6),
            _ViewSwitch(gallery: state.gallery, showLabels: wide, onChanged: cubit.setGallery),
          ],
        ),
      ),
    );
  }
}

class _ColumnsButton extends StatelessWidget {
  const _ColumnsButton({required this.count, required this.showLabel});

  final int count;
  final bool showLabel;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: () => showColumnOptions(context),
      child: Container(
        height: 38,
        padding: const EdgeInsets.symmetric(horizontal: 10),
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(11)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.view_column_outlined, size: 18, color: p.textSecondary),
            if (showLabel) ...[const SizedBox(width: 5), Text('Columns', style: ui(size: 12.5, weight: FontWeight.w700, color: p.textSecondary))],
            const SizedBox(width: 5),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
              decoration: BoxDecoration(color: p.primary, borderRadius: BorderRadius.circular(10)),
              child: Text('$count', style: ui(size: 10.5, weight: FontWeight.w800, color: Colors.white)),
            ),
          ],
        ),
      ),
    );
  }
}

class _ViewSwitch extends StatelessWidget {
  const _ViewSwitch({required this.gallery, required this.showLabels, required this.onChanged});

  final bool gallery;
  final bool showLabels;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    Widget option(bool isGallery, IconData icon, String label) {
      final on = gallery == isGallery;
      return GestureDetector(
        onTap: () => onChanged(isGallery),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          height: 32,
          padding: EdgeInsets.symmetric(horizontal: showLabels ? 11 : 8),
          decoration: BoxDecoration(gradient: on ? p.primaryGradient : null, borderRadius: BorderRadius.circular(9)),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 17, color: on ? Colors.white : p.textSecondary),
              if (showLabels) ...[
                const SizedBox(width: 5),
                Text(label, style: ui(size: 12.5, weight: FontWeight.w800, color: on ? Colors.white : p.textSecondary)),
              ],
            ],
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(11)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        option(false, Icons.table_rows_outlined, 'Table'),
        option(true, Icons.grid_view, 'Gallery'),
      ]),
    );
  }
}

/// The web table's filters as chips — department, main category, brand
/// (products) and status. Each opens a click-and-go picker.
class CatalogFilterBar extends StatelessWidget {
  const CatalogFilterBar({super.key});

  static const _statuses = [CatalogOption(id: 1, name: 'Active'), CatalogOption(id: 2, name: 'Disabled')];

  @override
  Widget build(BuildContext context) {
    final cubit = context.read<CatalogViewCubit>();
    final options = context.select<CatalogOptionsCubit, CatalogOptions>((c) => c.state.options);
    final query = context.select<CatalogViewCubit, CatalogQuery>((c) => c.state.query);

    String? nameOf(List<CatalogOption> list, int? id) {
      for (final o in list) {
        if (o.id == id) return o.name;
      }
      return null;
    }

    Future<void> pick(String title, List<CatalogOption> list, int? selected, String all, void Function(int? id) apply) async {
      final result = await showOptionPicker(context, title: title, options: list, selectedId: selected, clearLabel: all);
      if (result != null) apply(result.ref?.id);
    }

    Widget chip(String label, String? value, VoidCallback onTap) =>
        ActionChipButton(label: value ?? label, active: value != null, trailingIcon: Icons.expand_more, onTap: onTap);

    final statusId = switch (query.status) { 'active' => 1, 'disabled' => 2, _ => null };
    final chips = <Widget>[
      if (query.activeFilters > 0)
        ActionChipButton(label: 'Clear', icon: Icons.close, badge: query.activeFilters, onTap: cubit.clearFilters),
      chip('Department', nameOf(options.departments, query.departmentId),
          () => pick('Department', options.departments, query.departmentId, 'All departments',
              (id) => cubit.setFilters((q) => q.copyWith(departmentId: () => id)))),
      chip('Main Category', nameOf(options.mainCategories, query.mainCategoryId),
          () => pick('Main category', options.mainCategories, query.mainCategoryId, 'All categories',
              (id) => cubit.setFilters((q) => q.copyWith(mainCategoryId: () => id)))),
      if (cubit.type.isProduct)
        chip('Brand', nameOf(options.brands, query.brandId),
            () => pick('Brand', options.brands, query.brandId, 'All brands', (id) => cubit.setFilters((q) => q.copyWith(brandId: () => id)))),
      chip('Status', nameOf(_statuses, statusId),
          () => pick('Status', _statuses, statusId, 'Any status',
              (id) => cubit.setFilters((q) => q.copyWith(status: () => switch (id) { 1 => 'active', 2 => 'disabled', _ => null })))),
    ];
    return SizedBox(
      height: 36,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: chips.length,
        separatorBuilder: (_, __) => const SizedBox(width: 7),
        itemBuilder: (_, i) => chips[i],
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

import '../../logic/catalog_view_cubit/catalog_view_cubit.dart';
import 'catalog_controls.dart';

/// Which columns the table shows — a bottom sheet on a phone, a popover under
/// the toolbar on a tablet. Toggles apply live; sorting stays on the headers.
Future<void> showColumnOptions(BuildContext context) {
  final cubit = context.read<CatalogViewCubit>();
  final body = BlocProvider.value(value: cubit, child: const _ColumnOptions());
  final p = context.astra;
  if (context.isTablet) {
    return showDialog<void>(
      context: context,
      barrierColor: Colors.black.withValues(alpha: 0.18),
      builder: (_) => Align(
        alignment: Alignment.topRight,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(0, 210, 28, 24),
          child: Material(
            color: p.cardSolid,
            elevation: 12,
            shadowColor: Colors.black38,
            borderRadius: BorderRadius.circular(18),
            child: ConstrainedBox(constraints: const BoxConstraints(maxWidth: 340, maxHeight: 480), child: body),
          ),
        ),
      ),
    );
  }
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    backgroundColor: p.cardSolid,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
    builder: (_) => ConstrainedBox(constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * 0.78), child: body),
  );
}

class _ColumnOptions extends StatelessWidget {
  const _ColumnOptions();

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final cubit = context.read<CatalogViewCubit>();
    return BlocBuilder<CatalogViewCubit, CatalogViewState>(
      builder: (context, state) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (!context.isTablet) ...[
            const SizedBox(height: 9),
            Container(width: 38, height: 4, decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3))),
          ],
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 14, 12, 2),
            child: Row(
              children: [
                Expanded(child: Text('Columns', style: ui(size: 16, weight: FontWeight.w800, color: p.ink))),
                TextButton(onPressed: cubit.resetColumns, child: Text('Reset to defaults', style: ui(size: 12.5, weight: FontWeight.w700, color: p.primary))),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 6),
            child: Text('Tap a column header in the table to sort by it.',
                style: ui(size: 11.5, weight: FontWeight.w500, color: p.textMuted)),
          ),
          Flexible(
            child: ListView.separated(
              shrinkWrap: true,
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 8),
              itemCount: state.columns.length,
              separatorBuilder: (_, __) => Divider(height: 1, color: p.hairline),
              itemBuilder: (_, i) {
                final c = state.columns[i];
                if (c.pinned) {
                  return Padding(
                    padding: const EdgeInsets.symmetric(vertical: 11),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(c.label, style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                              Text('Always shown · pinned left', style: ui(size: 11, weight: FontWeight.w500, color: p.textMuted)),
                            ],
                          ),
                        ),
                        Icon(Icons.lock_outline, size: 18, color: p.textMuted),
                      ],
                    ),
                  );
                }
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: ToggleRow(
                    title: c.label,
                    subtitle: c.isImage ? 'Thumbnail beside the name' : null,
                    value: state.visible.contains(c.key),
                    onChanged: (_) => cubit.toggleColumn(c.key),
                  ),
                );
              },
            ),
          ),
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 14),
              child: SizedBox(
                width: double.infinity,
                height: 44,
                child: FilledButton(
                  style: FilledButton.styleFrom(backgroundColor: p.primary, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(13))),
                  onPressed: () => Navigator.pop(context),
                  child: Text('Done', style: ui(size: 14, weight: FontWeight.w800, color: Colors.white)),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

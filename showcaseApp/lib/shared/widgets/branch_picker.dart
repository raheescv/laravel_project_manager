import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../logic/branch_cubit/branch_cubit.dart';
import '../utils/components/theme/pearl_theme.dart';
import 'pearl_widgets.dart';
import '../../l10n/app_localizations.dart';

/// Click-and-go: tapping a store applies it and closes. There is no Save button
/// anywhere in this app.
Future<void> showBranchPicker(BuildContext context) {
  final cubit = context.read<BranchCubit>();
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: context.pearl.bg,
    builder: (sheetContext) => BlocProvider<BranchCubit>.value(
      value: cubit,
      child: const _BranchSheet(),
    ),
  );
}

class _BranchSheet extends StatelessWidget {
  const _BranchSheet();

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final state = context.watch<BranchCubit>().state;
    return SafeArea(
      child: ConstrainedBox(
        constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * .7),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(
                  PearlMetrics.pad, PearlMetrics.pad, PearlMetrics.pad, 4),
              child: SectionHeading(L.of(context).chooseStore),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: PearlMetrics.pad),
              child: Text(
                L.of(context).storeHint,
                style: PearlText.body(11.5).copyWith(color: p.muted),
              ),
            ),
            const SizedBox(height: 14),
            Flexible(
              child: ListView.builder(
                shrinkWrap: true,
                padding: const EdgeInsets.symmetric(horizontal: PearlMetrics.pad),
                // One row longer than the list: "all stores" leads, because
                // the question it answers — does the company have this at all —
                // is the one a customer asks before they ask where.
                itemCount: state.branches.length + 1,
                itemBuilder: (context, i) {
                  if (i == 0) {
                    return _BranchRow(
                      label: L.of(context).allStores,
                      selected: state.showingAll,
                      onTap: () {
                        context.read<BranchCubit>().selectAll();
                        Navigator.of(context).pop();
                      },
                    );
                  }
                  final branch = state.branches[i - 1];
                  return _BranchRow(
                    label: branch.label,
                    detail: branch.mobile,
                    selected: !state.showingAll && branch.id == state.selected?.id,
                    onTap: () {
                      context.read<BranchCubit>().select(branch);
                      Navigator.of(context).pop();
                    },
                  );
                },
              ),
            ),
            const SizedBox(height: PearlMetrics.pad),
          ],
        ),
      ),
    );
  }
}

class _BranchRow extends StatelessWidget {
  const _BranchRow({
    required this.label,
    required this.selected,
    required this.onTap,
    this.detail,
  });

  final String label;

  /// The shop's phone number. "All stores" has none, which is the whole reason
  /// this row takes a label and a detail rather than a Branch.
  final String? detail;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 15),
        decoration: BoxDecoration(
          border: Border(bottom: BorderSide(color: p.line)),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label.toUpperCase(),
                    style: PearlText.productName(11).copyWith(color: p.ink),
                  ),
                  if (detail != null && detail!.isNotEmpty) ...[
                    const SizedBox(height: 5),
                    Text(detail!, style: PearlText.micro.copyWith(color: p.faint)),
                  ],
                ],
              ),
            ),
            if (selected) Icon(Icons.check, size: 17, color: p.ink),
          ],
        ),
      ),
    );
  }
}

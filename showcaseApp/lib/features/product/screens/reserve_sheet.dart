import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../shared/domain/constants/global_variables.dart';
import '../../../shared/domain/helpers/formatters.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/logic/branch_cubit/branch_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/local_storage/local_storage_service.dart';
import '../../../shared/widgets/pearl_widgets.dart';

/// "Reserve in store" is a **local intent only**.
///
/// It writes a note to this device and shows the customer which store to go to
/// and what to ask for. It deliberately posts nothing: there is no reservation
/// endpoint, and the showcase must never create a sale. If reservations ever
/// become real, this is the one place that changes.
Future<void> showReserveSheet(
  BuildContext context, {
  required Product product,
  String? size,
}) {
  final branch = context.read<BranchCubit>().state.selected;
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: context.pearl.bg,
    isScrollControlled: true,
    builder: (_) => _ReserveSheet(product: product, size: size, branch: branch),
  );
}

class _ReserveSheet extends StatelessWidget {
  const _ReserveSheet({required this.product, required this.size, required this.branch});

  final Product product;
  final String? size;
  final Branch? branch;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final line = product.inventories
        .where((i) => i.branchId == branch?.id)
        .fold<int>(0, (sum, i) => sum + i.available);

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(PearlMetrics.pad),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const SectionHeading('Reserve in store'),
            Text(
              'We will note this on the tablet and tell a colleague. Nothing is '
              'charged and nothing is held online.',
              style: PearlText.body(12).copyWith(color: p.muted),
            ),
            const SizedBox(height: 20),
            _Line(label: 'Product', value: product.name),
            _Line(label: 'Code', value: product.code),
            if (size != null) _Line(label: 'Size', value: size!),
            _Line(label: 'Price', value: money(product.mrp)),
            _Line(label: 'Store', value: branch?.label ?? '—'),
            if (branch != null && branch!.mobile.isNotEmpty)
              _Line(label: 'Phone', value: branch!.mobile),
            _Line(
              label: 'On the shelf',
              value: line > 0 ? '$line in this store' : 'none in this store',
            ),
            const SizedBox(height: 22),
            PearlButton(
              label: 'Note it and close',
              onTap: () async {
                await serviceLocator<LocalStorageService>().addReservation(
                  productId: product.id,
                  productName: product.name,
                  size: size,
                  branchId: branch?.id,
                );
                if (context.mounted) Navigator.of(context).pop();
              },
            ),
            const SizedBox(height: 8),
            PearlButton(
              label: 'Cancel',
              ghost: true,
              onTap: () => Navigator.of(context).pop(),
            ),
          ],
        ),
      ),
    );
  }
}

class _Line extends StatelessWidget {
  const _Line({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 11),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.line))),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(
              label.toUpperCase(),
              style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
            ),
          ),
          Expanded(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: PearlText.label.copyWith(color: p.ink, fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }
}

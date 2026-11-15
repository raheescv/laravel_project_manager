import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:printing/printing.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

import '../../logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import 'atelier_parts.dart';

/// `handover-checklist-<unit>.pdf`, with the unit reduced to filename-safe
/// characters.
String checklistPdfFileName(String unit) {
  final safe = unit.trim().replaceAll(RegExp(r'[^A-Za-z0-9._-]+'), '-').replaceAll(RegExp(r'-+'), '-');
  return 'handover-checklist-${safe.isEmpty ? 'unit' : safe}.pdf';
}

/// The PDF action for a hand-over: fetches the server's "Unit Handover &
/// Snagging" form and opens it in a print / share preview. Shows a spinner
/// while downloading and a toast on failure; the rest of the screen stays live.
class ChecklistPdfButton extends StatelessWidget {
  const ChecklistPdfButton({super.key, this.clearBottomBar = false});

  /// The host docks an action bar at the bottom — keep the error toast off it.
  final bool clearBottomBar;

  Future<void> _open(BuildContext context) async {
    final cubit = context.read<ChecklistDetailCubit>();
    final unit = cubit.state.detail?.job.unit ?? '';
    final bytes = await cubit.fetchPdf();
    if (!context.mounted) return;
    if (bytes == null) {
      showChecklistToast(context, cubit.state.actionError ?? 'Could not download the PDF.', clearBottomBar: clearBottomBar);
      return;
    }
    await Navigator.of(context, rootNavigator: true).push(MaterialPageRoute<void>(
      fullscreenDialog: true,
      builder: (_) => ChecklistPdfPreviewScreen(bytes: bytes, fileName: checklistPdfFileName(unit)),
    ));
  }

  @override
  Widget build(BuildContext context) {
    final loading = context.select<ChecklistDetailCubit, bool>((c) => c.state.pdfLoading);
    return AtelierRoundButton(
      icon: Icons.picture_as_pdf_outlined,
      tooltip: 'Hand-over PDF',
      busy: loading,
      onTap: () => _open(context),
    );
  }
}

/// Print / share preview of the hand-over PDF. On a tablet the page is held to
/// a paper-width column — `PdfPreview` scales the page to the width it gets, so
/// edge-to-edge would blow an A4 up across the whole window.
class ChecklistPdfPreviewScreen extends StatelessWidget {
  const ChecklistPdfPreviewScreen({super.key, required this.bytes, required this.fileName});

  final Uint8List bytes;
  final String fileName;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Scaffold(
      backgroundColor: p.canvas,
      appBar: AppBar(
        backgroundColor: p.primary,
        foregroundColor: Colors.white,
        title: Text('Hand-over PDF', style: ui(size: 16, weight: FontWeight.w800, color: Colors.white)),
      ),
      body: LayoutBuilder(builder: (context, c) {
        const paper = 680.0;
        final side = !context.isTablet || c.maxWidth <= paper + 40 ? 0.0 : (c.maxWidth - paper) / 2;
        return PdfPreview(
          build: (_) async => bytes,
          useActions: true,
          canChangePageFormat: false,
          canChangeOrientation: false,
          canDebug: false,
          pdfFileName: fileName,
          padding: EdgeInsets.symmetric(horizontal: side, vertical: 12),
          scrollViewDecoration: BoxDecoration(color: p.canvas),
          actionBarTheme: PdfActionBarTheme(backgroundColor: p.primary, iconColor: Colors.white),
        );
      }),
    );
  }
}

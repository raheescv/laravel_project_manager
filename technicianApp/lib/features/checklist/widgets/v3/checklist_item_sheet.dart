import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/checklist_models.dart';
import '../../logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import 'atelier_parts.dart';
import 'photo_source_sheet.dart';
import 'photo_viewer.dart';

const _quickComments = ['Scratched', 'Not working', 'Missing', 'Stained', 'Loose'];

/// UpdateLineRequest's `comment` limit.
const _commentMax = 255;

/// Opens the item sheet (C4) over a room, stepping through [lineIds] — a
/// bottom sheet on phones, a centered dialog on tablets.
Future<void> showChecklistItemSheet(
  BuildContext context, {
  required String roomName,
  required List<int> lineIds,
  required int initialIndex,
}) {
  final cubit = context.read<ChecklistDetailCubit>();
  if (context.isTablet) {
    return showDialog<void>(
      context: context,
      builder: (_) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(24),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 640),
          child: BlocProvider.value(
            value: cubit,
            child: ChecklistItemSheet(
              roomName: roomName,
              lineIds: lineIds,
              initialIndex: initialIndex.clamp(0, lineIds.length - 1),
              dialog: true,
            ),
          ),
        ),
      ),
    );
  }
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => BlocProvider.value(
      value: cubit,
      child: ChecklistItemSheet(roomName: roomName, lineIds: lineIds, initialIndex: initialIndex.clamp(0, lineIds.length - 1)),
    ),
  );
}

/// Item inspection sheet: earlier-vs-now photos, status, qty, comment and (on
/// a damaged move-out) damage cost. Draft edits are PATCHed (changed keys
/// only) on "Save & next" and when stepping with the arrows.
class ChecklistItemSheet extends StatefulWidget {
  const ChecklistItemSheet({
    super.key,
    required this.roomName,
    required this.lineIds,
    required this.initialIndex,
    this.dialog = false,
  });
  final String roomName;
  final List<int> lineIds;
  final int initialIndex;

  /// Rendered inside a tablet dialog (rounded all round, no grabber, taller panes).
  final bool dialog;

  @override
  State<ChecklistItemSheet> createState() => _ChecklistItemSheetState();
}

class _ChecklistItemSheetState extends State<ChecklistItemSheet> {
  late int _index = widget.initialIndex;
  final _commentCtl = TextEditingController();
  final _costCtl = TextEditingController();
  final _costFocus = FocusNode();

  String? _status;
  num _qty = 1;
  bool _saving = false;
  bool _uploading = false;
  String? _error;

  /// Captured once: the sheet still needs it in [dispose], where reading
  /// the context is no longer allowed.
  late final ChecklistDetailCubit _cubit = context.read<ChecklistDetailCubit>();
  String get _phase => _cubit.state.detail?.phase ?? ChecklistPhase.moveOut;
  bool get _moveOut => _phase == ChecklistPhase.moveOut;
  ChecklistLine? get _line => _cubit.state.detail?.lineById(widget.lineIds[_index]);

  @override
  void initState() {
    super.initState();
    _seed();
  }

  @override
  void dispose() {
    // Swipe-down / tap-outside closes the sheet without "Save & next" — keep
    // the technician's edits anyway. The cubit belongs to the route, which
    // outlives the sheet; its writes already ignore a closed cubit.
    final line = _line;
    final changes = _changes();
    if (line != null && changes.isNotEmpty && !_cubit.isClosed && !(_cubit.state.detail?.sealed ?? true)) {
      _cubit.updateLine(line.id, changes);
    }
    _commentCtl.dispose();
    _costCtl.dispose();
    _costFocus.dispose();
    super.dispose();
  }

  /// Loads the current line's server values into the draft.
  void _seed() {
    final l = _line;
    _error = null;
    if (l == null) return;
    _status = l.statusFor(_phase);
    _qty = l.qty;
    _commentCtl.text = l.commentFor(_phase) ?? '';
    _costCtl.text = l.damageCost == 0 ? '' : l.damageCost.toStringAsFixed(2);
  }

  double get _cost => double.tryParse(_costCtl.text.trim().replaceAll(',', '')) ?? 0;

  /// Only the keys whose draft differs from the server line.
  Map<String, dynamic> _changes() {
    final l = _line;
    if (l == null) return const {};
    final out = <String, dynamic>{};
    final statusChanged = _status != l.statusFor(_phase);
    if (statusChanged) out['status'] = _status;
    final comment = _commentCtl.text.trim();
    if (comment != (l.commentFor(_phase) ?? '')) out['comment'] = comment.isEmpty ? null : comment;
    if (_qty != l.qty) out['qty'] = _qty;
    if (_moveOut) {
      if (_status == ChecklistLineStatus.notOk) {
        if ((_cost - l.damageCost).abs() > 0.004) out['damage_cost'] = _cost;
      } else if (statusChanged && l.damageCost != 0) {
        out['damage_cost'] = 0;
      }
    }
    return out;
  }

  Future<bool> _persist() async {
    final l = _line;
    final changes = _changes();
    if (l == null || changes.isEmpty) return true;
    setState(() {
      _saving = true;
      _error = null;
    });
    final ok = await _cubit.updateLine(l.id, changes);
    if (!mounted) return ok;
    setState(() {
      _saving = false;
      if (!ok) _error = _cubit.state.actionError ?? AppStrings.somethingWentWrong;
    });
    return ok;
  }

  /// Saves a dirty draft, then moves by [delta]; past the last item it closes.
  Future<void> _go(int delta) async {
    if (_saving) return;
    if (!await _persist() || !mounted) return;
    final next = _index + delta;
    if (next < 0 || next >= widget.lineIds.length) {
      if (delta > 0) Navigator.of(context).pop();
      return;
    }
    setState(() {
      _index = next;
      _seed();
    });
  }

  Future<void> _capture() async {
    final l = _line;
    if (l == null || _uploading) return;
    final file = await pickChecklistPhoto(context, title: l.name);
    if (file == null || !mounted) return;
    setState(() {
      _uploading = true;
      _error = null;
    });
    final ok = await _cubit.uploadLinePhoto(l.id, file.path);
    if (!mounted) return;
    setState(() {
      _uploading = false;
      if (!ok) _error = _cubit.state.actionError ?? AppStrings.somethingWentWrong;
    });
  }

  void _addQuick(String chip) {
    final current = _commentCtl.text.trim();
    final next = current.isEmpty ? chip : '$current, ${chip.toLowerCase()}';
    _commentCtl.text = next.length > _commentMax ? next.substring(0, _commentMax) : next;
    _commentCtl.selection = TextSelection.collapsed(offset: _commentCtl.text.length);
  }

  /// Every distinct photo of [line] for the viewer: reference, move-in, then
  /// this phase's shot (move-out on a rental).
  List<ChecklistViewerPhoto> _photosOf(ChecklistLine line, ChecklistDetail detail) {
    final moveInName = detail.isMoveOut ? 'Move-In' : detail.phaseName;
    final out = <ChecklistViewerPhoto>[];
    void add(String? path, String label) {
      if (path == null || out.any((x) => x.path == path)) return;
      out.add(ChecklistViewerPhoto(path: path, caption: '$label · ${line.name}'));
    }

    add(line.referenceImage, 'Reference');
    add(line.moveInImage, moveInName);
    if (detail.isMoveOut) add(line.moveOutImage, detail.phaseName);
    return out;
  }

  void _view(ChecklistLine line, ChecklistDetail detail, String path) {
    final photos = _photosOf(line, detail);
    final start = photos.indexWhere((x) => x.path == path);
    openChecklistPhotoViewer(context, photos, initialIndex: start < 0 ? 0 : start);
  }

  void _setStatus(String? value) => setState(() => _status = _status == value ? null : value);

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final bottomInset = MediaQuery.viewInsetsOf(context).bottom;
    final maxHeight = MediaQuery.sizeOf(context).height * 0.92;
    return BlocBuilder<ChecklistDetailCubit, ChecklistDetailState>(
      builder: (context, state) {
        final detail = state.detail;
        final line = detail?.lineById(widget.lineIds[_index]);
        if (detail == null || line == null) return const SizedBox.shrink();
        final locked = detail.sealed;
        final last = _index == widget.lineIds.length - 1;
        final dialog = widget.dialog;

        return Padding(
          padding: EdgeInsets.only(bottom: dialog ? 0 : bottomInset),
          child: Container(
            constraints: BoxConstraints(maxHeight: dialog ? MediaQuery.sizeOf(context).height * 0.88 : maxHeight),
            decoration: BoxDecoration(
              color: p.canvas,
              borderRadius: dialog ? BorderRadius.circular(28) : const BorderRadius.vertical(top: Radius.circular(28)),
            ),
            child: SafeArea(
              top: false,
              child: SingleChildScrollView(
                padding: EdgeInsets.fromLTRB(dialog ? 22 : 16, dialog ? 20 : 8, dialog ? 22 : 16, dialog ? 20 : 16),
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, mainAxisSize: MainAxisSize.min, children: [
                  if (!dialog) const SheetGrabber(),
                  Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        AtelierKicker('${widget.roomName} · Item ${_index + 1} of ${widget.lineIds.length}'),
                        const SizedBox(height: 3),
                        Text(line.name,
                            maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 22, color: p.ink, height: 1.1)),
                      ]),
                    ),
                    const SizedBox(width: 10),
                    AtelierRoundButton(icon: Icons.chevron_left, onTap: _index == 0 || _saving ? null : () => _go(-1)),
                    const SizedBox(width: 6),
                    AtelierRoundButton(icon: Icons.chevron_right, onTap: last || _saving ? null : () => _go(1)),
                  ]),
                  const SizedBox(height: 11),
                  SizedBox(
                    height: dialog ? 190 : 108,
                    child: Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                      Expanded(child: _earlierPane(context, line, detail)),
                      const SizedBox(width: 8),
                      Expanded(child: _nowPane(context, line, detail, locked)),
                    ]),
                  ),
                  const SizedBox(height: 11),
                  Row(children: [
                    Expanded(
                      child: Text('Condition at ${detail.phaseName.toLowerCase()}',
                          style: ui(size: 11, weight: FontWeight.w700, color: p.textMuted)),
                    ),
                    QtyStepper(
                      qty: qtyLabel(_qty),
                      onMinus: locked || _qty <= 0 ? null : () => setState(() => _qty = (_qty - 1).clamp(0, 9999)),
                      onPlus: locked ? null : () => setState(() => _qty = _qty + 1),
                    ),
                  ]),
                  const SizedBox(height: 11),
                  if (_moveOut)
                    Row(children: [
                      Expanded(
                        child: _StatusButton(
                          label: 'Good',
                          icon: Icons.check,
                          color: ColorManager.success,
                          selected: _status == ChecklistLineStatus.ok,
                          onTap: locked ? null : () => _setStatus(ChecklistLineStatus.ok),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: _StatusButton(
                          label: 'Damaged',
                          icon: Icons.close,
                          color: ColorManager.danger,
                          selected: _status == ChecklistLineStatus.notOk,
                          onTap: locked ? null : () => _setStatus(ChecklistLineStatus.notOk),
                        ),
                      ),
                    ])
                  else
                    _PresentToggle(
                      on: _status == ChecklistLineStatus.ok,
                      onTap: locked ? null : () => _setStatus(ChecklistLineStatus.ok),
                    ),
                  const SizedBox(height: 11),
                  AtelierField(
                    label: 'Comment',
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      TextField(
                        controller: _commentCtl,
                        readOnly: locked,
                        minLines: 1,
                        maxLines: 3,
                        maxLength: _commentMax,
                        textCapitalization: TextCapitalization.sentences,
                        style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
                        decoration: InputDecoration(
                          isDense: true,
                          border: InputBorder.none,
                          contentPadding: const EdgeInsets.symmetric(vertical: 6),
                          counterText: '',
                          hintText: 'What did you find?',
                          hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
                        ),
                      ),
                      if (!locked) ...[
                        const SizedBox(height: 4),
                        Wrap(spacing: 6, runSpacing: 6, children: [
                          for (final c in _quickComments) _QuickChip(label: c, onTap: () => _addQuick(c)),
                        ]),
                        const SizedBox(height: 2),
                      ],
                    ]),
                  ),
                  if (_moveOut && _status == ChecklistLineStatus.notOk) ...[
                    const SizedBox(height: 11),
                    AtelierField(
                      label: 'Damage cost',
                      borderColor: ColorManager.danger.withValues(alpha: 0.45),
                      child: Row(children: [
                        Text(Money.symbol.trim(), style: ui(size: 13, weight: FontWeight.w800, color: p.textMuted)),
                        const SizedBox(width: 6),
                        Expanded(
                          child: KeyboardDoneField(
                            focusNode: _costFocus,
                            child: TextField(
                              controller: _costCtl,
                              focusNode: _costFocus,
                              readOnly: locked,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              style: ui(size: 14, weight: FontWeight.w800, color: p.ink),
                              decoration: InputDecoration(
                                isDense: true,
                                border: InputBorder.none,
                                contentPadding: const EdgeInsets.symmetric(vertical: 6),
                                hintText: '0.00',
                                hintStyle: ui(size: 14, weight: FontWeight.w700, color: p.textMuted),
                              ),
                            ),
                          ),
                        ),
                      ]),
                    ),
                  ],
                  if (_error != null) ...[
                    const SizedBox(height: 10),
                    Row(children: [
                      const Icon(Icons.error_outline, size: 15, color: ColorManager.danger),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(_error!, style: ui(size: 11.5, weight: FontWeight.w700, color: ColorManager.danger)),
                      ),
                    ]),
                  ],
                  const SizedBox(height: 12),
                  if (locked)
                    Text('This hand-over is sealed — items are read-only.',
                        textAlign: TextAlign.center, style: ui(size: 11.5, weight: FontWeight.w700, color: p.textMuted))
                  else
                    AtelierButton(
                      label: last ? 'Save & close' : 'Save & next',
                      icon: last ? Icons.check : Icons.arrow_forward,
                      busy: _saving,
                      onTap: () => _go(1),
                    ),
                ]),
              ),
            ),
          ),
        );
      },
    );
  }

  /// Left pane: move-in photo (move-out) or reference image (move-in /
  /// hand-over). Tap opens the item's photos full screen.
  Widget _earlierPane(BuildContext context, ChecklistLine line, ChecklistDetail detail) {
    final p = context.astra;
    final Widget pane;
    final String? path;
    if (detail.isMoveOut) {
      final present = line.moveInStatus == ChecklistLineStatus.ok;
      path = line.moveInImage ?? line.referenceImage;
      pane = _PhotoPane(
        label: line.moveInImage != null ? 'Move-in' : 'Reference',
        path: path,
        tag: present ? 'Present' : 'Not present',
        tagColor: present ? ColorManager.success : p.textMuted,
      );
    } else {
      path = line.referenceImage;
      pane = _PhotoPane(label: 'Reference', path: path);
    }
    if (path == null) return pane;
    final viewPath = path;
    return GestureDetector(onTap: () => _view(line, detail, viewPath), behavior: HitTestBehavior.opaque, child: pane);
  }

  /// Right pane: this phase's photo (tap → full screen, "Retake" chip →
  /// camera) or the capture slot (tap → camera).
  Widget _nowPane(BuildContext context, ChecklistLine line, ChecklistDetail detail, bool locked) {
    final p = context.astra;
    final label = detail.phaseName;
    final path = line.imageFor(detail.phase);
    final Widget pane;
    if (path != null) {
      pane = _PhotoPane(label: '$label · now', path: path, onAction: locked || _uploading ? null : _capture);
    } else {
      pane = DashedBorderBox(
        color: p.accent,
        radius: 16,
        child: Container(
          decoration: BoxDecoration(color: p.cardSolid, borderRadius: BorderRadius.circular(16)),
          child: Stack(children: [
            Positioned(left: 8, top: 8, child: _PaneLabel(label, bg: p.accent)),
            Center(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(shape: BoxShape.circle, color: p.accent),
                  child: const Icon(Icons.photo_camera_outlined, size: 18, color: ColorManager.white),
                ),
                const SizedBox(height: 6),
                Text(locked ? 'No photo' : 'Take photo', style: ui(size: 11.5, weight: FontWeight.w800, color: p.goldText)),
              ]),
            ),
          ]),
        ),
      );
    }
    return GestureDetector(
      onTap: path != null ? () => _view(line, detail, path) : (locked ? null : _capture),
      behavior: HitTestBehavior.opaque,
      child: Stack(fit: StackFit.expand, children: [
        pane,
        if (_uploading)
          ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: ColoredBox(
              color: ColorManager.black.withValues(alpha: 0.35),
              child: const Center(
                child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4, color: ColorManager.white)),
              ),
            ),
          ),
      ]),
    );
  }
}

class _PhotoPane extends StatelessWidget {
  const _PhotoPane({required this.label, required this.path, this.tag, this.tagColor, this.onAction});
  final String label;
  final String? path;
  final String? tag;
  final Color? tagColor;

  /// Shows the "Retake" chip — its own tap target, separate from the pane's.
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: Stack(fit: StackFit.expand, children: [
        path != null
            ? Hero(tag: checklistPhotoHeroTag(path!), child: ChecklistPhoto(path: path!))
            : HatchedBox(child: Text('No photo', style: ui(size: 11, weight: FontWeight.w800, color: p.textMuted))),
        Positioned(left: 8, top: 8, child: _PaneLabel(label)),
        if (tag != null)
          Positioned(
            left: 8,
            bottom: 8,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
              decoration: BoxDecoration(color: tagColor ?? p.textMuted, borderRadius: BorderRadius.circular(6)),
              child: Text(tag!, style: ui(size: 10, weight: FontWeight.w800, color: ColorManager.white)),
            ),
          ),
        if (onAction != null)
          Positioned(
            right: 6,
            bottom: 6,
            child: GestureDetector(
              onTap: onAction,
              behavior: HitTestBehavior.opaque,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
                decoration: BoxDecoration(color: ColorManager.black.withValues(alpha: 0.6), borderRadius: BorderRadius.circular(9)),
                child: Row(mainAxisSize: MainAxisSize.min, children: [
                  const Icon(Icons.photo_camera_outlined, size: 12, color: ColorManager.white),
                  const SizedBox(width: 4),
                  Text('Retake', style: ui(size: 10.5, weight: FontWeight.w800, color: ColorManager.white)),
                ]),
              ),
            ),
          ),
      ]),
    );
  }
}

class _PaneLabel extends StatelessWidget {
  const _PaneLabel(this.text, {this.bg});
  final String text;
  final Color? bg;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
        decoration: BoxDecoration(color: bg ?? ColorManager.black.withValues(alpha: 0.5), borderRadius: BorderRadius.circular(6)),
        child: Text(text.toUpperCase(),
            style: ui(size: 9, weight: FontWeight.w800, letterSpacing: 0.6, color: ColorManager.white)),
      );
}

/// Move-out status button (Good / Damaged); tapping the selected one clears it.
class _StatusButton extends StatelessWidget {
  const _StatusButton({required this.label, required this.icon, required this.color, required this.selected, this.onTap});
  final String label;
  final IconData icon;
  final Color color;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final fg = selected ? ColorManager.white : p.textSecondary;
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(vertical: 11),
        decoration: BoxDecoration(
          color: selected ? color : p.cardSolid,
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: selected ? color : p.hairline, width: 1.5),
        ),
        child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
          Icon(icon, size: 15, color: fg),
          const SizedBox(width: 6),
          Text(label, style: ui(size: 12.5, weight: FontWeight.w800, color: fg)),
        ]),
      ),
    );
  }
}

/// Move-in's single binary "Present" toggle (on → ok, off → null).
class _PresentToggle extends StatelessWidget {
  const _PresentToggle({required this.on, this.onTap});
  final bool on;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final fg = on ? ColorManager.white : p.textSecondary;
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        height: 52,
        padding: const EdgeInsets.symmetric(horizontal: 14),
        decoration: BoxDecoration(
          color: on ? ColorManager.success : p.cardSolid,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: on ? ColorManager.success : p.hairline, width: 1.5),
        ),
        child: Row(children: [
          Icon(on ? Icons.check_circle : Icons.radio_button_unchecked, size: 20, color: fg),
          const SizedBox(width: 10),
          Expanded(child: Text('Present', style: ui(size: 14, weight: FontWeight.w800, color: fg))),
          Text(on ? 'Marked present' : 'Tap to mark', style: ui(size: 11, weight: FontWeight.w700, color: fg.withValues(alpha: 0.85))),
        ]),
      ),
    );
  }
}

class _QuickChip extends StatelessWidget {
  const _QuickChip({required this.label, required this.onTap});
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(color: p.canvas, borderRadius: BorderRadius.circular(999), border: Border.all(color: p.hairline)),
        child: Text(label, style: ui(size: 11, weight: FontWeight.w700, color: p.textSecondary)),
      ),
    );
  }
}

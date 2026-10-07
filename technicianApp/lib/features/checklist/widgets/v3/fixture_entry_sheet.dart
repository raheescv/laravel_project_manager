import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

import '../../domain/models/checklist_models.dart';
import '../../logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import 'atelier_parts.dart';
import 'photo_source_sheet.dart';
import 'photo_viewer.dart';

const _before = 'before';
const _after = 'after';

/// Opens the add / edit sheet for a fixture-comment entry in [category] —
/// a bottom sheet on phones, a centered dialog on tablets.
Future<void> showFixtureEntrySheet(BuildContext context, {required String category, FixtureEntry? entry}) {
  final cubit = context.read<ChecklistDetailCubit>();
  if (context.isTablet) {
    return showDialog<void>(
      context: context,
      builder: (_) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(24),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 600),
          child: BlocProvider.value(value: cubit, child: FixtureEntrySheet(category: category, entry: entry, dialog: true)),
        ),
      ),
    );
  }
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => BlocProvider.value(value: cubit, child: FixtureEntrySheet(category: category, entry: entry)),
  );
}

/// Add / edit a fixture entry: comments, status, before & after photos,
/// delete. When editing, photos upload immediately; when adding they are held
/// locally and uploaded right after the entry is created.
class FixtureEntrySheet extends StatefulWidget {
  const FixtureEntrySheet({super.key, required this.category, this.entry, this.dialog = false});
  final String category;
  final FixtureEntry? entry;

  /// Rendered inside a tablet dialog (rounded all round, no grabber).
  final bool dialog;

  @override
  State<FixtureEntrySheet> createState() => _FixtureEntrySheetState();
}

class _FixtureEntrySheetState extends State<FixtureEntrySheet> {
  late final _commentsCtl = TextEditingController(text: widget.entry?.comments ?? '');
  late String _status = widget.entry?.status ?? FixtureStatus.pending;

  /// Add mode only: photos picked before the entry exists.
  final Map<String, XFile> _pendingFiles = {};
  final Map<String, Uint8List> _pendingPreviews = {};

  bool _saving = false;
  bool _deleting = false;
  String? _uploadingWhich;
  String? _error;

  ChecklistDetailCubit get _cubit => context.read<ChecklistDetailCubit>();
  bool get _isEdit => widget.entry != null;

  @override
  void dispose() {
    _commentsCtl.dispose();
    super.dispose();
  }

  /// The live copy of the edited entry (refreshed after uploads).
  FixtureEntry? _liveEntry(ChecklistDetail? detail) {
    final id = widget.entry?.id;
    if (id == null || detail == null) return widget.entry;
    for (final a in detail.fixtures) {
      for (final e in a.entries) {
        if (e.id == id) return e;
      }
    }
    return widget.entry;
  }

  Set<int> _entryIds() => {
        for (final a in _cubit.state.detail?.fixtures ?? const <FixtureArea>[])
          for (final e in a.entries) e.id,
      };

  Future<void> _pick(String which) async {
    if (_uploadingWhich != null || _saving) return;
    final file = await pickChecklistPhoto(context, title: which == _before ? 'Before photo' : 'After photo');
    if (file == null || !mounted) return;
    final entry = widget.entry;
    if (entry == null) {
      final bytes = await file.readAsBytes();
      if (!mounted) return;
      setState(() {
        _pendingFiles[which] = file;
        _pendingPreviews[which] = bytes;
      });
      return;
    }
    setState(() {
      _uploadingWhich = which;
      _error = null;
    });
    final ok = await _cubit.uploadFixturePhoto(entry.id, which: which, filePath: file.path);
    if (!mounted) return;
    setState(() {
      _uploadingWhich = null;
      if (!ok) _error = _cubit.state.actionError ?? AppStrings.somethingWentWrong;
    });
  }

  void _view(FixtureEntry? entry, String which) {
    if (entry == null) return;
    final photos = [
      if (entry.beforeImage != null) ChecklistViewerPhoto(path: entry.beforeImage!, caption: 'Before · ${widget.category}'),
      if (entry.afterImage != null) ChecklistViewerPhoto(path: entry.afterImage!, caption: 'After · ${widget.category}'),
    ];
    openChecklistPhotoViewer(context, photos, initialIndex: which == _after && entry.beforeImage != null ? 1 : 0);
  }

  Future<void> _save() async {
    if (_saving) return;
    final comments = _commentsCtl.text.trim();
    final entry = widget.entry;
    final messenger = ScaffoldMessenger.of(context);
    setState(() {
      _saving = true;
      _error = null;
    });

    if (entry != null) {
      final commentsChanged = comments != entry.comments;
      final statusChanged = _status != entry.status;
      if (!commentsChanged && !statusChanged) {
        Navigator.of(context).pop();
        return;
      }
      final ok = await _cubit.updateFixtureEntry(
        entry.id,
        comments: commentsChanged ? comments : null,
        status: statusChanged ? _status : null,
      );
      if (!mounted) return;
      if (ok) {
        Navigator.of(context).pop();
      } else {
        setState(() {
          _saving = false;
          _error = _cubit.state.actionError ?? AppStrings.somethingWentWrong;
        });
      }
      return;
    }

    final before = _entryIds();
    final ok = await _cubit.addFixture(
      category: widget.category,
      comments: comments.isEmpty ? null : comments,
      status: _status,
    );
    if (!mounted) return;
    if (!ok) {
      setState(() {
        _saving = false;
        _error = _cubit.state.actionError ?? AppStrings.somethingWentWrong;
      });
      return;
    }
    var uploadsFailed = false;
    if (_pendingFiles.isNotEmpty) {
      final created = (_cubit.state.detail?.fixtureFor(widget.category)?.entries ?? const <FixtureEntry>[])
          .where((e) => !before.contains(e.id))
          .toList();
      if (created.isEmpty) {
        uploadsFailed = true;
      } else {
        for (final kv in _pendingFiles.entries) {
          final done = await _cubit.uploadFixturePhoto(created.last.id, which: kv.key, filePath: kv.value.path);
          if (!done) uploadsFailed = true;
          if (!mounted) return;
        }
      }
    }
    if (!mounted) return;
    Navigator.of(context).pop();
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        behavior: SnackBarBehavior.floating,
        content: Text(uploadsFailed ? 'Fixture added, but a photo failed to upload' : 'Fixture added'),
      ));
  }

  Future<void> _delete() async {
    final entry = widget.entry;
    if (entry == null || _deleting) return;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete fixture note?'),
        content: const Text('Its photos and comments will be removed.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('Delete', style: ui(size: 14, weight: FontWeight.w700, color: ColorManager.danger)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    setState(() {
      _deleting = true;
      _error = null;
    });
    final ok = await _cubit.deleteFixtureEntry(entry.id);
    if (!mounted) return;
    if (ok) {
      Navigator.of(context).pop();
    } else {
      setState(() {
        _deleting = false;
        _error = _cubit.state.actionError ?? AppStrings.somethingWentWrong;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final bottomInset = MediaQuery.viewInsetsOf(context).bottom;
    return BlocBuilder<ChecklistDetailCubit, ChecklistDetailState>(
      builder: (context, state) {
        final entry = _liveEntry(state.detail);
        final dialog = widget.dialog;
        return Padding(
          padding: EdgeInsets.only(bottom: dialog ? 0 : bottomInset),
          child: Container(
            constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * (dialog ? 0.86 : 0.92)),
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
                  AtelierKicker('${widget.category} · fixture comments'),
                  const SizedBox(height: 3),
                  Text(_isEdit ? 'Fixture note' : 'Add fixture', style: serif(size: 22, color: p.ink)),
                  const SizedBox(height: 12),
                  SizedBox(
                    height: dialog ? 170 : 108,
                    child: Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                      Expanded(
                        child: _PhotoSlot(
                          label: 'Before',
                          path: entry?.beforeImage,
                          preview: _pendingPreviews[_before],
                          uploading: _uploadingWhich == _before,
                          onCapture: () => _pick(_before),
                          onView: () => _view(entry, _before),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _PhotoSlot(
                          label: 'After',
                          path: entry?.afterImage,
                          preview: _pendingPreviews[_after],
                          uploading: _uploadingWhich == _after,
                          onCapture: () => _pick(_after),
                          onView: () => _view(entry, _after),
                        ),
                      ),
                    ]),
                  ),
                  const SizedBox(height: 12),
                  AtelierField(
                    label: 'Comments',
                    child: TextField(
                      controller: _commentsCtl,
                      minLines: 2,
                      maxLines: 4,
                      textCapitalization: TextCapitalization.sentences,
                      style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
                      decoration: InputDecoration(
                        isDense: true,
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(vertical: 6),
                        hintText: 'e.g. Re-grout tiles behind the sink',
                        hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(
                      color: p.cardSolid,
                      borderRadius: BorderRadius.circular(13),
                      border: Border.all(color: p.hairline),
                    ),
                    child: Row(children: [
                      for (final o in FixtureStatus.options)
                        Expanded(
                          child: GestureDetector(
                            onTap: () => setState(() => _status = o.$1),
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 150),
                              padding: const EdgeInsets.symmetric(vertical: 9),
                              alignment: Alignment.center,
                              decoration: BoxDecoration(
                                color: _status == o.$1 ? p.primary : ColorManager.transparent,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(o.$2,
                                  style: ui(
                                      size: 12,
                                      weight: FontWeight.w800,
                                      color: _status == o.$1 ? ColorManager.white : p.textSecondary)),
                            ),
                          ),
                        ),
                    ]),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 10),
                    Text(_error!, style: ui(size: 11.5, weight: FontWeight.w700, color: ColorManager.danger)),
                  ],
                  const SizedBox(height: 14),
                  Row(children: [
                    if (_isEdit) ...[
                      Expanded(
                        child: AtelierButton(
                          label: 'Delete',
                          leadingIcon: Icons.delete_outline,
                          kind: AtelierButtonKind.ghost,
                          busy: _deleting,
                          onTap: _saving ? null : _delete,
                        ),
                      ),
                      const SizedBox(width: 8),
                    ],
                    Expanded(
                      flex: 2,
                      child: AtelierButton(
                        label: _isEdit ? 'Save' : 'Add fixture',
                        leadingIcon: Icons.check,
                        busy: _saving,
                        onTap: _deleting || _uploadingWhich != null ? null : _save,
                      ),
                    ),
                  ]),
                ]),
              ),
            ),
          ),
        );
      },
    );
  }
}

/// Before / after slot. Empty or a local (not yet uploaded) preview: tap to
/// capture. A server photo: tap opens it full screen; the camera chip replaces.
class _PhotoSlot extends StatelessWidget {
  const _PhotoSlot({
    required this.label,
    required this.path,
    required this.preview,
    required this.uploading,
    required this.onCapture,
    required this.onView,
  });
  final String label;
  final String? path;
  final Uint8List? preview;
  final bool uploading;
  final VoidCallback onCapture;
  final VoidCallback onView;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final dpr = MediaQuery.devicePixelRatioOf(context);
    final Widget content;
    if (preview != null) {
      content = Image.memory(
        preview!,
        fit: BoxFit.cover,
        cacheWidth: (200 * dpr).round(),
        gaplessPlayback: true,
        errorBuilder: (_, __, ___) => HatchedBox(child: Icon(Icons.broken_image_outlined, size: 18, color: p.textMuted)),
      );
    } else if (path != null) {
      content = Hero(tag: checklistPhotoHeroTag(path!), child: ChecklistPhoto(path: path!));
    } else {
      content = DashedBorderBox(
        color: p.accent,
        child: Container(
          decoration: BoxDecoration(color: p.cardSolid, borderRadius: BorderRadius.circular(16)),
          alignment: Alignment.center,
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.photo_camera_outlined, size: 20, color: p.goldText),
            const SizedBox(height: 4),
            Text('Add photo', style: ui(size: 11, weight: FontWeight.w800, color: p.goldText)),
          ]),
        ),
      );
    }
    final viewable = preview == null && path != null;
    return GestureDetector(
      onTap: viewable ? onView : onCapture,
      behavior: HitTestBehavior.opaque,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: Stack(fit: StackFit.expand, children: [
          content,
          Positioned(
            left: 8,
            top: 8,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
              decoration: BoxDecoration(color: ColorManager.black.withValues(alpha: 0.5), borderRadius: BorderRadius.circular(6)),
              child: Text(label.toUpperCase(),
                  style: ui(size: 9, weight: FontWeight.w800, letterSpacing: 0.6, color: ColorManager.white)),
            ),
          ),
          if (preview != null || path != null)
            Positioned(
              right: 6,
              bottom: 6,
              child: GestureDetector(
                onTap: onCapture,
                behavior: HitTestBehavior.opaque,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
                  decoration: BoxDecoration(color: ColorManager.black.withValues(alpha: 0.55), borderRadius: BorderRadius.circular(8)),
                  child: Row(mainAxisSize: MainAxisSize.min, children: [
                    const Icon(Icons.photo_camera_outlined, size: 12, color: ColorManager.white),
                    const SizedBox(width: 4),
                    Text('Replace', style: ui(size: 10.5, weight: FontWeight.w800, color: ColorManager.white)),
                  ]),
                ),
              ),
            ),
          if (uploading)
            ColoredBox(
              color: ColorManager.black.withValues(alpha: 0.35),
              child: const Center(
                child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4, color: ColorManager.white)),
              ),
            ),
        ]),
      ),
    );
  }
}

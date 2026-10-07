import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/checklist_models.dart';
import '../../logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import '../../logic/checklist_inbox_cubit/checklist_inbox_cubit.dart';
import '../../widgets/v3/atelier_parts.dart';
import '../../widgets/v3/checklist_pdf.dart';
import '../../widgets/v3/signature_capture.dart';

/// Hand-over (C5): the condition record, remarks + actual date, the three
/// signatures as a timeline, and the gold "Seal hand-over".
class ChecklistHandoverScreen extends StatefulWidget {
  const ChecklistHandoverScreen({super.key});

  /// Pushes the hand-over over the current screen, sharing its cubit.
  static Future<void> open(BuildContext context, {bool replace = false}) {
    final cubit = context.read<ChecklistDetailCubit>();
    final route = MaterialPageRoute<void>(
      builder: (_) => TabletRailScaffold(
        activeTab: Routes.checklistsTabIndex,
        child: BlocProvider.value(value: cubit, child: const ChecklistHandoverScreen()),
      ),
    );
    final nav = Navigator.of(context);
    return replace ? nav.pushReplacement<void, void>(route) : nav.push<void>(route);
  }

  @override
  State<ChecklistHandoverScreen> createState() => _ChecklistHandoverScreenState();
}

class _ChecklistHandoverScreenState extends State<ChecklistHandoverScreen> {
  final _remarksCtl = TextEditingController();
  DateTime _actualDate = DateTime.now();
  int? _seededFor;
  bool _sealing = false;
  String? _signingRole;

  @override
  void dispose() {
    _remarksCtl.dispose();
    super.dispose();
  }

  void _seed(ChecklistDetail detail) {
    if (_seededFor == detail.job.id) return;
    _seededFor = detail.job.id;
    _remarksCtl.text = detail.remarks ?? '';
    final d = detail.actualDate == null ? null : DateTime.tryParse(detail.actualDate!);
    if (d != null) _actualDate = d;
  }

  Future<void> _pickDate() async {
    // A hand-over can't be dated in the future (SealRequest: before_or_equal:today).
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final first = DateTime(now.year - 5);
    final initial = _actualDate.isAfter(today) ? today : (_actualDate.isBefore(first) ? first : _actualDate);
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: first,
      lastDate: today,
    );
    if (picked == null || !mounted) return;
    setState(() => _actualDate = picked);
  }

  Future<void> _sign(ChecklistDetail detail, ChecklistSignature sig) async {
    final cubit = context.read<ChecklistDetailCubit>();
    final result = await showSignatureCapture(
      context,
      title: 'Sign as ${sig.label}',
      kicker: detail.job.title,
      initialName: sig.assigneeName,
    );
    if (result == null || !mounted) return;
    setState(() => _signingRole = sig.role);
    final ok = await cubit.sign(role: sig.role, signerName: result.name, signature: result.dataUrl);
    if (!mounted) return;
    setState(() => _signingRole = null);
    showChecklistToast(context, ok ? '${sig.label} signed' : (cubit.state.actionError ?? AppStrings.somethingWentWrong),
        clearBottomBar: true);
  }

  Future<void> _seal() async {
    final cubit = context.read<ChecklistDetailCubit>();
    final inbox = context.read<ChecklistInboxCubit>();
    setState(() => _sealing = true);
    final ok = await cubit.seal(remarks: _remarksCtl.text.trim(), actualDate: Dates.iso(_actualDate));
    if (!mounted) return;
    setState(() => _sealing = false);
    if (!ok) {
      showChecklistToast(context, cubit.state.actionError ?? AppStrings.somethingWentWrong, clearBottomBar: true);
      return;
    }
    showChecklistToast(context, 'Hand-over sealed');
    inbox.load(silent: true);
    goToShellTab(context, Routes.checklistsTabIndex);
  }

  /// Phone: one scrolling column. Tablet: condition record + details on the
  /// left, the signing timeline on the right.
  Widget _twoPane(BuildContext context, {required List<Widget> first, required List<Widget> second}) {
    if (!context.isTablet) {
      return ListView(padding: const EdgeInsets.only(bottom: 20), children: [...first, ...second]);
    }
    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(8, 0, 8, 24),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Expanded(flex: 11, child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: first)),
        Expanded(
          flex: 10,
          child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [const SizedBox(height: 4), ...second]),
        ),
      ]),
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: BlocBuilder<ChecklistDetailCubit, ChecklistDetailState>(
          builder: (context, state) {
            final detail = state.detail;
            if (detail == null) {
              return Column(children: [
                const AtelierTopBar(kicker: 'Hand-over', title: 'Hand-over'),
                Expanded(child: Center(child: CircularProgressIndicator(color: p.primary))),
              ]);
            }
            _seed(detail);
            final job = detail.job;
            final left = detail.uncheckedCount;
            final sigs = detail.signingOrder;
            final locked = detail.sealed;
            return Column(children: [
              AtelierTopBar(
                kicker: [job.phaseName, job.agreementLabel].where((s) => s.isNotEmpty).join(' · '),
                title: 'Hand-over',
                trailing: const ChecklistPdfButton(clearBottomBar: true),
              ),
              Expanded(
                child: MaxWidthBox(
                  maxWidth: checklistContentWidth(context),
                  child: _twoPane(
                    context,
                    first: [
                      _ConditionRecord(detail: detail),
                      if (left > 0 && !locked)
                        Container(
                          margin: const EdgeInsets.fromLTRB(14, 0, 14, 12),
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                          decoration: BoxDecoration(color: p.warnTint, borderRadius: BorderRadius.circular(12)),
                          child: Row(children: [
                            Icon(Icons.warning_amber_rounded, size: 16, color: p.warnText),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                '$left item${left == 1 ? '' : 's'} not checked yet — you can still seal once everyone has signed.',
                                style: ui(size: 11.5, weight: FontWeight.w700, color: p.warnText),
                              ),
                            ),
                          ]),
                        ),
                      const AtelierSectionTitle(title: 'Hand-over details'),
                      Padding(
                        padding: const EdgeInsets.fromLTRB(14, 0, 14, 12),
                        child: Column(children: [
                          AtelierField(
                            label: 'Remarks',
                            child: TextField(
                              controller: _remarksCtl,
                              readOnly: locked,
                              minLines: 2,
                              maxLines: 4,
                              textCapitalization: TextCapitalization.sentences,
                              style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
                              decoration: InputDecoration(
                                isDense: true,
                                border: InputBorder.none,
                                contentPadding: const EdgeInsets.symmetric(vertical: 6),
                                hintText: 'Keys handed over, meter readings…',
                                hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
                              ),
                            ),
                          ),
                          const SizedBox(height: 8),
                          GestureDetector(
                            onTap: locked ? null : _pickDate,
                            behavior: HitTestBehavior.opaque,
                            child: AtelierField(
                              label: 'Actual ${job.phaseName.toLowerCase()} date',
                              child: Padding(
                                padding: const EdgeInsets.symmetric(vertical: 6),
                                child: Row(children: [
                                  Expanded(
                                    child: Text(Dates.weekday(_actualDate),
                                        style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                                  ),
                                  Icon(Icons.calendar_today_outlined, size: 15, color: p.textMuted),
                                ]),
                              ),
                            ),
                          ),
                        ]),
                      ),
                    ],
                    second: [
                      AtelierSectionTitle(
                        title: 'Signatures',
                        trailing: '${detail.signaturesDone} of ${sigs.length} signed',
                      ),
                      Padding(
                        padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
                        child: Column(children: [
                          for (var i = 0; i < sigs.length; i++)
                            _SignatureStep(
                              signature: sigs[i],
                              number: i + 1,
                              isLast: i == sigs.length - 1,
                              busy: _signingRole == sigs[i].role,
                              onSign: locked || state.busy ? null : () => _sign(detail, sigs[i]),
                            ),
                        ]),
                      ),
                    ],
                  ),
                ),
              ),
              MaxWidthBox(
                maxWidth: context.isTablet ? 720 : 640,
                child: SafeArea(
                  top: false,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
                    child: locked
                        ? Container(
                            height: 50,
                            alignment: Alignment.center,
                            decoration: BoxDecoration(color: p.goldTint, borderRadius: BorderRadius.circular(16)),
                            child: Row(mainAxisSize: MainAxisSize.min, children: [
                              Icon(Icons.verified_outlined, size: 17, color: p.goldText),
                              const SizedBox(width: 7),
                              Text('Hand-over sealed', style: ui(size: 13.5, weight: FontWeight.w800, color: p.goldText)),
                            ]),
                          )
                        : Column(mainAxisSize: MainAxisSize.min, children: [
                            if (!detail.readyToSeal)
                              Padding(
                                padding: const EdgeInsets.only(bottom: 8),
                                child: Text('All three signatures are needed to seal.',
                                    style: ui(size: 11, weight: FontWeight.w700, color: p.textMuted)),
                              ),
                            AtelierButton(
                              label: 'Seal hand-over',
                              leadingIcon: Icons.key_outlined,
                              kind: AtelierButtonKind.gold,
                              busy: _sealing,
                              onTap: detail.readyToSeal && !state.busy ? _seal : null,
                            ),
                          ]),
                  ),
                ),
              ),
            ]);
          },
        ),
      ),
    );
  }
}

/// Certificate-style condition record with the phase's counts.
class _ConditionRecord extends StatelessWidget {
  const _ConditionRecord({required this.detail});
  final ChecklistDetail detail;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    final job = detail.job;
    final total = detail.lines.length;
    final left = detail.uncheckedCount;
    final stats = detail.isMoveOut
        ? [
            ('Good', detail.okCount, ColorManager.success),
            ('Damaged', detail.damagedCount, ColorManager.danger),
            ('Not checked', left, p.goldText),
          ]
        : [
            ('Present', detail.okCount, ColorManager.success),
            ('Not checked', left, p.goldText),
          ];
    final sub = [
      if (job.building.isNotEmpty) job.building else if (job.group.isNotEmpty) job.group,
      '$total item${total == 1 ? '' : 's'}',
      if (job.scheduledDate != null) Dates.human(job.scheduledDate),
    ].join(' · ');
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 4, 14, 12),
      decoration: BoxDecoration(color: p.cardSolid, borderRadius: BorderRadius.circular(20), boxShadow: t.softShadow),
      child: Stack(children: [
        Positioned.fill(
          child: Container(
            margin: const EdgeInsets.all(5),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: p.accent.withValues(alpha: 0.55)),
            ),
          ),
        ),
        Positioned.fill(
          child: Container(
            margin: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(13),
              border: Border.all(color: p.accent.withValues(alpha: 0.22)),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(18, 18, 18, 16),
          child: Column(children: [
            const AtelierKicker('Condition record'),
            const SizedBox(height: 5),
            Text(job.title, textAlign: TextAlign.center, style: serif(size: 19, color: p.ink, height: 1.2)),
            const SizedBox(height: 3),
            Text(sub, textAlign: TextAlign.center, style: ui(size: 11, weight: FontWeight.w700, color: p.textMuted)),
            const SizedBox(height: 12),
            IntrinsicHeight(
              child: Row(children: [
                for (var i = 0; i < stats.length; i++) ...[
                  if (i > 0) VerticalDivider(width: 1, thickness: 1, color: p.hairline),
                  Expanded(
                    child: Column(children: [
                      Text('${stats[i].$2}',
                          style: ui(size: 19, weight: FontWeight.w800, letterSpacing: -0.4, color: stats[i].$3)),
                      const SizedBox(height: 2),
                      Text(stats[i].$1.toUpperCase(),
                          style: ui(size: 9, weight: FontWeight.w800, letterSpacing: 0.6, color: p.textMuted)),
                    ]),
                  ),
                ],
              ]),
            ),
            const SizedBox(height: 12),
            DashedBorderBox(color: p.hairline, radius: 0, strokeWidth: 1, child: const SizedBox(height: 1, width: double.infinity)),
            const SizedBox(height: 10),
            Row(children: [
              Icon(Icons.photo_camera_outlined, size: 13, color: p.textSecondary),
              const SizedBox(width: 5),
              Expanded(
                child: Text('${detail.photoCount} photo${detail.photoCount == 1 ? '' : 's'} attached',
                    style: ui(size: 11.5, weight: FontWeight.w700, color: p.textSecondary)),
              ),
              if (detail.isMoveOut)
                Text('Damage ${Money.of(detail.damageTotal)}',
                    style: ui(size: 11.5, weight: FontWeight.w800, color: detail.damageTotal > 0 ? ColorManager.danger : p.textSecondary))
              else
                Text('${detail.okCount} of $total present', style: ui(size: 11.5, weight: FontWeight.w700, color: p.textSecondary)),
            ]),
          ]),
        ),
      ]),
    );
  }
}

/// One signature in the timeline: signed (image + signer + time), signable
/// here ("Sign"), or locked (awaiting the assignee's own login).
class _SignatureStep extends StatelessWidget {
  const _SignatureStep({
    required this.signature,
    required this.number,
    required this.isLast,
    required this.busy,
    this.onSign,
  });
  final ChecklistSignature signature;
  final int number;
  final bool isLast;
  final bool busy;
  final VoidCallback? onSign;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    final s = signature;
    final live = s.canSign && !s.signed;
    final signedAt = Dates.parseLocal(s.signedAt);
    final name = s.signed && (s.signerName ?? '').isNotEmpty
        ? s.signerName!
        : (s.assigneeName.isNotEmpty ? s.assigneeName : s.label);
    final String trailing;
    if (s.signed) {
      trailing = signedAt == null ? 'Signed' : Dates.time(signedAt);
    } else {
      trailing = live ? 'On this device' : 'Remote';
    }

    final Widget dot;
    if (s.signed) {
      dot = Container(
        width: 34,
        height: 34,
        decoration: BoxDecoration(shape: BoxShape.circle, color: p.primary, border: Border.all(color: p.primary, width: 2)),
        child: const Icon(Icons.check, size: 16, color: ColorManager.white),
      );
    } else if (live) {
      dot = Container(
        width: 34,
        height: 34,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: p.cardSolid,
          border: Border.all(color: p.accent, width: 2),
          boxShadow: [BoxShadow(color: p.goldTint, spreadRadius: 5)],
        ),
        child: Text('$number', style: ui(size: 13, weight: FontWeight.w800, color: p.goldText)),
      );
    } else {
      dot = Container(
        width: 34,
        height: 34,
        decoration: BoxDecoration(shape: BoxShape.circle, color: p.cardSolid, border: Border.all(color: p.hairline, width: 2)),
        child: Icon(Icons.lock_outline, size: 15, color: p.textMuted),
      );
    }

    return IntrinsicHeight(
      child: Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        SizedBox(
          width: 34,
          child: Column(children: [
            dot,
            if (!isLast)
              Expanded(child: Container(width: 2, color: s.signed ? p.accent : p.hairline)),
          ]),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Opacity(
              opacity: !s.signed && !live ? 0.85 : 1,
              child: Container(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
                decoration: BoxDecoration(
                  color: p.cardSolid,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: live ? p.accent : p.hairline),
                  boxShadow: t.softShadow,
                ),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        AtelierKicker(s.label.isEmpty ? s.role : s.label),
                        const SizedBox(height: 2),
                        Text(s.isMe ? '$name · You' : name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
                      ]),
                    ),
                    const SizedBox(width: 8),
                    Text(trailing, style: ui(size: 11, weight: FontWeight.w700, color: p.textMuted)),
                  ]),
                  if (s.signed) ...[
                    if (s.signature != null) ...[
                      const SizedBox(height: 8),
                      SignatureImage(path: s.signature!, height: 50, caption: '${s.label} · $name'),
                    ],
                    if (signedAt != null) ...[
                      const SizedBox(height: 5),
                      Text('Signed ${Dates.humanDateTime(s.signedAt)}',
                          style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
                    ],
                  ] else if (live) ...[
                    const SizedBox(height: 9),
                    AtelierButton(
                      label: 'Sign',
                      leadingIcon: Icons.draw_outlined,
                      height: 40,
                      busy: busy,
                      onTap: onSign,
                    ),
                  ] else ...[
                    const SizedBox(height: 6),
                    Row(children: [
                      Icon(Icons.send_outlined, size: 12, color: p.textSecondary),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          s.assigneeName.isEmpty
                              ? 'Awaiting signature · signs from their own login'
                              : 'Awaiting ${s.assigneeName} · signs from their own login',
                          style: ui(size: 11, weight: FontWeight.w600, color: p.textSecondary),
                        ),
                      ),
                    ]),
                  ],
                ]),
              ),
            ),
          ),
        ),
      ]),
    );
  }
}

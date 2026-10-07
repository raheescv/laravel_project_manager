import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/checklist_models.dart';
import '../../logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import '../../widgets/v3/atelier_parts.dart';
import '../../widgets/v3/checklist_pdf.dart';
import '../../widgets/v3/room_tile.dart';
import 'checklist_handover_screen.dart';
import 'checklist_room_screen.dart';

/// Hand-over overview (C2): hero progress card, a tile per room and the
/// "Hand-over & signatures" tile. Owns nothing — reads the route's
/// [ChecklistDetailCubit], which the Room and Hand-over screens share.
class ChecklistRoomsScreen extends StatelessWidget {
  const ChecklistRoomsScreen({super.key});

  Future<void> _call(BuildContext context, String mobile) async {
    final uri = Uri(scheme: 'tel', path: mobile.replaceAll(' ', ''));
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else if (context.mounted) {
      showChecklistToast(context, 'Could not start a call');
    }
  }

  Widget _roomsTitle(int left) => AtelierSectionTitle(
        title: 'Rooms',
        trailing: left == 0 ? 'All checked' : '$left item${left == 1 ? '' : 's'} left',
      );

  Widget _roomsGrid(BuildContext context, ChecklistDetail detail, List<ChecklistRoom> rooms,
      {required int columns, required double extent, double mosaicHeight = 74}) {
    final p = context.astra;
    if (rooms.isEmpty) {
      return SliverToBoxAdapter(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(18, 10, 18, 20),
          child: Text('This checklist has no items yet.', style: ui(size: 13, weight: FontWeight.w600, color: p.textMuted)),
        ),
      );
    }
    return SliverPadding(
      padding: const EdgeInsets.symmetric(horizontal: 14),
      sliver: SliverGrid.builder(
        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: columns,
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          mainAxisExtent: extent,
        ),
        itemCount: rooms.length,
        itemBuilder: (_, i) => RoomTile(
          room: rooms[i],
          phase: detail.phase,
          mosaicHeight: mosaicHeight,
          onTap: () => ChecklistRoomScreen.open(context, rooms[i].name),
        ),
      ),
    );
  }

  /// Tablet: hero summary + hand-over tile in a side column, rooms beside.
  Widget _tabletBody(BuildContext context, ChecklistDetail detail, List<ChecklistRoom> rooms, int left) {
    return Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
      SizedBox(
        width: context.isWide ? 440 : 380,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.only(bottom: 40),
          children: [
            _HeroCard(detail: detail),
            if (detail.sealed) const _SealedBanner(),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14),
              child: HandoverTile(detail: detail, onTap: () => ChecklistHandoverScreen.open(context)),
            ),
          ],
        ),
      ),
      Expanded(
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(child: _roomsTitle(left)),
            _roomsGrid(context, detail, rooms, columns: checklistGridColumns(context), extent: 176, mosaicHeight: 98),
            const SliverToBoxAdapter(child: SizedBox(height: 40)),
          ],
        ),
      ),
    ]);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: BlocBuilder<ChecklistDetailCubit, ChecklistDetailState>(
          // The PDF spinner and write flags live in their own widgets; this
          // screen only redraws when the hand-over itself changes.
          buildWhen: (a, b) => a.detail != b.detail || a.status != b.status || a.errorMessage != b.errorMessage,
          builder: (context, state) {
            final cubit = context.read<ChecklistDetailCubit>();
            final detail = state.detail;
            if (detail == null) {
              return Column(children: [
                const AtelierTopBar(kicker: 'Hand-over', title: 'Checklist'),
                Expanded(
                  child: state.status == DataFetchStatus.failed
                      ? EmptyState(
                          icon: Icons.wifi_off_rounded,
                          title: 'Could not load',
                          message: state.errorMessage,
                          action: AstraButton(label: 'Retry', expand: false, onTap: cubit.reload),
                        )
                      : Center(child: CircularProgressIndicator(color: p.primary)),
                ),
              ]);
            }
            final job = detail.job;
            final rooms = detail.rooms;
            final left = detail.uncheckedCount;
            return Column(children: [
              AtelierTopBar(
                kicker: [job.phaseName, job.agreementLabel].where((s) => s.isNotEmpty).join(' · '),
                title: job.title,
                trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                  const ChecklistPdfButton(),
                  if (job.lesseeMobile.isNotEmpty) ...[
                    const SizedBox(width: 8),
                    AtelierRoundButton(
                        icon: Icons.phone_outlined, tooltip: 'Call lessee', onTap: () => _call(context, job.lesseeMobile)),
                  ],
                ]),
              ),
              Expanded(
                child: MaxWidthBox(
                  maxWidth: checklistContentWidth(context),
                  child: RefreshIndicator(
                    color: p.primary,
                    onRefresh: cubit.reload,
                    child: context.isTablet
                        ? _tabletBody(context, detail, rooms, left)
                        : CustomScrollView(
                            physics: const AlwaysScrollableScrollPhysics(),
                            slivers: [
                              SliverToBoxAdapter(child: _HeroCard(detail: detail)),
                              if (detail.sealed) const SliverToBoxAdapter(child: _SealedBanner()),
                              SliverToBoxAdapter(child: _roomsTitle(left)),
                              _roomsGrid(context, detail, rooms, columns: 2, extent: 152),
                              SliverToBoxAdapter(
                                child: Padding(
                                  padding: const EdgeInsets.fromLTRB(14, 10, 14, 0),
                                  child: HandoverTile(detail: detail, onTap: () => ChecklistHandoverScreen.open(context)),
                                ),
                              ),
                              const SliverToBoxAdapter(child: SizedBox(height: 40)),
                            ],
                          ),
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

/// Emerald hero: lessee / scheduled / you-sign-as, big percent, stacked bar.
class _HeroCard extends StatelessWidget {
  const _HeroCard({required this.detail});
  final ChecklistDetail detail;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final job = detail.job;
    final total = detail.lines.length;
    final ok = detail.okCount;
    final bad = detail.damagedCount;
    final left = detail.uncheckedCount;
    final pct = (detail.progress * 100).round();
    final labelStyle = ui(size: 9, weight: FontWeight.w800, letterSpacing: 0.8, color: p.heroLabel);
    final valueStyle = ui(size: 12, weight: FontWeight.w700, color: ColorManager.white, height: 1.25);
    Widget fact(String label, String value) => Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(label.toUpperCase(), style: labelStyle),
          const SizedBox(height: 2),
          Text(value.isEmpty ? '—' : value, maxLines: 2, overflow: TextOverflow.ellipsis, style: valueStyle),
        ]);
    final okTone = Color.lerp(ColorManager.success, ColorManager.white, 0.3)!;
    final badTone = Color.lerp(ColorManager.danger, ColorManager.white, 0.3)!;
    final legendStyle = ui(size: 10, weight: FontWeight.w700, color: ColorManager.white.withValues(alpha: 0.9));
    Widget dot(Color c) => Container(
        width: 7, height: 7, margin: const EdgeInsets.only(right: 4), decoration: BoxDecoration(shape: BoxShape.circle, color: c));

    return Container(
      margin: const EdgeInsets.fromLTRB(14, 0, 14, 14),
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: BorderRadius.circular(22),
        boxShadow: [BoxShadow(color: p.primaryDark.withValues(alpha: 0.55), blurRadius: 30, spreadRadius: -18, offset: const Offset(0, 18))],
      ),
      child: Stack(children: [
        Positioned.fill(
          child: DecoratedBox(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(22),
              gradient: RadialGradient(
                center: const Alignment(1, -1),
                radius: 0.9,
                colors: [p.accent.withValues(alpha: 0.32), p.accent.withValues(alpha: 0)],
              ),
            ),
          ),
        ),
        Positioned.fill(
          child: Container(
            margin: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(17),
              border: Border.all(color: p.accent.withValues(alpha: 0.28)),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(15),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Expanded(flex: 11, child: fact('Lessee', job.lesseeName)),
              const SizedBox(width: 8),
              Expanded(flex: 10, child: fact('Scheduled', Dates.human(job.scheduledDate))),
              const SizedBox(width: 8),
              Expanded(flex: 10, child: fact('You sign as', detail.mySigningLabel)),
            ]),
            const SizedBox(height: 12),
            Container(height: 1, color: ColorManager.white.withValues(alpha: 0.12)),
            const SizedBox(height: 12),
            Row(children: [
              Text.rich(TextSpan(children: [
                TextSpan(text: '$pct', style: ui(size: 36, weight: FontWeight.w800, color: ColorManager.white, height: 0.9, letterSpacing: -1.5)),
                TextSpan(text: '%', style: ui(size: 16, weight: FontWeight.w800, color: p.heroLabel)),
              ])),
              const SizedBox(width: 14),
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  StatusSegmentBar(
                    ok: ok,
                    bad: bad,
                    total: total,
                    height: 7,
                    okColor: okTone,
                    badColor: badTone,
                    trackColor: ColorManager.white.withValues(alpha: 0.15),
                  ),
                  const SizedBox(height: 7),
                  Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                    Row(mainAxisSize: MainAxisSize.min, children: [
                      dot(okTone),
                      Text('$ok ${detail.isMoveOut ? 'good' : 'present'}', style: legendStyle),
                    ]),
                    if (detail.isMoveOut)
                      Row(mainAxisSize: MainAxisSize.min, children: [dot(badTone), Text('$bad damaged', style: legendStyle)]),
                    Text('$left left', style: legendStyle),
                  ]),
                ]),
              ),
            ]),
          ]),
        ),
      ]),
    );
  }
}

class _SealedBanner extends StatelessWidget {
  const _SealedBanner();

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 0, 14, 12),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      decoration: BoxDecoration(color: p.goldTint, borderRadius: BorderRadius.circular(12)),
      child: Row(children: [
        Icon(Icons.verified_outlined, size: 16, color: p.goldText),
        const SizedBox(width: 8),
        Expanded(
          child: Text('This hand-over is sealed — the record is read-only.',
              style: ui(size: 11.5, weight: FontWeight.w700, color: p.goldText)),
        ),
      ]),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/checklist_models.dart';
import '../../logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import '../../widgets/v3/atelier_parts.dart';
import '../../widgets/v3/checklist_item_card.dart';
import '../../widgets/v3/checklist_item_sheet.dart';
import '../../widgets/v3/fixture_section.dart';
import 'checklist_handover_screen.dart';

enum _RoomFilter {
  all('All'),
  todo('To do'),
  damaged('Damaged');

  const _RoomFilter(this.label);
  final String label;

  bool matches(ChecklistLine l, String phase) => switch (this) {
        _RoomFilter.all => true,
        _RoomFilter.todo => !l.isCheckedFor(phase),
        _RoomFilter.damaged => l.isDamagedFor(phase),
      };
}

/// One room (C3): filterable 2-column item grid, the room's fixture comments,
/// and the "Rest are good / Next room" bar.
class ChecklistRoomScreen extends StatefulWidget {
  const ChecklistRoomScreen({super.key, required this.roomName});
  final String roomName;

  /// Pushes a room over the current screen, sharing its [ChecklistDetailCubit].
  static Future<void> open(BuildContext context, String roomName, {bool replace = false}) {
    final cubit = context.read<ChecklistDetailCubit>();
    final route = MaterialPageRoute<void>(
      builder: (_) => TabletRailScaffold(
        activeTab: Routes.checklistsTabIndex,
        child: BlocProvider.value(value: cubit, child: ChecklistRoomScreen(roomName: roomName)),
      ),
    );
    final nav = Navigator.of(context);
    return replace ? nav.pushReplacement<void, void>(route) : nav.push<void>(route);
  }

  @override
  State<ChecklistRoomScreen> createState() => _ChecklistRoomScreenState();
}

class _ChecklistRoomScreenState extends State<ChecklistRoomScreen> {
  _RoomFilter _filter = _RoomFilter.all;
  bool _marking = false;

  Future<void> _markRest(ChecklistRoom room, String phase, bool moveOut) async {
    final ids = room.uncheckedFor(phase).map((l) => l.id).toList();
    if (ids.isEmpty) return;
    final cubit = context.read<ChecklistDetailCubit>();
    setState(() => _marking = true);
    final ok = await cubit.markOk(ids);
    if (!mounted) return;
    setState(() => _marking = false);
    showChecklistToast(
      context,
      ok
          ? (moveOut ? 'Remaining items marked good' : 'Remaining items marked present')
          : (cubit.state.actionError ?? AppStrings.somethingWentWrong),
      clearBottomBar: true,
    );
  }

  void _next(List<ChecklistRoom> rooms, int index, String phase) {
    for (var k = 1; k < rooms.length; k++) {
      final candidate = rooms[(index + k) % rooms.length];
      if (candidate.uncheckedFor(phase).isNotEmpty) {
        ChecklistRoomScreen.open(context, candidate.name, replace: true);
        return;
      }
    }
    ChecklistHandoverScreen.open(context, replace: true);
  }

  /// The item grid: 2 columns on phones, 3–4 with taller photos on tablets.
  Widget _itemGrid(BuildContext context, ChecklistRoom room, List<ChecklistLine> visible, String phase) {
    final p = context.astra;
    if (visible.isEmpty) {
      return SliverToBoxAdapter(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(18, 24, 18, 24),
          child: Center(
            child: Text('Nothing in this filter', style: ui(size: 13, weight: FontWeight.w600, color: p.textMuted)),
          ),
        ),
      );
    }
    final tablet = context.isTablet;
    return SliverPadding(
      padding: const EdgeInsets.fromLTRB(14, 0, 14, 16),
      sliver: SliverGrid.builder(
        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: checklistGridColumns(context),
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          mainAxisExtent: tablet ? 180 : 158,
        ),
        itemCount: visible.length,
        itemBuilder: (_, i) => ChecklistItemCard(
          line: visible[i],
          phase: phase,
          photoHeight: tablet ? 120 : 98,
          onTap: () => showChecklistItemSheet(
            context,
            roomName: room.name,
            lineIds: room.lines.map((l) => l.id).toList(),
            initialIndex: room.lines.indexOf(visible[i]),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: BlocBuilder<ChecklistDetailCubit, ChecklistDetailState>(
          builder: (context, state) {
            final detail = state.detail;
            final rooms = detail?.rooms ?? const <ChecklistRoom>[];
            final index = rooms.indexWhere((r) => r.name == widget.roomName);
            if (detail == null || index < 0) {
              return Column(children: [
                AtelierTopBar(kicker: 'Room', title: widget.roomName),
                const Expanded(
                  child: EmptyState(icon: Icons.meeting_room_outlined, title: 'Room not found', message: 'Pull to refresh the hand-over and try again.'),
                ),
              ]);
            }
            final room = rooms[index];
            final phase = detail.phase;
            final moveOut = detail.isMoveOut;
            final filters = moveOut ? _RoomFilter.values : const [_RoomFilter.all, _RoomFilter.todo];
            final filter = filters.contains(_filter) ? _filter : _RoomFilter.all;
            final visible = room.lines.where((l) => filter.matches(l, phase)).toList();
            final unchecked = room.uncheckedFor(phase);
            final hasNextRoom = rooms.asMap().entries.any((e) => e.key != index && e.value.uncheckedFor(phase).isNotEmpty);
            final locked = detail.sealed;

            return Column(children: [
              AtelierTopBar(
                kicker: 'Room ${index + 1} of ${rooms.length} · Unit ${detail.job.unit}',
                title: room.name,
              ),
              MaxWidthBox(
                maxWidth: checklistContentWidth(context),
                child: SizedBox(
                  height: 40,
                  child: ListView(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
                    children: [
                      for (final f in filters) ...[
                        _FilterChip(
                          label: f.label,
                          count: room.lines.where((l) => f.matches(l, phase)).length,
                          active: f == filter,
                          onTap: () => setState(() => _filter = f),
                        ),
                        const SizedBox(width: 6),
                      ],
                    ],
                  ),
                ),
              ),
              Expanded(
                child: MaxWidthBox(
                  maxWidth: checklistContentWidth(context),
                  // One shape at every width: the item scroll view is always the
                  // Row's first child (its scroll position survives a rotation);
                  // wide windows only add the fixture column beside it.
                  child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Expanded(
                      child: CustomScrollView(slivers: [
                        _itemGrid(context, room, visible, phase),
                        if (!context.isWide) SliverToBoxAdapter(child: FixtureSection(detail: detail, category: room.name)),
                        const SliverToBoxAdapter(child: SizedBox(height: 24)),
                      ]),
                    ),
                    if (context.isWide)
                      SizedBox(
                        width: 400,
                        child: ListView(
                          padding: const EdgeInsets.only(bottom: 24),
                          children: [FixtureSection(detail: detail, category: room.name)],
                        ),
                      ),
                  ]),
                ),
              ),
              if (!locked)
                MaxWidthBox(
                  maxWidth: context.isTablet ? 720 : 640,
                  child: SafeArea(
                    top: false,
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
                      child: Row(children: [
                        Expanded(
                          flex: 10,
                          child: AtelierButton(
                            label: moveOut ? 'Rest are good' : 'Mark rest present',
                            leadingIcon: Icons.done_all,
                            kind: AtelierButtonKind.ghost,
                            busy: _marking,
                            onTap: unchecked.isEmpty || state.busy ? null : () => _markRest(room, phase, moveOut),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          flex: 12,
                          child: AtelierButton(
                            label: hasNextRoom ? 'Next room' : 'Hand-over',
                            icon: hasNextRoom ? Icons.arrow_forward : Icons.draw_outlined,
                            onTap: () => _next(rooms, index, phase),
                          ),
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

class _FilterChip extends StatelessWidget {
  const _FilterChip({required this.label, required this.count, required this.active, required this.onTap});
  final String label;
  final int count;
  final bool active;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final fg = active ? p.canvas : p.textSecondary;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
        decoration: BoxDecoration(
          color: active ? p.ink : p.cardSolid,
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: active ? p.ink : p.hairline),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Text(label, style: ui(size: 11.5, weight: FontWeight.w800, color: fg)),
          const SizedBox(width: 4),
          Text('$count', style: ui(size: 11.5, weight: FontWeight.w800, color: active ? fg.withValues(alpha: 0.7) : p.textMuted)),
        ]),
      ),
    );
  }
}

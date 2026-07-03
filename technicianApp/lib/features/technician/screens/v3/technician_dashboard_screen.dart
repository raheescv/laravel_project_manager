import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/technician_models.dart';
import '../../logic/dashboard_cubit/dashboard_cubit.dart';
import '../../widgets/v3/complaint_card.dart';
import '../../widgets/v3/status_style.dart';

/// Mission-control dashboard: gradient hero with a shift-progress ring,
/// floating KPI bento, outstanding alert, "up next" job spotlight, priority
/// meter, weekly completion chart and the recent complaints feed.
class TechnicianDashboardScreen extends StatefulWidget {
  const TechnicianDashboardScreen({super.key});

  @override
  State<TechnicianDashboardScreen> createState() =>
      _TechnicianDashboardScreenState();
}

class _TechnicianDashboardScreenState extends State<TechnicianDashboardScreen> {
  /// How far the KPI bento rides up over the hero's bottom edge.
  static const double _overlap = 44;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
        (_) => context.read<TechnicianDashboardCubit>().load());
  }

  @override
  Widget build(BuildContext context) {
    final cubit = context.watch<TechnicianDashboardCubit>();
    final data = cubit.data;
    final name = data?.technicianName.isNotEmpty == true
        ? data!.technicianName
        : (context.watch<AuthCubit>().user?.name ?? 'Technician');

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: RefreshIndicator(
          onRefresh: () => cubit.load(),
          edgeOffset: MediaQuery.of(context).padding.top,
          child: MaxWidthBox(
            maxWidth: 620,
            child: _body(context, cubit, data, name),
          ),
        ),
      ),
    );
  }

  Widget _body(BuildContext context, TechnicianDashboardCubit cubit,
      TechnicianDashboard? data, String name) {
    final p = context.astra;
    if (cubit.loading && data == null) {
      return Center(child: CircularProgressIndicator(color: p.primary));
    }
    if (cubit.error != null && data == null) {
      return ListView(children: [
        const SizedBox(height: 120),
        EmptyState(
          icon: Icons.wifi_off_rounded,
          title: 'Could not load',
          message: cubit.error,
          action: AstraButton(label: 'Retry', expand: false, onTap: () => cubit.load()),
        ),
      ]);
    }
    if (data == null) return const SizedBox.shrink();

    final c = data.counts;
    return ListView(
      padding: const EdgeInsets.only(bottom: 160),
      children: [
        _hero(context, data, name),
        Container(
          transform: Matrix4.translationValues(0, -_overlap, 0),
          child: Column(
            children: [
              _kpiGrid(context, c),
              if (c.outstanding > 0) ...[
                const SizedBox(height: 12),
                _outstandingAlert(context, c.outstanding),
              ],
              if (data.next != null) ...[
                const SizedBox(height: 20),
                _section(SectionLabel('Up next')),
                const SizedBox(height: 10),
                _section(_nextJobCard(context, data.next!)),
              ],
              const SizedBox(height: 20),
              _section(SectionLabel('Open jobs by priority')),
              const SizedBox(height: 10),
              _section(_priorityCard(context, data.priority)),
              if (data.week.isNotEmpty) ...[
                const SizedBox(height: 20),
                _section(SectionLabel('Your week')),
                const SizedBox(height: 10),
                _section(_weekCard(context, data.week)),
              ],
              const SizedBox(height: 20),
              _section(SectionLabel('Recent complaints',
                  trailing: GestureDetector(
                    onTap: () => context.go('/complaints'),
                    child: Text('View all',
                        style: ui(size: 11.5, weight: FontWeight.w700, color: p.primary)),
                  ))),
              const SizedBox(height: 10),
              if (data.recent.isEmpty)
                const EmptyState(
                    icon: Icons.inbox_outlined,
                    title: 'No complaints yet',
                    message: 'Assigned jobs will show up here.')
              else
                for (final item in data.recent)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
                    child: ComplaintCard(
                        item: item,
                        onTap: () => context.push('/complaints/${item.id}')),
                  ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _section(Widget child) =>
      Padding(padding: const EdgeInsets.symmetric(horizontal: 16), child: child);

  // ---- Hero -----------------------------------------------------------------

  Widget _hero(BuildContext context, TechnicianDashboard data, String name) {
    final p = context.astra;
    final c = data.counts;
    final open = c.assigned + c.pending + c.outstanding;
    final total = c.completedToday + open;
    final progress = total == 0 ? 0.0 : c.completedToday / total;

    Widget deco(double size) => Container(
          width: size,
          height: size,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: Colors.white.withValues(alpha: 0.07),
          ),
        );

    return Container(
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(30)),
      ),
      child: ClipRRect(
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(30)),
        child: Stack(
          children: [
            Positioned(top: -110, right: -70, child: deco(260)),
            Positioned(bottom: -80, left: -50, child: deco(180)),
            SafeArea(
              bottom: false,
              child: Padding(
                padding: EdgeInsets.fromLTRB(20, 10, 20, 24 + _overlap),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                DateFormat('EEEE · d MMMM')
                                    .format(DateTime.now())
                                    .toUpperCase(),
                                style: ui(
                                    size: 10,
                                    weight: FontWeight.w700,
                                    color: Colors.white.withValues(alpha: 0.66),
                                    letterSpacing: 1.4),
                              ),
                              const SizedBox(height: 4),
                              Text('Welcome back, $name',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: serif(size: 22, color: Colors.white)),
                              const SizedBox(height: 6),
                              Row(
                                children: [
                                  Container(
                                    width: 7,
                                    height: 7,
                                    decoration: const BoxDecoration(
                                      shape: BoxShape.circle,
                                      color: Color(0xFF4ADE80),
                                      boxShadow: [
                                        BoxShadow(color: Color(0xFF4ADE80), blurRadius: 8)
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 6),
                                  Text(
                                    'On duty · $open open ${open == 1 ? 'job' : 'jobs'}',
                                    style: ui(
                                        size: 11,
                                        weight: FontWeight.w600,
                                        color: Colors.white.withValues(alpha: 0.72)),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 10),
                        Monogram(letter: name.isNotEmpty ? name[0].toUpperCase() : 'T', size: 46),
                      ],
                    ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        _progressRing(progress),
                        const SizedBox(width: 20),
                        Expanded(
                          child: Column(
                            children: [
                              Row(children: [
                                _heroStat('${c.completedToday}', 'Done today'),
                                _heroStat('$open', 'Remaining'),
                              ]),
                              const SizedBox(height: 12),
                              Row(children: [
                                _heroStat('${c.completedWeek}', 'This week'),
                                _heroStat('${data.priority.critical}', 'Critical'),
                              ]),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _heroStat(String value, String label) => Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(value, style: serif(size: 19, color: Colors.white)),
            const SizedBox(height: 1),
            Text(label.toUpperCase(),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(
                    size: 8.5,
                    weight: FontWeight.w700,
                    color: Colors.white.withValues(alpha: 0.62),
                    letterSpacing: 0.8)),
          ],
        ),
      );

  Widget _progressRing(double progress) {
    return SizedBox(
      width: 92,
      height: 92,
      child: Stack(
        alignment: Alignment.center,
        children: [
          CustomPaint(
            size: const Size(92, 92),
            painter: _RingPainter(progress: progress),
          ),
          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('${(progress * 100).round()}%',
                  style: serif(size: 21, color: Colors.white)),
              Text('CLEARED',
                  style: ui(
                      size: 7.5,
                      weight: FontWeight.w800,
                      color: Colors.white.withValues(alpha: 0.6),
                      letterSpacing: 1.1)),
            ],
          ),
        ],
      ),
    );
  }

  // ---- KPI bento --------------------------------------------------------------

  Widget _kpiGrid(BuildContext context, DashboardCounts c) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: GridView.count(
        crossAxisCount: 2,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: 11,
        crossAxisSpacing: 11,
        childAspectRatio: 1.9,
        children: [
          _kpi(context, 'Assigned', c.assigned, Icons.assignment_ind_outlined, 'info'),
          _kpi(context, 'Pending', c.pending, Icons.pending_actions_outlined, 'warning'),
          _kpi(context, 'Done today', c.completedToday, Icons.today_outlined, 'success'),
          _kpi(context, 'Done this week', c.completedWeek, Icons.date_range_outlined, 'success'),
        ],
      ),
    );
  }

  Widget _kpi(BuildContext context, String label, int value, IconData icon, String color) {
    final p = context.astra;
    final tint = astraTint(context, color);
    return AstraCard(
      radius: 16,
      padding: const EdgeInsets.all(13),
      child: Row(
        children: [
          IconChip(icon: icon, size: 38, radius: 11, bg: tint.bg, fg: tint.fg),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text('$value', style: serif(size: 22, color: p.ink)),
                Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ---- Outstanding alert -------------------------------------------------------

  Widget _outstandingAlert(BuildContext context, int outstanding) {
    final p = context.astra;
    return _section(GestureDetector(
      onTap: () => context.go('/complaints'),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: p.dangerTint,
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: AstraPalette.danger.withValues(alpha: 0.28)),
        ),
        child: Row(
          children: [
            const Icon(Icons.error_outline, size: 20, color: AstraPalette.danger),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                      '$outstanding outstanding ${outstanding == 1 ? 'job' : 'jobs'}',
                      style: ui(size: 12.5, weight: FontWeight.w800, color: AstraPalette.danger)),
                  Text('Waiting on you — open the list to follow up',
                      style: ui(size: 10.5, weight: FontWeight.w600, color: p.textSecondary)),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, size: 20, color: AstraPalette.danger),
          ],
        ),
      ),
    ));
  }

  // ---- Up next spotlight ---------------------------------------------------------

  Widget _nextJobCard(BuildContext context, ComplaintListItem item) {
    final p = context.astra;
    final location = [
      if (item.propertyNumber.isNotEmpty) 'Unit ${item.propertyNumber}',
      if (item.building.isNotEmpty) item.building,
      if (item.group.isNotEmpty) item.group,
    ].join(' · ');
    final scheduled = item.date.isEmpty
        ? '—'
        : '${Dates.human(item.date)}${item.time.isNotEmpty ? ' · ${item.time}' : ''}';

    return AstraCard(
      radius: 18,
      padding: const EdgeInsets.all(15),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              if (item.registrationId.isNotEmpty) ...[
                Text('#${item.registrationId}',
                    style: ui(size: 10.5, weight: FontWeight.w800, color: p.primary, letterSpacing: 0.4)),
                const SizedBox(width: 8),
              ],
              if (item.priorityLabel.isNotEmpty)
                AstraStatusPill(
                    label: item.priorityLabel.toUpperCase(),
                    colorName: item.priorityColor,
                    icon: priorityIcon(item.priority)),
              const Spacer(),
              AstraStatusPill(label: item.statusLabel, colorName: item.statusColor),
            ],
          ),
          const SizedBox(height: 9),
          Text(item.complaintName.isEmpty ? 'Complaint #${item.id}' : item.complaintName,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: serif(size: 17, color: p.ink)),
          if (location.isNotEmpty) ...[
            const SizedBox(height: 4),
            Row(
              children: [
                Icon(Icons.place_outlined, size: 13, color: p.textMuted),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(location,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary)),
                ),
              ],
            ),
          ],
          const SizedBox(height: 12),
          Container(height: 1, color: p.hairline),
          const SizedBox(height: 11),
          Row(
            children: [
              _nextMeta(context, 'Customer',
                  item.customerName.isEmpty ? '—' : item.customerName),
              _nextMeta(context, 'Scheduled', scheduled),
              _nextMeta(context, 'Category',
                  item.categoryName.isEmpty ? '—' : item.categoryName),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: AstraButton(
                  label: 'Open job',
                  icon: Icons.arrow_forward_rounded,
                  onTap: () => context.push('/complaints/${item.id}'),
                ),
              ),
              if (item.customerMobile.isNotEmpty) ...[
                const SizedBox(width: 9),
                GestureDetector(
                  onTap: () => _call(item.customerMobile),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                    decoration: BoxDecoration(
                      color: p.tint,
                      borderRadius: BorderRadius.circular(context.astraTheme.rButton),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.call_outlined, size: 15, color: p.primary),
                        const SizedBox(width: 6),
                        Text('Call', style: ui(size: 13.5, weight: FontWeight.w800, color: p.primary)),
                      ],
                    ),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _nextMeta(BuildContext context, String label, String value) {
    final p = context.astra;
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label.toUpperCase(),
              style: ui(
                  size: 8.5,
                  weight: FontWeight.w800,
                  color: p.textMuted,
                  letterSpacing: 0.8)),
          const SizedBox(height: 3),
          Text(value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 11.5, weight: FontWeight.w700, color: p.ink)),
        ],
      ),
    );
  }

  Future<void> _call(String mobile) async {
    final uri = Uri.parse('tel:$mobile');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  // ---- Priority breakdown -------------------------------------------------------

  Widget _priorityCard(BuildContext context, PriorityBreakdown pr) {
    final p = context.astra;
    final rows = [
      ('Critical', pr.critical, 'danger', 'critical'),
      ('High', pr.high, 'warning', 'high'),
      ('Medium', pr.medium, 'info', 'medium'),
      ('Low', pr.low, 'secondary', 'low'),
    ];
    final max = [pr.critical, pr.high, pr.medium, pr.low].fold<int>(1, (a, b) => b > a ? b : a);
    return AstraCard(
      radius: 16,
      child: Column(
        children: [
          for (var i = 0; i < rows.length; i++) ...[
            if (i > 0) const SizedBox(height: 12),
            Builder(builder: (context) {
              final (label, value, color, key) = rows[i];
              final tint = astraTint(context, color);
              return Row(
                children: [
                  Icon(priorityIcon(key), size: 15, color: tint.fg),
                  const SizedBox(width: 8),
                  SizedBox(width: 62, child: Text(label, style: ui(size: 12, weight: FontWeight.w700, color: p.ink))),
                  Expanded(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(6),
                      child: LinearProgressIndicator(
                        value: max == 0 ? 0 : value / max,
                        minHeight: 8,
                        backgroundColor: p.hairline.withValues(alpha: 0.5),
                        valueColor: AlwaysStoppedAnimation(tint.fg),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Text('$value', style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
                ],
              );
            }),
          ],
          if (pr.total == 0)
            Padding(
              padding: const EdgeInsets.only(top: 12),
              child: Text('No open jobs 🎉', style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
            ),
        ],
      ),
    );
  }

  // ---- Week chart -----------------------------------------------------------------

  Widget _weekCard(BuildContext context, List<DayStat> week) {
    final p = context.astra;
    final today = DateFormat('yyyy-MM-dd').format(DateTime.now());
    final max = week.fold<int>(1, (a, d) => d.count > a ? d.count : a);

    return AstraCard(
      radius: 16,
      padding: const EdgeInsets.fromLTRB(12, 14, 12, 12),
      child: SizedBox(
        height: 106,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            for (final d in week)
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      Text('${d.count}',
                          style: ui(size: 9.5, weight: FontWeight.w800, color: p.textSecondary)),
                      const SizedBox(height: 5),
                      Container(
                        height: 4 + 60 * (d.count / max),
                        decoration: BoxDecoration(
                          gradient: d.date == today ? p.primaryGradient : null,
                          color: d.date == today ? null : p.tint,
                          borderRadius: BorderRadius.circular(6),
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(d.label.toUpperCase(),
                          style: ui(
                              size: 8.5,
                              weight: FontWeight.w800,
                              color: d.date == today ? p.primary : p.textMuted,
                              letterSpacing: 0.5)),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// Draws the hero shift-progress ring: a faint full track plus a round-capped
/// white arc from 12 o'clock.
class _RingPainter extends CustomPainter {
  const _RingPainter({required this.progress});

  final double progress;

  @override
  void paint(Canvas canvas, Size size) {
    final center = size.center(Offset.zero);
    final radius = (size.shortestSide - 8) / 2;

    final track = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 8
      ..color = Colors.white.withValues(alpha: 0.18);
    canvas.drawCircle(center, radius, track);

    if (progress <= 0) return;
    final arc = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 8
      ..strokeCap = StrokeCap.round
      ..color = Colors.white;
    canvas.drawArc(
      Rect.fromCircle(center: center, radius: radius),
      -math.pi / 2,
      2 * math.pi * progress.clamp(0.0, 1.0),
      false,
      arc,
    );
  }

  @override
  bool shouldRepaint(_RingPainter old) => old.progress != progress;
}

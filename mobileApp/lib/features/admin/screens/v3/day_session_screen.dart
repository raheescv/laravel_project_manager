import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/features/admin/logic/day_session_cubit/day_session_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Day Session — open / close the branch sale day for a chosen date & time.
///
/// Hybrid layout: a Signature status hero, a Date + Time picker pair, a
/// lifecycle timeline (Opened → In session → Close), and a sticky action that
/// raises a confirm sheet before closing. State + the toggle live in
/// [DaySessionCubit]; a successful toggle syncs back to the auth user.
class DaySessionScreen extends StatefulWidget {
  const DaySessionScreen({super.key});

  @override
  State<DaySessionScreen> createState() => _DaySessionScreenState();
}

class _DaySessionScreenState extends State<DaySessionScreen> {
  @override
  void initState() {
    super.initState();
    // Re-seed the status from the current user each time the screen opens (the
    // controller persists across navigations / re-logins).
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      context.read<DaySessionCubit>().seedFromUser(context.read<AuthCubit>().user);
      setState(() {});
    });
  }

  @override
  Widget build(BuildContext context) {
    final c = context.watch<DaySessionCubit>();
    final user = context.watch<AuthCubit>().user;
    final branchCtrl = context.watch<BranchCubit>();
    final branchName = c.session?.branch.isNotEmpty == true
        ? c.session!.branch
        : (branchCtrl.selected?.name ?? '');

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            _hero(c, user, branchName),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 620,
                child: Stack(
                  children: [
                    ListView(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 130),
                      children: [
                        _dateTimeCard(c),
                        const SizedBox(height: 14),
                        _timelineCard(c, user),
                        const SizedBox(height: 14),
                        _notice(c),
                      ],
                    ),
                    Positioned(left: 0, right: 0, bottom: 0, child: _dock(c)),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ---------------------------------------------------------------- HERO
  Widget _hero(DaySessionCubit c, ApiUser? user, String branchName) {
    final p = context.astra;
    final open = c.isOpen;
    final openedAt = c.session?.openedAt.isNotEmpty == true
        ? c.session!.openedAt
        : (user?.daySessionOpenedAt ?? '');
    final closedAt = c.session?.closedAt.isNotEmpty == true
        ? c.session!.closedAt
        : (user?.lastClosedSessionAt ?? '');

    final since = open
        ? (openedAt.isEmpty ? 'Session is open' : 'Open since ${Dates.humanDateTime(openedAt)}')
        : (closedAt.isEmpty ? 'No open day right now' : 'Last closed ${Dates.humanDateTime(closedAt)}');

    return Container(
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(30)),
      ),
      child: Stack(
        children: [
          Positioned(
            right: -40,
            top: -50,
            child: Container(
              width: 200,
              height: 200,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(colors: [p.accent.withValues(alpha: 0.22), Colors.transparent]),
              ),
            ),
          ),
          SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 6, 16, 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
                      const SizedBox(width: 11),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('BRANCH DAY SESSION',
                                style: ui(size: 9.5, weight: FontWeight.w800, color: p.accent, letterSpacing: 2)),
                            const SizedBox(height: 3),
                            Text('Day Session', style: serif(size: 21, color: Colors.white)),
                          ],
                        ),
                      ),
                      if (branchName.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.14),
                            borderRadius: BorderRadius.circular(999),
                            border: Border.all(color: Colors.white.withValues(alpha: 0.18)),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.storefront_outlined, size: 13, color: Colors.white),
                              const SizedBox(width: 5),
                              Text(branchName,
                                  style: ui(size: 11, weight: FontWeight.w800, color: Colors.white)),
                            ],
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  Row(
                    children: [
                      Container(
                        width: 54,
                        height: 54,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.14),
                          borderRadius: BorderRadius.circular(18),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
                        ),
                        child: Icon(open ? Icons.wb_sunny_outlined : Icons.bedtime_outlined,
                            size: 26, color: Colors.white),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Text(open ? 'Day Open' : 'Day Closed',
                                    style: serif(size: 26, color: Colors.white)),
                                const SizedBox(width: 10),
                                _heroPill(open),
                              ],
                            ),
                            const SizedBox(height: 3),
                            Text(since,
                                style: ui(
                                    size: 11.5,
                                    weight: FontWeight.w600,
                                    color: Colors.white.withValues(alpha: 0.82))),
                          ],
                        ),
                      ),
                    ],
                  ),
                  if (c.session != null) ...[
                    const SizedBox(height: 16),
                    Divider(color: Colors.white.withValues(alpha: 0.16), height: 1),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        if (c.session!.openedBy.isNotEmpty)
                          _heroMeta('Opened by', c.session!.openedBy),
                        if (open)
                          _heroMeta('Opening float', Money.of(c.session!.openingAmount), gold: true)
                        else if (c.session!.closedBy.isNotEmpty)
                          _heroMeta('Closed by', c.session!.closedBy),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _heroPill(bool open) {
    final c = open ? AstraPalette.success : AstraPalette.danger;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(width: 7, height: 7, decoration: BoxDecoration(color: c, shape: BoxShape.circle)),
          const SizedBox(width: 5),
          Text(open ? 'OPEN' : 'CLOSED',
              style: ui(size: 9.5, weight: FontWeight.w800, color: Colors.white, letterSpacing: 0.5)),
        ],
      ),
    );
  }

  Widget _heroMeta(String k, String v, {bool gold = false}) {
    final p = context.astra;
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(k.toUpperCase(),
              style: ui(
                  size: 8.5,
                  weight: FontWeight.w800,
                  color: Colors.white.withValues(alpha: 0.6),
                  letterSpacing: 1.4)),
          const SizedBox(height: 3),
          gold
              ? Text(v, style: serif(size: 16, color: p.accent))
              : Text(v, style: ui(size: 13, weight: FontWeight.w800, color: Colors.white)),
        ],
      ),
    );
  }

  // ------------------------------------------------------- DATE + TIME CARD
  Widget _dateTimeCard(DaySessionCubit c) {
    final label = c.isOpen ? 'Closing date & time' : 'Opening date & time';
    return AstraCard(
      radius: 18,
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel(label),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                flex: 5,
                child: _dtCell(
                  icon: Icons.calendar_today_outlined,
                  k: 'Date',
                  v: Dates.weekday(c.selected),
                  onTap: () => _pickDate(c),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                flex: 4,
                child: _dtCell(
                  icon: Icons.schedule_outlined,
                  k: 'Time',
                  v: Dates.time(c.selected),
                  onTap: () => _pickTime(c),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          _quickChip(Icons.bolt_outlined, 'Set to now', c.setNow),
        ],
      ),
    );
  }

  /// A small tinted one-tap quick-set chip used under the pickers.
  Widget _quickChip(IconData icon, String label, VoidCallback onTap) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(11)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 13, color: p.primary),
            const SizedBox(width: 6),
            Text(label, style: ui(size: 11.5, weight: FontWeight.w800, color: p.primary)),
          ],
        ),
      ),
    );
  }

  Widget _dtCell({required IconData icon, required String k, required String v, required VoidCallback onTap}) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 11),
        decoration: BoxDecoration(
          color: p.isDark ? Colors.white.withValues(alpha: 0.04) : Colors.black.withValues(alpha: 0.015),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: p.hairline),
        ),
        child: Row(
          children: [
            IconChip(icon: icon, size: 36, radius: 10),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(k.toUpperCase(),
                      style: ui(size: 8.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.2)),
                  const SizedBox(height: 2),
                  Text(v, style: serif(size: 15, color: p.ink), maxLines: 1, overflow: TextOverflow.ellipsis),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ----------------------------------------------------------- TIMELINE
  Widget _timelineCard(DaySessionCubit c, ApiUser? user) {
    final open = c.isOpen;
    final openedAt = c.session?.openedAt.isNotEmpty == true
        ? c.session!.openedAt
        : (user?.daySessionOpenedAt ?? '');
    final closedAt = c.session?.closedAt.isNotEmpty == true
        ? c.session!.closedAt
        : (user?.lastClosedSessionAt ?? '');

    final nodes = <Widget>[];
    if (open) {
      nodes.add(_node(
        state: _NodeState.done,
        isLast: false,
        title: 'Day opened',
        subtitle: openedAt.isEmpty ? 'This session is open.' : Dates.humanDateTime(openedAt),
        trailingFloat: c.session != null ? Money.of(c.session!.openingAmount) : null,
      ));
      nodes.add(_node(
        state: _NodeState.live,
        isLast: false,
        title: 'In session',
        subtitle: 'Sales are recorded against this day. Close it when the branch is done.',
        badge: 'LIVE',
      ));
      nodes.add(_node(
        state: _NodeState.pending,
        isLast: true,
        title: 'Close day',
        subtitle: 'Pick the closing date & time above, then confirm below to finalise.',
      ));
    } else {
      nodes.add(_node(
        state: _NodeState.done,
        isLast: false,
        title: 'Last day closed',
        subtitle: closedAt.isEmpty ? 'No recent session on record.' : Dates.humanDateTime(closedAt),
      ));
      nodes.add(_node(
        state: _NodeState.pending,
        isLast: true,
        title: 'Open a new day',
        subtitle: 'Pick the opening date & time above, then start the day below.',
      ));
    }

    return AstraCard(
      radius: 18,
      padding: const EdgeInsets.fromLTRB(18, 18, 18, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel('Session lifecycle'),
          const SizedBox(height: 14),
          ...nodes,
        ],
      ),
    );
  }

  Widget _node({
    required _NodeState state,
    required bool isLast,
    required String title,
    required String subtitle,
    String? badge,
    String? trailingFloat,
  }) {
    final p = context.astra;
    final Color dotColor;
    final Widget dotInner;
    switch (state) {
      case _NodeState.done:
        dotColor = AstraPalette.success;
        dotInner = const Icon(Icons.check, size: 13, color: Colors.white);
      case _NodeState.live:
        dotColor = p.primary;
        dotInner = Container(
          width: 8,
          height: 8,
          decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
        );
      case _NodeState.pending:
        dotColor = p.textMuted;
        dotInner = const SizedBox.shrink();
    }
    final filled = state != _NodeState.pending;

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            children: [
              Container(
                width: 24,
                height: 24,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: filled ? dotColor : Colors.transparent,
                  shape: BoxShape.circle,
                  border: Border.all(color: dotColor, width: 2),
                  boxShadow: filled
                      ? [BoxShadow(color: dotColor.withValues(alpha: 0.18), blurRadius: 0, spreadRadius: 3)]
                      : null,
                ),
                child: dotInner,
              ),
              if (!isLast)
                Expanded(
                  child: Container(width: 2, color: p.hairline),
                ),
            ],
          ),
          const SizedBox(width: 13),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(bottom: isLast ? 8 : 22, top: 1),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(title, style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
                      if (badge != null) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(999)),
                          child: Text(badge,
                              style: ui(size: 8.5, weight: FontWeight.w800, color: p.primary, letterSpacing: 0.6)),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(subtitle,
                      style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary, height: 1.45)),
                  if (trailingFloat != null) ...[
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(10)),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text('Opening float  ',
                              style: ui(size: 10.5, weight: FontWeight.w700, color: p.textSecondary)),
                          Text(trailingFloat, style: serif(size: 13, color: p.goldText)),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ------------------------------------------------------------- NOTICE
  Widget _notice(DaySessionCubit c) {
    final p = context.astra;
    final open = c.isOpen;
    // The warn tint/text getters are already dark-aware (translucent wash + a
    // legible gold on dark), so the banner reads correctly on either canvas.
    final bg = open ? p.warnTint : p.tint;
    final fg = open ? p.warnText : p.primary;
    final text = open
        ? 'Closing finalises sales for this session. You\'ll need to open a new day to keep selling.'
        : 'Opening a day lets staff record sales against it. Nothing is finalised until you close.';
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 13, 14, 13),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(14)),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(open ? Icons.info_outline : Icons.lightbulb_outline, size: 17, color: fg),
          const SizedBox(width: 10),
          Expanded(
            child: Text(text,
                style: ui(size: 11.5, weight: FontWeight.w600, color: p.ink, height: 1.45)),
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------- DOCK
  Widget _dock(DaySessionCubit c) {
    final p = context.astra;
    final open = c.isOpen;
    final stamp = '${Dates.weekday(c.selected).split(',').last.trim()}, ${Dates.time(c.selected)}';
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [p.canvas.withValues(alpha: 0), p.canvas, p.canvas],
          stops: const [0, 0.4, 1],
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          _bigButton(
            label: open ? 'Close day · $stamp' : 'Open day · $stamp',
            icon: open ? Icons.lock_outline : Icons.lock_open_outlined,
            danger: open,
            busy: c.busy,
            onTap: () => _act(c),
          ),
          const SizedBox(height: 9),
          Text(
            open ? 'You\'ll confirm before the day is closed' : 'Starts a new sale session for this branch',
            style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted),
          ),
        ],
      ),
    );
  }

  Widget _bigButton({
    required String label,
    required IconData icon,
    required bool danger,
    required bool busy,
    required VoidCallback onTap,
  }) {
    final p = context.astra;
    final t = context.astraTheme;
    final gradient = danger
        ? const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFFE0657A), Color(0xFFC0405A)])
        : p.primaryGradient;
    final glow = danger ? const Color(0xFFC0405A) : p.primary;
    return GestureDetector(
      onTap: busy ? null : onTap,
      child: Container(
        height: 56,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          gradient: gradient,
          borderRadius: BorderRadius.circular(17),
          boxShadow: t.floatShadow(glow),
        ),
        child: busy
            ? const SizedBox(
                width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.6, color: Colors.white))
            : Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(icon, size: 19, color: Colors.white),
                  const SizedBox(width: 10),
                  Text(label, style: ui(size: 15, weight: FontWeight.w800, color: Colors.white)),
                ],
              ),
      ),
    );
  }

  // -------------------------------------------------------- INTERACTIONS
  /// Themed calendar dialog with a one-tap **Today** button in its action bar.
  Future<void> _pickDate(DaySessionCubit c) async {
    final now = DateTime.now();
    final user = context.read<AuthCubit>().user;
    DateTime first;
    if (c.isOpen) {
      final opened = DateTime.tryParse(user?.daySessionOpenedAt ?? '');
      first = opened != null ? DateUtils.dateOnly(opened) : DateUtils.dateOnly(now.subtract(const Duration(days: 1)));
    } else {
      first = DateUtils.dateOnly(now.subtract(const Duration(days: 30)));
    }
    final last = DateUtils.dateOnly(now);
    final today = DateUtils.dateOnly(now);
    final todayInRange = !today.isBefore(first) && !today.isAfter(last);

    var temp = DateUtils.dateOnly(c.selected);
    if (temp.isBefore(first)) temp = first;
    if (temp.isAfter(last)) temp = last;

    final picked = await showDialog<DateTime>(
      context: context,
      builder: (dctx) {
        final p = dctx.astra;
        return StatefulBuilder(
          builder: (dctx, setLocal) => Dialog(
            backgroundColor: p.cardSolid,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                _pickerHeader(c.isOpen ? 'Closing date' : 'Opening date', Dates.weekday(temp)),
                CalendarDatePicker(
                  initialDate: temp,
                  firstDate: first,
                  lastDate: last,
                  onDateChanged: (d) => setLocal(() => temp = d),
                ),
                _pickerActions(
                  quickIcon: Icons.today_outlined,
                  quickLabel: 'Today',
                  onQuick: todayInRange ? () => setLocal(() => temp = today) : null,
                  onCancel: () => Navigator.pop(dctx),
                  onOk: () => Navigator.pop(dctx, temp),
                ),
              ],
            ),
          ),
        );
      },
    );
    if (picked != null) c.setDate(picked);
  }

  /// Themed time-wheel dialog with a one-tap **Now** button in its action bar.
  Future<void> _pickTime(DaySessionCubit c) async {
    var temp = c.selected;
    final picked = await showDialog<TimeOfDay>(
      context: context,
      builder: (dctx) {
        final p = dctx.astra;
        return StatefulBuilder(
          builder: (dctx, setLocal) => Dialog(
            backgroundColor: p.cardSolid,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                _pickerHeader(c.isOpen ? 'Closing time' : 'Opening time', 'Scroll to set, or tap Now'),
                SizedBox(
                  height: 180,
                  child: CupertinoTheme(
                    data: CupertinoThemeData(
                      brightness: p.isDark ? Brightness.dark : Brightness.light,
                      textTheme: CupertinoTextThemeData(
                        dateTimePickerTextStyle: ui(size: 19, weight: FontWeight.w700, color: p.ink),
                      ),
                    ),
                    child: CupertinoDatePicker(
                      // The key changes only when "Now" is tapped, so the wheel
                      // jumps to the current time without rebuilding mid-scroll.
                      key: ValueKey(temp.millisecondsSinceEpoch),
                      mode: CupertinoDatePickerMode.time,
                      initialDateTime: temp,
                      use24hFormat: false,
                      onDateTimeChanged: (d) =>
                          temp = DateTime(temp.year, temp.month, temp.day, d.hour, d.minute),
                    ),
                  ),
                ),
                _pickerActions(
                  quickIcon: Icons.access_time,
                  quickLabel: 'Now',
                  onQuick: () {
                    final n = DateTime.now();
                    setLocal(() => temp = DateTime(temp.year, temp.month, temp.day, n.hour, n.minute));
                  },
                  onCancel: () => Navigator.pop(dctx),
                  onOk: () => Navigator.pop(dctx, TimeOfDay.fromDateTime(temp)),
                ),
              ],
            ),
          ),
        );
      },
    );
    if (picked != null) c.setTime(picked.hour, picked.minute);
  }

  Widget _pickerHeader(String eyebrow, String title) {
    final p = context.astra;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(eyebrow.toUpperCase(),
              style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.4)),
          const SizedBox(height: 4),
          Text(title, style: serif(size: 19, color: p.ink)),
        ],
      ),
    );
  }

  Widget _pickerActions({
    required IconData quickIcon,
    required String quickLabel,
    required VoidCallback? onQuick,
    required VoidCallback onCancel,
    required VoidCallback onOk,
  }) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.fromLTRB(10, 2, 12, 10),
      child: Row(
        children: [
          TextButton.icon(
            onPressed: onQuick,
            icon: Icon(quickIcon, size: 16),
            label: Text(quickLabel, style: ui(size: 13, weight: FontWeight.w800, color: p.primary)),
            style: TextButton.styleFrom(foregroundColor: p.primary),
          ),
          const Spacer(),
          TextButton(
            onPressed: onCancel,
            child: Text('Cancel', style: ui(size: 13.5, weight: FontWeight.w800, color: p.textSecondary)),
          ),
          TextButton(
            onPressed: onOk,
            child: Text('OK', style: ui(size: 13.5, weight: FontWeight.w800, color: p.primary)),
          ),
        ],
      ),
    );
  }

  Future<void> _act(DaySessionCubit c) async {
    if (c.busy) return;
    if (c.isOpen) {
      final ok = await _confirmClose(c);
      if (ok != true) return;
    }
    final res = await c.toggle();
    if (!mounted) return;
    final messenger = ScaffoldMessenger.of(context);
    messenger.clearSnackBars();
    if (res != null) {
      messenger.showSnackBar(SnackBar(
        backgroundColor: res.isOpen ? AstraPalette.success : const Color(0xFF334155),
        behavior: SnackBarBehavior.floating,
        content: Text(res.message.isEmpty ? 'Day session updated.' : res.message,
            style: ui(size: 13, weight: FontWeight.w700, color: Colors.white)),
      ));
    } else if (c.error != null) {
      messenger.showSnackBar(SnackBar(
        backgroundColor: AstraPalette.danger,
        behavior: SnackBarBehavior.floating,
        content: Text(c.error!, style: ui(size: 13, weight: FontWeight.w700, color: Colors.white)),
      ));
    }
  }

  Future<bool?> _confirmClose(DaySessionCubit c) {
    final p = context.astra;
    final t = context.astraTheme;
    return showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (sheetCtx) => Container(
        decoration: BoxDecoration(
          color: p.cardSolid,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
        ),
        padding: EdgeInsets.fromLTRB(20, 10, 20, 22 + MediaQuery.of(sheetCtx).padding.bottom),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 4,
              margin: const EdgeInsets.only(bottom: 18),
              decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(99)),
            ),
            Container(
              width: 54,
              height: 54,
              decoration: BoxDecoration(
                  color: p.dangerTint,
                  borderRadius: BorderRadius.circular(16)),
              child: const Icon(Icons.lock_outline, size: 26, color: AstraPalette.danger),
            ),
            const SizedBox(height: 14),
            Text('Close the day?', style: serif(size: 21, color: p.ink)),
            const SizedBox(height: 6),
            Text(
              'This finalises the session and records the close at the selected time. '
              'You\'ll need to open a new day to keep selling.',
              textAlign: TextAlign.center,
              style: ui(size: 12.5, weight: FontWeight.w600, color: p.textSecondary, height: 1.5),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
              decoration: BoxDecoration(
                color: p.isDark ? Colors.white.withValues(alpha: 0.04) : Colors.black.withValues(alpha: 0.02),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: p.hairline),
              ),
              child: Row(
                children: [
                  Text('Closing at',
                      style: ui(size: 11.5, weight: FontWeight.w700, color: p.textSecondary)),
                  const Spacer(),
                  Text('${Dates.weekday(c.selected)} · ${Dates.time(c.selected)}',
                      style: serif(size: 14, color: p.ink)),
                ],
              ),
            ),
            const SizedBox(height: 18),
            Row(
              children: [
                Expanded(
                  child: GestureDetector(
                    onTap: () => Navigator.of(sheetCtx).pop(false),
                    child: Container(
                      height: 52,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(15)),
                      child: Text('Cancel', style: ui(size: 14.5, weight: FontWeight.w800, color: p.ink)),
                    ),
                  ),
                ),
                const SizedBox(width: 11),
                Expanded(
                  child: GestureDetector(
                    onTap: () => Navigator.of(sheetCtx).pop(true),
                    child: Container(
                      height: 52,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [Color(0xFFE0657A), Color(0xFFC0405A)]),
                        borderRadius: BorderRadius.circular(15),
                        boxShadow: t.floatShadow(const Color(0xFFC0405A)),
                      ),
                      child: Text('Close day', style: ui(size: 14.5, weight: FontWeight.w800, color: Colors.white)),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

enum _NodeState { done, live, pending }

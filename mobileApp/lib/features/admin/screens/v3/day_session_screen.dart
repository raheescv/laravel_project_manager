import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/features/admin/logic/day_session_cubit/day_session_cubit.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

part 'day_session_views.dart';

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
    final user = context.select<AuthCubit, ApiUser?>((c) => c.state.user);
    final branchCtrl = context.watch<BranchCubit>();
    final branchName = c.session?.branch.isNotEmpty == true
        ? c.session!.branch
        : (branchCtrl.selected?.name ?? '');

    // Day Session is form-shaped, so the preview keeps it a centred, width-capped
    // card stack. On a tablet the hero joins that stack as an inset card instead
    // of a full-bleed band stretched across the whole sheet.
    final tablet = context.isTablet;
    final body = Stack(
      children: [
        ListView(
          padding: EdgeInsets.fromLTRB(16, tablet ? 12 : 16, 16, 130),
          children: [
            if (tablet) ...[
              _hero(c, user, branchName),
              const SizedBox(height: 14),
            ],
            _dateTimeCard(c),
            const SizedBox(height: 14),
            _timelineCard(c, user),
            const SizedBox(height: 14),
            _notice(c),
          ],
        ),
        Positioned(left: 0, right: 0, bottom: 0, child: _dock(c)),
      ],
    );

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: tablet
            ? SafeArea(bottom: false, child: MaxWidthBox(maxWidth: 620, child: body))
            : Column(
                children: [
                  _hero(c, user, branchName),
                  Expanded(child: MaxWidthBox(maxWidth: 620, child: body)),
                ],
              ),
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
                  Flexible(
                    child: Text(label,
                        maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: ui(size: 15, weight: FontWeight.w800, color: Colors.white)),
                  ),
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

  Future<void> _act(DaySessionCubit c) async {
    if (c.busy) return;
    if (c.isOpen) {
      // Closing over a queued sale would strand it: the server stamps a sale
      // into whichever session is open when it arrives, so one synced after the
      // close lands in tomorrow's takings and this day's cash-up is wrong.
      if (!await _guardPendingSales()) return;
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

  /// Refuse to close while this till still owes the server money, offering the
  /// one action that resolves it. Returns true when the day may be closed.
  ///
  /// Parked drafts do not count — a draft is not unbanked takings, and blocking a
  /// close over one teaches people to force past the guard that protects the money.
  ///
  /// This device only. A till cannot see another till's queue, and there is
  /// deliberately no server-side register of what each device is holding.
  Future<bool> _guardPendingSales() async {
    final sync = serviceLocator<OfflineSyncCubit>();
    await sync.refresh();
    // One attempt to clear the queue before troubling anyone — the connection is
    // usually back by the time someone is cashing up.
    if (sync.state.hasUnbankedTakings) await sync.drain();
    if (!mounted || !sync.state.hasUnbankedTakings) return true;

    final p = context.astra;
    final count = sync.state.unbankedCount;
    await showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('$count ${count == 1 ? 'sale' : 'sales'} not synced',
            style: serif(size: 19, color: p.ink)),
        content: Text(
          'This till is still holding takings the server has not got. Close the day '
          'now and they will be missing from this day’s report. Get this till back '
          'on the network first.',
          style: ui(size: 13, color: p.textSecondary, height: 1.45),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Not now')),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              context.push(Routes.pendingSales);
            },
            child: const Text('Review'),
          ),
        ],
      ),
    );
    return false;
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
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text('${Dates.weekday(c.selected)} · ${Dates.time(c.selected)}',
                        maxLines: 1, overflow: TextOverflow.ellipsis, textAlign: TextAlign.right,
                        style: serif(size: 14, color: p.ink)),
                  ),
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

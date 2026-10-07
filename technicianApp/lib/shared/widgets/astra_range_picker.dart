import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

/// The app's date-range picker: one-tap presets, a single month grid with the
/// range banded across it, and a live readout — in a calendar-sized card.
/// Replaces Material's full-screen range picker, which on a tablet stretched
/// one month across the whole window behind a bare ✕.
Future<DateTimeRange?> showAstraDateRangePicker(
  BuildContext context, {
  required String title,
  required DateTime firstDate,
  required DateTime lastDate,
  DateTimeRange? initialDateRange,
}) {
  return showDialog<DateTimeRange>(
    context: context,
    builder: (_) => _RangePickerDialog(
      title: title,
      first: DateUtils.dateOnly(firstDate),
      last: DateUtils.dateOnly(lastDate),
      initial: initialDateRange,
    ),
  );
}

class _RangePickerDialog extends StatefulWidget {
  const _RangePickerDialog({required this.title, required this.first, required this.last, this.initial});

  final String title;
  final DateTime first;
  final DateTime last;
  final DateTimeRange? initial;

  @override
  State<_RangePickerDialog> createState() => _RangePickerDialogState();
}

class _RangePickerDialogState extends State<_RangePickerDialog> {
  DateTime? _start;
  DateTime? _end;
  late DateTime _month;

  @override
  void initState() {
    super.initState();
    final initial = widget.initial;
    if (initial != null) {
      _start = _clamp(DateUtils.dateOnly(initial.start));
      _end = _clamp(DateUtils.dateOnly(initial.end));
    }
    final focus = _end ?? _start ?? widget.last;
    _month = DateTime(focus.year, focus.month);
  }

  DateTime _clamp(DateTime d) =>
      d.isBefore(widget.first) ? widget.first : (d.isAfter(widget.last) ? widget.last : d);

  /// Presets end today — or at [widget.last], should that be earlier.
  List<(String, DateTimeRange)> get _presets {
    final now = DateUtils.dateOnly(DateTime.now());
    final t = now.isAfter(widget.last) ? widget.last : now;
    DateTimeRange r(DateTime a, DateTime b) => DateTimeRange(start: _clamp(a), end: _clamp(b));
    return [
      ('Today', r(t, t)),
      ('Yesterday', r(t.subtract(const Duration(days: 1)), t.subtract(const Duration(days: 1)))),
      ('Last 7 days', r(t.subtract(const Duration(days: 6)), t)),
      ('This month', r(DateTime(t.year, t.month), t)),
      ('Last month', r(DateTime(t.year, t.month - 1), DateTime(t.year, t.month, 0))),
      ('Last 30 days', r(t.subtract(const Duration(days: 29)), t)),
    ];
  }

  void _tap(DateTime d) => setState(() {
        if (_start == null || _end != null || d.isBefore(_start!)) {
          _start = d;
          _end = null;
        } else {
          _end = d;
        }
      });

  void _applyPreset(DateTimeRange range) => setState(() {
        _start = range.start;
        _end = range.end;
        _month = DateTime(range.end.year, range.end.month);
      });

  bool get _canPrev => DateTime(_month.year, _month.month - 1, 1)
      .isAfter(DateTime(widget.first.year, widget.first.month - 1, 1));
  bool get _canNext => DateTime(_month.year, _month.month + 1).isBefore(DateTime(widget.last.year, widget.last.month + 1));

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Dialog(
      backgroundColor: p.cardSolid,
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      clipBehavior: Clip.antiAlias,
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 380),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _header(),
              const SizedBox(height: 14),
              _presetRow(),
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 14),
                child: Divider(height: 1, thickness: 1, color: p.hairline),
              ),
              _monthNav(),
              const SizedBox(height: 6),
              _weekdays(),
              const SizedBox(height: 4),
              _grid(),
              _actions(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _header() {
    final p = context.astra;
    final start = _start;
    final end = _end ?? _start;
    String readout;
    String? hint;
    if (start == null || end == null) {
      readout = 'Pick a start date';
    } else {
      final sameYear = start.year == end.year;
      readout = DateUtils.isSameDay(start, end)
          ? DateFormat('d MMM yyyy').format(start)
          : '${DateFormat(sameYear ? 'd MMM' : 'd MMM yyyy').format(start)} – ${DateFormat('d MMM yyyy').format(end)}';
      final days = end.difference(start).inDays + 1;
      hint = _end == null ? 'Tap an end date' : '$days ${days == 1 ? 'day' : 'days'}';
    }
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(widget.title.toUpperCase(),
              style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.4)),
          const SizedBox(height: 6),
          Row(
            children: [
              Expanded(
                child: Text(readout,
                    maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 21, color: p.ink)),
              ),
              if (hint != null) ...[
                const SizedBox(width: 10),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(999)),
                  child: Text(hint, style: ui(size: 10.5, weight: FontWeight.w800, color: p.primary)),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _presetRow() {
    final p = context.astra;
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Row(
        children: [
          for (final (label, range) in _presets) ...[
            Builder(builder: (_) {
              final on = _start != null &&
                  _end != null &&
                  DateUtils.isSameDay(_start, range.start) &&
                  DateUtils.isSameDay(_end, range.end);
              return GestureDetector(
                onTap: () => _applyPreset(range),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 140),
                  height: 34,
                  padding: const EdgeInsets.symmetric(horizontal: 13),
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: on ? p.primary : Colors.transparent,
                    borderRadius: BorderRadius.circular(11),
                    border: Border.all(color: on ? p.primary : p.hairline),
                  ),
                  child: Text(label,
                      style: ui(size: 12, weight: FontWeight.w800, color: on ? Colors.white : p.ink)),
                ),
              );
            }),
            const SizedBox(width: 7),
          ],
        ],
      ),
    );
  }

  Widget _monthNav() {
    final p = context.astra;
    Widget arrow(IconData icon, bool enabled, int step) => GestureDetector(
          onTap: enabled ? () => setState(() => _month = DateTime(_month.year, _month.month + step)) : null,
          child: Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: enabled ? p.tint : Colors.transparent,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 20, color: enabled ? p.primary : p.textMuted.withValues(alpha: 0.4)),
          ),
        );
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Row(
        children: [
          Expanded(child: Text(DateFormat('MMMM yyyy').format(_month), style: serif(size: 17, color: p.ink))),
          arrow(Icons.chevron_left_rounded, _canPrev, -1),
          const SizedBox(width: 6),
          arrow(Icons.chevron_right_rounded, _canNext, 1),
        ],
      ),
    );
  }

  Widget _weekdays() {
    final p = context.astra;
    final loc = MaterialLocalizations.of(context);
    final first = loc.firstDayOfWeekIndex;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14),
      child: Row(
        children: [
          for (var i = 0; i < 7; i++)
            Expanded(
              child: Center(
                child: Text(loc.narrowWeekdays[(first + i) % 7],
                    style: ui(size: 10.5, weight: FontWeight.w800, color: p.textMuted)),
              ),
            ),
        ],
      ),
    );
  }

  /// Always six rows, so paging between months never resizes the card.
  Widget _grid() {
    final firstIndex = MaterialLocalizations.of(context).firstDayOfWeekIndex;
    final offset = (DateTime(_month.year, _month.month).weekday % 7 - firstIndex + 7) % 7;
    final daysInMonth = DateUtils.getDaysInMonth(_month.year, _month.month);
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14),
      child: Column(
        children: [
          for (var row = 0; row < 6; row++)
            Row(
              children: [
                for (var col = 0; col < 7; col++)
                  Expanded(
                    child: Builder(builder: (_) {
                      final day = row * 7 + col - offset + 1;
                      if (day < 1 || day > daysInMonth) return const SizedBox(height: 44);
                      return _day(DateTime(_month.year, _month.month, day), col: col, lastDay: daysInMonth);
                    }),
                  ),
              ],
            ),
        ],
      ),
    );
  }

  /// A day cell. The range is one tinted strip behind the row, rounded where
  /// it starts, ends, or wraps to the next week, so it reads as a single span;
  /// the start and end sit on it as filled, lifted circles.
  Widget _day(DateTime d, {required int col, required int lastDay}) {
    final p = context.astra;
    final disabled = d.isBefore(widget.first) || d.isAfter(widget.last);
    final isStart = _start != null && DateUtils.isSameDay(d, _start);
    final isEnd = _end != null && DateUtils.isSameDay(d, _end);
    final selected = isStart || isEnd;
    final spans = _start != null && _end != null && !DateUtils.isSameDay(_start, _end);
    final inside = spans && d.isAfter(_start!) && d.isBefore(_end!);
    final bandLeft = inside || (spans && isEnd);
    final bandRight = inside || (spans && isStart);
    final roundLeft = col == 0 || d.day == 1;
    final roundRight = col == 6 || d.day == lastDay;
    final today = DateUtils.isSameDay(d, DateTime.now());
    final band = p.primary.withValues(alpha: p.isDark ? 0.24 : 0.13);
    const r = Radius.circular(18);

    return GestureDetector(
      onTap: disabled ? null : () => _tap(d),
      behavior: HitTestBehavior.opaque,
      child: SizedBox(
        height: 44,
        child: Stack(
          alignment: Alignment.center,
          children: [
            if (bandLeft || bandRight)
              Positioned.fill(
                top: 4,
                bottom: 4,
                child: Row(
                  children: [
                    Expanded(
                      child: bandLeft
                          ? Container(
                              margin: EdgeInsets.only(left: inside && roundLeft ? 3 : 0),
                              decoration: BoxDecoration(
                                color: band,
                                borderRadius: BorderRadius.horizontal(left: inside && roundLeft ? r : Radius.zero),
                              ),
                            )
                          : const SizedBox.shrink(),
                    ),
                    Expanded(
                      child: bandRight
                          ? Container(
                              margin: EdgeInsets.only(right: inside && roundRight ? 3 : 0),
                              decoration: BoxDecoration(
                                color: band,
                                borderRadius: BorderRadius.horizontal(right: inside && roundRight ? r : Radius.zero),
                              ),
                            )
                          : const SizedBox.shrink(),
                    ),
                  ],
                ),
              ),
            Container(
              width: 36,
              height: 36,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: selected ? p.primaryGradient : null,
                border: today && !selected ? Border.all(color: p.primary, width: 1.5) : null,
                boxShadow: selected
                    ? [BoxShadow(color: p.primary.withValues(alpha: 0.35), blurRadius: 10, offset: const Offset(0, 3))]
                    : null,
              ),
              child: Text(
                '${d.day}',
                style: ui(
                  size: 13.5,
                  weight: selected ? FontWeight.w800 : (inside || today ? FontWeight.w700 : FontWeight.w600),
                  color: selected
                      ? Colors.white
                      : disabled
                          ? p.textMuted.withValues(alpha: 0.4)
                          : (today ? p.primary : p.ink),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _actions() {
    final p = context.astra;
    final canApply = _start != null;
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
      child: Row(
        children: [
          Expanded(
            child: GestureDetector(
              onTap: () => Navigator.of(context).pop(),
              child: Container(
                height: 48,
                alignment: Alignment.center,
                decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(14)),
                child: Text('Cancel', style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Opacity(
              opacity: canApply ? 1 : 0.45,
              child: GestureDetector(
                onTap: canApply
                    ? () => Navigator.of(context).pop(DateTimeRange(start: _start!, end: _end ?? _start!))
                    : null,
                child: Container(
                  height: 48,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(gradient: p.primaryGradient, borderRadius: BorderRadius.circular(14)),
                  child: Text('Apply', style: ui(size: 14, weight: FontWeight.w800, color: Colors.white)),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

part of 'day_session_screen.dart';

// The tablet Day Session — the "Status Board" (docs/mobile-day-session-tablet-preview.html):
// a slim hero band, a lifecycle stepper across the full width, the closing /
// opening moment beside the Sale Bill Report, and the action inline at the
// foot instead of the phone's floating dock. Phones never reach this file.

extension _DaySessionTablet on _DaySessionScreenState {
  Widget _tabletBody(
      DaySessionCubit c, ApiUser? user, String branchName, DaySessionSummary? report, bool noticeFirst) {
    // Fills the window: the cards stretch into the space between the stepper
    // and the action, so the Close / Open button sits at the foot of the screen.
    // Taller than the window (a small tablet), it simply scrolls.
    return LayoutBuilder(builder: (context, box) {
      final split = box.maxWidth >= 760;
      final showReport = report != null && !noticeFirst;
      final Widget lower;
      if (!showReport) {
        lower = _momentCard(c, user, fill: split);
      } else if (split) {
        lower = Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(flex: 145, child: _momentCard(c, user, fill: true)),
            const SizedBox(width: 14),
            Expanded(flex: 100, child: _tabletReportCard(report, fill: true)),
          ],
        );
      } else {
        lower = Column(children: [_momentCard(c, user), const SizedBox(height: 14), _tabletReportCard(report)]);
      }
      return CustomScrollView(
        slivers: [
          SliverPadding(
            padding: EdgeInsets.fromLTRB(24, 16, 24, 24 + MediaQuery.paddingOf(context).bottom),
            sliver: SliverFillRemaining(
              hasScrollBody: false,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _heroBand(c, user, branchName),
                  const SizedBox(height: 14),
                  if (noticeFirst) ...[
                    _saleGateCard(),
                    const SizedBox(height: 14),
                  ],
                  _stepperCard(c, user),
                  const SizedBox(height: 14),
                  if (split) Expanded(child: lower) else ...[lower, const Spacer()],
                  const SizedBox(height: 16),
                  _tabletActions(c, noticeFirst: noticeFirst, split: split),
                ],
              ),
            ),
          ),
        ],
      );
    });
  }

  DateTime? _openedMoment(DaySessionCubit c, ApiUser? user) {
    final raw = c.session?.openedAt.isNotEmpty == true ? c.session!.openedAt : (user?.daySessionOpenedAt ?? '');
    return DateTime.tryParse(raw);
  }

  /// The earliest moment the day may be closed / opened at — the same bounds
  /// [_pickDate] gives its calendar, but to the minute. Rounded up so a close
  /// never lands in the seconds before the open.
  DateTime _earliest(DaySessionCubit c, ApiUser? user) {
    final now = DateTime.now();
    if (c.isOpen) {
      final opened = _openedMoment(c, user);
      if (opened != null) {
        final floor = DateTime(opened.year, opened.month, opened.day, opened.hour, opened.minute);
        return floor.isBefore(opened) ? floor.add(const Duration(minutes: 1)) : floor;
      }
      return DateUtils.dateOnly(now.subtract(const Duration(days: 1)));
    }
    return DateUtils.dateOnly(now.subtract(const Duration(days: 30)));
  }

  static String _span(Duration d) {
    if (d.isNegative) return '—';
    if (d.inDays > 0) return '${d.inDays}d ${d.inHours % 24}h';
    if (d.inHours > 0) return '${d.inHours}h ${d.inMinutes % 60}m';
    return '${d.inMinutes}m';
  }

  // ------------------------------------------------------------ HERO BAND
  Widget _heroBand(DaySessionCubit c, ApiUser? user, String branchName) {
    final p = context.astra;
    final open = c.isOpen;
    final opened = _openedMoment(c, user);
    final closedAt = c.session?.closedAt.isNotEmpty == true ? c.session!.closedAt : (user?.lastClosedSessionAt ?? '');
    final since = open
        ? (opened == null ? 'Session is open' : 'Open since ${Dates.humanDateTime(opened.toString())}')
        : (closedAt.isEmpty ? 'No open day right now' : 'Last closed ${Dates.humanDateTime(closedAt)}');
    final s = c.session;
    final meta = <Widget>[
      if (s != null && s.openedBy.isNotEmpty) _bandMeta('Opened by', s.openedBy),
      if (s != null && open) _bandMeta('Opening float', Money.of(s.openingAmount), gold: true),
      if (s != null && !open && s.closedBy.isNotEmpty) _bandMeta('Closed by', s.closedBy),
      if (open && opened != null) _bandMeta('Duration', _span(DateTime.now().difference(opened))),
    ];

    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: BorderRadius.circular(22),
        boxShadow: context.astraTheme.floatShadow(p.primary),
      ),
      child: Stack(
        children: [
          Positioned(
            right: -60,
            top: -70,
            child: Container(
              width: 240,
              height: 240,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(colors: [p.accent.withValues(alpha: 0.22), Colors.transparent]),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 18, 24, 18),
            child: Row(
              children: [
                if (context.canPop()) ...[
                  HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
                  const SizedBox(width: 14),
                ],
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.14),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
                  ),
                  child: Icon(open ? Icons.wb_sunny_outlined : Icons.bedtime_outlined, size: 28, color: Colors.white),
                ),
                const SizedBox(width: 18),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(branchName.isEmpty ? 'BRANCH DAY SESSION' : 'BRANCH DAY SESSION · ${branchName.toUpperCase()}',
                          maxLines: 1, overflow: TextOverflow.ellipsis,
                          style: ui(size: 9.5, weight: FontWeight.w800, color: p.accent, letterSpacing: 2)),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Flexible(
                            child: Text(open ? 'Day Open' : 'Day Closed',
                                maxLines: 1, overflow: TextOverflow.ellipsis,
                                style: serif(size: 28, color: Colors.white)),
                          ),
                          const SizedBox(width: 10),
                          _heroPill(open),
                        ],
                      ),
                      const SizedBox(height: 2),
                      Text(since,
                          maxLines: 1, overflow: TextOverflow.ellipsis,
                          style: ui(size: 12.5, weight: FontWeight.w600, color: Colors.white.withValues(alpha: 0.82))),
                    ],
                  ),
                ),
                if (meta.isNotEmpty) ...[
                  const SizedBox(width: 18),
                  Container(
                    padding: const EdgeInsets.only(left: 24),
                    decoration: BoxDecoration(
                      border: Border(left: BorderSide(color: Colors.white.withValues(alpha: 0.18))),
                    ),
                    child: Row(children: [
                      for (var i = 0; i < meta.length; i++) ...[
                        if (i > 0) const SizedBox(width: 30),
                        meta[i],
                      ],
                    ]),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _bandMeta(String k, String v, {bool gold = false}) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(k.toUpperCase(),
            style: ui(size: 8.5, weight: FontWeight.w800, color: Colors.white.withValues(alpha: 0.6), letterSpacing: 1.4)),
        const SizedBox(height: 4),
        ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 140),
          child: gold
              ? Text(v, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 18, color: p.accent))
              : Text(v, maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: ui(size: 14, weight: FontWeight.w800, color: Colors.white)),
        ),
      ],
    );
  }

  // --------------------------------------------------------- STEPPER CARD
  /// The lifecycle across the full width: one track from the first dot to the
  /// last, filled up to where the day is.
  Widget _stepperCard(DaySessionCubit c, ApiUser? user) {
    final p = context.astra;
    final open = c.isOpen;
    final opened = _openedMoment(c, user);
    final closedAt = c.session?.closedAt.isNotEmpty == true ? c.session!.closedAt : (user?.lastClosedSessionAt ?? '');
    final closedBy = c.session?.closedBy ?? '';

    final steps = open
        ? [
            _step(_NodeState.done, 'Day opened',
                opened == null ? 'This session is open.' : Dates.humanDateTime(opened.toString()),
                align: CrossAxisAlignment.start,
                float: c.session != null ? Money.of(c.session!.openingAmount) : null),
            _step(_NodeState.live, 'In session', 'Sales are recorded against this day.',
                align: CrossAxisAlignment.center, badge: 'LIVE'),
            _step(_NodeState.pending, 'Close day', 'Pick the moment below, then confirm.',
                align: CrossAxisAlignment.end),
          ]
        : [
            _step(_NodeState.done, 'Last day closed',
                closedAt.isEmpty
                    ? 'No recent session on record.'
                    : '${Dates.humanDateTime(closedAt)}${closedBy.isEmpty ? '' : ' by $closedBy'}',
                align: CrossAxisAlignment.start),
            _step(_NodeState.pending, 'Open a new day', 'Pick the moment below, then start the day.',
                align: CrossAxisAlignment.end),
          ];

    return AstraCard(
      radius: 20,
      padding: const EdgeInsets.fromLTRB(26, 16, 26, 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel('Session lifecycle'),
          const SizedBox(height: 14),
          Stack(
            children: [
              Positioned(
                left: 14,
                right: 14,
                top: 12.5,
                child: Container(
                  height: 3,
                  alignment: Alignment.centerLeft,
                  decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3)),
                  child: FractionallySizedBox(
                    widthFactor: open ? 0.5 : 0,
                    child: Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(3),
                        gradient: LinearGradient(colors: [AstraPalette.success, p.primary]),
                      ),
                    ),
                  ),
                ),
              ),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [for (final s in steps) Expanded(child: s)],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _step(_NodeState state, String title, String subtitle,
      {required CrossAxisAlignment align, String? badge, String? float}) {
    final p = context.astra;
    final textAlign = switch (align) {
      CrossAxisAlignment.center => TextAlign.center,
      CrossAxisAlignment.end => TextAlign.right,
      _ => TextAlign.left,
    };
    return Column(
      crossAxisAlignment: align,
      children: [
        _stepDot(state, size: 28, onTrack: true),
        const SizedBox(height: 12),
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Flexible(
              child: Text(title,
                  maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: ui(size: 15, weight: FontWeight.w800, color: p.ink)),
            ),
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
        const SizedBox(height: 3),
        Text(subtitle,
            textAlign: textAlign,
            style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary, height: 1.45)),
        if (float != null) ...[
          const SizedBox(height: 7),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(10)),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text('Float  ', style: ui(size: 10.5, weight: FontWeight.w700, color: p.textSecondary)),
                Text(float, style: serif(size: 13, color: p.goldText)),
              ],
            ),
          ),
        ],
      ],
    );
  }

  // ---------------------------------------------------------- MOMENT CARD
  // [fill] when the card is stretched to the window: the bounds footer then
  // settles at its foot.
  /// The closing / opening moment: a big tappable readout (either half opens
  /// its full picker) over one-tap Day and Time choices. Nothing points into
  /// the future — the server refuses it — or before [_earliest].
  Widget _momentCard(DaySessionCubit c, ApiUser? user, {bool fill = false}) {
    final p = context.astra;
    final sel = c.selected;
    final n = DateTime.now();
    final now = DateTime(n.year, n.month, n.day, n.hour, n.minute);
    final today = DateUtils.dateOnly(now);
    final yesterday = today.subtract(const Duration(days: 1));
    final earliest = _earliest(c, user);
    final opened = _openedMoment(c, user);

    // Clamped into [earliest, now], so a quick choice can never build a moment
    // the server will turn down.
    void setAt(DateTime at) {
      var v = at;
      if (v.isAfter(now)) v = now;
      if (v.isBefore(earliest)) v = earliest;
      c.setMoment(v);
    }

    final isNow = sel == now;
    final dayChoices = <Widget>[
      _choice('Today', sub: DateFormat('EEE d').format(today),
          selected: DateUtils.isSameDay(sel, today),
          onTap: () => setAt(DateTime(today.year, today.month, today.day, sel.hour, sel.minute))),
      if (!DateUtils.dateOnly(earliest).isAfter(yesterday))
        _choice('Yesterday', sub: DateFormat('EEE d').format(yesterday),
            selected: DateUtils.isSameDay(sel, yesterday),
            onTap: () => setAt(DateTime(yesterday.year, yesterday.month, yesterday.day, sel.hour, sel.minute))),
      _choice('Other date', icon: Icons.calendar_today_outlined, ghost: true, onTap: () => _pickDate(c)),
    ];
    Widget back(String label, int minutes) {
      final at = now.subtract(Duration(minutes: minutes));
      return _choice(label, selected: sel == at, onTap: at.isBefore(earliest) ? null : () => setAt(at));
    }

    final timeChoices = <Widget>[
      _choice('Now', icon: Icons.bolt_outlined, selected: isNow, onTap: c.setNow),
      back('−15 min', 15),
      back('−30 min', 30),
      back('−1 hr', 60),
      _choice('Custom', icon: Icons.schedule_outlined, ghost: true, onTap: () => _pickTime(c)),
    ];

    final footer = c.isOpen
        ? [
            ('Session length', opened == null ? '—' : _span(sel.difference(opened))),
            ('Earliest', opened == null ? '—' : '${Dates.day(earliest)} · ${Dates.time(earliest)}'),
            ('Latest', 'Now · ${Dates.time(now)}'),
          ]
        : [
            ('Opens', '${Dates.day(sel)} · ${Dates.time(sel)}'),
            ('Earliest', Dates.day(earliest)),
            ('Latest', 'Now · ${Dates.time(now)}'),
          ];

    return AstraCard(
      radius: 20,
      padding: const EdgeInsets.fromLTRB(22, 18, 22, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel(
            c.isOpen ? 'Closing date & time' : 'Opening date & time',
            trailing: isNow
                ? Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                        color: AstraPalette.success.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(999)),
                    child: Row(mainAxisSize: MainAxisSize.min, children: [
                      Container(
                          width: 6, height: 6,
                          decoration: const BoxDecoration(color: AstraPalette.success, shape: BoxShape.circle)),
                      const SizedBox(width: 5),
                      Text('Set to now', style: ui(size: 10.5, weight: FontWeight.w800, color: AstraPalette.success)),
                    ]),
                  )
                : null,
          ),
          const SizedBox(height: 14),
          Container(
            clipBehavior: Clip.antiAlias,
            decoration: BoxDecoration(
              color: p.isDark ? Colors.white.withValues(alpha: 0.04) : Colors.black.withValues(alpha: 0.015),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: p.hairline),
            ),
            child: IntrinsicHeight(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Expanded(
                    flex: 5,
                    child: _readout('Date', DateFormat('d').format(sel), DateFormat('EEEE').format(sel),
                        DateFormat('MMMM yyyy').format(sel), () => _pickDate(c)),
                  ),
                  Container(width: 1, color: p.hairline),
                  Expanded(
                    flex: 4,
                    child: _readout('Time', DateFormat('h:mm').format(sel), DateFormat('a').format(sel),
                        'Tap to change', () => _pickTime(c)),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 14),
          _choiceRow('Day', dayChoices),
          const SizedBox(height: 10),
          _choiceRow('Time', timeChoices),
          const SizedBox(height: 16),
          if (fill) const Spacer(),
          Container(
            padding: const EdgeInsets.only(top: 12),
            decoration: BoxDecoration(border: Border(top: BorderSide(color: p.hairline))),
            child: IntrinsicHeight(
              child: Row(
                children: [
                  for (var i = 0; i < footer.length; i++) ...[
                    if (i > 0) ...[
                      VerticalDivider(width: 1, thickness: 1, color: p.hairline),
                      const SizedBox(width: 14),
                    ],
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(footer[i].$1.toUpperCase(),
                              style: ui(size: 8.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.2)),
                          const SizedBox(height: 4),
                          Text(footer[i].$2,
                              maxLines: 1, overflow: TextOverflow.ellipsis,
                              style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
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

  Widget _readout(String k, String big, String strong, String soft, VoidCallback onTap) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Stack(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 16, 44, 18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(k.toUpperCase(),
                    style: ui(size: 8.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.2)),
                const SizedBox(height: 6),
                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Row(
                    children: [
                      Text(big, style: serif(size: 44, color: p.ink, height: 1)),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(strong, style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
                          const SizedBox(height: 2),
                          Text(soft, style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Positioned(
            right: 12,
            top: 12,
            child: Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(9)),
              child: Icon(Icons.edit_outlined, size: 14, color: p.primary),
            ),
          ),
        ],
      ),
    );
  }

  Widget _choiceRow(String k, List<Widget> choices) {
    final p = context.astra;
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 48,
          height: 42,
          child: Align(
            alignment: Alignment.centerLeft,
            child: Text(k.toUpperCase(),
                style: ui(size: 8.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.2)),
          ),
        ),
        Expanded(child: Wrap(spacing: 8, runSpacing: 8, children: choices)),
      ],
    );
  }

  /// A one-tap moment choice. Filled when it is the selected moment, dashed
  /// for the ones that open a full picker, faded when out of bounds.
  Widget _choice(String label,
      {String? sub, IconData? icon, bool selected = false, bool ghost = false, required VoidCallback? onTap}) {
    final p = context.astra;
    final fg = selected ? Colors.white : (ghost ? p.primary : p.ink);
    return Opacity(
      opacity: onTap == null ? 0.4 : 1,
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 140),
          height: 42,
          padding: const EdgeInsets.symmetric(horizontal: 14),
          decoration: BoxDecoration(
            color: selected ? p.primary : (ghost ? p.tint : Colors.transparent),
            borderRadius: BorderRadius.circular(13),
            border: Border.all(color: selected ? p.primary : (ghost ? Colors.transparent : p.hairline)),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (icon != null) ...[
                Icon(icon, size: 15, color: fg),
                const SizedBox(width: 6),
              ],
              Text(label, style: ui(size: 13, weight: FontWeight.w800, color: fg)),
              if (sub != null) ...[
                const SizedBox(width: 6),
                Text(sub,
                    style: ui(
                        size: 11.5,
                        weight: FontWeight.w600,
                        color: selected ? Colors.white.withValues(alpha: 0.75) : p.textMuted)),
              ],
            ],
          ),
        ),
      ),
    );
  }

  // ---------------------------------------------------------- REPORT CARD
  /// [fill] when it stands beside the moment card and is stretched to its
  /// height — the actions then sit at the foot.
  Widget _tabletReportCard(DaySessionSummary s, {bool fill = false}) {
    final p = context.astra;
    final printer = context.watch<PrintSettingsCubit>();
    final when = s.isOpen
        ? 'Figures so far'
        : 'Closed ${Dates.humanDateTime(s.closedAt)}${s.closedBy.isEmpty ? '' : ' by ${s.closedBy}'}';
    return AstraCard(
      radius: 20,
      padding: const EdgeInsets.fromLTRB(22, 18, 22, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel('Session report'),
          const SizedBox(height: 14),
          Row(
            children: [
              const IconChip(icon: Icons.receipt_long_outlined, size: 46, radius: 13),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Sale Bill Report #${s.id}',
                        maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 18, color: p.ink)),
                    const SizedBox(height: 2),
                    Text(when,
                        maxLines: 2, overflow: TextOverflow.ellipsis,
                        style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary, height: 1.4)),
                  ],
                ),
              ),
            ],
          ),
          if (fill) const Spacer() else const SizedBox(height: 16),
          _reportButton(Icons.print_outlined, 'Print thermal', primary: true,
              onTap: () => unawaited(printDaySessionRoll(context, s.id))),
          const SizedBox(height: 10),
          _reportButton(Icons.visibility_outlined, 'Preview',
              onTap: () => unawaited(openReportPreview(context, ExportReport.daySession, sessionId: s.id))),
          const SizedBox(height: 10),
          Row(
            children: [
              Icon(printer.hasPrinter ? Icons.print_outlined : Icons.info_outline, size: 13, color: p.textMuted),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                    printer.hasPrinter
                        ? 'Prints straight to ${printer.printer.displayName}'
                        : 'No printer paired — the preview opens to print',
                    maxLines: 1, overflow: TextOverflow.ellipsis,
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------- ACTIONS
  /// The notice and the one call to action, inline at the foot of the page —
  /// a tablet has the room, so nothing floats over the content.
  Widget _tabletActions(DaySessionCubit c, {required bool noticeFirst, required bool split}) {
    final p = context.astra;
    final open = c.isOpen;
    final stamp = '${DateFormat('d MMM').format(c.selected)}, ${Dates.time(c.selected)}';
    final cta = Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        _bigButton(
          label: open ? 'Close day · $stamp' : 'Open day · $stamp',
          icon: open ? Icons.lock_outline : Icons.lock_open_outlined,
          danger: open,
          busy: c.busy || c.syncing,
          onTap: () => _act(c),
        ),
        const SizedBox(height: 8),
        Text(
          open
              ? 'You\'ll confirm before the day is closed'
              : widget.forSale
                  ? 'Opens the day, then takes you to New Sale'
                  : 'Starts a new sale session for this branch',
          textAlign: TextAlign.center,
          style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted),
        ),
      ],
    );
    if (noticeFirst) return cta;
    if (!split) return Column(children: [_notice(c), const SizedBox(height: 14), cta]);
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(child: _notice(c)),
        const SizedBox(width: 18),
        SizedBox(width: 380, child: cta),
      ],
    );
  }
}

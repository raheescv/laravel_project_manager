part of 'day_session_screen.dart';

// The Day Session hero, timeline and picker chrome. Split out of
// day_session_screen.dart; a `part`, so nothing else changed.

extension _DaySessionViews on _DaySessionScreenState {
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

    final tablet = context.isTablet;
    return Container(
      clipBehavior: tablet ? Clip.antiAlias : Clip.none,
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: tablet
            ? BorderRadius.circular(22)
            : const BorderRadius.vertical(bottom: Radius.circular(30)),
        boxShadow: tablet ? context.astraTheme.floatShadow(p.primary) : null,
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
            // Inset card on tablet — it no longer meets the status bar, so it
            // must not reserve the notch inset either.
            top: !tablet,
            bottom: false,
            child: Padding(
              padding: EdgeInsets.fromLTRB(16, tablet ? 16 : 6, 16, 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      if (context.canPop()) ...[
                        HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
                        const SizedBox(width: 11),
                      ],
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
                        ConstrainedBox(
                          constraints: const BoxConstraints(maxWidth: 150),
                          child: Container(
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
                                Flexible(
                                  child: Text(branchName,
                                      maxLines: 1, overflow: TextOverflow.ellipsis,
                                      style: ui(size: 11, weight: FontWeight.w800, color: Colors.white)),
                                ),
                              ],
                            ),
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
                                Flexible(
                                  child: Text(open ? 'Day Open' : 'Day Closed',
                                      maxLines: 1, overflow: TextOverflow.ellipsis,
                                      style: serif(size: 26, color: Colors.white)),
                                ),
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
}

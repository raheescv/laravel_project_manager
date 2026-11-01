part of 'sales_returns_list_screen.dart';

// The Sales Returns list's controls — the whole-set figures strip, the date
// button and the filter chips — shared by the phone's tools card and the
// tablet's card above the table, the same shapes the Sales list uses. A
// `part`, so it reads the screen's state directly.

extension _ReturnsListControls on _SalesReturnListScreenState {
  /// Phone: the figures, the date and the filter chips in one card under the
  /// header.
  Widget _toolsCard() {
    return AstraCard(
      radius: 20,
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _kpiStrip(),
          const SizedBox(height: 10),
          _dateButton(),
          const SizedBox(height: 9),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                for (final (i, chip) in _filterChips().indexed) ...[
                  if (i > 0) const SizedBox(width: 7),
                  chip,
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// Tablet: the same pieces in one card above the table — figures beside the
  /// tools when there is room, above them when not.
  Widget _tabletTools(double width) {
    final tools = Wrap(
      spacing: 7,
      runSpacing: 7,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: [_dateButton(), ..._filterChips()],
    );
    return AstraCard(
      radius: 18,
      padding: const EdgeInsets.all(12),
      child: width >= 860
          ? Row(
              children: [
                SizedBox(width: 320, child: _kpiStrip()),
                const SizedBox(width: 14),
                Expanded(child: tools),
              ],
            )
          : Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [_kpiStrip(), const SizedBox(height: 10), tools],
            ),
    );
  }

  /// Two joined figures for the whole filtered set: Returns · Refunded.
  Widget _kpiStrip() {
    final p = context.astra;
    final s = _list.state;
    final pending = s.isLoading && s.items.isEmpty;
    final cells = <(String, String, Color?)>[
      ('RETURNS', '${s.total}', null),
      ('REFUNDED', Money.of(s.totalPaid), s.totalPaid > 0.005 ? AstraPalette.danger : null),
    ];
    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: p.hairline),
        borderRadius: BorderRadius.circular(12),
      ),
      child: IntrinsicHeight(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            for (final (i, (label, value, color)) in cells.indexed)
              Expanded(
                // The count is short; the money figure gets the room.
                flex: i == 0 ? 2 : 3,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                  decoration: i == 0 ? null : BoxDecoration(border: Border(left: BorderSide(color: p.hairline))),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(label, style: ui(size: 8.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1)),
                      const SizedBox(height: 2),
                      FittedBox(
                        fit: BoxFit.scaleDown,
                        alignment: Alignment.centerLeft,
                        child: Text(pending ? '—' : value, maxLines: 1, style: serif(size: 15, color: color ?? p.ink)),
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

  Widget _dateButton() {
    final p = context.astra;
    return GestureDetector(
      onTap: _openDateSheet,
      child: Container(
        padding: const EdgeInsets.fromLTRB(11, 12, 8, 12),
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.event_rounded, size: 16, color: p.primary),
            const SizedBox(width: 7),
            Flexible(
              child: Text(_dateLabel,
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12, weight: FontWeight.w700, color: p.ink)),
            ),
            const SizedBox(width: 2),
            Icon(Icons.keyboard_arrow_down_rounded, size: 16, color: p.textMuted),
          ],
        ),
      ),
    );
  }

  /// Every filter as a chip that opens its tap-to-apply sheet; a chip with a
  /// value set fills in, so what is narrowing the list is visible at a glance.
  List<Widget> _filterChips() => [
        TabletFilterChip(
            label: _statusLabel,
            active: _status != null,
            icon: Icons.task_alt_rounded,
            trailingIcon: Icons.keyboard_arrow_down_rounded,
            onTap: _openStatusSheet),
        TabletFilterChip(
            label: _methodLabel,
            active: _methodId != null,
            icon: Icons.account_balance_wallet_outlined,
            trailingIcon: Icons.keyboard_arrow_down_rounded,
            onTap: _openPaymentSheet),
        TabletFilterChip(
            label: _sortLabel,
            active: false,
            icon: Icons.swap_vert_rounded,
            trailingIcon: Icons.keyboard_arrow_down_rounded,
            onTap: _openSort),
      ];

  Widget _optTile({
    required String label,
    required IconData icon,
    required bool active,
    required VoidCallback onTap,
    String? trailing,
  }) {
    final p = context.astra;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 6),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        decoration: BoxDecoration(color: active ? p.tint : Colors.transparent, borderRadius: BorderRadius.circular(13)),
        child: Row(
          children: [
            Icon(icon, size: 18, color: active ? p.primary : p.textSecondary),
            const SizedBox(width: 12),
            Expanded(
              child: Text(label, style: ui(size: 13, weight: active ? FontWeight.w800 : FontWeight.w600, color: active ? p.ink : p.textSecondary)),
            ),
            if (trailing != null)
              Padding(
                padding: const EdgeInsets.only(right: 6),
                child: Text(trailing, style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
              ),
            if (active) Icon(Icons.check_circle_rounded, size: 18, color: p.primary),
          ],
        ),
      ),
    );
  }
}

/// One column of the tablet ledger table.
class _Col {
  const _Col(this.id, this.label, this.flex, {this.end = false});
  final String id;
  final String label;
  final int flex;

  /// Right-aligned — the figure columns.
  final bool end;
}

/// The "loading the next page" footer under both lists.
class _MoreSpinner extends StatelessWidget {
  const _MoreSpinner();

  @override
  Widget build(BuildContext context) => const Padding(
        padding: EdgeInsets.symmetric(vertical: 16),
        child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4))),
      );
}

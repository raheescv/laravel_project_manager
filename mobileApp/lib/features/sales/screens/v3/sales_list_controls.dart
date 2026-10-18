part of 'sales_list_screen.dart';

// The Sales list's controls — the Invoices | Items switch, the whole-set
// figures strip, search + date, and the filter chips — shared by the phone's
// tools card and the tablet's card above the table. A `part`, so it reads the
// screen's state directly.

extension _SalesListControls on _SalesListScreenState {
  /// Phone: the figures, search + date and the filter chips in one card under
  /// the header — the compact stand-in for the old labelled bento card.
  Widget _toolsCard() {
    return AstraCard(
      radius: 20,
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _kpiStrip(),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(child: _searchBox()),
              const SizedBox(width: 8),
              _dateButton(),
            ],
          ),
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
    final tools = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(child: _searchBox()),
            const SizedBox(width: 8),
            _dateButton(),
          ],
        ),
        const SizedBox(height: 9),
        Wrap(spacing: 7, runSpacing: 7, children: _filterChips()),
      ],
    );
    return AstraCard(
      radius: 18,
      padding: const EdgeInsets.all(12),
      child: width >= 860
          ? Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SizedBox(width: 380, child: _kpiStrip()),
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

  /// Entry point into the Sales Return module — a compact translucent pill in the
  /// header so it doesn't crowd the bottom nav. Hidden when the user can't view
  /// returns.
  Widget? _returnsPill() {
    if (!_canSeeReturns) return null;
    final p = context.astra;
    return GestureDetector(
      onTap: _openReturns,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.14),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.white.withValues(alpha: 0.20)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.assignment_return_outlined, size: 15, color: p.accent),
            const SizedBox(width: 7),
            Text('Returns', style: ui(size: 12, weight: FontWeight.w800, color: Colors.white)),
          ],
        ),
      ),
    );
  }

  /// Invoices | Items — the same segmented shape the status filter used. On the
  /// phone's gradient header it goes translucent white, like the header's pill.
  Widget _tabSeg({bool onHero = false}) {
    final p = context.astra;
    Widget seg(String label, IconData icon, String tab) {
      final active = _tab == tab;
      final color = active ? p.primary : (onHero ? Colors.white.withValues(alpha: 0.82) : p.textSecondary);
      return Expanded(
        child: GestureDetector(
          onTap: () => _switchTab(tab),
          behavior: HitTestBehavior.opaque,
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 8),
            decoration: BoxDecoration(
              color: active ? (onHero ? Colors.white : p.card) : Colors.transparent,
              borderRadius: BorderRadius.circular(10),
              boxShadow: active && !onHero ? context.astraTheme.softShadow : null,
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(icon, size: 15, color: color),
                const SizedBox(width: 6),
                Text(label, style: ui(size: 12, weight: active ? FontWeight.w800 : FontWeight.w700, color: color)),
              ],
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(
        color: onHero ? Colors.white.withValues(alpha: 0.14) : p.tint,
        borderRadius: BorderRadius.circular(13),
      ),
      child: Row(children: [
        seg('Invoices', Icons.receipt_long_outlined, 'invoices'),
        seg('Items', Icons.inventory_2_outlined, 'items'),
      ]),
    );
  }

  /// Three joined figures for the whole filtered set: Bills · Collected · Due on
  /// Invoices, Lines · Qty · Net on Items.
  Widget _kpiStrip() {
    final p = context.astra;
    final s = _active.state;
    final pending = s.isLoading && s.items.isEmpty;
    final due = s.summary['balance'] ?? 0;
    final cells = _isItems
        ? <(String, String, Color?)>[
            ('LINES', '${s.total}', null),
            ('QTY', qtyLabel(s.summary['quantity'] ?? 0), null),
            ('NET', Money.of(s.totalPaid), null),
          ]
        : <(String, String, Color?)>[
            ('BILLS', '${s.total}', null),
            ('COLLECTED', Money.of(s.totalPaid), null),
            ('DUE', Money.of(due), due > 0.005 ? AstraPalette.danger : null),
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
                // The count is short; the two money figures get the room.
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

  /// Debounced, because the list refetches on every change and a per-keystroke
  /// request would queue a page load for each letter of a customer's name. On
  /// Items it also matches the product name, code and barcode.
  Widget _searchBox() {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
      child: Row(
        children: [
          Icon(Icons.search_rounded, size: 17, color: p.primary),
          const SizedBox(width: 8),
          Expanded(
            child: TextField(
              controller: _searchCtl,
              onChanged: _setSearch,
              textInputAction: TextInputAction.search,
              style: ui(size: 13.5, weight: FontWeight.w600, color: p.ink),
              decoration: InputDecoration(
                isDense: true,
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(vertical: 12),
                hintText: _isItems ? 'Product, code or invoice' : 'Invoice no. or customer',
                hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
              ),
            ),
          ),
          if (_search.isNotEmpty)
            GestureDetector(
              onTap: () {
                _searchCtl.clear();
                _setSearch('');
              },
              behavior: HitTestBehavior.opaque,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 11),
                child: Icon(Icons.close_rounded, size: 16, color: p.textMuted),
              ),
            ),
        ],
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
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 120),
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
        // Only someone who sees the whole till's sales has anything to narrow by
        // staff.
        if (_seesAllSales)
          TabletFilterChip(
              label: _staffLabel,
              active: _staffId != null,
              icon: Icons.badge_outlined,
              trailingIcon: Icons.keyboard_arrow_down_rounded,
              onTap: _openStaffSheet),
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

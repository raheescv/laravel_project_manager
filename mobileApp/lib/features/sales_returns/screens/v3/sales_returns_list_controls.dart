part of 'sales_returns_list_screen.dart';

// The Sales Returns list's filter controls. Split out of
// sales_returns_list_screen.dart; a `part`, so nothing else changed.

extension _ReturnsListControls on _SalesReturnListScreenState {
  Widget _bento() {
    return AstraCard(
      radius: 20,
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _fieldLabel('DATE RANGE'),
          _dateBox(),
          const SizedBox(height: 13),
          _fieldLabel('STATUS'),
          _statusSeg(),
          const SizedBox(height: 13),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(child: _field('PAYMENT', _selBox(_methodLabel, Icons.account_balance_wallet_outlined, _openPaymentSheet))),
              const SizedBox(width: 10),
              Expanded(child: _field('SORT', _selBox(_sortLabel, Icons.swap_vert_rounded, _openSort))),
            ],
          ),
        ],
      ),
    );
  }

  Widget _field(String label, Widget child) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [_fieldLabel(label), child],
      );

  Widget _fieldLabel(String t) => Padding(
        padding: const EdgeInsets.only(left: 2, bottom: 6),
        child: Text(t, style: ui(size: 8.5, weight: FontWeight.w800, color: context.astra.textMuted, letterSpacing: 1.1)),
      );

  Widget _dateBox() {
    final p = context.astra;
    return GestureDetector(
      onTap: _openDateSheet,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 12),
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
        child: Row(
          children: [
            Icon(Icons.event_rounded, size: 17, color: p.primary),
            const SizedBox(width: 10),
            Expanded(child: Text(_dateLabel, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 15, color: p.ink))),
            Icon(Icons.keyboard_arrow_down_rounded, size: 18, color: p.textMuted),
          ],
        ),
      ),
    );
  }

  Widget _statusSeg() {
    final p = context.astra;
    Widget seg(String label, String? status) {
      final active = _status == status;
      return Expanded(
        child: GestureDetector(
          onTap: () => _setStatus(status),
          behavior: HitTestBehavior.opaque,
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 8),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: active ? p.card : Colors.transparent,
              borderRadius: BorderRadius.circular(10),
              boxShadow: active ? context.astraTheme.softShadow : null,
            ),
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(label,
                  style: ui(size: 11, weight: active ? FontWeight.w800 : FontWeight.w700, color: active ? p.primary : p.textSecondary)),
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
      child: Row(children: [
        seg('All', null),
        seg('Completed', 'completed'),
        seg('Draft', 'draft'),
      ]),
    );
  }

  Widget _selBox(String value, IconData icon, VoidCallback onTap) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12)),
        child: Row(
          children: [
            Icon(icon, size: 15, color: p.primary),
            const SizedBox(width: 8),
            Expanded(child: Text(value, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12, weight: FontWeight.w700, color: p.ink))),
            Icon(Icons.keyboard_arrow_down_rounded, size: 16, color: p.textMuted),
          ],
        ),
      ),
    );
  }

  Widget _resultLine() {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 16, 4, 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Flexible(
            child: Text('${_list.state.total} return${_list.state.total == 1 ? '' : 's'}',
                maxLines: 1, overflow: TextOverflow.ellipsis,
                style: ui(size: 11.5, weight: FontWeight.w700, color: p.textMuted)),
          ),
          const SizedBox(width: 8),
          Flexible(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerRight,
              child: Text('− ${Money.of(_list.state.totalPaid)}', style: serif(size: 16, color: p.goldText)),
            ),
          ),
        ],
      ),
    );
  }

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

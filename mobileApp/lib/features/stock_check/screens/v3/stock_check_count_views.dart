part of 'stock_check_count_screen.dart';

// The Stock Check count screen's hero, filters and tablet side panel. Split
// out of stock_check_count_screen.dart; a `part`, so nothing else changed.

extension _CountViews on _StockCheckCountScreenState {
  /// Tablet head — identity and back only; progress lives in the side panel.
  Widget _tabletPageHead() {
    final s = _stats;
    return TabletPageHead(
      leading: context.canPop()
          ? TabletIconButton(icon: Icons.chevron_left, tooltip: 'Back', onTap: () => context.pop())
          : null,
      title: s.title,
      subtitle: 'STOCK CHECK · #${s.id}',
      actions: [
        TabletActionButton(label: 'Scan', icon: Icons.qr_code_scanner, onTap: _openScan),
      ],
    );
  }

  Widget _hero() {
    final p = context.astra;
    final s = _stats;
    final pct = (s.progress * 100).round();
    final net = s.netDifference;
    final netColor = net < 0 ? const Color(0xFFFFC2CC) : (net > 0 ? p.accent : Colors.white);
    return Container(
      decoration: BoxDecoration(gradient: p.heroGradient, borderRadius: const BorderRadius.vertical(bottom: Radius.circular(30))),
      child: Stack(
        children: [
          Positioned(right: -40, top: -46, child: Container(width: 180, height: 180, decoration: BoxDecoration(shape: BoxShape.circle, gradient: RadialGradient(colors: [p.accent.withValues(alpha: 0.20), Colors.transparent])))),
          SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 6, 16, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      HeaderIconButton(icon: Icons.arrow_back_ios_new, onTap: () => context.pop()),
                      const SizedBox(width: 11),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('STOCK CHECK · #${s.id}', style: ui(size: 9.5, weight: FontWeight.w800, color: p.accent, letterSpacing: 1.6)),
                            const SizedBox(height: 2),
                            Text(s.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 21, color: Colors.white)),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      RichText(
                        text: TextSpan(children: [
                          TextSpan(text: '${s.itemsCounted}', style: serif(size: 25, color: Colors.white)),
                          TextSpan(text: ' / ${s.itemsTotal}', style: serif(size: 14, color: Colors.white.withValues(alpha: 0.7))),
                        ]),
                      ),
                      Text('$pct% counted', style: ui(size: 12, weight: FontWeight.w800, color: p.accent)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: LinearProgressIndicator(
                      value: s.progress.clamp(0, 1),
                      minHeight: 8,
                      backgroundColor: Colors.white.withValues(alpha: 0.18),
                      valueColor: AlwaysStoppedAnimation(p.accent),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      _heroStat('Counted', '${s.itemsCounted}', Colors.white),
                      const SizedBox(width: 9),
                      _heroStat('Variances', '${s.varianceCount}', p.accent),
                      const SizedBox(width: 9),
                      _heroStat('Net diff', net == 0 ? '0' : '${net > 0 ? '+' : ''}${qtyLabel(net)}', netColor),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _heroStat(String label, String value, Color valueColor) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 9),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label.toUpperCase(), style: ui(size: 8.5, weight: FontWeight.w800, color: Colors.white.withValues(alpha: 0.66), letterSpacing: 0.6)),
            const SizedBox(height: 3),
            Text(value, style: serif(size: 17, color: valueColor)),
          ],
        ),
      ),
    );
  }

  Widget _searchBox() {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
      decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(13), boxShadow: context.astraTheme.softShadow, border: Border.all(color: p.cardBorder)),
      child: Row(
        children: [
          Icon(Icons.search, size: 16, color: p.textMuted),
          const SizedBox(width: 8),
          Expanded(
            child: TextField(
              controller: _searchCtl,
              onChanged: _onSearch,
              style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink),
              decoration: InputDecoration(isDense: true, border: InputBorder.none, hintText: 'Product, code or barcode…', hintStyle: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted)),
            ),
          ),
          if (_searchCtl.text.isNotEmpty)
            GestureDetector(
              onTap: () {
                _searchCtl.clear();
                _onSearch('');
              },
              child: Icon(Icons.close, size: 16, color: p.textMuted),
            ),
        ],
      ),
    );
  }

  Widget _filterChips() {
    final s = _stats;
    int? badge(String key) => switch (key) {
          'all' => s.itemsTotal,
          'pending' => s.itemsTotal - s.itemsCounted,
          'counted' => s.itemsCounted,
          'variance' => s.varianceCount,
          _ => null,
        };
    return SizedBox(
      height: 34,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: _filters.length,
        separatorBuilder: (_, __) => const SizedBox(width: 7),
        itemBuilder: (_, i) {
          final f = _filters[i];
          return _chip(f.label, badge(f.key), _filter == f.key, () => _setFilter(f.key));
        },
      ),
    );
  }

  Widget _chip(String label, int? badge, bool active, VoidCallback onTap) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 13),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          gradient: active ? p.heroGradient : null,
          color: active ? null : p.card,
          borderRadius: BorderRadius.circular(20),
          boxShadow: active ? null : context.astraTheme.softShadow,
          border: Border.all(color: active ? Colors.transparent : p.cardBorder),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(label, style: ui(size: 11.5, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary)),
            if (badge != null) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                decoration: BoxDecoration(color: active ? Colors.white.withValues(alpha: 0.22) : p.tint, borderRadius: BorderRadius.circular(10)),
                child: Text('$badge', style: ui(size: 9.5, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary)),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _sidePanel(double width) {
    final p = context.astra;
    final s = _stats;
    final pct = (s.progress * 100).round();
    final net = s.netDifference;
    final netColor = net < 0 ? AstraPalette.danger : (net > 0 ? p.primary : p.textSecondary);
    Widget stat(String label, String value, Color color) => Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label.toUpperCase(), style: ui(size: 8.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
            const SizedBox(height: 2),
            Text(value, style: serif(size: 18, color: color)),
          ],
        );
    return TabletPane(
      width: width,
      edge: PaneEdge.left,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(18, 18, 18, 18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.only(left: 2, bottom: 10),
              child: SectionLabel('Progress'),
            ),
            AstraCard(
              radius: 16,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      RichText(
                        text: TextSpan(children: [
                          TextSpan(text: '${s.itemsCounted}', style: serif(size: 26, color: p.ink)),
                          TextSpan(text: ' / ${s.itemsTotal}', style: serif(size: 15, color: p.textMuted)),
                        ]),
                      ),
                      Text('$pct%', style: ui(size: 12, weight: FontWeight.w800, color: p.primary)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: LinearProgressIndicator(
                      value: s.progress.clamp(0, 1),
                      minHeight: 8,
                      backgroundColor: p.tint,
                      valueColor: AlwaysStoppedAnimation(p.primary),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Expanded(child: stat('Counted', '${s.itemsCounted}', p.ink)),
                      Expanded(child: stat('Variances', '${s.varianceCount}', p.ink)),
                      Expanded(child: stat('Net diff', net == 0 ? '0' : '${net > 0 ? '+' : ''}${qtyLabel(net)}', netColor)),
                    ],
                  ),
                ],
              ),
            ),
            const Spacer(),
            if (_dirty.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Text('${_dirty.length} unsaved change${_dirty.length == 1 ? '' : 's'}',
                    style: ui(size: 11, weight: FontWeight.w800, color: p.warnText)),
              ),
            GestureDetector(
              onTap: _openScan,
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 15),
                alignment: Alignment.center,
                decoration: BoxDecoration(gradient: p.accentGradient, borderRadius: BorderRadius.circular(15), boxShadow: context.astraTheme.floatShadow(p.accent)),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.qr_code_scanner, size: 17, color: p.primaryDark),
                    const SizedBox(width: 8),
                    Text('Scan', style: ui(size: 14, weight: FontWeight.w800, color: p.primaryDark)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 10),
            AstraButton(label: 'Save count', icon: Icons.check_rounded, busy: _saving, onTap: _save),
          ],
        ),
      ),
    );
  }
}

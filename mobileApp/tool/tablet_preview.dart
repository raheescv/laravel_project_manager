// Throwaway visual harness for the tablet layouts — NOT part of the app.
//
//   flutter run -t tool/tablet_preview.dart -d <ipad-simulator-udid>
//
// It mounts the real shared widgets from `lib/shared/widgets/tablet_widgets.dart`
// — the same ones the tablet branches of the real screens compose — with stub
// data, so the panes, heads, spacing and contrast can be checked at true iPad
// sizes (and in dark mode) without a backend or a login.
import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

void main() => runApp(const PreviewApp());

class PreviewApp extends StatefulWidget {
  const PreviewApp({super.key});
  @override
  State<PreviewApp> createState() => _PreviewAppState();
}

class _PreviewAppState extends State<PreviewApp> {
  int _screen = 0;
  bool _dark = false;

  /// Simulate a smaller tablet viewport (iPad mini portrait) without a second
  /// simulator: constrain the box AND the MediaQuery so width-derived layout
  /// (TabletMetrics, the LayoutBuilder guards) sees the same size.
  bool _narrow = false;
  static const _narrowSize = Size(744, 1000);

  @override
  Widget build(BuildContext context) {
    final palette = _dark ? AstraPresets.emeraldGold.dark : AstraPresets.emeraldGold;
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      theme: buildAstraTheme(palette),
      home: Builder(
        builder: (context) => Scaffold(
          body: AstraBackground(
            child: SafeArea(
              child: Column(
                children: [
                  _toolbar(context),
                  Expanded(
                    child: _narrow
                        ? Align(
                            alignment: Alignment.topLeft,
                            child: SizedBox(
                              width: _narrowSize.width,
                              child: MediaQuery(
                                data: MediaQuery.of(context).copyWith(size: _narrowSize),
                                child: Builder(builder: _body),
                              ),
                            ),
                          )
                        : _body(context),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _toolbar(BuildContext context) {
    final p = context.astra;
    const names = ['Sales', 'Returns', 'Settings', 'Stock', 'Reports', 'Home'];
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.hairline))),
      child: Row(
        children: [
          for (var i = 0; i < names.length; i++)
            Padding(
              padding: const EdgeInsets.only(right: 7),
              child: TabletFilterChip(
                  label: names[i], active: _screen == i, onTap: () => setState(() => _screen = i)),
            ),
          const Spacer(),
          TabletActionButton(
              label: _narrow ? '744pt' : 'Full',
              icon: Icons.width_normal,
              primary: _narrow,
              onTap: () => setState(() => _narrow = !_narrow)),
          const SizedBox(width: 8),
          TabletActionButton(
              label: _dark ? 'Light' : 'Dark',
              icon: Icons.brightness_6,
              onTap: () => setState(() => _dark = !_dark)),
        ],
      ),
    );
  }

  Widget _body(BuildContext context) => switch (_screen) {
        0 => _masterDetail(context, sales: true),
        1 => _masterDetail(context, sales: false),
        2 => _settings(context),
        3 => _stock(context),
        4 => _reports(context),
        _ => _home(context),
      };

  // ---- Sales / Returns master–detail ----

  Widget _masterDetail(BuildContext context, {required bool sales}) {
    final p = context.astra;
    final rows = sales
        ? const [
            ('INV-1042', 'QAR 3,990.00', 'Walk-in · 26 Jul · Cash', 'COMPLETED'),
            ('INV-1041', 'QAR 640.00', 'Sara M. · 26 Jul · Card', 'COMPLETED'),
            ('INV-1040', 'QAR 210.00', 'Walk-in · 26 Jul · Cash', 'DRAFT'),
            ('INV-1039', 'QAR 1,180.00', 'Ahmed K. · 25 Jul · Card, Cash', 'COMPLETED'),
            ('INV-1038', 'QAR 75.00', 'Walk-in · 25 Jul · Cash', 'CANCELLED'),
          ]
        : const [
            ('RET-208', '− QAR 390.00', 'Ahmed K. · 26 Jul · Cash', 'COMPLETED'),
            ('RET-207', '− QAR 120.00', 'Walk-in · 25 Jul · Cash', 'COMPLETED'),
            ('RET-206', '− QAR 60.00', 'Sara M. · 25 Jul · Card', 'DRAFT'),
          ];
    return LayoutBuilder(
      builder: (ctx, c) => Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TabletPane(
            width: TabletMetrics.forWidth(c.maxWidth).listColumn,
            child: Column(
              children: [
                TabletPaneHead(
                  title: sales ? 'Sales' : 'Sales Returns',
                  subtitle: sales
                      ? 'QAR 12,480.00 collected · 24 invoices'
                      : 'QAR 570.00 refunded · 3 returns',
                  leading: sales
                      ? null
                      : TabletIconButton(icon: Icons.chevron_left, tooltip: 'Back', onTap: () {}),
                  trailing: sales
                      ? TabletActionButton(
                          label: 'Returns', icon: Icons.assignment_return_outlined, onTap: () {})
                      : TabletActionButton(label: 'New', icon: Icons.add, primary: true, onTap: () {}),
                  children: [
                    const SizedBox(height: 13),
                    Wrap(spacing: 7, runSpacing: 7, children: [
                      for (final (i, s) in ['All', 'Completed', 'Draft', 'Cancelled'].indexed)
                        TabletFilterChip(label: s, active: i == 0, onTap: () {}),
                    ]),
                    const SizedBox(height: 8),
                    Wrap(spacing: 7, runSpacing: 7, children: [
                      TabletFilterChip(
                          label: 'This month',
                          active: false,
                          icon: Icons.event_rounded,
                          trailingIcon: Icons.keyboard_arrow_down_rounded,
                          onTap: () {}),
                      TabletFilterChip(
                          label: 'All methods',
                          active: false,
                          icon: Icons.account_balance_wallet_outlined,
                          trailingIcon: Icons.keyboard_arrow_down_rounded,
                          onTap: () {}),
                      TabletFilterChip(
                          label: 'Newest first',
                          active: false,
                          icon: Icons.swap_vert_rounded,
                          trailingIcon: Icons.keyboard_arrow_down_rounded,
                          onTap: () {}),
                    ]),
                  ],
                ),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.only(bottom: 24),
                    children: [
                      for (final (i, r) in rows.indexed)
                        TabletListRow(
                          selected: i == 0,
                          onTap: () {},
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(children: [
                                Expanded(
                                    child: Text(r.$1,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: ui(size: 13, weight: FontWeight.w800, color: p.ink))),
                                const SizedBox(width: 8),
                                Text(r.$2,
                                    style: serif(size: 15, color: sales ? p.ink : AstraPalette.danger)),
                              ]),
                              const SizedBox(height: 4),
                              Row(children: [
                                Expanded(
                                    child: Text(r.$3,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted))),
                                const SizedBox(width: 8),
                                StatusPill(
                                    label: r.$4,
                                    bg: switch (r.$4) {
                                      'COMPLETED' => p.successTint,
                                      'CANCELLED' => p.dangerTint,
                                      _ => p.warnTint,
                                    },
                                    fg: switch (r.$4) {
                                      'COMPLETED' => AstraPalette.success,
                                      'CANCELLED' => AstraPalette.danger,
                                      _ => p.goldText,
                                    }),
                              ]),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Expanded(child: _detail(context, sales)),
        ],
      ),
    );
  }

  /// Mirrors what InvoiceScreen / ReturnReceiptScreen now build when embedded.
  Widget _detail(BuildContext context, bool sales) {
    final p = context.astra;
    return LayoutBuilder(
      builder: (ctx, c) => ListView(
        padding: TabletMetrics.forWidth(c.maxWidth).detailPadding,
        children: [
          TabletDetailHead(
            label: sales ? 'Invoice · INV-1042' : 'Return · RET-208',
            amount: sales ? 'QAR 3,990.00' : '− QAR 390.00',
            amountColor: sales ? null : AstraPalette.danger,
            subtitle: 'Walk-in  ·  26 Jul 2026, 4:12 PM  ·  Cash',
            badge: StatusPill(
                label: 'PAID', bg: p.successTint, fg: AstraPalette.success, icon: Icons.check_circle),
            actions: [
              TabletActionButton(label: 'Print', icon: Icons.print_outlined, onTap: () {}),
              if (sales)
                TabletActionButton(
                    label: 'Return', icon: Icons.assignment_return_outlined, onTap: () {}),
              TabletActionButton(
                  label: 'Edit', icon: Icons.edit_outlined, primary: true, onTap: () {}),
              TabletIconButton(icon: Icons.more_horiz, onTap: () {}),
              TabletIconButton(icon: Icons.close_rounded, onTap: () {}),
            ],
          ),
          const SizedBox(height: 22),
          TabletPanel(
            title: sales ? 'Items' : 'Returned items',
            child: Column(children: [
              for (final it in const [
                ('Silk Abaya — Onyx', '2 × QAR 1,800.00', 'QAR 3,600.00'),
                ('Perfume Oil 12ml', '1 × QAR 390.00', 'QAR 390.00'),
              ])
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 11),
                  child: Row(children: [
                    IconChip(icon: Icons.shopping_bag_outlined, size: 38, radius: 12),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(it.$1, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                        const SizedBox(height: 2),
                        Text(it.$2, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                      ]),
                    ),
                    Text(it.$3, style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
                  ]),
                ),
            ]),
          ),
          const SizedBox(height: 18),
          TabletPanel(
            title: 'Summary',
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
            child: Column(children: [
              for (final s in const [('Subtotal', 'QAR 3,990.00'), ('Discount', '− QAR 0.00')])
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                    Text(s.$1, style: ui(size: 12.5, weight: FontWeight.w600, color: p.textSecondary)),
                    Text(s.$2, style: ui(size: 12.5, weight: FontWeight.w700, color: p.textSecondary)),
                  ]),
                ),
              const SizedBox(height: 14),
              Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                Text('Total Paid', style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
                Text('QAR 3,990.00', style: serif(size: 22, color: p.primaryDark)),
              ]),
            ]),
          ),
          const SizedBox(height: 18),
          TabletPanel(
            title: sales ? 'Payment' : 'Refund',
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 11),
              child: Row(children: [
                IconChip(icon: Icons.payments_outlined, size: 38, radius: 12),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text('Cash', style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                    const SizedBox(height: 2),
                    Text('Payment received',
                        style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                  ]),
                ),
                Text('QAR 3,990.00', style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
              ]),
            ),
          ),
        ],
      ),
    );
  }

  // ---- Settings two-pane ----

  Widget _settings(BuildContext context) {
    final p = context.astra;
    const cats = [
      (Icons.palette_outlined, 'Colour preset', 'Emerald & Gold'),
      (Icons.light_mode_outlined, 'Appearance', 'System · Light'),
      (Icons.vibration, 'Haptics', 'On'),
      (Icons.payments_outlined, 'Currency', 'QAR'),
      (Icons.business, 'Branch', 'Main branch'),
      (Icons.receipt_long_outlined, 'Printer & receipt', 'Compact · 80mm'),
      (Icons.verified_user_outlined, 'My permissions', 'Administrator'),
      (Icons.cloud_outlined, 'Server connection', 'Base URL & tenant'),
    ];
    return LayoutBuilder(
      builder: (ctx, c) {
        final m = TabletMetrics.forWidth(c.maxWidth);
        return Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TabletPane(
              width: m.settingsNav,
              child: Column(
                children: [
                  const TabletPaneHead(title: 'Settings', subtitle: 'Device and account preferences'),
                  Expanded(
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(12, 12, 12, 24),
                      children: [
                        for (final (i, cat) in cats.indexed)
                          Container(
                            margin: const EdgeInsets.only(bottom: 6),
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            decoration: BoxDecoration(
                                color: i == 1 ? p.tint : Colors.transparent,
                                borderRadius: BorderRadius.circular(14)),
                            child: Row(children: [
                              Container(
                                width: 36,
                                height: 36,
                                alignment: Alignment.center,
                                decoration: BoxDecoration(
                                  gradient: i == 1 ? p.primaryGradient : null,
                                  color: i == 1 ? null : p.tint,
                                  borderRadius: BorderRadius.circular(11),
                                ),
                                child: Icon(cat.$1, size: 18, color: i == 1 ? Colors.white : p.textSecondary),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                  Text(cat.$2,
                                      style: ui(
                                          size: 13,
                                          weight: i == 1 ? FontWeight.w800 : FontWeight.w700,
                                          color: p.ink)),
                                  Text(cat.$3,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                                ]),
                              ),
                            ]),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: m.detailPadding.copyWith(top: 24, bottom: 40),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(children: [
                      IconChip(icon: Icons.light_mode_outlined, size: 44, radius: 14, bg: p.tint),
                      const SizedBox(width: 13),
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text('Appearance', style: serif(size: 22, color: p.ink)),
                          const SizedBox(height: 2),
                          Text('Light, dark, or follow the system setting.',
                              style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
                        ]),
                      ),
                    ]),
                    const SizedBox(height: 20),
                    for (final (i, mo) in ['Light', 'Dark', 'System'].indexed)
                      Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                        decoration: BoxDecoration(
                          color: i == 0 ? p.tint : p.card,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: i == 0 ? p.primary : p.cardBorder, width: i == 0 ? 1.5 : 1),
                        ),
                        child: Row(children: [
                          Icon(Icons.circle_outlined, size: 20, color: i == 0 ? p.primary : p.textSecondary),
                          const SizedBox(width: 12),
                          Expanded(child: Text(mo, style: ui(size: 14, weight: FontWeight.w700, color: p.ink))),
                          if (i == 0) Icon(Icons.check_circle_rounded, size: 20, color: p.primary),
                        ]),
                      ),
                  ],
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  // ---- Stock check ----

  Widget _stock(BuildContext context) {
    final p = context.astra;
    return Column(
      children: [
        TabletPageHead(
          leading: TabletIconButton(icon: Icons.chevron_left, onTap: () {}),
          title: 'July floor count',
          subtitle: 'STOCK CHECK · #SC-14',
          actions: [TabletActionButton(label: 'Scan', icon: Icons.qr_code_scanner, onTap: () {})],
        ),
        Expanded(
          child: LayoutBuilder(
            builder: (ctx, c) => Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(14, 14, 14, 24),
                    children: [
                      LayoutBuilder(builder: (ctx2, c2) {
                        const gap = 12.0;
                        const minTile = 300.0;
                        final cols = ((c2.maxWidth + gap) / (minTile + gap)).floor().clamp(1, 4);
                        final colW = (c2.maxWidth - gap * (cols - 1)) / cols;
                        return Wrap(
                          spacing: gap,
                          runSpacing: gap,
                          children: [
                            for (final it in const [
                              ('Silk Abaya — Onyx', 'SA-01', 12),
                              ('Perfume Oil 12ml', 'PO-12', 30),
                              ('Kaftan Set', 'KF-03', 8),
                              ('Cotton Shayla', 'CS-07', 22),
                              ('Prayer Set', 'PR-02', 15),
                              ('Bakhoor Box', 'BK-09', 6),
                            ])
                              SizedBox(
                                width: colW,
                                child: AstraCard(
                                  radius: 18,
                                  child: Row(children: [
                                    IconChip(icon: Icons.inventory_2_outlined, size: 44, radius: 12),
                                    const SizedBox(width: 11),
                                    Expanded(
                                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                        Text(it.$1,
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: serif(size: 14.5, color: p.ink)),
                                        Text(it.$2,
                                            style: ui(size: 9.5, weight: FontWeight.w700, color: p.textMuted)),
                                      ]),
                                    ),
                                    Text('${it.$3}', style: serif(size: 20, color: p.primary)),
                                  ]),
                                ),
                              ),
                          ],
                        );
                      }),
                    ],
                  ),
                ),
                TabletPane(
                  width: TabletMetrics.forWidth(c.maxWidth).sidePanel,
                  edge: PaneEdge.left,
                  child: Padding(
                    padding: const EdgeInsets.all(18),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      SectionLabel('Progress'),
                      const SizedBox(height: 10),
                      AstraCard(
                        radius: 16,
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                            RichText(
                                text: TextSpan(children: [
                              TextSpan(text: '18', style: serif(size: 26, color: p.ink)),
                              TextSpan(text: ' / 128', style: serif(size: 15, color: p.textMuted)),
                            ])),
                            Text('14%', style: ui(size: 12, weight: FontWeight.w800, color: p.primary)),
                          ]),
                          const SizedBox(height: 8),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(6),
                            child: LinearProgressIndicator(
                                value: 0.14,
                                minHeight: 8,
                                backgroundColor: p.tint,
                                valueColor: AlwaysStoppedAnimation(p.primary)),
                          ),
                        ]),
                      ),
                      const Spacer(),
                      AstraButton(label: 'Save count', icon: Icons.check_rounded, onTap: () {}),
                    ]),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  // ---- Reports ----

  Widget _reports(BuildContext context) {
    final p = context.astra;
    return Column(
      children: [
        TabletPageHead(
          title: 'Reports',
          subtitle: '1 Jul – 27 Jul 2026',
          actions: [
            for (final (i, s) in ['Today', '7 Days', '30 Days', 'Month'].indexed)
              TabletFilterChip(label: s, active: i == 3, onTap: () {}),
            Container(
              width: 34,
              height: 34,
              alignment: Alignment.center,
              decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(11)),
              child: Icon(Icons.edit_calendar, size: 17, color: p.primary),
            ),
          ],
        ),
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 40),
            children: [
              LayoutBuilder(builder: (ctx, c) {
                Widget card(String t) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: AstraCard(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text(t, style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
                          const SizedBox(height: 10),
                          Container(height: 90, decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12))),
                        ]),
                      ),
                    );
                if (c.maxWidth < 820) {
                  return Column(children: [card('Sales performance'), card('By day'), card('Payments'), card('Breakdown')]);
                }
                return Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Expanded(child: Column(children: [card('Sales performance'), card('By day')])),
                  const SizedBox(width: 16),
                  Expanded(child: Column(children: [card('Payments'), card('Breakdown')])),
                ]);
              }),
            ],
          ),
        ),
      ],
    );
  }

  // ---- Home ----

  Widget _home(BuildContext context) {
    final p = context.astra;
    final tiles = [
      for (final t in const [
        ('New Sale', Icons.add_shopping_cart),
        ('Sales', Icons.receipt_long),
        ('Returns', Icons.assignment_return_outlined),
        ('Stock Check', Icons.fact_check_outlined),
        ('Reports', Icons.bar_chart),
        ('Day Session', Icons.schedule),
      ])
        AstraCard(
          radius: 20,
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 8),
          child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(15)),
              child: Icon(t.$2, size: 22, color: p.primary),
            ),
            const SizedBox(height: 10),
            Text(t.$1,
                textAlign: TextAlign.center,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(size: 11.5, weight: FontWeight.w700, color: p.ink)),
          ]),
        ),
    ];
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 40),
      children: [
        // Inset rounded hero card (the preview's `.hero`), not a full-bleed band.
        Container(
          clipBehavior: Clip.antiAlias,
          decoration: BoxDecoration(
            gradient: p.heroGradient,
            borderRadius: BorderRadius.circular(22),
            boxShadow: context.astraTheme.floatShadow(p.primary),
          ),
          padding: const EdgeInsets.fromLTRB(24, 20, 24, 22),
          child: Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
            Expanded(
              flex: 3,
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('GOOD EVENING,',
                    style: ui(size: 10, weight: FontWeight.w700, color: p.accent, letterSpacing: 2)),
                const SizedBox(height: 3),
                Text('hasif', style: serif(size: 24, color: Colors.white)),
                const SizedBox(height: 18),
                Text("TODAY'S REVENUE",
                    style: ui(size: 11, weight: FontWeight.w800, color: Colors.white70, letterSpacing: 1.5)),
                const SizedBox(height: 6),
                Text('QAR 3,990.00', style: serif(size: 46, color: Colors.white, height: 1)),
                const SizedBox(height: 8),
                Text('12 bills today · avg QAR 332.50',
                    style: ui(size: 12.5, weight: FontWeight.w600, color: Colors.white70)),
              ]),
            ),
          ]),
        ),
        const SizedBox(height: 20),
        SectionLabel('Quick actions'),
        const SizedBox(height: 10),
        LayoutBuilder(builder: (ctx, c) {
          const gap = 12.0;
          final n = tiles.length;
          final single = (c.maxWidth - gap * (n - 1)) / n;
          final cols = single >= 118 ? n : ((c.maxWidth + gap) / (150 + gap)).floor().clamp(2, n);
          final tileW = (c.maxWidth - gap * (cols - 1)) / cols;
          return GridView.count(
            crossAxisCount: cols,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: gap,
            crossAxisSpacing: gap,
            childAspectRatio: tileW / 118,
            children: tiles,
          );
        }),
        const SizedBox(height: 20),
        SectionLabel('Insights'),
        const SizedBox(height: 10),
        LayoutBuilder(builder: (ctx, c) {
          final main = Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Row(children: [
              Expanded(child: _kpi(context, 'This week', 'QAR 3,990')),
              const SizedBox(width: 12),
              Expanded(child: _kpi(context, 'This month', 'QAR 48,920')),
            ]),
            const SizedBox(height: 18),
            SectionLabel('Session payments'),
            const SizedBox(height: 10),
            AstraCard(child: Text('Cash · QAR 3,990', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink))),
          ]);
          final board = AstraCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Top performers', style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
              const SizedBox(height: 12),
              for (final s in const ['Hasif', 'Sara', 'Ahmed'])
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Text(s, style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary)),
                ),
            ]),
          );
          if (c.maxWidth < 820) {
            return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [main, const SizedBox(height: 18), board]);
          }
          return Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Expanded(flex: 3, child: main),
            const SizedBox(width: 16),
            Expanded(flex: 2, child: board),
          ]);
        }),
      ],
    );
  }

  Widget _kpi(BuildContext context, String label, String value) {
    final p = context.astra;
    return AstraCard(
      radius: 18,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        IconChip(icon: Icons.calendar_month, size: 30, radius: 9),
        const SizedBox(height: 14),
        Text(value, style: serif(size: 24, color: p.ink)),
        Text(label, style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
      ]),
    );
  }
}

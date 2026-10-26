import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart' show DateFormat;

import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/features/token/domain/models/token_models.dart';
import 'package:invo/features/token/logic/token_cubit/token_cubit.dart';
import 'package:invo/features/token/widgets/v3/token_hero_band.dart';
import 'package:invo/features/token/widgets/v3/token_slip_pdf.dart';
import 'package:invo/features/token/widgets/v3/token_slip_view.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/receipt_printer.dart';

/// Queue token kiosk ("Paper First"): the screen IS the slip, so what the
/// cashier sees is exactly what prints. New Token takes the next number for
/// the selected counter and, with auto-print on, prints it straight away;
/// Reprint prints the last slip again without taking a new number.
///
/// Numbering is local and date-wise (see [TokenLedger]); access is gated on
/// `sale.queue token` by the router and the drawer.
class TokenScreen extends StatefulWidget {
  const TokenScreen({super.key, this.onClose});

  /// Set when hosted as a tablet shell destination: Close returns to the
  /// dashboard tab instead of popping a route.
  final VoidCallback? onClose;

  @override
  State<TokenScreen> createState() => _TokenScreenState();
}

class _TokenScreenState extends State<TokenScreen> with WidgetsBindingObserver {
  bool _printing = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    context.read<TokenCubit>().load(context.read<BranchCubit>().selectedId);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  /// A kiosk left open overnight must start the new day from the start number.
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) context.read<TokenCubit>().refreshDay();
  }

  Future<void> _issue() async {
    final cubit = context.read<TokenCubit>();
    final snack = AstraSnack.capture(context);
    final slip = await cubit.issue();
    if (slip == null) {
      if (cubit.state.status == DataFetchStatus.failed) snack.error(cubit.state.errorMessage ?? '');
      return;
    }
    if (cubit.state.settings.autoPrint) await _print(slip);
  }

  /// Never undoes the issue: a failed print leaves the token issued and on
  /// screen, with a Retry on the snackbar.
  Future<void> _print(TokenSlip slip) async {
    if (_printing) return;
    final print = context.read<PrintSettingsCubit>();
    final settings = context.read<TokenCubit>().state.settings;
    final snack = AstraSnack.capture(context);
    setState(() => _printing = true);
    var result = ReceiptPrintResult.failed;
    try {
      final bytes = await buildTokenPdf(slip, settings,
          paper: print.width, logo: print.state.logoBytes, companyName: print.companyName);
      result = await printRollPdf(bytes, print.snapshot, title: 'Token ${slip.displayNo}', target: print.printer);
    } catch (_) {
      result = ReceiptPrintResult.failed;
    } finally {
      if (mounted) setState(() => _printing = false);
    }
    if (result == ReceiptPrintResult.cancelled) return;
    if (result.ok) {
      snack.success('Token ${slip.displayNo} printed', duration: const Duration(milliseconds: 1300));
    } else {
      snack.show('Couldn\'t reach the printer. The token is still issued.',
          kind: AstraSnackKind.error,
          action: SnackBarAction(label: 'Retry print', onPressed: () => _print(slip)));
    }
  }

  void _close() {
    if (widget.onClose != null) return widget.onClose!();
    context.canPop() ? context.pop() : context.go(Routes.home);
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<TokenCubit, TokenState>(
      builder: (context, s) {
        // A tablet gets the Status Board, the same frame as Day Session: a
        // floating hero band, cards filling the window, the action at the foot.
        if (context.isTablet) {
          return Scaffold(
            backgroundColor: Colors.transparent,
            body: AstraBackground(child: SafeArea(bottom: false, child: _tablet(context, s))),
          );
        }
        return Scaffold(
          backgroundColor: Colors.transparent,
          body: AstraBackground(
            child: Column(
              children: [
                EmeraldHeader(
                  title: 'Token',
                  subtitle: '${DateFormat('EEE, dd MMM').format(DateTime.now())} · ${s.ledger.issued} issued today',
                  leading: HeaderIconButton(icon: Icons.arrow_back, onTap: _close),
                  trailing: HeaderIconButton(icon: Icons.tune, onTap: () => context.push(Routes.tokenSettings)),
                ),
                Expanded(child: _phone(context, s)),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _phone(BuildContext context, TokenState s) => Column(
        children: [
          if (s.settings.hasCounters)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 0),
              child: _CounterChips(state: s, onSelect: context.read<TokenCubit>().selectCounter),
            ),
          Expanded(
            child: LayoutBuilder(
              builder: (_, c) => SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(24, 20, 24, 16),
                child: Column(
                  children: [
                    _SlipStage(state: s, width: math.min(c.maxWidth - 48, 300)),
                    const SizedBox(height: 12),
                    _TodayHint(state: s),
                  ],
                ),
              ),
            ),
          ),
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 14),
              child: _Dock(
                issuing: s.issuing,
                printing: _printing,
                canReprint: s.last != null,
                onIssue: _issue,
                onReprint: () => _print(s.last!),
                onClose: _close,
              ),
            ),
          ),
        ],
      );

  /// Tablet Status Board. Wide windows split the stage (the slip) and the
  /// Today panel side by side and stretch them to the action row; a narrow
  /// tablet stacks them and scrolls.
  Widget _tablet(BuildContext context, TokenState s) {
    final branch = context.watch<BranchCubit>().selected?.name ?? '';
    return LayoutBuilder(builder: (context, box) {
      final split = box.maxWidth >= 760;
      final stage = AstraCard(
        radius: 22,
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 22),
        child: Column(
          children: [
            Row(children: [
              SectionLabel(s.last == null ? 'Next token' : 'Last printed'),
              const SizedBox(width: 12),
              Expanded(child: _TodayHint(state: s, align: TextAlign.right)),
            ]),
            const SizedBox(height: 18),
            if (split) Expanded(child: _SlipStage(state: s)) else _SlipStage(state: s, width: 320),
          ],
        ),
      );
      final panel = _TodayPanel(state: s, onSelectCounter: context.read<TokenCubit>().selectCounter);
      return CustomScrollView(
        slivers: [
          SliverPadding(
            padding: EdgeInsets.fromLTRB(24, 16, 24, 24 + MediaQuery.paddingOf(context).bottom),
            sliver: SliverFillRemaining(
              hasScrollBody: false,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  TokenHeroBand(
                    icon: Icons.confirmation_number_outlined,
                    overline: branch.isEmpty ? 'QUEUE TOKENS' : 'QUEUE TOKENS · ${branch.toUpperCase()}',
                    title: 'Token',
                    subtitle: '${DateFormat('EEEE, d MMM yyyy').format(DateTime.now())} · numbers restart daily',
                    pill: s.settings.autoPrint
                        ? const TokenHeroPill(icon: Icons.bolt, label: 'Auto-print')
                        : const TokenHeroPill(icon: Icons.touch_app_outlined, label: 'Manual print'),
                    meta: [
                      (label: 'Issued today', value: '${s.ledger.issued}', gold: false),
                      (label: 'Last token', value: s.last?.displayNo ?? '—', gold: true),
                    ],
                    onBack: widget.onClose == null && context.canPop() ? _close : null,
                    compact: box.maxWidth < 820,
                    trailing: HeaderIconButton(icon: Icons.tune, onTap: () => context.push(Routes.tokenSettings)),
                  ),
                  const SizedBox(height: 14),
                  if (split)
                    Expanded(
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Expanded(flex: 145, child: stage),
                          const SizedBox(width: 14),
                          Expanded(flex: 100, child: panel),
                        ],
                      ),
                    )
                  else ...[
                    stage,
                    const SizedBox(height: 14),
                    panel,
                    const Spacer(),
                  ],
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      SizedBox(
                        width: split ? 220 : 160,
                        child: _OutlineAction(
                            icon: Icons.print_outlined,
                            label: _printing ? 'Printing…' : 'Reprint',
                            height: 56,
                            onTap: s.last == null || _printing ? null : () => _print(s.last!)),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: AstraButton(
                          label: 'New Token',
                          subtitle: 'Next ${s.nextDisplayNo}${s.settings.autoPrint ? ' · prints automatically' : ''}',
                          icon: Icons.add,
                          busy: s.issuing,
                          onTap: _issue,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      );
    });
  }
}

/// Today panel beside the slip: the counter picker, then the day's numbers
/// and where the slip goes.
class _TodayPanel extends StatelessWidget {
  const _TodayPanel({required this.state, required this.onSelectCounter});

  final TokenState state;
  final ValueChanged<String> onSelectCounter;

  @override
  Widget build(BuildContext context) {
    final print = context.watch<PrintSettingsCubit>();
    final printer = print.printer;
    return AstraCard(
      radius: 22,
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (state.settings.hasCounters) ...[
            const SectionLabel('Counter'),
            const SizedBox(height: 10),
            _CounterChips(state: state, onSelect: onSelectCounter, wrap: true),
            const SizedBox(height: 22),
          ],
          const SectionLabel('Today'),
          const SizedBox(height: 6),
          _PanelRow(label: 'Issued', value: '${state.ledger.issued}'),
          _PanelRow(label: 'Last token', value: state.last?.displayNo ?? '—'),
          _PanelRow(label: 'Next token', value: state.nextDisplayNo, strong: true),
          _PanelRow(label: 'Starts at', value: '${state.settings.startAt} · daily'),
          const SizedBox(height: 16),
          const SectionLabel('Printing'),
          const SizedBox(height: 6),
          _PanelRow(label: 'Paper', value: print.width.label),
          _PanelRow(
              label: 'Printer',
              value: printer.isPaired ? printer.displayName : 'Print dialog',
              last: true),
        ],
      ),
    );
  }
}

class _PanelRow extends StatelessWidget {
  const _PanelRow({required this.label, required this.value, this.strong = false, this.last = false});

  final String label;
  final String value;
  final bool strong;
  final bool last;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(
        border: last ? null : Border(bottom: BorderSide(color: p.hairline)),
      ),
      child: Row(
        children: [
          Text(label, style: ui(size: 12.5, weight: FontWeight.w600, color: p.textSecondary)),
          const SizedBox(width: 12),
          Expanded(
            child: Text(value,
                textAlign: TextAlign.right,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(size: strong ? 15 : 13, weight: FontWeight.w800, color: strong ? p.primary : p.ink)),
          ),
        ],
      ),
    );
  }
}

/// The paper itself: the last issued slip, or before the first token of the
/// day a faded preview of the number New Token will print.
class _SlipStage extends StatelessWidget {
  const _SlipStage({required this.state, this.width});

  final TokenState state;

  /// Fixed width, or null to fit (scaled) inside whatever box it is given.
  final double? width;

  @override
  Widget build(BuildContext context) {
    final print = context.watch<PrintSettingsCubit>();
    final last = state.last;
    final slip = TokenSlipView(
      displayNo: last?.displayNo ?? state.nextDisplayNo,
      counterName: last?.counterName ?? state.counter?.name ?? '',
      issuedAt: last?.issuedAt,
      settings: state.settings,
      paper: print.width,
      logo: print.state.logoBytes,
      companyName: print.companyName,
      ghost: last == null,
    );
    final paper = Container(
      width: width,
      decoration: BoxDecoration(boxShadow: [
        BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 26, offset: const Offset(0, 14)),
      ]),
      child: AnimatedSwitcher(
        duration: const Duration(milliseconds: 220),
        transitionBuilder: (child, a) => FadeTransition(
          opacity: a,
          child: SlideTransition(
              position: Tween(begin: const Offset(0, -0.04), end: Offset.zero).animate(a), child: child),
        ),
        child: FittedBox(key: ValueKey('${last?.displayNo}-${last?.issuedAt}'), fit: BoxFit.contain, child: slip),
      ),
    );
    if (width != null) return paper;
    return Center(
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 420),
        child: FittedBox(fit: BoxFit.contain, child: SizedBox(width: 300, child: paper)),
      ),
    );
  }
}

class _TodayHint extends StatelessWidget {
  const _TodayHint({required this.state, this.align = TextAlign.center});

  final TokenState state;
  final TextAlign align;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final last = state.last;
    return Text(
      last == null ? 'Next token · tap New Token to issue' : 'Last ${last.displayNo} · ${state.ledger.issued} today',
      textAlign: align,
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted),
    );
  }
}

/// One chip per counter showing its prefix and its next number; a tap picks
/// the counter New Token issues for.
class _CounterChips extends StatelessWidget {
  const _CounterChips({required this.state, required this.onSelect, this.wrap = false});

  final TokenState state;
  final ValueChanged<String> onSelect;
  final bool wrap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final selected = state.counter?.id;
    final chips = [
      for (final c in state.settings.counters)
        GestureDetector(
          onTap: () => onSelect(c.id),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 160),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
            decoration: BoxDecoration(
              gradient: c.id == selected ? p.primaryGradient : null,
              color: c.id == selected ? null : p.card,
              borderRadius: BorderRadius.circular(13),
              border: Border.all(color: c.id == selected ? Colors.transparent : p.hairline),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(c.name.isEmpty ? c.prefix : c.name,
                    style: ui(size: 12.5, weight: FontWeight.w800, color: c.id == selected ? Colors.white : p.ink)),
                Text('next ${state.settings.format(state.ledger.nextSequence(c.id, state.settings.startAt), counter: c)}',
                    style: ui(
                        size: 10,
                        weight: FontWeight.w600,
                        color: c.id == selected ? Colors.white.withValues(alpha: 0.8) : p.textMuted)),
              ],
            ),
          ),
        ),
    ];
    if (wrap) return Wrap(spacing: 8, runSpacing: 8, children: chips);
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(children: [
        for (final chip in chips) Padding(padding: const EdgeInsets.only(right: 8), child: chip),
      ]),
    );
  }
}

/// Floating bottom dock: Close · New Token · Reprint.
class _Dock extends StatelessWidget {
  const _Dock({
    required this.issuing,
    required this.printing,
    required this.canReprint,
    required this.onIssue,
    required this.onReprint,
    required this.onClose,
  });

  final bool issuing;
  final bool printing;
  final bool canReprint;
  final VoidCallback onIssue;
  final VoidCallback onReprint;
  final VoidCallback onClose;

  @override
  Widget build(BuildContext context) {
    return AstraCard(
      radius: 20,
      padding: const EdgeInsets.all(6),
      child: Row(
        children: [
          _DockIcon(icon: Icons.close, label: 'Close', onTap: onClose),
          const SizedBox(width: 6),
          Expanded(child: AstraButton(label: 'New Token', icon: Icons.add, busy: issuing, onTap: onIssue)),
          const SizedBox(width: 6),
          _DockIcon(
              icon: Icons.print_outlined,
              label: printing ? 'Printing' : 'Reprint',
              onTap: canReprint && !printing ? onReprint : null),
        ],
      ),
    );
  }
}

class _DockIcon extends StatelessWidget {
  const _DockIcon({required this.icon, required this.label, this.onTap});

  final IconData icon;
  final String label;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final color = onTap == null ? p.textMuted.withValues(alpha: 0.5) : p.textSecondary;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: SizedBox(
        width: 58,
        height: 50,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 19, color: color),
            const SizedBox(height: 2),
            Text(label, style: ui(size: 9.5, weight: FontWeight.w700, color: color)),
          ],
        ),
      ),
    );
  }
}

class _OutlineAction extends StatelessWidget {
  const _OutlineAction({required this.icon, required this.label, this.onTap, this.height = 48});

  final IconData icon;
  final String label;
  final VoidCallback? onTap;
  final double height;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final color = onTap == null ? p.textMuted : p.ink;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: height,
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: p.hairline),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 17, color: color),
            const SizedBox(width: 7),
            Text(label, style: ui(size: 13, weight: FontWeight.w700, color: color)),
          ],
        ),
      ),
    );
  }
}

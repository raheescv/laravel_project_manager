import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart' show DateFormat;

import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/features/token/domain/models/token_models.dart';
import 'package:invo/features/token/logic/token_cubit/token_cubit.dart';
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
        // A tablet gets "Now Serving": the number as a queue display beside a
        // column of actions.
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

  /// Tablet "Now Serving" (sample A in docs/mobile-tablet-token-preview.html):
  /// the number is the page. A gradient stage shows the token just printed at
  /// queue-display size with the next one under it; the column beside it is
  /// all action — counters, a tall New Token, Reprint. A narrow (portrait)
  /// window stacks the stage over the column.
  Widget _tablet(BuildContext context, TokenState s) {
    final branch = context.watch<BranchCubit>().selected?.name ?? '';
    return LayoutBuilder(builder: (context, box) {
      final split = box.maxWidth >= 760 && box.maxWidth > box.maxHeight;
      final stage = _ServingStage(state: s, numberSize: split && box.maxHeight >= 650 ? 180 : 150);
      final actions = _tabletActions(context, s, branch, portrait: !split);
      return Padding(
        padding: EdgeInsets.fromLTRB(18, 18, 18, 18 + MediaQuery.paddingOf(context).bottom),
        child: split
            ? Row(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Expanded(child: stage),
                  const SizedBox(width: 18),
                  SizedBox(width: box.maxWidth < 1150 ? 300 : 330, child: actions),
                ],
              )
            : Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  SizedBox(height: math.min(430.0, box.maxHeight * 0.45), child: stage),
                  const SizedBox(height: 16),
                  Expanded(child: actions),
                ],
              ),
      );
    });
  }

  /// The action column: title + settings, the counter picker, then New Token
  /// and Reprint pinned to the foot where a thumb finds them.
  Widget _tabletActions(BuildContext context, TokenState s, String branch, {required bool portrait}) {
    final p = context.astra;
    final last = s.last;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            if (widget.onClose == null && context.canPop()) ...[
              _SquareButton(icon: Icons.arrow_back, onTap: _close),
              const SizedBox(width: 12),
            ],
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Token', style: serif(size: 26, color: p.ink)),
                  const SizedBox(height: 2),
                  Text(branch.isEmpty ? 'Queue tokens' : '$branch · queue',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
            _SquareButton(icon: Icons.tune, onTap: () => context.push(Routes.tokenSettings)),
          ],
        ),
        if (s.settings.hasCounters) ...[
          const SizedBox(height: 18),
          const SectionLabel('Counter'),
          const SizedBox(height: 10),
          // Many counters scroll here rather than pushing New Token off the foot.
          Expanded(
            child: SingleChildScrollView(
              child: _CounterChips(
                  state: s, onSelect: context.read<TokenCubit>().selectCounter, columns: portrait ? 3 : 1),
            ),
          ),
        ] else
          const Spacer(),
        const SizedBox(height: 12),
        SizedBox(
          height: s.settings.hasCounters || portrait ? 150 : 170,
          child: _IssueButton(
            next: s.nextDisplayNo,
            autoPrint: s.settings.autoPrint,
            busy: s.issuing,
            onTap: _issue,
          ),
        ),
        const SizedBox(height: 12),
        _OutlineAction(
            icon: Icons.print_outlined,
            label: _printing ? 'Printing…' : last == null ? 'Reprint' : 'Reprint ${last.displayNo}',
            height: 52,
            onTap: last == null || _printing ? null : () => _print(last)),
      ],
    );
  }
}

/// The queue-display stage: the token just printed, huge, or before the first
/// token of the day a faded preview of the number New Token will issue.
class _ServingStage extends StatelessWidget {
  const _ServingStage({required this.state, required this.numberSize});

  final TokenState state;
  final double numberSize;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final print = context.watch<PrintSettingsCubit>();
    final last = state.last;
    final shown = last?.displayNo ?? state.nextDisplayNo;
    final counterName = last?.counterName ?? state.counter?.name ?? '';
    final caption = [
      if (counterName.isNotEmpty) counterName,
      if (last != null) 'printed ${DateFormat('h:mm a').format(last.issuedAt)}',
    ].join(' · ');
    final autoPrint = state.settings.autoPrint;
    return ClipRRect(
      borderRadius: BorderRadius.circular(24),
      child: DecoratedBox(
        decoration: BoxDecoration(
          gradient: RadialGradient(
            center: Alignment.topLeft,
            radius: 1.6,
            colors: [p.primary, p.primaryDark, Color.lerp(p.primaryDark, Colors.black, 0.25)!],
            stops: const [0, 0.6, 1],
          ),
        ),
        child: Stack(
          fit: StackFit.expand,
          children: [
            Positioned(
              right: -120,
              top: -140,
              width: 380,
              height: 380,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(colors: [p.accent.withValues(alpha: 0.4), Colors.transparent]),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(28, 24, 28, 22),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(last == null ? 'FIRST TOKEN OF THE DAY' : 'NOW SERVING',
                                style: ui(size: 10.5, weight: FontWeight.w800, color: p.accent, letterSpacing: 1.8)),
                            const SizedBox(height: 4),
                            Text('${DateFormat('EEEE, d MMM yyyy').format(DateTime.now())} · numbers restart daily',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: ui(size: 12.5, weight: FontWeight.w600, color: Colors.white.withValues(alpha: 0.65))),
                          ],
                        ),
                      ),
                      const SizedBox(width: 16),
                      _StageStat(label: 'ISSUED', value: '${state.ledger.issued}'),
                      const SizedBox(width: 18),
                      _StageStat(label: 'STARTS AT', value: '${state.settings.startAt}'),
                    ],
                  ),
                  Expanded(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        AnimatedSwitcher(
                          duration: const Duration(milliseconds: 240),
                          transitionBuilder: (child, a) => FadeTransition(
                            opacity: a,
                            child: ScaleTransition(scale: Tween(begin: 0.94, end: 1.0).animate(a), child: child),
                          ),
                          child: FittedBox(
                            key: ValueKey('$shown-${last?.issuedAt}'),
                            fit: BoxFit.scaleDown,
                            child: Opacity(
                              opacity: last == null ? 0.35 : 1,
                              child: Text(shown,
                                  maxLines: 1,
                                  style: ui(
                                      size: numberSize,
                                      weight: FontWeight.w900,
                                      color: Colors.white,
                                      height: 1,
                                      letterSpacing: -numberSize / 70)),
                            ),
                          ),
                        ),
                        if (caption.isNotEmpty) ...[
                          const SizedBox(height: 10),
                          Text(caption,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: ui(size: 15, weight: FontWeight.w700, color: Colors.white.withValues(alpha: 0.75))),
                        ],
                      ],
                    ),
                  ),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Flexible(
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.14),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(autoPrint ? Icons.bolt : Icons.touch_app_outlined, size: 13, color: Colors.white),
                              const SizedBox(width: 6),
                              Flexible(
                                child: Text('${autoPrint ? 'Auto-print' : 'Manual print'} · ${print.width.label}',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: ui(size: 11, weight: FontWeight.w800, color: Colors.white)),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Text.rich(TextSpan(
                        text: 'Next  ',
                        style: ui(size: 13, weight: FontWeight.w700, color: Colors.white.withValues(alpha: 0.75)),
                        children: [
                          TextSpan(
                              text: state.nextDisplayNo,
                              style: ui(size: 16, weight: FontWeight.w800, color: p.accent)),
                        ],
                      )),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StageStat extends StatelessWidget {
  const _StageStat({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Column(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(label,
              style: ui(
                  size: 9, weight: FontWeight.w800, color: Colors.white.withValues(alpha: 0.55), letterSpacing: 1.3)),
          const SizedBox(height: 2),
          Text(value, style: ui(size: 20, weight: FontWeight.w800, color: Colors.white)),
        ],
      );
}

/// The tall New Token target in the tablet action column.
class _IssueButton extends StatelessWidget {
  const _IssueButton({required this.next, required this.autoPrint, required this.busy, required this.onTap});

  final String next;
  final bool autoPrint;
  final bool busy;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: busy ? null : onTap,
      child: Container(
        decoration: BoxDecoration(
          gradient: p.primaryGradient,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [
            BoxShadow(
                color: p.primary.withValues(alpha: 0.28),
                blurRadius: 24,
                spreadRadius: -14,
                offset: const Offset(0, 10)),
          ],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 52,
              height: 52,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.16),
                borderRadius: BorderRadius.circular(16),
              ),
              child: busy
                  ? const SizedBox(
                      width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white))
                  : const Icon(Icons.add, color: Colors.white, size: 26),
            ),
            const SizedBox(height: 10),
            Text('New Token', style: ui(size: 19, weight: FontWeight.w800, color: Colors.white)),
            const SizedBox(height: 2),
            Text('Next $next${autoPrint ? ' · prints automatically' : ''}',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(size: 12, weight: FontWeight.w600, color: Colors.white.withValues(alpha: 0.75))),
          ],
        ),
      ),
    );
  }
}

class _SquareButton extends StatelessWidget {
  const _SquareButton({required this.icon, required this.onTap});

  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 42,
        height: 42,
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: p.hairline),
        ),
        child: Icon(icon, size: 18, color: p.textSecondary),
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
  const _TodayHint({required this.state});

  final TokenState state;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final last = state.last;
    return Text(
      last == null ? 'Next token · tap New Token to issue' : 'Last ${last.displayNo} · ${state.ledger.issued} today',
      textAlign: TextAlign.center,
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted),
    );
  }
}

/// One chip per counter showing its prefix and its next number; a tap picks
/// the counter New Token issues for.
class _CounterChips extends StatelessWidget {
  const _CounterChips({required this.state, required this.onSelect, this.columns});

  final TokenState state;
  final ValueChanged<String> onSelect;

  /// Lay the chips out as equal-width tiles in this many columns (tablet),
  /// or null for the phone's single scrolling row.
  final int? columns;

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
    final perRow = columns;
    if (perRow != null) {
      return LayoutBuilder(
        builder: (_, c) => Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            for (final chip in chips) SizedBox(width: (c.maxWidth - 8 * (perRow - 1)) / perRow, child: chip),
          ],
        ),
      );
    }
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

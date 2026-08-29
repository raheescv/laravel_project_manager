import 'package:flutter/material.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// The notes a cashier is realistically handed, per currency — what the tender
/// pad's note grid offers. Six per ladder so the grid stays two tidy rows; an
/// unlisted currency falls back to a generic one rather than showing nothing.
class CashDenominations {
  const CashDenominations._();

  static const _ladders = <String, List<double>>{
    'QAR': [1, 5, 10, 50, 100, 500],
    'SAR': [1, 5, 10, 50, 100, 500],
    'AED': [5, 10, 20, 50, 100, 200],
    'USD': [1, 5, 10, 20, 50, 100],
    'EUR': [5, 10, 20, 50, 100, 200],
    'GBP': [1, 2, 5, 10, 20, 50],
    'INR': [10, 20, 50, 100, 200, 500],
    'JPY': [100, 500, 1000, 2000, 5000, 10000],
  };
  static const _fallback = <double>[1, 5, 10, 20, 50, 100];

  static List<double> forCode(String? code) =>
      _ladders[(code ?? '').toUpperCase()] ?? _fallback;

  /// The ladder for the currency the POS is currently priced in.
  static List<double> current() {
    if (!serviceLocator.isRegistered<CurrencyCubit>()) return _fallback;
    return forCode(serviceLocator<CurrencyCubit>().currency.code);
  }
}

/// "Received" — how much cash the customer actually handed over, and what goes
/// back to them.
///
/// This is a **counter aid, not a payment**. Cash always settles the ticket in
/// full, so nothing entered here reaches the payload: `paidAmount` stays the
/// total and the balance stays zero. Keying 50 against a 36 ticket means the
/// drawer took 50 and 14 comes back — not that the customer overpaid by 14.
/// A genuine part payment is Credit or Split, which is what the short-tendered
/// hint points at.
///
/// Two ways in, because a counter uses both: tap the notes that were handed
/// over (the common case, no keyboard at all), or type an odd amount on the
/// pad. [onChanged] reports the running amount so the checkout bar can name the
/// change on its label.
class CashTenderPanel extends StatefulWidget {
  const CashTenderPanel({super.key, required this.total, required this.onChanged});

  final double total;
  final ValueChanged<double?> onChanged;

  @override
  State<CashTenderPanel> createState() => _CashTenderPanelState();
}

class _CashTenderPanelState extends State<CashTenderPanel> {
  /// How many of each note went into the drawer, in ladder order.
  final Map<double, int> _notes = {};

  /// The keypad's digit buffer. Kept as text, not a double, so a half-typed
  /// "36." survives the rebuild between the dot and the decimals.
  String _typed = '';
  bool _keypad = false;

  late final List<double> _ladder = CashDenominations.current();

  double get _noteTotal => _notes.entries.fold(0.0, (a, e) => a + e.key * e.value);
  int get _noteCount => _notes.values.fold(0, (a, n) => a + n);

  /// Null until something has actually been entered — an untouched pad must not
  /// read as "received nothing", which would show the full total as short.
  double? get _tendered {
    if (!_keypad) return _notes.isEmpty ? null : _noteTotal;
    if (_typed.isEmpty) return null;
    // A buffer parked on its decimal point ("36.") is still a number — dropping
    // the band between the dot and the cents would make it flicker.
    return double.tryParse(_typed.endsWith('.') ? _typed.substring(0, _typed.length - 1) : _typed);
  }

  /// What the amount line reads. The pad echoes the keys as pressed — a
  /// half-typed "36." must not redraw itself as "36.00" under the thumb — while
  /// a note tally is a computed total, so that one is formatted.
  String get _display => _keypad ? (_typed.isEmpty ? Money.plain(0) : _typed) : Money.plain(_noteTotal);

  void _report() => widget.onChanged(_tendered);

  void _addNote(double value) {
    setState(() => _notes[value] = (_notes[value] ?? 0) + 1);
    _report();
  }

  /// Long-press takes one back, for the note tapped twice by mistake.
  void _removeNote(double value) {
    final n = _notes[value];
    if (n == null) return;
    setState(() => n <= 1 ? _notes.remove(value) : _notes[value] = n - 1);
    _report();
  }

  void _key(String k) {
    setState(() {
      if (k == '<') {
        if (_typed.isNotEmpty) _typed = _typed.substring(0, _typed.length - 1);
        return;
      }
      // One dot only, and no more decimals than the currency actually has.
      if (k == '.') {
        if (Money.decimals == 0 || _typed.contains('.')) return;
        _typed = _typed.isEmpty ? '0.' : '$_typed.';
        return;
      }
      final dot = _typed.indexOf('.');
      if (dot >= 0 && _typed.length - dot > Money.decimals) return;
      if (_typed == '0') _typed = '';
      if (_typed.length >= 9) return;
      _typed += k;
    });
    _report();
  }

  void _reset() {
    setState(() {
      _notes.clear();
      _typed = '';
    });
    _report();
  }

  /// Switching surface carries the amount forward into the pad, so "50 in notes,
  /// plus the 3 in coins" doesn't mean starting again. Going back to the notes
  /// resumes the tally that is still on screen.
  void _toggleSurface() {
    setState(() {
      if (!_keypad) _typed = _noteTotal == 0 ? '' : Money.plain(_noteTotal).replaceAll(',', '');
      _keypad = !_keypad;
    });
    _report();
  }

  void _exact() {
    setState(() {
      _keypad = true;
      _typed = Money.plain(widget.total).replaceAll(',', '');
    });
    _report();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final tendered = _tendered;

    return Column(
      children: [
        AstraCard(
          padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Text('RECEIVED',
                      style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
                  const Spacer(),
                  if (tendered != null) ...[
                    _chip(label: 'Reset', icon: Icons.close, onTap: _reset),
                    const SizedBox(width: 6),
                  ],
                  _chip(
                    label: _keypad ? 'Notes' : 'Keypad',
                    icon: _keypad ? Icons.grid_view_rounded : Icons.dialpad,
                    onTap: _toggleSurface,
                    accent: true,
                  ),
                ],
              ),
              const SizedBox(height: 6),
              _amountLine(p, tendered),
              const SizedBox(height: 12),
              if (_keypad) _pad(p) else _noteGrid(p),
            ],
          ),
        ),
        if (tendered != null) ...[
          const SizedBox(height: 12),
          ChangeDueBand(total: widget.total, tendered: tendered),
        ],
      ],
    );
  }

  /// The running amount, big enough to read across the counter.
  Widget _amountLine(AstraPalette p, double? tendered) {
    final symbol = Money.symbol.trim();
    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        if (symbol.isNotEmpty) ...[
          Padding(
            padding: const EdgeInsets.only(bottom: 5),
            child: Text(symbol, style: ui(size: 12, weight: FontWeight.w800, color: p.textMuted)),
          ),
          const SizedBox(width: 7),
        ],
        Flexible(
          child: FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(
              _display,
              style: serif(size: 32, color: tendered == null ? p.textMuted : p.ink),
            ),
          ),
        ),
        const SizedBox(width: 8),
        Padding(
          padding: const EdgeInsets.only(bottom: 5),
          child: _noteCount > 0 && !_keypad
              ? Text('$_noteCount note${_noteCount == 1 ? '' : 's'}',
                  style: ui(size: 10.5, weight: FontWeight.w800, color: p.textMuted))
              : GestureDetector(
                  onTap: _exact,
                  child: Text('Exact ${Money.plain(widget.total)}',
                      style: ui(size: 10.5, weight: FontWeight.w800, color: p.primary)),
                ),
        ),
      ],
    );
  }

  Widget _chip({required String label, required IconData icon, required VoidCallback onTap, bool accent = false}) {
    final p = context.astra;
    final color = accent ? p.primary : p.textMuted;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: accent ? p.tint : Colors.transparent,
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: accent ? Colors.transparent : p.hairline),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 12, color: color),
            const SizedBox(width: 5),
            Text(label, style: ui(size: 10, weight: FontWeight.w800, color: color)),
          ],
        ),
      ),
    );
  }

  /// Tap the notes that came across the counter; long-press takes one back.
  Widget _noteGrid(AstraPalette p) {
    return GridView.count(
      crossAxisCount: 3,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 7,
      crossAxisSpacing: 7,
      childAspectRatio: 2.5,
      children: [
        for (final note in _ladder)
          GestureDetector(
            onTap: () => _addNote(note),
            onLongPress: () => _removeNote(note),
            child: Container(
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: (_notes[note] ?? 0) > 0 ? p.tint : p.card,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: (_notes[note] ?? 0) > 0 ? p.primary : p.hairline),
              ),
              child: Stack(
                alignment: Alignment.center,
                children: [
                  Text(Money.plain(note, decimals: 0),
                      style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
                  if ((_notes[note] ?? 0) > 0)
                    Positioned(
                      top: 3,
                      right: 6,
                      child: Text('×${_notes[note]}',
                          style: ui(size: 9, weight: FontWeight.w800, color: p.primary)),
                    ),
                ],
              ),
            ),
          ),
      ],
    );
  }

  Widget _pad(AstraPalette p) {
    const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', '<'];
    return GridView.count(
      crossAxisCount: 3,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 7,
      crossAxisSpacing: 7,
      childAspectRatio: 2.2,
      children: [
        for (final k in keys)
          GestureDetector(
            onTap: () => _key(k),
            child: Container(
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: p.card,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: p.hairline),
              ),
              child: k == '<'
                  ? Icon(Icons.backspace_outlined, size: 17, color: AstraPalette.danger)
                  : Text(k,
                      style: ui(
                        size: 18,
                        weight: FontWeight.w700,
                        // A currency with no minor unit has no use for the dot;
                        // it stays in place so the grid keeps its shape, greyed
                        // out rather than silently doing nothing.
                        color: k == '.' && Money.decimals == 0 ? p.textMuted : p.ink,
                      )),
            ),
          ),
      ],
    );
  }
}

/// What goes back into the customer's hand — or what is still owed.
///
/// Short of the total is a hint, not a block: cash posts the ticket in full
/// either way, so the honest thing is to name the shortfall and point at the
/// tool that actually models a part payment.
class ChangeDueBand extends StatelessWidget {
  const ChangeDueBand({super.key, required this.total, required this.tendered});

  final double total;
  final double tendered;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final diff = double.parse((tendered - total).toStringAsFixed(2));

    final (Color tint, Color color, IconData icon, String title, String desc) = diff > 0
        ? (p.goldTint, p.goldText, Icons.undo, 'Give back',
            '${Money.of(tendered)} received · ${Money.of(total)} due')
        : diff == 0
            ? (p.successTint, AstraPalette.success, Icons.check_circle, 'Exact amount',
                'No change to hand back')
            : (p.dangerTint, AstraPalette.danger, Icons.warning_amber_rounded, 'Short by',
                'Take the rest, or split the payment');

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: tint,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: 0.35)),
      ),
      child: Row(
        children: [
          Container(
            width: 30,
            height: 30,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
            child: Icon(icon, size: 15, color: Colors.white),
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: ui(size: 12, weight: FontWeight.w800, color: color)),
                const SizedBox(height: 1),
                Text(desc, style: ui(size: 10, weight: FontWeight.w600, color: color.withValues(alpha: 0.85))),
              ],
            ),
          ),
          const SizedBox(width: 8),
          if (diff != 0)
            Text(Money.of(diff.abs()), style: serif(size: 22, color: color)),
        ],
      ),
    );
  }
}

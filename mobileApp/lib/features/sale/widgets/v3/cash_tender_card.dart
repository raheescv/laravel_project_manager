import 'package:flutter/material.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

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

/// "Received" — how much cash the customer actually handed over.
///
/// This is a **counter aid, not a payment**. Cash always settles the ticket in
/// full, so nothing entered here reaches the payload: `paidAmount` stays the
/// total and the balance stays zero. Keying 50 against a 36 ticket means the
/// drawer took 50 and 14 comes back — not that the customer overpaid by 14.
/// A genuine part payment is Credit or Split.
///
/// Two ways in, because a counter uses both: tap the notes that were handed
/// over (the common case, no keyboard at all), or type an odd amount on the
/// pad. [onChanged] reports the running amount; the screen states the change
/// in its own header, so the panel is only the pad and its tool row.
///
/// The pad fills the height it is given, up to [maxPadHeight] — the panel is
/// laid out in a fixed-height column, never in a scroll view.
class CashTenderPanel extends StatefulWidget {
  const CashTenderPanel({
    super.key,
    required this.total,
    required this.onChanged,
    this.maxPadHeight,
    this.tools = const [],
  });

  final double total;
  final ValueChanged<double?> onChanged;

  /// The tallest the note grid may grow (the keypad, one row more, gets a
  /// third extra); null lets the pad take the whole panel.
  final double? maxPadHeight;

  /// More buttons for the tool row after Reset and Exact — the phone's Note.
  final List<Widget> tools;

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

  /// The customer handed over exactly the total. A switch of its own rather
  /// than a typed amount, so taking it never moves the cashier off the surface
  /// they are on — and tapping it again, a note or a key takes it back off.
  bool _exactTaken = false;

  late final List<double> _ladder = CashDenominations.current();

  double get _noteTotal => _notes.entries.fold(0.0, (a, e) => a + e.key * e.value);
  int get _noteCount => _notes.values.fold(0, (a, n) => a + n);

  /// Null until something has actually been entered — an untouched pad must not
  /// read as "received nothing", which would show the full total as short.
  double? get _tendered {
    if (_exactTaken) return widget.total;
    if (!_keypad) return _notes.isEmpty ? null : _noteTotal;
    if (_typed.isEmpty) return null;
    // A buffer parked on its decimal point ("36.") is still a number — dropping
    // the change between the dot and the cents would make it flicker.
    return double.tryParse(_typed.endsWith('.') ? _typed.substring(0, _typed.length - 1) : _typed);
  }

  void _report() => widget.onChanged(_tendered);

  void _addNote(double value) {
    setState(() {
      _exactTaken = false;
      _notes[value] = (_notes[value] ?? 0) + 1;
    });
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
      _exactTaken = false;
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
      _exactTaken = false;
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

  /// Exact on clears whatever was counted or typed; Exact off goes back to an
  /// untouched pad.
  void _toggleExact() {
    setState(() {
      _exactTaken = !_exactTaken;
      _notes.clear();
      _typed = '';
    });
    _report();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final cap = widget.maxPadHeight;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(_keypad ? 'TYPE THE AMOUNT RECEIVED' : 'TAP THE NOTES HANDED OVER',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
            ),
            if (!_keypad && _noteCount > 0) ...[
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
                decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(999)),
                child: Text('$_noteCount note${_noteCount == 1 ? '' : 's'}',
                    style: ui(size: 11, weight: FontWeight.w800, color: p.primary)),
              ),
              const SizedBox(width: 6),
            ],
            _chip(
              label: _keypad ? 'Notes' : 'Keypad',
              icon: _keypad ? Icons.grid_view_rounded : Icons.dialpad,
              onTap: _toggleSurface,
            ),
          ],
        ),
        const SizedBox(height: 9),
        if (_keypad) ...[
          _readout(p),
          const SizedBox(height: 8),
        ],
        Flexible(
          child: ConstrainedBox(
            constraints: BoxConstraints(maxHeight: cap == null ? double.infinity : (_keypad ? cap * 1.35 : cap)),
            child: _keypad ? _pad(p) : _grid([for (final note in _ladder) _noteTile(p, note, _notes[note] ?? 0)], 3),
          ),
        ),
        const SizedBox(height: 9),
        Row(
          children: [
            Expanded(child: PayToolButton(icon: Icons.close_rounded, label: 'Reset', onTap: _tendered == null ? null : _reset)),
            const SizedBox(width: 8),
            Expanded(
              // Names the amount it fills in, and stays lit while it is the
              // amount received — the button is the state, not a shortcut.
              child: PayToolButton(
                icon: _exactTaken ? Icons.check_circle_rounded : Icons.done_all_rounded,
                label: _exactTaken ? 'Exact taken' : 'Exact ${Money.plain(widget.total)}',
                lit: _exactTaken,
                onTap: _toggleExact,
              ),
            ),
            for (final tool in widget.tools) ...[const SizedBox(width: 8), Expanded(child: tool)],
          ],
        ),
      ],
    );
  }

  /// What has been typed, big enough to check before charging. The pad echoes
  /// the keys as pressed — a half-typed "36." must not redraw itself as "36.00"
  /// under the thumb.
  Widget _readout(AstraPalette p) {
    final symbol = Money.symbol.trim();
    final typed = _typed.isNotEmpty;
    final shown = _exactTaken ? Money.plain(widget.total) : (typed ? _typed : Money.plain(0));
    return Container(
      height: 50,
      padding: const EdgeInsets.symmetric(horizontal: 14),
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: typed || _exactTaken ? p.primary : p.hairline, width: 1.4),
      ),
      child: Row(
        children: [
          Text('RECEIVED', style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
          const Spacer(),
          if (symbol.isNotEmpty) ...[
            Text(symbol, style: ui(size: 12, weight: FontWeight.w800, color: p.textMuted)),
            const SizedBox(width: 6),
          ],
          Flexible(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerRight,
              child: Text(shown, style: serif(size: 26, color: typed || _exactTaken ? p.ink : p.textMuted)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _chip({required String label, required IconData icon, required VoidCallback onTap}) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(999)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 12, color: p.primary),
            const SizedBox(width: 5),
            Text(label, style: ui(size: 10, weight: FontWeight.w800, color: p.primary)),
          ],
        ),
      ),
    );
  }

  /// [cells] in rows of [columns], every row sharing the height equally — the
  /// pad stretches to the panel instead of keeping a fixed aspect ratio.
  Widget _grid(List<Widget> cells, int columns) {
    return Column(
      children: [
        for (var row = 0; row * columns < cells.length; row++) ...[
          if (row > 0) const SizedBox(height: 8),
          Expanded(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                for (var i = row * columns; i < row * columns + columns; i++) ...[
                  if (i > row * columns) const SizedBox(width: 8),
                  Expanded(child: i < cells.length ? cells[i] : const SizedBox()),
                ],
              ],
            ),
          ),
        ],
      ],
    );
  }

  /// Tap the notes that came across the counter. A counted note shows how many
  /// as a solid badge on its right, and a − on its left takes one back
  /// (long-press still does too, but nobody finds a long-press).
  Widget _noteTile(AstraPalette p, double note, int count) {
    final counted = count > 0;
    return GestureDetector(
      onTap: () => _addNote(note),
      onLongPress: () => _removeNote(note),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 5),
        decoration: BoxDecoration(
          color: counted ? p.tint : p.card,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: counted ? p.primary : Colors.transparent, width: 1.5),
          boxShadow: counted ? null : context.astraTheme.softShadow,
        ),
        child: Row(
          children: [
            // The − and the badge are the same width, so the value stays
            // centred whether the note is counted or not.
            SizedBox(
              width: 26,
              child: counted
                  ? GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onTap: () => _removeNote(note),
                      child: Container(
                        width: 24,
                        height: 24,
                        decoration: BoxDecoration(
                          color: p.card,
                          shape: BoxShape.circle,
                          border: Border.all(color: p.primary.withValues(alpha: 0.4)),
                        ),
                        child: Icon(Icons.remove, size: 14, color: p.primary),
                      ),
                    )
                  : null,
            ),
            Expanded(
              child: FittedBox(
                fit: BoxFit.scaleDown,
                child: Text(Money.plain(note, decimals: 0),
                    style: ui(size: 19, weight: FontWeight.w800, color: counted ? p.primaryDark : p.ink)),
              ),
            ),
            SizedBox(
              width: 26,
              child: counted
                  ? FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Container(
                        height: 24,
                        constraints: const BoxConstraints(minWidth: 24),
                        padding: const EdgeInsets.symmetric(horizontal: 6),
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          gradient: p.primaryGradient,
                          borderRadius: BorderRadius.circular(999),
                          boxShadow: context.astraTheme.floatShadow(p.primary),
                        ),
                        child: Text('×$count',
                            style: ui(size: 12, weight: FontWeight.w800, color: Colors.white, height: 1.1)),
                      ),
                    )
                  : null,
            ),
          ],
        ),
      ),
    );
  }

  Widget _pad(AstraPalette p) {
    const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', '<'];
    return _grid([
      for (final k in keys)
        GestureDetector(
          onTap: () => _key(k),
          child: Container(
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: p.card,
              borderRadius: BorderRadius.circular(14),
              boxShadow: context.astraTheme.softShadow,
            ),
            child: k == '<'
                ? Icon(Icons.backspace_outlined, size: 18, color: AstraPalette.danger)
                : Text(k,
                    style: ui(
                      size: 19,
                      weight: FontWeight.w700,
                      // A currency with no minor unit has no use for the dot;
                      // it stays in place so the grid keeps its shape, greyed
                      // out rather than silently doing nothing.
                      color: k == '.' && Money.decimals == 0 ? p.textMuted : p.ink,
                    )),
          ),
        ),
    ], 3);
  }
}

/// One button of the row under the pay panel — Reset, Exact, the item count,
/// the note. A null [onTap] greys it out. [lit] marks one that holds something
/// (a note already written).
class PayToolButton extends StatelessWidget {
  const PayToolButton({super.key, required this.icon, required this.label, this.onTap, this.lit = false});

  final IconData icon;
  final String label;
  final VoidCallback? onTap;
  final bool lit;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: AnimatedOpacity(
        duration: const Duration(milliseconds: 150),
        opacity: onTap == null ? 0.45 : 1,
        child: Container(
          height: 38,
          padding: const EdgeInsets.symmetric(horizontal: 8),
          decoration: BoxDecoration(
            color: lit ? p.tint : p.card,
            borderRadius: BorderRadius.circular(11),
            boxShadow: lit ? null : context.astraTheme.softShadow,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 16, color: p.primary),
              const SizedBox(width: 5),
              Flexible(
                child: Text(label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 11.5, weight: FontWeight.w800, color: lit ? p.primary : p.textSecondary)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

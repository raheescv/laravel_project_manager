import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:invo/shared/utils/components/feedback_sound.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/domain/services/nfc_card_reader.dart';
import 'package:invo/features/student_card/logic/student_card_cubit/student_card_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/offline_banner.dart';

/// Tap a student card at the till. Resolves the card with the server and returns
/// the student the moment it is recognised — no confirm step; the photo check
/// happens on the ticket, where the student shows as the client before charging.
/// Null when dismissed.
///
/// [uid] is a card already read elsewhere (the Client sheet listens too), looked
/// up straight away. [onBack] adds a back arrow that closes this sheet and runs it.
Future<StudentCard?> showTapCardSheet(BuildContext context, {String? uid, VoidCallback? onBack}) =>
    showModalBottomSheet<StudentCard>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _TapCardSheet(lookup: true, initialUid: uid, onBack: onBack),
    );

/// Read a card's UID only (Link Card). Returns the normalised UID, or null.
Future<String?> showReadCardUidSheet(BuildContext context, {required String studentName}) =>
    showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _TapCardSheet(lookup: false, studentName: studentName),
    );

class _TapCardSheet extends StatefulWidget {
  const _TapCardSheet({required this.lookup, this.studentName = '', this.initialUid, this.onBack});

  /// True: look the card up and return the student. False: return the UID.
  final bool lookup;
  final String studentName;
  final String? initialUid;
  final VoidCallback? onBack;

  @override
  State<_TapCardSheet> createState() => _TapCardSheetState();
}

class _TapCardSheetState extends State<_TapCardSheet> {
  final _reader = NfcCardReader();
  late final StudentCardCubit _cubit = serviceLocator<StudentCardCubit>();
  late final TextEditingController _manual = TextEditingController();
  StreamSubscription<StudentCardState>? _sub;

  bool _readerStopped = false;
  bool _nfc = false;
  bool _checkingNfc = true;
  String? _nfcError;
  String? _uid;

  /// The sheet has popped (or is popping) with its answer. Taps still in flight
  /// must not pop again — a second pop would close the screen underneath.
  bool _done = false;

  @override
  void initState() {
    super.initState();
    _sub = _cubit.stream.listen((_) {
      if (mounted) setState(() {});
    });
    if (widget.initialUid != null) unawaited(_onUid(widget.initialUid!));
    _startNfc();
  }

  /// Listen first, ask after: a card tapped while "Starting the card reader…"
  /// is still up is read rather than lost to the availability check. Where
  /// there is no NFC the start fails quietly, and the check below switches the
  /// sheet to typed entry.
  Future<void> _startNfc() async {
    unawaited(_reader.start(
      onUid: (uid) => unawaited(_onUid(uid)),
      onError: (message) {
        if (!mounted) return;
        // An unreadable card; a reader that would not start says so quietly.
        if (!_checkingNfc && _nfc) FeedbackSound.cardRefused();
        setState(() => _nfcError = message);
      },
    ));
    final available = await _reader.isAvailable;
    if (!mounted) return;
    setState(() {
      _nfc = available;
      _checkingNfc = false;
    });
  }

  Future<void> _onUid(String raw) async {
    final uid = NfcCardReader.normalize(raw);
    if (uid.isEmpty || !mounted || _done) return;
    unawaited(HapticFeedback.mediumImpact());
    setState(() {
      _uid = uid;
      _nfcError = null;
    });
    if (!widget.lookup) {
      _done = true;
      await _stopReader();
      if (mounted) Navigator.pop(context, uid);
      return;
    }
    // While "Checking card…" is up a new tap is taken too: it supersedes the
    // card being checked (StudentCardCubit.lookup — the latest tap wins).
    _accept(await _cubit.lookup(uid));
  }

  /// A recognised card closes the sheet straight away with the student — a
  /// cashier would only ever tap "use it" next. Unknown cards stay on the sheet
  /// with the error, so the next tap can be tried without reopening it.
  void _accept(StudentCard? card) {
    if (!mounted || _done) return;
    if (card == null) {
      // The latest tap failed (a superseded one leaves no error behind).
      if (_cubit.state.errorMessage != null) FeedbackSound.cardRefused();
      return;
    }
    _done = true;
    unawaited(_stopReader());
    Navigator.pop(context, card);
  }

  /// Typed or keyboard-wedge entry. Sent as typed (not hex-normalised) so an
  /// admission number like "NR123" survives; the server normalises card numbers.
  Future<void> _onManual(String raw) async {
    final value = raw.trim();
    if (value.isEmpty || !mounted || _done) return;
    if (!widget.lookup) return _onUid(value);
    setState(() {
      _uid = value;
      _nfcError = null;
    });
    _accept(await _cubit.lookup(value));
  }

  /// Idempotent: the sheet can stop on accept, on pop and again in [dispose].
  Future<void> _stopReader() async {
    if (_readerStopped) return;
    _readerStopped = true;
    await _reader.stop();
  }

  @override
  void dispose() {
    _stopReader();
    _sub?.cancel();
    _cubit.close();
    _manual.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final state = _cubit.state;

    // Stop listening the moment the sheet is popped — however it is closed —
    // not in [dispose] after the exit animation, so a tap during the animation
    // already reaches whatever is underneath (the POS) instead of this sheet.
    return PopScope(
      onPopInvokedWithResult: (didPop, _) {
        if (didPop) unawaited(_stopReader());
      },
      child: Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
        child: Container(
          decoration: BoxDecoration(color: p.sheet, borderRadius: const BorderRadius.vertical(top: Radius.circular(30))),
          padding: EdgeInsets.fromLTRB(20, 16, 20, 24 + OfflineBanner.floatingClearance(context)),
          child: SafeArea(
            top: false,
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      if (widget.onBack != null) ...[
                        IconButton.filledTonal(
                          tooltip: 'Back',
                          icon: const Icon(Icons.arrow_back),
                          // Channel calls run in order, so this stop lands before
                          // the Client sheet starts its own session.
                          onPressed: () {
                            unawaited(_stopReader());
                            Navigator.pop(context);
                            widget.onBack!();
                          },
                        ),
                        const SizedBox(width: 10),
                      ],
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            SectionLabel(widget.lookup ? 'Student card' : 'Link card'),
                            const SizedBox(height: 4),
                            Text(widget.lookup ? 'Tap the card' : 'Tap ${widget.studentName.isEmpty ? 'the' : '${widget.studentName}\'s'} new card',
                                style: serif(size: 22, color: p.ink)),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _waiting(state),
                  const SizedBox(height: 16),
                  _manualEntry(),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _waiting(StudentCardState state) {
    final p = context.astra;
    final (IconData icon, String title, Color color) = state.isBusy
        ? (Icons.hourglass_top, 'Checking card…', p.primary)
        : state.errorMessage != null
            ? (Icons.error_outline, state.errorMessage!, AstraPalette.danger)
            : _checkingNfc
                ? (Icons.nfc, 'Starting the card reader…', p.textMuted)
                : _nfc
                    ? (Icons.nfc, _nfcError ?? 'Hold the card against the back of the device', _nfcError == null ? p.primary : AstraPalette.danger)
                    : (Icons.keyboard_alt_outlined, 'NFC is off or not available. Use a card reader or type the card number below.', p.textSecondary);

    return AstraCard(
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(shape: BoxShape.circle, color: color.withValues(alpha: 0.12)),
            child: state.isBusy
                ? Padding(padding: const EdgeInsets.all(14), child: CircularProgressIndicator(strokeWidth: 2.5, color: color))
                : Icon(icon, color: color, size: 26),
          ),
          const SizedBox(width: 12),
          Expanded(child: Text(title, style: ui(size: 13, weight: FontWeight.w700, color: color))),
        ],
      ),
    );
  }

  Widget _manualEntry() {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(widget.lookup ? 'OR ENTER THE CARD OR ADMISSION NUMBER' : 'OR ENTER THE CARD NUMBER', style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 6),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _manual,
                // USB / Bluetooth card readers type the number and press Enter.
                autofocus: !_nfc && !_checkingNfc,
                textCapitalization: TextCapitalization.characters,
                onSubmitted: _onManual,
                style: ui(size: 14, weight: FontWeight.w700, color: p.ink),
                decoration: InputDecoration(
                  hintText: _uid ?? (widget.lookup ? 'Card no. or admission no.' : 'e.g. 04A21B9C'),
                  filled: true,
                  fillColor: p.card,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                ),
              ),
            ),
            const SizedBox(width: 8),
            AstraButton(label: 'Go', expand: false, onTap: () => _onManual(_manual.text)),
          ],
        ),
      ],
    );
  }
}

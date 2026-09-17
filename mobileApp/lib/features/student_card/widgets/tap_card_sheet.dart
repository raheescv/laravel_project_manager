import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/domain/services/nfc_card_reader.dart';
import 'package:invo/features/student_card/logic/student_card_cubit/student_card_cubit.dart';
import 'package:invo/features/student_card/widgets/student_card_tile.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Tap a student card at the till. Resolves the card with the server and returns
/// the student once the cashier confirms it is the right child (photo check) —
/// or null when dismissed.
Future<StudentCard?> showTapCardSheet(BuildContext context) => showModalBottomSheet<StudentCard>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const _TapCardSheet(lookup: true),
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
  const _TapCardSheet({required this.lookup, this.studentName = ''});

  /// True: look the card up and return the student. False: return the UID.
  final bool lookup;
  final String studentName;

  @override
  State<_TapCardSheet> createState() => _TapCardSheetState();
}

class _TapCardSheetState extends State<_TapCardSheet> {
  final _reader = const NfcCardReader();
  late final StudentCardCubit _cubit = serviceLocator<StudentCardCubit>();
  late final TextEditingController _manual = TextEditingController();
  StreamSubscription<StudentCardState>? _sub;

  bool _nfc = false;
  bool _checkingNfc = true;
  String? _nfcError;
  String? _uid;

  @override
  void initState() {
    super.initState();
    _sub = _cubit.stream.listen((_) {
      if (mounted) setState(() {});
    });
    _startNfc();
  }

  Future<void> _startNfc() async {
    final available = await _reader.isAvailable;
    if (!mounted) return;
    setState(() {
      _nfc = available;
      _checkingNfc = false;
    });
    if (available) {
      await _reader.start(
        onUid: (uid) => unawaited(_onUid(uid)),
        onError: (message) {
          if (mounted) setState(() => _nfcError = message);
        },
      );
    }
  }

  Future<void> _onUid(String raw) async {
    final uid = NfcCardReader.normalize(raw);
    if (uid.isEmpty || !mounted) return;
    unawaited(HapticFeedback.mediumImpact());
    setState(() {
      _uid = uid;
      _nfcError = null;
    });
    if (!widget.lookup) {
      await _reader.stop();
      if (mounted) Navigator.pop(context, uid);
      return;
    }
    await _cubit.lookup(uid);
  }

  @override
  void dispose() {
    _reader.stop();
    _sub?.cancel();
    _cubit.close();
    _manual.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final state = _cubit.state;
    final card = state.card;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        decoration: BoxDecoration(color: p.sheet, borderRadius: const BorderRadius.vertical(top: Radius.circular(30))),
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: SafeArea(
          top: false,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SectionLabel(widget.lookup ? 'Student card' : 'Link card'),
                const SizedBox(height: 4),
                Text(widget.lookup ? 'Tap the card' : 'Tap ${widget.studentName.isEmpty ? 'the' : '${widget.studentName}\'s'} new card',
                    style: serif(size: 22, color: p.ink)),
                const SizedBox(height: 16),
                if (card != null && widget.lookup)
                  _found(card)
                else
                  _waiting(state),
                const SizedBox(height: 16),
                if (card == null || !widget.lookup) _manualEntry(),
              ],
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

  Widget _found(StudentCard card) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        AstraCard(child: StudentCardTile(card: card)),
        const SizedBox(height: 10),
        Text(
          card.overdraftLimit > 0
              ? 'Can spend ${Money.of(card.available)} (includes an overdraft of ${Money.of(card.overdraftLimit)}).'
              : 'Can spend ${Money.of(card.available)}.',
          style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary),
        ),
        if (card.preOrder != null && card.preOrder!.items.isNotEmpty) ...[
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: p.primary.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(14)),
            child: Row(
              children: [
                Icon(Icons.restaurant_menu, size: 18, color: p.primary),
                const SizedBox(width: 10),
                Expanded(
                  child: Text('Parent pre-ordered: ${card.preOrder!.summary} · ${Money.of(card.preOrder!.total)}',
                      style: ui(size: 12, weight: FontWeight.w700, color: p.ink)),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 4),
        Text('Check the photo matches the student before charging.', style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
        const SizedBox(height: 14),
        AstraButton(
          label: 'Use this card',
          icon: Icons.check,
          gold: true,
          onTap: () => Navigator.pop(context, card),
        ),
      ],
    );
  }

  Widget _manualEntry() {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('OR ENTER THE CARD NUMBER', style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 6),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _manual,
                // USB / Bluetooth card readers type the number and press Enter.
                autofocus: !_nfc && !_checkingNfc,
                textCapitalization: TextCapitalization.characters,
                onSubmitted: _onUid,
                style: ui(size: 14, weight: FontWeight.w700, color: p.ink),
                decoration: InputDecoration(
                  hintText: _uid ?? 'e.g. 04A21B9C',
                  filled: true,
                  fillColor: p.card,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                ),
              ),
            ),
            const SizedBox(width: 8),
            AstraButton(label: 'Go', expand: false, onTap: () => _onUid(_manual.text)),
          ],
        ),
      ],
    );
  }
}

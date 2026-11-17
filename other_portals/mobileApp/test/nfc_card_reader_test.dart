import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/student_card/domain/services/nfc_card_reader.dart';

/// One reader session for the whole app, many listeners: whoever started last
/// hears the tap, and stopping hands it straight back to whoever was under it —
/// with no moment where nobody is listening (when Android would show its own
/// "New tag collected" screen instead of the app).
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('the latest listener hears the tap, and hands back when it stops', () async {
    final heard = <String>[];
    final pos = NfcCardReader();
    final sheet = NfcCardReader();
    final tapSheet = NfcCardReader();

    await pos.start(onUid: (uid) => heard.add('pos:$uid'));
    await sheet.start(onUid: (uid) => heard.add('client:$uid'));
    await tapSheet.start(onUid: (uid) => heard.add('tap:$uid'));

    NfcCardReader.debugTap('A');
    await tapSheet.stop();
    NfcCardReader.debugTap('B');
    await sheet.stop();
    NfcCardReader.debugTap('C');
    await pos.stop();
    NfcCardReader.debugTap('D');

    expect(heard, ['tap:A', 'client:B', 'pos:C']);
    expect(NfcCardReader.debugListenerCount, 0);
  });

  test('stopping out of order removes only that listener', () async {
    final heard = <String>[];
    final pos = NfcCardReader();
    final sheet = NfcCardReader();

    await pos.start(onUid: (uid) => heard.add('pos:$uid'));
    await sheet.start(onUid: (uid) => heard.add('sheet:$uid'));
    // The POS leaving while a sheet is still open must not end the sheet's reads.
    await pos.stop();
    NfcCardReader.debugTap('A');
    await sheet.stop();
    // Stopping twice is harmless.
    await sheet.stop();

    expect(heard, ['sheet:A']);
    expect(NfcCardReader.debugListenerCount, 0);
  });

  test('starting again moves a listener back to the top', () async {
    final heard = <String>[];
    final pos = NfcCardReader();
    final sheet = NfcCardReader();

    await pos.start(onUid: (uid) => heard.add('pos:$uid'));
    await sheet.start(onUid: (uid) => heard.add('sheet:$uid'));
    await pos.start(onUid: (uid) => heard.add('pos:$uid'));
    NfcCardReader.debugTap('A');

    expect(heard, ['pos:A']);
    expect(NfcCardReader.debugListenerCount, 2);
    await pos.stop();
    await sheet.stop();
  });

  test('a screen-wide listener registered late still stays under an open sheet', () async {
    final heard = <String>[];
    final sheet = NfcCardReader();
    final pos = NfcCardReader();

    // The Client sheet opens first; the POS registers after its settings sync.
    await sheet.start(onUid: (uid) => heard.add('sheet:$uid'));
    await pos.start(onUid: (uid) => heard.add('pos:$uid'), underneath: true);
    NfcCardReader.debugTap('A');
    await sheet.stop();
    NfcCardReader.debugTap('B');
    await pos.stop();

    expect(heard, ['sheet:A', 'pos:B']);
  });

  test('a covered screen passes the tap down to the screen that is showing', () async {
    final heard = <String>[];
    final linkCard = NfcCardReader();
    final pos = NfcCardReader();
    var posShowing = false;

    await linkCard.start(onUid: (uid) => heard.add('link:$uid'), underneath: true);
    // Registered later and underneath — below Link Card — but covered.
    await pos.start(onUid: (uid) => heard.add('pos:$uid'), underneath: true, accepts: () => posShowing);
    await linkCard.stop();
    await linkCard.start(onUid: (uid) => heard.add('link:$uid'), accepts: () => !posShowing);

    NfcCardReader.debugTap('A');
    posShowing = true;
    NfcCardReader.debugTap('B');

    expect(heard, ['link:A', 'pos:B']);
    await linkCard.stop();
    await pos.stop();
  });
}

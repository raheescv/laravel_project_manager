import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/features/settings/screens/v3/print_settings_screen.dart';

import 'support/test_harness.dart';

/// The picker is the whole point of the direct-print work: without a way to
/// choose a printer, auto-print falls back to the OS dialog and still costs a
/// tap. These tests run on the host, where the native channel is absent — so
/// they cover exactly the "no Bluetooth/USB/built-in here" path a plain
/// Android phone without a POS printer also takes.
void main() {
  late TestHarness h;

  setUp(() async {
    h = TestHarness();
    await h.init();
  });

  tearDown(() async => h.dispose());

  /// The sheet shows an indeterminate spinner while it asks the platform what
  /// it can reach, and an indeterminate spinner never lets `pumpAndSettle`
  /// settle. Advance frames by hand instead.
  Future<void> settle(WidgetTester tester) async {
    for (var i = 0; i < 12; i++) {
      await tester.pump(const Duration(milliseconds: 100));
    }
  }

  Future<void> openPicker(WidgetTester tester) async {
    await tester.pumpWidget(h.wrap(const PrintSettingsScreen()));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Printer'));
    await settle(tester);
  }

  testWidgets('the Printer row opens the picker', (tester) async {
    await openPicker(tester);
    expect(find.text('Choose printer'), findsOneWidget);
  });

  testWidgets('offers the transports this device actually has', (tester) async {
    await openPicker(tester);
    // Network needs no native half, so it is always on offer; the dialog is
    // always the fallback. Bluetooth/USB/built-in are Android-only and this
    // host has no channel, so they must not be advertised.
    expect(find.text('Wi-Fi / LAN'), findsOneWidget);
    expect(find.text('Print dialog'), findsOneWidget);
    expect(find.text('Bluetooth'), findsNothing);
    expect(find.text('USB'), findsNothing);
  });

  testWidgets('a hand-typed IP pairs the till over the network', (tester) async {
    await openPicker(tester);
    await tester.enterText(find.byType(TextField), '192.168.1.50');
    await tester.tap(find.text('Use'));
    await settle(tester);

    final printer = h.printSettings.printer;
    expect(printer.transport, PrinterTransport.network);
    expect(printer.host, '192.168.1.50');
    expect(printer.port, 9100);
    expect(printer.isDirect, isTrue, reason: 'a LAN printer prints with no dialog');
  });

  testWidgets('the pairing survives as the stored device-local setting',
      (tester) async {
    await openPicker(tester);
    await tester.enterText(find.byType(TextField), '10.0.0.7:9101');
    await tester.tap(find.text('Use'));
    await settle(tester);

    expect(h.storage.printerTransport, 'network');
    expect(h.storage.printerUrl, '10.0.0.7:9101');
  });

  testWidgets('choosing the print dialog un-pairs the till', (tester) async {
    await h.printSettings.setPrinter(const PrinterTarget(
        transport: PrinterTransport.network, address: '192.168.1.50:9100', name: 'Till'));

    await openPicker(tester);
    await tester.tap(find.text('Print dialog'));
    await settle(tester);
    await tester.tap(find.text('Always use the print dialog'));
    await settle(tester);

    expect(h.printSettings.hasPrinter, isFalse);
    expect(h.printSettings.printsSilently, isFalse);
    expect(h.storage.printerUrl, isNull);
  });

  testWidgets('Back to New Sale only unlocks behind a direct link',
      (tester) async {
    // A system-dialog pairing still shows a printer name, but the dialog
    // always needs a tap — so there is no silent print to skip ahead from.
    await h.printSettings.setPrinter(const PrinterTarget(
        transport: PrinterTransport.system, address: 'ipp://office', name: 'Office'));
    expect(h.printSettings.hasPrinter, isTrue);
    expect(h.printSettings.printsSilently, isFalse);

    await tester.pumpWidget(h.wrap(const PrintSettingsScreen()));
    await tester.pumpAndSettle();
    expect(find.text('Needs a direct printer link'), findsOneWidget);

    await h.printSettings.setPrinter(const PrinterTarget(
        transport: PrinterTransport.network, address: '192.168.1.50:9100', name: 'Till'));
    await tester.pumpAndSettle();
    expect(find.text('Skip the invoice screen once the receipt prints'),
        findsOneWidget);
  });

  testWidgets('switching to a dialog-only printer drops the skip shortcut',
      (tester) async {
    await h.printSettings.setPrinter(const PrinterTarget(
        transport: PrinterTransport.network, address: '192.168.1.50:9100', name: 'Till'));
    await h.printSettings.setSkipInvoice(true);
    expect(h.printSettings.skipInvoice, isTrue);

    await h.printSettings.setPrinter(const PrinterTarget(
        transport: PrinterTransport.system, address: 'ipp://office', name: 'Office'));
    expect(h.printSettings.skipInvoice, isFalse,
        reason: 'nothing prints silently any more, so there is nothing to skip');
  });

  testWidgets('a pairing saved before transports existed reads as the dialog',
      (tester) async {
    // The old build stored a URL and a name with no transport key.
    await h.storage.setPrinter('', 'ipp://office', 'Office');
    final restored = PrintSettingsCubit();
    expect(restored.printer.transport, PrinterTransport.system);
    expect(restored.printer.name, 'Office');
    expect(restored.printsSilently, isFalse);
    await restored.close();
  });
}

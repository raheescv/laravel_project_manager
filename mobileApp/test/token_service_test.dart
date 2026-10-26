import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/token/domain/models/token_models.dart';
import 'package:invo/features/token/domain/services/token_service.dart';
import 'package:invo/features/token/logic/token_cubit/token_cubit.dart';
import 'package:invo/features/token/widgets/v3/token_slip_pdf.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/print_settings.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Queue tokens are numbered on the device, so these tests pin the promises the
/// kiosk makes: numbers run in order, restart every day, never repeat after a
/// failed print, and stay separate per branch and per counter.
void main() {
  late TokenService tokens;
  final morning = DateTime(2026, 10, 26, 9, 30);

  setUp(() async {
    TestWidgetsFlutterBinding.ensureInitialized();
    SharedPreferences.setMockInitialValues({});
    await serviceLocator.reset();
    serviceLocator.registerSingleton<LocalStorageService>(await LocalStorageService.create());
    tokens = TokenService();
  });

  tearDown(() => serviceLocator.reset());

  group('format', () {
    test('pads the sequence and joins tag, prefix and number', () {
      const s = TokenSettings(prefix: 'A', separator: '-', digits: 3);
      expect(s.format(7), 'A-007');
      expect(s.copyWith(deviceTag: 'K1').format(42), 'K1-A-042');
      expect(s.copyWith(prefix: '', separator: '').format(5), '005');
      expect(s.copyWith(digits: 2).format(123), 'A-123');
    });

    test('a counter prefix replaces the device prefix', () {
      const cashier = TokenCounter(id: 'c1', name: 'Cashier', prefix: 'B');
      expect(const TokenSettings(prefix: 'A').format(9, counter: cashier), 'B-009');
    });

    test('digits are clamped to a sane range', () {
      expect(const TokenSettings().copyWith(digits: 0).digits, TokenSettings.minDigits);
      expect(const TokenSettings().copyWith(digits: 40).digits, TokenSettings.maxDigits);
    });
  });

  test('issues sequential numbers from the start number', () async {
    const s = TokenSettings(startAt: 1);
    final (first, _) = await tokens.issue(3, s, now: morning);
    final (second, ledger) = await tokens.issue(3, s, now: morning);

    expect(first.displayNo, 'A-001');
    expect(second.displayNo, 'A-002');
    expect(ledger.issued, 2);
    expect(ledger.last, second);
  });

  test('the number is persisted before it is handed back', () async {
    const s = TokenSettings();
    await tokens.issue(3, s, now: morning);

    // A fresh service reads only what was stored — the print step never ran.
    final (next, _) = await TokenService().issue(3, s, now: morning);
    expect(next.sequence, 2);
  });

  test('numbering restarts on a new date', () async {
    const s = TokenSettings(startAt: 1);
    await tokens.issue(3, s, now: morning);
    await tokens.issue(3, s, now: morning);

    final tomorrow = morning.add(const Duration(days: 1));
    expect(tokens.ledger(3, now: tomorrow).issued, 0);
    final (slip, _) = await tokens.issue(3, s, now: tomorrow);
    expect(slip.sequence, 1);
  });

  test('reset starts today again from the start number', () async {
    const s = TokenSettings(startAt: 10);
    await tokens.issue(3, s, now: morning);
    await tokens.issue(3, s, now: morning);

    final ledger = await tokens.resetToday(3, now: morning);
    expect(ledger.issued, 0);
    expect(ledger.last, isNull);
    final (slip, _) = await tokens.issue(3, s, now: morning);
    expect(slip.sequence, 10);
  });

  test('raising the start number mid-day jumps ahead, lowering it never repeats', () async {
    await tokens.issue(3, const TokenSettings(startAt: 1), now: morning);
    final (jumped, _) = await tokens.issue(3, const TokenSettings(startAt: 50), now: morning);
    final (after, _) = await tokens.issue(3, const TokenSettings(startAt: 1), now: morning);

    expect(jumped.sequence, 50);
    expect(after.sequence, 51);
  });

  test('each branch and each counter keeps its own sequence', () async {
    const general = TokenCounter(id: 'g', name: 'General', prefix: 'A');
    const service = TokenCounter(id: 's', name: 'Service', prefix: 'C');
    const s = TokenSettings(counters: [general, service]);

    await tokens.issue(3, s, counter: general, now: morning);
    await tokens.issue(3, s, counter: general, now: morning);
    final (c1, ledger) = await tokens.issue(3, s, counter: service, now: morning);
    final (otherBranch, _) = await tokens.issue(4, s, counter: general, now: morning);

    expect(c1.displayNo, 'C-001');
    expect(c1.counterName, 'Service');
    expect(ledger.issued, 3);
    expect(otherBranch.displayNo, 'A-001');
  });

  test('settings round-trip through storage', () async {
    const s = TokenSettings(
      prefix: 'Q',
      digits: 4,
      deviceTag: 'K2',
      counters: [TokenCounter(id: 'x', name: 'Desk', prefix: 'D')],
      footerArabic: 'شكراً لانتظاركم',
      numberSize: TokenNumberSize.extraLarge,
      autoPrint: false,
    );
    await tokens.saveSettings(s);
    expect(TokenService().settings(), s);
  });

  test('the cubit ignores a second tap while a number is being taken', () async {
    final cubit = TokenCubit(tokens)..load(3);
    final results = await Future.wait([cubit.issue(), cubit.issue()]);

    expect(results.whereType<TokenSlip>(), hasLength(1));
    expect(cubit.state.ledger.issued, 1);
    await cubit.close();
  });

  test('the slip PDF builds at both roll widths', () async {
    final slip = TokenSlip(sequence: 42, displayNo: 'K1-A-0042', issuedAt: morning, counterName: 'Cashier');
    const s = TokenSettings(header: 'Please wait', footerEnglish: 'Thank you', footerArabic: 'شكراً لانتظاركم');
    for (final paper in PaperWidth.values) {
      final bytes = await buildTokenPdf(slip, s, paper: paper, companyName: 'Astra');
      expect(String.fromCharCodes(bytes.take(5)), '%PDF-');
    }
  });
}

import 'package:invo/features/token/domain/models/token_models.dart';

/// Queue tokens are numbered on the device — no server round-trip, so the
/// kiosk keeps issuing with no network. The ledger is scoped per tenant and
/// branch; the settings are device-wide.
abstract class TokenRepository {
  TokenSettings settings();

  Future<void> saveSettings(TokenSettings settings);

  /// Today's ledger for [branchId] — a fresh one when the stored day is over.
  TokenLedger ledger(int? branchId, {DateTime? now});

  /// Takes the next number and persists it BEFORE returning, so a crash or a
  /// failed print can never hand the same number out twice.
  Future<(TokenSlip, TokenLedger)> issue(
    int? branchId,
    TokenSettings settings, {
    TokenCounter? counter,
    DateTime? now,
  });

  /// Clears today's numbering for [branchId]; the next token starts again from
  /// [TokenSettings.startAt].
  Future<TokenLedger> resetToday(int? branchId, {DateTime? now});
}

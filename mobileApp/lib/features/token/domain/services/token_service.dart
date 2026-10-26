import 'dart:convert';

import 'package:invo/features/token/domain/models/token_models.dart';
import 'package:invo/features/token/domain/repository/token_repository.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

/// [TokenRepository] over shared prefs.
class TokenService implements TokenRepository {
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  String _scope(int? branchId) => '${branchId ?? 0}';

  @override
  TokenSettings settings() {
    final raw = _storage.tokenSettingsJson;
    if (raw == null || raw.isEmpty) return const TokenSettings();
    try {
      return TokenSettings.fromJson(Map<String, dynamic>.from(jsonDecode(raw)));
    } on FormatException catch (_) {
      // Corrupt prefs entry — start from the defaults rather than lock the screen.
      return const TokenSettings();
    }
  }

  @override
  Future<void> saveSettings(TokenSettings settings) =>
      _storage.setTokenSettingsJson(jsonEncode(settings.toJson()));

  @override
  TokenLedger ledger(int? branchId, {DateTime? now}) {
    final at = now ?? DateTime.now();
    final raw = _storage.tokenLedgerJson(_scope(branchId));
    if (raw == null || raw.isEmpty) return TokenLedger.today(at);
    try {
      return TokenLedger.fromJson(Map<String, dynamic>.from(jsonDecode(raw))).rolledTo(at);
    } on FormatException catch (_) {
      return TokenLedger.today(at);
    }
  }

  @override
  Future<(TokenSlip, TokenLedger)> issue(
    int? branchId,
    TokenSettings settings, {
    TokenCounter? counter,
    DateTime? now,
  }) async {
    final at = now ?? DateTime.now();
    final day = ledger(branchId, now: at);
    final counterId = counter?.id ?? kTokenSingleStream;
    final sequence = day.nextSequence(counterId, settings.startAt);
    final slip = TokenSlip(
      sequence: sequence,
      displayNo: settings.format(sequence, counter: counter),
      issuedAt: at,
      counterId: counterId,
      counterName: counter?.name ?? '',
    );
    final next = day.record(slip);
    await _storage.setTokenLedgerJson(_scope(branchId), jsonEncode(next.toJson()));
    return (slip, next);
  }

  @override
  Future<TokenLedger> resetToday(int? branchId, {DateTime? now}) async {
    final fresh = TokenLedger.today(now ?? DateTime.now());
    await _storage.setTokenLedgerJson(_scope(branchId), jsonEncode(fresh.toJson()));
    return fresh;
  }
}

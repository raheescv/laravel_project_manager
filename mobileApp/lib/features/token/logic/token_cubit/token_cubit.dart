import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/token/domain/models/token_models.dart';
import 'package:invo/features/token/domain/repository/token_repository.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/utils/components/app_strings.dart';

part 'token_state.dart';

/// Issues queue tokens and holds the Token settings. One instance app-wide so
/// the Token screen and Settings → Token edit the same state. Printing stays
/// with the screen, which owns the snackbars, and a print can never undo an
/// issue.
class TokenCubit extends Cubit<TokenState> {
  TokenCubit(this._repo)
      : super(TokenState(settings: _repo.settings(), ledger: _repo.ledger(null)));

  final TokenRepository _repo;

  /// Points the cubit at [branchId] and re-reads today's ledger. Called every
  /// time the Token screen opens, so a branch switch or a new day applies.
  void load(int? branchId) {
    emit(state.copyWith(
      branchId: branchId,
      settings: _repo.settings(),
      ledger: _repo.ledger(branchId),
      status: DataFetchStatus.idle,
      clearError: true,
    ));
  }

  /// Rolls the ledger over when the date has changed since it was read (the
  /// screen calls this when the app returns to the foreground).
  void refreshDay() {
    final ledger = _repo.ledger(state.branchId);
    if (ledger != state.ledger) emit(state.copyWith(ledger: ledger));
  }

  void selectCounter(String id) => emit(state.copyWith(counterId: id));

  /// Takes the next number. Returns the slip to print, or null when a take is
  /// already in flight or storage failed (the state carries the message).
  Future<TokenSlip?> issue() async {
    if (state.issuing) return null;
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final (slip, ledger) = await _repo.issue(state.branchId, state.settings, counter: state.counter);
      emit(state.copyWith(status: DataFetchStatus.success, ledger: ledger));
      return slip;
    } catch (_) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: AppStrings.somethingWentWrong));
      return null;
    }
  }

  /// Starts today's numbering again from the start number.
  Future<void> resetToday() async {
    final ledger = await _repo.resetToday(state.branchId);
    emit(state.copyWith(ledger: ledger, status: DataFetchStatus.idle));
  }

  /// Applies [settings] immediately and saves them. Settings save on every
  /// change, with no Save button.
  Future<void> updateSettings(TokenSettings settings) async {
    if (settings == state.settings) return;
    emit(state.copyWith(settings: settings));
    await _repo.saveSettings(settings);
  }
}

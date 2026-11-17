import 'dart:async';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/domain/repository/student_card_repository.dart';
import 'package:invo/features/student_card/domain/services/student_card_cache.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

part 'student_card_state.dart';

class StudentCardCubit extends Cubit<StudentCardState> {
  StudentCardCubit(this._repo) : super(const StudentCardState());

  final StudentCardRepository _repo;

  /// Bumped by every [lookup]; an answer for an older tap is dropped.
  int _lookupSeq = 0;

  /// Resolve a tapped card. Returns the student only when the card can pay —
  /// a blocked card or an inactive student comes back as an error message.
  ///
  /// The latest tap wins: a card tapped while another is still being checked
  /// supersedes it, and the older answer — whenever it lands — returns null and
  /// leaves the state alone, so it can neither be accepted nor overwrite the
  /// newer card's result.
  ///
  /// A card this device holds ([StudentCardCache]) answers at once, with no
  /// round trip; the cache confirms it with the server in the background. Any
  /// other card is asked about live, and a good answer is cached for next time.
  Future<StudentCard?> lookup(String uid) async {
    final seq = ++_lookupSeq;
    bool current() => seq == _lookupSeq && !isClosed;
    final cache = serviceLocator.isRegistered<StudentCardCache>() ? serviceLocator<StudentCardCache>() : null;
    final held = cache?.find(uid);
    if (held != null) {
      emit(state.copyWith(status: DataFetchStatus.success, card: held, clearError: true));
      unawaited(cache!.confirm(held));
      return held;
    }
    emit(state.copyWith(status: DataFetchStatus.waiting, clearCard: true, clearError: true));
    try {
      final card = await _repo.lookup(uid);
      cache?.put(card);
      if (!current()) return null;
      emit(state.copyWith(status: DataFetchStatus.success, card: card));
      return card;
    } on ApiException catch (e) {
      if (current()) emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      if (current()) emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: AppStrings.somethingWentWrong));
    }
    return null;
  }

  Future<void> search(String query) async {
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final results = await _repo.search(query.trim());
      emit(state.copyWith(status: DataFetchStatus.success, results: results));
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: AppStrings.somethingWentWrong));
    }
  }

  /// Link [uid] to the student; replaces that student's row in [StudentCardState.results].
  Future<StudentCard?> link(int accountId, String uid) async {
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final card = await _repo.link(accountId, uid);
      emit(state.copyWith(
        status: DataFetchStatus.success,
        card: card,
        results: [for (final r in state.results) r.accountId == card.accountId ? card : r],
      ));
      return card;
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: AppStrings.somethingWentWrong));
    }
    return null;
  }
}

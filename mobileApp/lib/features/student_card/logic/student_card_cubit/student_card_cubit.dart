import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/domain/repository/student_card_repository.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

part 'student_card_state.dart';

class StudentCardCubit extends Cubit<StudentCardState> {
  StudentCardCubit(this._repo) : super(const StudentCardState());

  final StudentCardRepository _repo;

  /// Resolve a tapped card. Returns the student only when the card can pay —
  /// a blocked card or an inactive student comes back as an error message.
  Future<StudentCard?> lookup(String uid) async {
    emit(state.copyWith(status: DataFetchStatus.waiting, clearCard: true, clearError: true));
    try {
      final card = await _repo.lookup(uid);
      emit(state.copyWith(status: DataFetchStatus.success, card: card));
      return card;
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: AppStrings.somethingWentWrong));
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

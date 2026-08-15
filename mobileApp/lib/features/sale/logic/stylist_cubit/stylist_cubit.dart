import 'dart:async';

import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/base/list_fetch_state.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

/// Loads the assignable stylists (active employees) for the New Sale / Edit Line
/// pickers. Fetched once and filtered client-side.
///
/// The server hands back every employee on purpose — one device serves several
/// cashiers, and the offline snapshot has to cover whoever signs in next — so
/// who may be *assigned* is decided here, against the signed-in user.
class StylistCubit extends Cubit<ListFetchState<Employee>> {
  StylistCubit() : super(const ListFetchState<Employee>()) {
    // Employees are branch-scoped — drop the cache and refetch (if it had
    // loaded) when the active branch changes so pickers show the new branch's
    // staff, not the previous branch's.
    _branchSub = serviceLocator<BranchCubit>().onBranchChanged.listen((_) {
      if (!state.loaded) return;
      unawaited(load());
    });
  }

  StreamSubscription<int>? _branchSub;

  LookupRepository get _repo => serviceLocator<LookupRepository>();

  AuthCubit get _auth => serviceLocator<AuthCubit>();

  // Read facade over `state`.
  bool get loading => state.loading;
  String? get error => state.errorMessage;

  /// The stylists the signed-in user may assign. An admin gets the whole staff
  /// list, as does a back-office ('user'-type) account; a non-admin employee
  /// sees only itself — it can't ring a ticket under a colleague's name.
  /// Applied on read rather than on fetch, so the cached list stays whole for
  /// the next user of a shared till.
  List<Employee> get all {
    final user = _auth.user;
    if (user == null || !user.isNonAdminEmployee) return state.items;
    final selfId = int.tryParse(user.id);
    return state.items.where((e) => e.id == selfId).toList();
  }

  Future<void> loadIfNeeded() async {
    if (state.loaded) return;
    await load();
  }

  Future<void> load() async {
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      emit(state.copyWith(
          status: DataFetchStatus.success, items: await _repo.employees()));
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      emit(state.copyWith(
          status: DataFetchStatus.failed, errorMessage: 'Could not load stylists.'));
    }
  }

  @override
  Future<void> close() {
    _branchSub?.cancel();
    return super.close();
  }

  List<Employee> search(String term) {
    final q = term.trim().toLowerCase();
    if (q.isEmpty) return all;
    return all
        .where((e) =>
            e.name.toLowerCase().contains(q) ||
            e.code.toLowerCase().contains(q) ||
            e.mobile.toLowerCase().contains(q))
        .toList();
  }
}

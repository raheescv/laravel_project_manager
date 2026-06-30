import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

/// Loads the assignable stylists (active employees) for the New Sale / Edit Line
/// pickers. Fetched once and filtered client-side.
class StylistCubit extends HolderCubit {
  StylistCubit();

  LookupRepository get _repo => serviceLocator<LookupRepository>();

  bool loading = false;
  String? error;
  List<Employee> _all = [];
  bool _loaded = false;

  List<Employee> get all => List.unmodifiable(_all);

  Future<void> loadIfNeeded() async {
    if (_loaded) return;
    await load();
  }

  Future<void> load() async {
    loading = true;
    error = null;
    refresh();
    try {
      _all = await _repo.employees();
      _loaded = true;
    } on ApiException catch (e) {
      error = e.message;
    } catch (e) {
      error = 'Could not load stylists.';
    }
    loading = false;
    refresh();
  }

  List<Employee> search(String term) {
    final q = term.trim().toLowerCase();
    if (q.isEmpty) return all;
    return _all
        .where((e) =>
            e.name.toLowerCase().contains(q) ||
            e.code.toLowerCase().contains(q) ||
            e.mobile.toLowerCase().contains(q))
        .toList();
  }
}

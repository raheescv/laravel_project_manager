import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/models/technician_models.dart';
import '../../domain/repository/technician_repository.dart';

/// Backs the technician dashboard: KPI counts, priority breakdown and recent
/// complaints for the signed-in technician.
class TechnicianDashboardCubit extends HolderCubit {
  TechnicianRepository get _repo => serviceLocator<TechnicianRepository>();

  bool loading = false;
  String? error;
  TechnicianDashboard? data;

  /// Bumped per load / reset — a slower, older response must not overwrite
  /// newer data (or a signed-out session's cleared state).
  int _reqId = 0;

  Future<void> load() async {
    final req = ++_reqId;
    loading = true;
    error = null;
    refresh();
    try {
      final result = await _repo.dashboard();
      if (req != _reqId) return;
      data = result;
    } on ApiException catch (e) {
      if (req != _reqId) return;
      error = e.message;
    } catch (_) {
      if (req != _reqId) return;
      error = 'Could not load your dashboard.';
    }
    loading = false;
    refresh();
  }

  /// Forget the signed-out technician's numbers.
  void reset() {
    _reqId++;
    loading = false;
    error = null;
    data = null;
    refresh();
  }
}

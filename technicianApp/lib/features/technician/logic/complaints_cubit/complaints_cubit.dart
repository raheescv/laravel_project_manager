import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/models/technician_models.dart';
import '../../domain/repository/technician_repository.dart';

/// Backs the "My Complaints" list: status filter, search, date range and
/// paginated infinite scroll. Mirrors the Sales "Bento control card" flow.
class ComplaintsCubit extends HolderCubit {
  ComplaintsCubit() {
    final now = DateTime.now();
    endDate = DateTime(now.year, now.month, now.day);
    startDate = DateTime(now.year, now.month, 1); // month-to-date default
  }

  TechnicianRepository get _repo => serviceLocator<TechnicianRepository>();

  static const int _pageSize = 15;

  bool loading = false;
  bool loadingMore = false;
  String? error;
  List<ComplaintListItem> rows = [];
  int total = 0;
  int _page = 1;
  int _lastPage = 1;
  int _reqId = 0;

  bool get hasMore => _page < _lastPage;

  // Filters
  String? status; // null = all
  String? priority; // null = all — low | medium | high | critical
  String search = '';
  String datePreset = 'month'; // today | 7d | 30d | month | all | custom
  late DateTime startDate;
  late DateTime endDate;

  bool get _rangeActive => datePreset != 'all';

  void setStatus(String? value) {
    if (status == value) return;
    status = value;
    load();
  }

  void setPriority(String? value) {
    if (priority == value) return;
    priority = value;
    load();
  }

  void setSearch(String value) {
    search = value;
    load();
  }

  void setPreset(String id) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    switch (id) {
      case 'today':
        startDate = today;
        endDate = today;
      case '7d':
        startDate = today.subtract(const Duration(days: 6));
        endDate = today;
      case '30d':
        startDate = today.subtract(const Duration(days: 29));
        endDate = today;
      case 'month':
        startDate = DateTime(now.year, now.month, 1);
        endDate = today;
      case 'all':
        break;
    }
    datePreset = id;
    load();
  }

  void setCustomRange(DateTime start, DateTime end) {
    startDate = DateTime(start.year, start.month, start.day);
    endDate = DateTime(end.year, end.month, end.day);
    datePreset = 'custom';
    load();
  }

  Future<void> load() async {
    final req = ++_reqId;
    loading = true;
    loadingMore = false;
    error = null;
    refresh();
    try {
      final data = await _fetch(1);
      if (req != _reqId) return;
      _apply(data, append: false);
      error = null;
    } on ApiException catch (e) {
      if (req == _reqId) error = e.message;
    } catch (_) {
      if (req == _reqId) error = 'Could not load your complaints.';
    }
    if (req == _reqId) {
      loading = false;
      refresh();
    }
  }

  Future<void> loadMore() async {
    if (loadingMore || loading || !hasMore) return;
    final req = _reqId;
    loadingMore = true;
    refresh();
    try {
      final data = await _fetch(_page + 1);
      if (req != _reqId) return;
      _apply(data, append: true);
    } catch (_) {
      // Keep what we have; the next scroll retries.
    }
    if (req == _reqId) {
      loadingMore = false;
      refresh();
    }
  }

  Future<Map<String, dynamic>> _fetch(int page) => _repo.complaints(
        status: status,
        priority: priority,
        search: search,
        fromDate: _rangeActive ? Dates.iso(startDate) : null,
        toDate: _rangeActive ? Dates.iso(endDate) : null,
        page: page,
        perPage: _pageSize,
      );

  void _apply(Map<String, dynamic> data, {required bool append}) {
    final list = (data['data'] as List? ?? const [])
        .map((e) => ComplaintListItem.fromJson(Map<String, dynamic>.from(e)))
        .toList();
    final pag = Map<String, dynamic>.from(data['pagination'] ?? const {});
    _page = asNum(pag['current_page'] ?? 1).toInt();
    _lastPage = asNum(pag['last_page'] ?? 1).toInt();
    total = asNum(pag['total'] ?? list.length).toInt();
    rows = append ? [...rows, ...list] : list;
  }
}

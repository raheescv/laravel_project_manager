import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/domain/repository/student_card_repository.dart';
import 'package:invo/shared/api/end_points.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

class StudentCardService implements StudentCardRepository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<StudentCard> lookup(String uid) async {
    final data = await _http.get(EndPoints.studentCard(uid));
    return StudentCard.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<List<StudentCard>> search(String query) async {
    final data = await _http.get(EndPoints.students, query: {if (query.isNotEmpty) 'search': query});
    return (data as List<dynamic>? ?? [])
        .map((e) => StudentCard.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  @override
  Future<StudentCard> link(int accountId, String uid) async {
    final data = await _http.post(EndPoints.studentCardLink(accountId), body: {'cardUid': uid});
    return StudentCard.fromJson(Map<String, dynamic>.from(data));
  }
}

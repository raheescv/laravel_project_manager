import 'package:invo/features/student_card/domain/models/student_card.dart';

abstract class StudentCardRepository {
  /// Who a tapped card belongs to. Throws [ApiException] — 404 for an unknown
  /// card, 422 for a blocked card or an inactive student.
  Future<StudentCard> lookup(String uid);

  /// Active students matching [query] (name, admission no, card, parent), to link a card.
  Future<List<StudentCard>> search(String query);

  /// Link [uid] to the student (or replace a lost card).
  Future<StudentCard> link(int accountId, String uid);
}

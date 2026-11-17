import 'package:equatable/equatable.dart';
import 'package:invo/features/student_card/domain/models/card_pre_order.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';

/// A student as QLOUD POS sees them after a card tap — mirrors the summary from
/// `GET /students/card/{uid}` (App\Actions\Student\Card\FindByUidAction).
///
/// [balance] is the student account's ledger balance; [available] already adds
/// the school's overdraft limit, so it is what the card can spend right now.
class StudentCard extends Equatable {
  const StudentCard({
    required this.accountId,
    required this.name,
    this.imageUrl = '',
    this.admissionNo = '',
    this.grade = '',
    this.section = '',
    this.status = 'active',
    this.cardUid = '',
    this.cardStatus = 'active',
    this.balance = 0,
    this.overdraftLimit = 0,
    this.available = 0,
    this.cardMethodId,
    this.preOrder,
  });

  factory StudentCard.fromJson(Map<String, dynamic> j) => StudentCard(
        accountId: asNum(j['account_id']).toInt(),
        name: asStr(j['name']),
        imageUrl: asStr(j['image_url']),
        admissionNo: asStr(j['admission_no']),
        grade: asStr(j['grade']),
        section: asStr(j['section']),
        status: asStr(j['status']).isEmpty ? 'active' : asStr(j['status']),
        cardUid: asStr(j['card_uid']),
        cardStatus: asStr(j['card_status']).isEmpty ? 'active' : asStr(j['card_status']),
        balance: asNum(j['balance']).toDouble(),
        overdraftLimit: asNum(j['overdraft_limit']).toDouble(),
        available: asNum(j['available']).toDouble(),
        cardMethodId: j['card_method_id'] == null ? null : asNum(j['card_method_id']).toInt(),
        preOrder: j['pre_order'] is Map ? CardPreOrder.fromJson(Map<String, dynamic>.from(j['pre_order'] as Map)) : null,
      );

  final int accountId;
  final String name;
  final String imageUrl;
  final String admissionNo;
  final String grade;
  final String section;
  final String status;
  final String cardUid;
  final String cardStatus;
  final double balance;
  final double overdraftLimit;
  final double available;

  /// The locked "Student Card" payment method, for a card + cash split.
  final int? cardMethodId;

  /// Today's canteen pre-order from the parent portal, when there is one to hand over.
  final CardPreOrder? preOrder;

  bool get isBlocked => cardStatus == 'blocked';
  bool get hasCard => cardUid.isNotEmpty;

  /// "Grade 5 - B", or the admission number when no class is set.
  String get classLabel {
    final c = [grade, section].where((e) => e.isNotEmpty).join(' - ');
    return c.isEmpty ? admissionNo : c;
  }

  @override
  List<Object?> get props => [accountId, name, imageUrl, admissionNo, grade, section, status, cardUid, cardStatus, balance, overdraftLimit, available, cardMethodId, preOrder];
}

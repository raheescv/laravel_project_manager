import 'package:equatable/equatable.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';

/// An Office Bill printed offline whose number the server could not keep —
/// another sale of that biller already held it, or it synced onto a different
/// business day. The biller's slip says [printed]; the sale is [billNo] now, so
/// the slip needs reprinting before the day is counted.
class RenumberedBill extends Equatable {
  const RenumberedBill({
    required this.saleId,
    required this.provisionalRef,
    required this.printed,
    required this.billNo,
    required this.biller,
  });

  final String saleId;
  final String provisionalRef;
  final String printed;
  final String billNo;
  final String biller;

  factory RenumberedBill.fromJson(Map<String, dynamic> j) => RenumberedBill(
        saleId: asStr(j['sale_id']),
        provisionalRef: asStr(j['provisional_ref']),
        printed: asStr(j['printed']),
        billNo: asStr(j['bill_no']),
        biller: asStr(j['biller']),
      );

  Map<String, dynamic> toJson() => {
        'sale_id': saleId,
        'provisional_ref': provisionalRef,
        'printed': printed,
        'bill_no': billNo,
        'biller': biller,
      };

  @override
  List<Object?> get props => [saleId, provisionalRef, printed, billNo, biller];
}

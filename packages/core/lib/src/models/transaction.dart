import 'package:freezed_annotation/freezed_annotation.dart';

part 'transaction.freezed.dart';
part 'transaction.g.dart';

@freezed
abstract class Transaction with _$Transaction {
  const factory Transaction({
    required String id,
    @JsonKey(name: 'payment_reference_id') required String paymentReferenceId,
    @JsonKey(name: 'shift_id') required String shiftId,
    required int amount,
    required String currency,
    @JsonKey(name: 'collected_at') required DateTime collectedAt,
    required String status,
  }) = _Transaction;

  factory Transaction.fromJson(Map<String, dynamic> json) =>
      _$TransactionFromJson(json);
}

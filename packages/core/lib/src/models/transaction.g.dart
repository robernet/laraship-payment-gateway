// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transaction.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_Transaction _$TransactionFromJson(Map<String, dynamic> json) => _Transaction(
  id: json['id'] as String,
  paymentReferenceId: json['payment_reference_id'] as String,
  shiftId: json['shift_id'] as String,
  amount: (json['amount'] as num).toInt(),
  currency: json['currency'] as String,
  collectedAt: DateTime.parse(json['collected_at'] as String),
  status: json['status'] as String,
);

Map<String, dynamic> _$TransactionToJson(_Transaction instance) =>
    <String, dynamic>{
      'id': instance.id,
      'payment_reference_id': instance.paymentReferenceId,
      'shift_id': instance.shiftId,
      'amount': instance.amount,
      'currency': instance.currency,
      'collected_at': instance.collectedAt.toIso8601String(),
      'status': instance.status,
    };

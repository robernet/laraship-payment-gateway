// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shift.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_Shift _$ShiftFromJson(Map<String, dynamic> json) => _Shift(
  id: json['id'] as String,
  storeId: json['store_id'] as String,
  operatorId: json['operator_id'] as String,
  openedAt: DateTime.parse(json['opened_at'] as String),
  closedAt: json['closed_at'] == null
      ? null
      : DateTime.parse(json['closed_at'] as String),
  countedAmountMinor: (json['counted_amount_minor'] as num?)?.toInt(),
  discrepancyMinor: (json['discrepancy_minor'] as num?)?.toInt(),
);

Map<String, dynamic> _$ShiftToJson(_Shift instance) => <String, dynamic>{
  'id': instance.id,
  'store_id': instance.storeId,
  'operator_id': instance.operatorId,
  'opened_at': instance.openedAt.toIso8601String(),
  'closed_at': instance.closedAt?.toIso8601String(),
  'counted_amount_minor': instance.countedAmountMinor,
  'discrepancy_minor': instance.discrepancyMinor,
};

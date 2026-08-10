import 'package:freezed_annotation/freezed_annotation.dart';

part 'shift.freezed.dart';
part 'shift.g.dart';

@freezed
abstract class Shift with _$Shift {
  const factory Shift({
    required String id,
    @JsonKey(name: 'branch_id') required String branchId,
    @JsonKey(name: 'store_id') required String storeId,
    @JsonKey(name: 'operator_id') String? operatorId,
    // Mirrors the API contract's response shape; not consumed by this app yet.
    @JsonKey(name: 'pos_id') String? posId,
    @JsonKey(name: 'opened_at') required DateTime openedAt,
    @JsonKey(name: 'closed_at') DateTime? closedAt,
    @JsonKey(name: 'counted_amount_minor') int? countedAmountMinor,
    @JsonKey(name: 'discrepancy_minor') int? discrepancyMinor,
  }) = _Shift;

  factory Shift.fromJson(Map<String, dynamic> json) => _$ShiftFromJson(json);
}

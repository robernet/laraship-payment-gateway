// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'shift.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$Shift {

 String get id;@JsonKey(name: 'branch_id') String get branchId;@JsonKey(name: 'store_id') String get storeId;@JsonKey(name: 'operator_id') String? get operatorId;@JsonKey(name: 'pos_id') String? get posId;@JsonKey(name: 'opened_at') DateTime get openedAt;@JsonKey(name: 'closed_at') DateTime? get closedAt;@JsonKey(name: 'counted_amount_minor') int? get countedAmountMinor;@JsonKey(name: 'discrepancy_minor') int? get discrepancyMinor;
/// Create a copy of Shift
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$ShiftCopyWith<Shift> get copyWith => _$ShiftCopyWithImpl<Shift>(this as Shift, _$identity);

  /// Serializes this Shift to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is Shift&&(identical(other.id, id) || other.id == id)&&(identical(other.branchId, branchId) || other.branchId == branchId)&&(identical(other.storeId, storeId) || other.storeId == storeId)&&(identical(other.operatorId, operatorId) || other.operatorId == operatorId)&&(identical(other.posId, posId) || other.posId == posId)&&(identical(other.openedAt, openedAt) || other.openedAt == openedAt)&&(identical(other.closedAt, closedAt) || other.closedAt == closedAt)&&(identical(other.countedAmountMinor, countedAmountMinor) || other.countedAmountMinor == countedAmountMinor)&&(identical(other.discrepancyMinor, discrepancyMinor) || other.discrepancyMinor == discrepancyMinor));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,branchId,storeId,operatorId,posId,openedAt,closedAt,countedAmountMinor,discrepancyMinor);

@override
String toString() {
  return 'Shift(id: $id, branchId: $branchId, storeId: $storeId, operatorId: $operatorId, posId: $posId, openedAt: $openedAt, closedAt: $closedAt, countedAmountMinor: $countedAmountMinor, discrepancyMinor: $discrepancyMinor)';
}


}

/// @nodoc
abstract mixin class $ShiftCopyWith<$Res>  {
  factory $ShiftCopyWith(Shift value, $Res Function(Shift) _then) = _$ShiftCopyWithImpl;
@useResult
$Res call({
 String id,@JsonKey(name: 'branch_id') String branchId,@JsonKey(name: 'store_id') String storeId,@JsonKey(name: 'operator_id') String? operatorId,@JsonKey(name: 'pos_id') String? posId,@JsonKey(name: 'opened_at') DateTime openedAt,@JsonKey(name: 'closed_at') DateTime? closedAt,@JsonKey(name: 'counted_amount_minor') int? countedAmountMinor,@JsonKey(name: 'discrepancy_minor') int? discrepancyMinor
});




}
/// @nodoc
class _$ShiftCopyWithImpl<$Res>
    implements $ShiftCopyWith<$Res> {
  _$ShiftCopyWithImpl(this._self, this._then);

  final Shift _self;
  final $Res Function(Shift) _then;

/// Create a copy of Shift
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? branchId = null,Object? storeId = null,Object? operatorId = freezed,Object? posId = freezed,Object? openedAt = null,Object? closedAt = freezed,Object? countedAmountMinor = freezed,Object? discrepancyMinor = freezed,}) {
  return _then(_self.copyWith(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,branchId: null == branchId ? _self.branchId : branchId // ignore: cast_nullable_to_non_nullable
as String,storeId: null == storeId ? _self.storeId : storeId // ignore: cast_nullable_to_non_nullable
as String,operatorId: freezed == operatorId ? _self.operatorId : operatorId // ignore: cast_nullable_to_non_nullable
as String?,posId: freezed == posId ? _self.posId : posId // ignore: cast_nullable_to_non_nullable
as String?,openedAt: null == openedAt ? _self.openedAt : openedAt // ignore: cast_nullable_to_non_nullable
as DateTime,closedAt: freezed == closedAt ? _self.closedAt : closedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,countedAmountMinor: freezed == countedAmountMinor ? _self.countedAmountMinor : countedAmountMinor // ignore: cast_nullable_to_non_nullable
as int?,discrepancyMinor: freezed == discrepancyMinor ? _self.discrepancyMinor : discrepancyMinor // ignore: cast_nullable_to_non_nullable
as int?,
  ));
}

}


/// Adds pattern-matching-related methods to [Shift].
extension ShiftPatterns on Shift {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _Shift value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _Shift() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _Shift value)  $default,){
final _that = this;
switch (_that) {
case _Shift():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _Shift value)?  $default,){
final _that = this;
switch (_that) {
case _Shift() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String id, @JsonKey(name: 'branch_id')  String branchId, @JsonKey(name: 'store_id')  String storeId, @JsonKey(name: 'operator_id')  String? operatorId, @JsonKey(name: 'pos_id')  String? posId, @JsonKey(name: 'opened_at')  DateTime openedAt, @JsonKey(name: 'closed_at')  DateTime? closedAt, @JsonKey(name: 'counted_amount_minor')  int? countedAmountMinor, @JsonKey(name: 'discrepancy_minor')  int? discrepancyMinor)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _Shift() when $default != null:
return $default(_that.id,_that.branchId,_that.storeId,_that.operatorId,_that.posId,_that.openedAt,_that.closedAt,_that.countedAmountMinor,_that.discrepancyMinor);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String id, @JsonKey(name: 'branch_id')  String branchId, @JsonKey(name: 'store_id')  String storeId, @JsonKey(name: 'operator_id')  String? operatorId, @JsonKey(name: 'pos_id')  String? posId, @JsonKey(name: 'opened_at')  DateTime openedAt, @JsonKey(name: 'closed_at')  DateTime? closedAt, @JsonKey(name: 'counted_amount_minor')  int? countedAmountMinor, @JsonKey(name: 'discrepancy_minor')  int? discrepancyMinor)  $default,) {final _that = this;
switch (_that) {
case _Shift():
return $default(_that.id,_that.branchId,_that.storeId,_that.operatorId,_that.posId,_that.openedAt,_that.closedAt,_that.countedAmountMinor,_that.discrepancyMinor);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String id, @JsonKey(name: 'branch_id')  String branchId, @JsonKey(name: 'store_id')  String storeId, @JsonKey(name: 'operator_id')  String? operatorId, @JsonKey(name: 'pos_id')  String? posId, @JsonKey(name: 'opened_at')  DateTime openedAt, @JsonKey(name: 'closed_at')  DateTime? closedAt, @JsonKey(name: 'counted_amount_minor')  int? countedAmountMinor, @JsonKey(name: 'discrepancy_minor')  int? discrepancyMinor)?  $default,) {final _that = this;
switch (_that) {
case _Shift() when $default != null:
return $default(_that.id,_that.branchId,_that.storeId,_that.operatorId,_that.posId,_that.openedAt,_that.closedAt,_that.countedAmountMinor,_that.discrepancyMinor);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _Shift implements Shift {
  const _Shift({required this.id, @JsonKey(name: 'branch_id') required this.branchId, @JsonKey(name: 'store_id') required this.storeId, @JsonKey(name: 'operator_id') this.operatorId, @JsonKey(name: 'pos_id') this.posId, @JsonKey(name: 'opened_at') required this.openedAt, @JsonKey(name: 'closed_at') this.closedAt, @JsonKey(name: 'counted_amount_minor') this.countedAmountMinor, @JsonKey(name: 'discrepancy_minor') this.discrepancyMinor});
  factory _Shift.fromJson(Map<String, dynamic> json) => _$ShiftFromJson(json);

@override final  String id;
@override@JsonKey(name: 'branch_id') final  String branchId;
@override@JsonKey(name: 'store_id') final  String storeId;
@override@JsonKey(name: 'operator_id') final  String? operatorId;
@override@JsonKey(name: 'pos_id') final  String? posId;
@override@JsonKey(name: 'opened_at') final  DateTime openedAt;
@override@JsonKey(name: 'closed_at') final  DateTime? closedAt;
@override@JsonKey(name: 'counted_amount_minor') final  int? countedAmountMinor;
@override@JsonKey(name: 'discrepancy_minor') final  int? discrepancyMinor;

/// Create a copy of Shift
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$ShiftCopyWith<_Shift> get copyWith => __$ShiftCopyWithImpl<_Shift>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$ShiftToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _Shift&&(identical(other.id, id) || other.id == id)&&(identical(other.branchId, branchId) || other.branchId == branchId)&&(identical(other.storeId, storeId) || other.storeId == storeId)&&(identical(other.operatorId, operatorId) || other.operatorId == operatorId)&&(identical(other.posId, posId) || other.posId == posId)&&(identical(other.openedAt, openedAt) || other.openedAt == openedAt)&&(identical(other.closedAt, closedAt) || other.closedAt == closedAt)&&(identical(other.countedAmountMinor, countedAmountMinor) || other.countedAmountMinor == countedAmountMinor)&&(identical(other.discrepancyMinor, discrepancyMinor) || other.discrepancyMinor == discrepancyMinor));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,branchId,storeId,operatorId,posId,openedAt,closedAt,countedAmountMinor,discrepancyMinor);

@override
String toString() {
  return 'Shift(id: $id, branchId: $branchId, storeId: $storeId, operatorId: $operatorId, posId: $posId, openedAt: $openedAt, closedAt: $closedAt, countedAmountMinor: $countedAmountMinor, discrepancyMinor: $discrepancyMinor)';
}


}

/// @nodoc
abstract mixin class _$ShiftCopyWith<$Res> implements $ShiftCopyWith<$Res> {
  factory _$ShiftCopyWith(_Shift value, $Res Function(_Shift) _then) = __$ShiftCopyWithImpl;
@override @useResult
$Res call({
 String id,@JsonKey(name: 'branch_id') String branchId,@JsonKey(name: 'store_id') String storeId,@JsonKey(name: 'operator_id') String? operatorId,@JsonKey(name: 'pos_id') String? posId,@JsonKey(name: 'opened_at') DateTime openedAt,@JsonKey(name: 'closed_at') DateTime? closedAt,@JsonKey(name: 'counted_amount_minor') int? countedAmountMinor,@JsonKey(name: 'discrepancy_minor') int? discrepancyMinor
});




}
/// @nodoc
class __$ShiftCopyWithImpl<$Res>
    implements _$ShiftCopyWith<$Res> {
  __$ShiftCopyWithImpl(this._self, this._then);

  final _Shift _self;
  final $Res Function(_Shift) _then;

/// Create a copy of Shift
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? branchId = null,Object? storeId = null,Object? operatorId = freezed,Object? posId = freezed,Object? openedAt = null,Object? closedAt = freezed,Object? countedAmountMinor = freezed,Object? discrepancyMinor = freezed,}) {
  return _then(_Shift(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,branchId: null == branchId ? _self.branchId : branchId // ignore: cast_nullable_to_non_nullable
as String,storeId: null == storeId ? _self.storeId : storeId // ignore: cast_nullable_to_non_nullable
as String,operatorId: freezed == operatorId ? _self.operatorId : operatorId // ignore: cast_nullable_to_non_nullable
as String?,posId: freezed == posId ? _self.posId : posId // ignore: cast_nullable_to_non_nullable
as String?,openedAt: null == openedAt ? _self.openedAt : openedAt // ignore: cast_nullable_to_non_nullable
as DateTime,closedAt: freezed == closedAt ? _self.closedAt : closedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,countedAmountMinor: freezed == countedAmountMinor ? _self.countedAmountMinor : countedAmountMinor // ignore: cast_nullable_to_non_nullable
as int?,discrepancyMinor: freezed == discrepancyMinor ? _self.discrepancyMinor : discrepancyMinor // ignore: cast_nullable_to_non_nullable
as int?,
  ));
}


}

// dart format on

// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'payment_reference.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$PaymentReference {

 String get id; String get reference;@JsonKey(name: 'issuer_id') String get issuerId;@JsonKey(name: 'invoice_id') String get invoiceId;@JsonKey(name: 'integration_mode') String get integrationMode; String get status; int get amount; String get currency;@JsonKey(name: 'due_date') DateTime get dueDate; String get folio;@JsonKey(name: 'barcode_url') String get barcodeUrl;@JsonKey(name: 'pay_format_url') String get payFormatUrl;@JsonKey(name: 'pay_td_url') String? get payTdUrl;@JsonKey(name: 'autopay_enabled') bool get autopayEnabled;@JsonKey(name: 'autopay_payment_number') int? get autopayPaymentNumber;@JsonKey(name: 'autopay_frequency_days') int? get autopayFrequencyDays;
/// Create a copy of PaymentReference
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$PaymentReferenceCopyWith<PaymentReference> get copyWith => _$PaymentReferenceCopyWithImpl<PaymentReference>(this as PaymentReference, _$identity);

  /// Serializes this PaymentReference to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is PaymentReference&&(identical(other.id, id) || other.id == id)&&(identical(other.reference, reference) || other.reference == reference)&&(identical(other.issuerId, issuerId) || other.issuerId == issuerId)&&(identical(other.invoiceId, invoiceId) || other.invoiceId == invoiceId)&&(identical(other.integrationMode, integrationMode) || other.integrationMode == integrationMode)&&(identical(other.status, status) || other.status == status)&&(identical(other.amount, amount) || other.amount == amount)&&(identical(other.currency, currency) || other.currency == currency)&&(identical(other.dueDate, dueDate) || other.dueDate == dueDate)&&(identical(other.folio, folio) || other.folio == folio)&&(identical(other.barcodeUrl, barcodeUrl) || other.barcodeUrl == barcodeUrl)&&(identical(other.payFormatUrl, payFormatUrl) || other.payFormatUrl == payFormatUrl)&&(identical(other.payTdUrl, payTdUrl) || other.payTdUrl == payTdUrl)&&(identical(other.autopayEnabled, autopayEnabled) || other.autopayEnabled == autopayEnabled)&&(identical(other.autopayPaymentNumber, autopayPaymentNumber) || other.autopayPaymentNumber == autopayPaymentNumber)&&(identical(other.autopayFrequencyDays, autopayFrequencyDays) || other.autopayFrequencyDays == autopayFrequencyDays));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,reference,issuerId,invoiceId,integrationMode,status,amount,currency,dueDate,folio,barcodeUrl,payFormatUrl,payTdUrl,autopayEnabled,autopayPaymentNumber,autopayFrequencyDays);

@override
String toString() {
  return 'PaymentReference(id: $id, reference: $reference, issuerId: $issuerId, invoiceId: $invoiceId, integrationMode: $integrationMode, status: $status, amount: $amount, currency: $currency, dueDate: $dueDate, folio: $folio, barcodeUrl: $barcodeUrl, payFormatUrl: $payFormatUrl, payTdUrl: $payTdUrl, autopayEnabled: $autopayEnabled, autopayPaymentNumber: $autopayPaymentNumber, autopayFrequencyDays: $autopayFrequencyDays)';
}


}

/// @nodoc
abstract mixin class $PaymentReferenceCopyWith<$Res>  {
  factory $PaymentReferenceCopyWith(PaymentReference value, $Res Function(PaymentReference) _then) = _$PaymentReferenceCopyWithImpl;
@useResult
$Res call({
 String id, String reference,@JsonKey(name: 'issuer_id') String issuerId,@JsonKey(name: 'invoice_id') String invoiceId,@JsonKey(name: 'integration_mode') String integrationMode, String status, int amount, String currency,@JsonKey(name: 'due_date') DateTime dueDate, String folio,@JsonKey(name: 'barcode_url') String barcodeUrl,@JsonKey(name: 'pay_format_url') String payFormatUrl,@JsonKey(name: 'pay_td_url') String? payTdUrl,@JsonKey(name: 'autopay_enabled') bool autopayEnabled,@JsonKey(name: 'autopay_payment_number') int? autopayPaymentNumber,@JsonKey(name: 'autopay_frequency_days') int? autopayFrequencyDays
});




}
/// @nodoc
class _$PaymentReferenceCopyWithImpl<$Res>
    implements $PaymentReferenceCopyWith<$Res> {
  _$PaymentReferenceCopyWithImpl(this._self, this._then);

  final PaymentReference _self;
  final $Res Function(PaymentReference) _then;

/// Create a copy of PaymentReference
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? reference = null,Object? issuerId = null,Object? invoiceId = null,Object? integrationMode = null,Object? status = null,Object? amount = null,Object? currency = null,Object? dueDate = null,Object? folio = null,Object? barcodeUrl = null,Object? payFormatUrl = null,Object? payTdUrl = freezed,Object? autopayEnabled = null,Object? autopayPaymentNumber = freezed,Object? autopayFrequencyDays = freezed,}) {
  return _then(_self.copyWith(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,reference: null == reference ? _self.reference : reference // ignore: cast_nullable_to_non_nullable
as String,issuerId: null == issuerId ? _self.issuerId : issuerId // ignore: cast_nullable_to_non_nullable
as String,invoiceId: null == invoiceId ? _self.invoiceId : invoiceId // ignore: cast_nullable_to_non_nullable
as String,integrationMode: null == integrationMode ? _self.integrationMode : integrationMode // ignore: cast_nullable_to_non_nullable
as String,status: null == status ? _self.status : status // ignore: cast_nullable_to_non_nullable
as String,amount: null == amount ? _self.amount : amount // ignore: cast_nullable_to_non_nullable
as int,currency: null == currency ? _self.currency : currency // ignore: cast_nullable_to_non_nullable
as String,dueDate: null == dueDate ? _self.dueDate : dueDate // ignore: cast_nullable_to_non_nullable
as DateTime,folio: null == folio ? _self.folio : folio // ignore: cast_nullable_to_non_nullable
as String,barcodeUrl: null == barcodeUrl ? _self.barcodeUrl : barcodeUrl // ignore: cast_nullable_to_non_nullable
as String,payFormatUrl: null == payFormatUrl ? _self.payFormatUrl : payFormatUrl // ignore: cast_nullable_to_non_nullable
as String,payTdUrl: freezed == payTdUrl ? _self.payTdUrl : payTdUrl // ignore: cast_nullable_to_non_nullable
as String?,autopayEnabled: null == autopayEnabled ? _self.autopayEnabled : autopayEnabled // ignore: cast_nullable_to_non_nullable
as bool,autopayPaymentNumber: freezed == autopayPaymentNumber ? _self.autopayPaymentNumber : autopayPaymentNumber // ignore: cast_nullable_to_non_nullable
as int?,autopayFrequencyDays: freezed == autopayFrequencyDays ? _self.autopayFrequencyDays : autopayFrequencyDays // ignore: cast_nullable_to_non_nullable
as int?,
  ));
}

}


/// Adds pattern-matching-related methods to [PaymentReference].
extension PaymentReferencePatterns on PaymentReference {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _PaymentReference value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _PaymentReference() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _PaymentReference value)  $default,){
final _that = this;
switch (_that) {
case _PaymentReference():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _PaymentReference value)?  $default,){
final _that = this;
switch (_that) {
case _PaymentReference() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String id,  String reference, @JsonKey(name: 'issuer_id')  String issuerId, @JsonKey(name: 'invoice_id')  String invoiceId, @JsonKey(name: 'integration_mode')  String integrationMode,  String status,  int amount,  String currency, @JsonKey(name: 'due_date')  DateTime dueDate,  String folio, @JsonKey(name: 'barcode_url')  String barcodeUrl, @JsonKey(name: 'pay_format_url')  String payFormatUrl, @JsonKey(name: 'pay_td_url')  String? payTdUrl, @JsonKey(name: 'autopay_enabled')  bool autopayEnabled, @JsonKey(name: 'autopay_payment_number')  int? autopayPaymentNumber, @JsonKey(name: 'autopay_frequency_days')  int? autopayFrequencyDays)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _PaymentReference() when $default != null:
return $default(_that.id,_that.reference,_that.issuerId,_that.invoiceId,_that.integrationMode,_that.status,_that.amount,_that.currency,_that.dueDate,_that.folio,_that.barcodeUrl,_that.payFormatUrl,_that.payTdUrl,_that.autopayEnabled,_that.autopayPaymentNumber,_that.autopayFrequencyDays);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String id,  String reference, @JsonKey(name: 'issuer_id')  String issuerId, @JsonKey(name: 'invoice_id')  String invoiceId, @JsonKey(name: 'integration_mode')  String integrationMode,  String status,  int amount,  String currency, @JsonKey(name: 'due_date')  DateTime dueDate,  String folio, @JsonKey(name: 'barcode_url')  String barcodeUrl, @JsonKey(name: 'pay_format_url')  String payFormatUrl, @JsonKey(name: 'pay_td_url')  String? payTdUrl, @JsonKey(name: 'autopay_enabled')  bool autopayEnabled, @JsonKey(name: 'autopay_payment_number')  int? autopayPaymentNumber, @JsonKey(name: 'autopay_frequency_days')  int? autopayFrequencyDays)  $default,) {final _that = this;
switch (_that) {
case _PaymentReference():
return $default(_that.id,_that.reference,_that.issuerId,_that.invoiceId,_that.integrationMode,_that.status,_that.amount,_that.currency,_that.dueDate,_that.folio,_that.barcodeUrl,_that.payFormatUrl,_that.payTdUrl,_that.autopayEnabled,_that.autopayPaymentNumber,_that.autopayFrequencyDays);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String id,  String reference, @JsonKey(name: 'issuer_id')  String issuerId, @JsonKey(name: 'invoice_id')  String invoiceId, @JsonKey(name: 'integration_mode')  String integrationMode,  String status,  int amount,  String currency, @JsonKey(name: 'due_date')  DateTime dueDate,  String folio, @JsonKey(name: 'barcode_url')  String barcodeUrl, @JsonKey(name: 'pay_format_url')  String payFormatUrl, @JsonKey(name: 'pay_td_url')  String? payTdUrl, @JsonKey(name: 'autopay_enabled')  bool autopayEnabled, @JsonKey(name: 'autopay_payment_number')  int? autopayPaymentNumber, @JsonKey(name: 'autopay_frequency_days')  int? autopayFrequencyDays)?  $default,) {final _that = this;
switch (_that) {
case _PaymentReference() when $default != null:
return $default(_that.id,_that.reference,_that.issuerId,_that.invoiceId,_that.integrationMode,_that.status,_that.amount,_that.currency,_that.dueDate,_that.folio,_that.barcodeUrl,_that.payFormatUrl,_that.payTdUrl,_that.autopayEnabled,_that.autopayPaymentNumber,_that.autopayFrequencyDays);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _PaymentReference implements PaymentReference {
  const _PaymentReference({required this.id, required this.reference, @JsonKey(name: 'issuer_id') required this.issuerId, @JsonKey(name: 'invoice_id') required this.invoiceId, @JsonKey(name: 'integration_mode') required this.integrationMode, required this.status, required this.amount, required this.currency, @JsonKey(name: 'due_date') required this.dueDate, required this.folio, @JsonKey(name: 'barcode_url') required this.barcodeUrl, @JsonKey(name: 'pay_format_url') required this.payFormatUrl, @JsonKey(name: 'pay_td_url') this.payTdUrl, @JsonKey(name: 'autopay_enabled') required this.autopayEnabled, @JsonKey(name: 'autopay_payment_number') this.autopayPaymentNumber, @JsonKey(name: 'autopay_frequency_days') this.autopayFrequencyDays});
  factory _PaymentReference.fromJson(Map<String, dynamic> json) => _$PaymentReferenceFromJson(json);

@override final  String id;
@override final  String reference;
@override@JsonKey(name: 'issuer_id') final  String issuerId;
@override@JsonKey(name: 'invoice_id') final  String invoiceId;
@override@JsonKey(name: 'integration_mode') final  String integrationMode;
@override final  String status;
@override final  int amount;
@override final  String currency;
@override@JsonKey(name: 'due_date') final  DateTime dueDate;
@override final  String folio;
@override@JsonKey(name: 'barcode_url') final  String barcodeUrl;
@override@JsonKey(name: 'pay_format_url') final  String payFormatUrl;
@override@JsonKey(name: 'pay_td_url') final  String? payTdUrl;
@override@JsonKey(name: 'autopay_enabled') final  bool autopayEnabled;
@override@JsonKey(name: 'autopay_payment_number') final  int? autopayPaymentNumber;
@override@JsonKey(name: 'autopay_frequency_days') final  int? autopayFrequencyDays;

/// Create a copy of PaymentReference
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$PaymentReferenceCopyWith<_PaymentReference> get copyWith => __$PaymentReferenceCopyWithImpl<_PaymentReference>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$PaymentReferenceToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _PaymentReference&&(identical(other.id, id) || other.id == id)&&(identical(other.reference, reference) || other.reference == reference)&&(identical(other.issuerId, issuerId) || other.issuerId == issuerId)&&(identical(other.invoiceId, invoiceId) || other.invoiceId == invoiceId)&&(identical(other.integrationMode, integrationMode) || other.integrationMode == integrationMode)&&(identical(other.status, status) || other.status == status)&&(identical(other.amount, amount) || other.amount == amount)&&(identical(other.currency, currency) || other.currency == currency)&&(identical(other.dueDate, dueDate) || other.dueDate == dueDate)&&(identical(other.folio, folio) || other.folio == folio)&&(identical(other.barcodeUrl, barcodeUrl) || other.barcodeUrl == barcodeUrl)&&(identical(other.payFormatUrl, payFormatUrl) || other.payFormatUrl == payFormatUrl)&&(identical(other.payTdUrl, payTdUrl) || other.payTdUrl == payTdUrl)&&(identical(other.autopayEnabled, autopayEnabled) || other.autopayEnabled == autopayEnabled)&&(identical(other.autopayPaymentNumber, autopayPaymentNumber) || other.autopayPaymentNumber == autopayPaymentNumber)&&(identical(other.autopayFrequencyDays, autopayFrequencyDays) || other.autopayFrequencyDays == autopayFrequencyDays));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,reference,issuerId,invoiceId,integrationMode,status,amount,currency,dueDate,folio,barcodeUrl,payFormatUrl,payTdUrl,autopayEnabled,autopayPaymentNumber,autopayFrequencyDays);

@override
String toString() {
  return 'PaymentReference(id: $id, reference: $reference, issuerId: $issuerId, invoiceId: $invoiceId, integrationMode: $integrationMode, status: $status, amount: $amount, currency: $currency, dueDate: $dueDate, folio: $folio, barcodeUrl: $barcodeUrl, payFormatUrl: $payFormatUrl, payTdUrl: $payTdUrl, autopayEnabled: $autopayEnabled, autopayPaymentNumber: $autopayPaymentNumber, autopayFrequencyDays: $autopayFrequencyDays)';
}


}

/// @nodoc
abstract mixin class _$PaymentReferenceCopyWith<$Res> implements $PaymentReferenceCopyWith<$Res> {
  factory _$PaymentReferenceCopyWith(_PaymentReference value, $Res Function(_PaymentReference) _then) = __$PaymentReferenceCopyWithImpl;
@override @useResult
$Res call({
 String id, String reference,@JsonKey(name: 'issuer_id') String issuerId,@JsonKey(name: 'invoice_id') String invoiceId,@JsonKey(name: 'integration_mode') String integrationMode, String status, int amount, String currency,@JsonKey(name: 'due_date') DateTime dueDate, String folio,@JsonKey(name: 'barcode_url') String barcodeUrl,@JsonKey(name: 'pay_format_url') String payFormatUrl,@JsonKey(name: 'pay_td_url') String? payTdUrl,@JsonKey(name: 'autopay_enabled') bool autopayEnabled,@JsonKey(name: 'autopay_payment_number') int? autopayPaymentNumber,@JsonKey(name: 'autopay_frequency_days') int? autopayFrequencyDays
});




}
/// @nodoc
class __$PaymentReferenceCopyWithImpl<$Res>
    implements _$PaymentReferenceCopyWith<$Res> {
  __$PaymentReferenceCopyWithImpl(this._self, this._then);

  final _PaymentReference _self;
  final $Res Function(_PaymentReference) _then;

/// Create a copy of PaymentReference
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? reference = null,Object? issuerId = null,Object? invoiceId = null,Object? integrationMode = null,Object? status = null,Object? amount = null,Object? currency = null,Object? dueDate = null,Object? folio = null,Object? barcodeUrl = null,Object? payFormatUrl = null,Object? payTdUrl = freezed,Object? autopayEnabled = null,Object? autopayPaymentNumber = freezed,Object? autopayFrequencyDays = freezed,}) {
  return _then(_PaymentReference(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,reference: null == reference ? _self.reference : reference // ignore: cast_nullable_to_non_nullable
as String,issuerId: null == issuerId ? _self.issuerId : issuerId // ignore: cast_nullable_to_non_nullable
as String,invoiceId: null == invoiceId ? _self.invoiceId : invoiceId // ignore: cast_nullable_to_non_nullable
as String,integrationMode: null == integrationMode ? _self.integrationMode : integrationMode // ignore: cast_nullable_to_non_nullable
as String,status: null == status ? _self.status : status // ignore: cast_nullable_to_non_nullable
as String,amount: null == amount ? _self.amount : amount // ignore: cast_nullable_to_non_nullable
as int,currency: null == currency ? _self.currency : currency // ignore: cast_nullable_to_non_nullable
as String,dueDate: null == dueDate ? _self.dueDate : dueDate // ignore: cast_nullable_to_non_nullable
as DateTime,folio: null == folio ? _self.folio : folio // ignore: cast_nullable_to_non_nullable
as String,barcodeUrl: null == barcodeUrl ? _self.barcodeUrl : barcodeUrl // ignore: cast_nullable_to_non_nullable
as String,payFormatUrl: null == payFormatUrl ? _self.payFormatUrl : payFormatUrl // ignore: cast_nullable_to_non_nullable
as String,payTdUrl: freezed == payTdUrl ? _self.payTdUrl : payTdUrl // ignore: cast_nullable_to_non_nullable
as String?,autopayEnabled: null == autopayEnabled ? _self.autopayEnabled : autopayEnabled // ignore: cast_nullable_to_non_nullable
as bool,autopayPaymentNumber: freezed == autopayPaymentNumber ? _self.autopayPaymentNumber : autopayPaymentNumber // ignore: cast_nullable_to_non_nullable
as int?,autopayFrequencyDays: freezed == autopayFrequencyDays ? _self.autopayFrequencyDays : autopayFrequencyDays // ignore: cast_nullable_to_non_nullable
as int?,
  ));
}


}

// dart format on

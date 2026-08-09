import 'package:freezed_annotation/freezed_annotation.dart';

part 'payment_reference.freezed.dart';
part 'payment_reference.g.dart';

@freezed
abstract class PaymentReference with _$PaymentReference {
  const factory PaymentReference({
    required String id,
    required String reference,
    @JsonKey(name: 'issuer_id') required String issuerId,
    @JsonKey(name: 'invoice_id') required String invoiceId,
    @JsonKey(name: 'integration_mode') required String integrationMode,
    required String status,
    required int amount,
    required String currency,
    @JsonKey(name: 'due_date') required DateTime dueDate,
    required String folio,
    @JsonKey(name: 'barcode_url') required String barcodeUrl,
    @JsonKey(name: 'pay_format_url') required String payFormatUrl,
    @JsonKey(name: 'pay_td_url') String? payTdUrl,
    @JsonKey(name: 'autopay_enabled') required bool autopayEnabled,
    @JsonKey(name: 'autopay_payment_number') int? autopayPaymentNumber,
    @JsonKey(name: 'autopay_frequency_days') int? autopayFrequencyDays,
  }) = _PaymentReference;

  factory PaymentReference.fromJson(Map<String, dynamic> json) =>
      _$PaymentReferenceFromJson(json);
}

// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'payment_reference.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_PaymentReference _$PaymentReferenceFromJson(Map<String, dynamic> json) =>
    _PaymentReference(
      id: json['id'] as String,
      reference: json['reference'] as String,
      issuerId: json['issuer_id'] as String,
      invoiceId: json['invoice_id'] as String,
      integrationMode: json['integration_mode'] as String,
      status: json['status'] as String,
      amount: (json['amount'] as num).toInt(),
      currency: json['currency'] as String,
      dueDate: DateTime.parse(json['due_date'] as String),
      folio: json['folio'] as String,
      barcodeUrl: json['barcode_url'] as String,
      payFormatUrl: json['pay_format_url'] as String,
      payTdUrl: json['pay_td_url'] as String?,
      autopayEnabled: json['autopay_enabled'] as bool,
      autopayPaymentNumber: (json['autopay_payment_number'] as num?)?.toInt(),
      autopayFrequencyDays: (json['autopay_frequency_days'] as num?)?.toInt(),
    );

Map<String, dynamic> _$PaymentReferenceToJson(_PaymentReference instance) =>
    <String, dynamic>{
      'id': instance.id,
      'reference': instance.reference,
      'issuer_id': instance.issuerId,
      'invoice_id': instance.invoiceId,
      'integration_mode': instance.integrationMode,
      'status': instance.status,
      'amount': instance.amount,
      'currency': instance.currency,
      'due_date': instance.dueDate.toIso8601String(),
      'folio': instance.folio,
      'barcode_url': instance.barcodeUrl,
      'pay_format_url': instance.payFormatUrl,
      'pay_td_url': instance.payTdUrl,
      'autopay_enabled': instance.autopayEnabled,
      'autopay_payment_number': instance.autopayPaymentNumber,
      'autopay_frequency_days': instance.autopayFrequencyDays,
    };

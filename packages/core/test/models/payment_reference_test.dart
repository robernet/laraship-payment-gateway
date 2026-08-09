import 'package:core/core.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('PaymentReference round-trips through JSON', () {
    final json = {
      'id': 'pr123',
      'reference': '77700112340000019',
      'issuer_id': 'iss1',
      'invoice_id': 'inv1',
      'integration_mode': 'online',
      'status': 'pending',
      'amount': 15000,
      'currency': 'MXN',
      'due_date': '2026-09-01T00:00:00.000Z',
      'folio': 'FOL-20260804-000001',
      'barcode_url': 'https://example.test/barcode.png',
      'pay_format_url': 'https://example.test/payformat.pdf',
      'pay_td_url': null,
      'autopay_enabled': false,
      'autopay_payment_number': null,
      'autopay_frequency_days': null,
    };

    final reference = PaymentReference.fromJson(json);

    expect(reference.id, 'pr123');
    expect(reference.reference, '77700112340000019');
    expect(reference.issuerId, 'iss1');
    expect(reference.invoiceId, 'inv1');
    expect(reference.integrationMode, 'online');
    expect(reference.status, 'pending');
    expect(reference.amount, 15000);
    expect(reference.currency, 'MXN');
    expect(reference.dueDate, DateTime.parse('2026-09-01T00:00:00.000Z'));
    expect(reference.folio, 'FOL-20260804-000001');
    expect(reference.autopayEnabled, false);

    expect(PaymentReference.fromJson(reference.toJson()), reference);
  });
}

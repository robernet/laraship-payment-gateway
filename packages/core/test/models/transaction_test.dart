import 'package:core/core.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('Transaction round-trips through JSON', () {
    final json = {
      'id': 'txn1',
      'payment_reference_id': 'pr123',
      'shift_id': 'sh1',
      'amount': 15000,
      'currency': 'MXN',
      'collected_at': '2026-08-04T18:00:00.000Z',
      'status': 'collected',
    };

    final transaction = Transaction.fromJson(json);

    expect(transaction.id, 'txn1');
    expect(transaction.paymentReferenceId, 'pr123');
    expect(transaction.shiftId, 'sh1');
    expect(transaction.amount, 15000);
    expect(transaction.currency, 'MXN');
    expect(transaction.collectedAt, DateTime.parse('2026-08-04T18:00:00.000Z'));
    expect(transaction.status, 'collected');

    expect(Transaction.fromJson(transaction.toJson()), transaction);
  });
}

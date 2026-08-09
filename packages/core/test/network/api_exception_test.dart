import 'package:core/core.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ApiException.fromResponse', () {
    test('reads validation errors nested under data.errors (real Laravel shape)', () {
      final error = ApiException.fromResponse(422, {
        'status': 'error',
        'message': 'The given data was invalid',
        'data': {
          'errors': {
            'payment_reference_id': ['This reference is overdue and can no longer be collected.'],
          },
        },
      });

      expect(error.isValidation, isTrue);
      expect(error.firstError('payment_reference_id'),
          'This reference is overdue and can no longer be collected.');
    });

    test('still reads top-level errors if a response ever sends the documented shape', () {
      final error = ApiException.fromResponse(422, {
        'message': 'Validation failed',
        'errors': {
          'amount': ['The collected amount does not match the reference amount.'],
        },
      });

      expect(error.firstError('amount'), 'The collected amount does not match the reference amount.');
    });

    test('falls back to the generic message with no body', () {
      final error = ApiException.fromResponse(null, null);

      expect(error.message, 'Request failed');
      expect(error.errors, isEmpty);
    });
  });
}

import 'package:core/core.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';

import '../support/fake_http_adapter.dart';
import '../support/fake_token_store.dart';

DioPaymentGatewayService _serviceWith(FakeHttpAdapter adapter, {FakeTokenStore? tokens}) {
  final dio = Dio(BaseOptions(baseUrl: 'https://test.local/api/v1'))
    ..httpClientAdapter = adapter;
  return DioPaymentGatewayService(
    api: ApiClient.forTesting(dio),
    tokens: tokens ?? FakeTokenStore(),
  );
}

void main() {
  test('login returns a PosSession and persists the token', () async {
    final tokens = FakeTokenStore();
    final adapter = FakeHttpAdapter(200, {
      'data': {
        'token': 'plain-text-token',
        'abilities': ['payment:lookup', 'payment:collect'],
        'branch_id': 'b1',
        'store_id': 'st1',
      },
    });
    final service = _serviceWith(adapter, tokens: tokens);

    final session = await service.login(email: 'op@example.test', password: 'secret', branchId: 'b1');

    expect(session.token, 'plain-text-token');
    expect(session.abilities, ['payment:lookup', 'payment:collect']);
    expect(session.branchId, 'b1');
    expect(session.storeId, 'st1');
    expect(tokens.written, 'plain-text-token');
    expect(adapter.lastRequest!.path, '/pos/login');
    expect(adapter.lastRequest!.method, 'POST');
    expect(adapter.lastRequest!.data, {'email': 'op@example.test', 'password': 'secret', 'branch_id': 'b1'});
  });

  test('lookupReference GETs the lookup-by-reference-string route', () async {
    final adapter = FakeHttpAdapter(200, {
      'data': {
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
      },
    });
    final service = _serviceWith(adapter);

    final reference = await service.lookupReference('77700112340000019');

    expect(reference.id, 'pr123');
    expect(reference.status, 'pending');
    expect(adapter.lastRequest!.path, '/payment-references/lookup/77700112340000019');
    expect(adapter.lastRequest!.method, 'GET');
  });

  test('collectPayment POSTs to /transactions', () async {
    final adapter = FakeHttpAdapter(200, {
      'data': {
        'id': 'txn1',
        'payment_reference_id': 'pr123',
        'shift_id': 'sh1',
        'amount': 15000,
        'currency': 'MXN',
        'collected_at': '2026-08-04T18:00:00.000Z',
        'status': 'collected',
      },
    });
    final service = _serviceWith(adapter);

    final transaction = await service.collectPayment(paymentReferenceId: 'pr123', amount: 15000, currency: 'MXN');

    expect(transaction.id, 'txn1');
    expect(adapter.lastRequest!.path, '/transactions');
    expect(adapter.lastRequest!.data, {'payment_reference_id': 'pr123', 'amount': 15000, 'currency': 'MXN'});
  });

  test('collectPayment surfaces a 422 as ApiException with the field error', () async {
    final adapter = FakeHttpAdapter(422, {
      'message': 'The given data was invalid.',
      'errors': {
        'amount': ['The collected amount does not match the reference amount.'],
      },
    });
    final service = _serviceWith(adapter);

    await expectLater(
      () => service.collectPayment(paymentReferenceId: 'pr123', amount: 100, currency: 'MXN'),
      throwsA(
        isA<ApiException>()
            .having((e) => e.isValidation, 'isValidation', true)
            .having((e) => e.firstError('amount'), 'firstError(amount)', isNotNull),
      ),
    );
  });

  test('openShift POSTs to /shifts', () async {
    final adapter = FakeHttpAdapter(200, {
      'data': {
        'id': 'sh1',
        'branch_id': 'b1',
        'store_id': 'st1',
        'operator_id': 'op1',
        'opened_at': '2026-08-04T12:00:00.000Z',
        'closed_at': null,
        'counted_amount_minor': null,
        'discrepancy_minor': null,
      },
    });
    final service = _serviceWith(adapter);

    final shift = await service.openShift(branchId: 'b1');

    expect(shift.id, 'sh1');
    expect(adapter.lastRequest!.path, '/shifts');
    expect(adapter.lastRequest!.data, {'branch_id': 'b1'});
  });

  test('closeShift PATCHes /shifts/{id} and returns the computed discrepancy', () async {
    final adapter = FakeHttpAdapter(200, {
      'data': {
        'id': 'sh1',
        'branch_id': 'b1',
        'store_id': 'st1',
        'operator_id': 'op1',
        'opened_at': '2026-08-04T12:00:00.000Z',
        'closed_at': '2026-08-04T20:00:00.000Z',
        'counted_amount_minor': 49500,
        'discrepancy_minor': -500,
      },
    });
    final service = _serviceWith(adapter);

    final shift = await service.closeShift(shiftId: 'sh1', countedAmountMinor: 49500);

    expect(shift.discrepancyMinor, -500);
    expect(adapter.lastRequest!.path, '/shifts/sh1');
    expect(adapter.lastRequest!.method, 'PATCH');
    expect(adapter.lastRequest!.data, {'counted_amount': 49500});
  });
}

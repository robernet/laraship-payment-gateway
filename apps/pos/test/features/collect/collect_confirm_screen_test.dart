import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/core_providers.dart';
import 'package:pos/features/collect/presentation/collect_confirm_screen.dart';
import 'package:pos/features/shift/domain/shift_state.dart';

import '../../support/fake_payment_gateway_service.dart';

final _reference = PaymentReference(
  id: 'pr123',
  reference: '77700112340000019',
  issuerId: 'iss1',
  invoiceId: 'inv1',
  integrationMode: 'online',
  status: 'pending',
  amount: 15000,
  currency: 'MXN',
  dueDate: DateTime.parse('2026-09-01T00:00:00.000Z'),
  folio: 'FOL-20260804-000001',
  barcodeUrl: 'https://example.test/barcode.png',
  payFormatUrl: 'https://example.test/payformat.pdf',
  autopayEnabled: false,
);

void main() {
  testWidgets('confirming a collection calls onCollected and records it on the shift', (tester) async {
    final transaction = Transaction(
      id: 'txn1',
      paymentReferenceId: 'pr123',
      shiftId: 'sh1',
      amount: 15000,
      currency: 'MXN',
      collectedAt: DateTime.parse('2026-08-04T18:00:00.000Z'),
      status: 'collected',
    );
    final fake = FakePaymentGatewayService(collectResult: transaction);
    (PaymentReference, Transaction)? collected;
    final container = ProviderContainer(
      overrides: [paymentGatewayServiceProvider.overrideWithValue(fake)],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(
          home: CollectConfirmScreen(
            reference: _reference,
            onCollected: (reference, txn) => collected = (reference, txn),
          ),
        ),
      ),
    );

    await tester.tap(find.byKey(const Key('collect_confirm')));
    await tester.pumpAndSettle();

    expect(fake.lastCollectArgs, {'payment_reference_id': 'pr123', 'amount': 15000, 'currency': 'MXN'});
    expect(collected?.$2.id, 'txn1');
    expect(container.read(shiftProvider).transactions.single.id, 'txn1');
  });

  testWidgets('a 422 amount mismatch shows an inline error, not a crash', (tester) async {
    final fake = FakePaymentGatewayService(
      collectError: ApiException(
        statusCode: 422,
        message: 'The given data was invalid.',
        errors: {
          'amount': ['The collected amount does not match the reference amount.'],
        },
      ),
    );
    final container = ProviderContainer(
      overrides: [paymentGatewayServiceProvider.overrideWithValue(fake)],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(home: CollectConfirmScreen(reference: _reference, onCollected: (_, _) {})),
      ),
    );

    await tester.tap(find.byKey(const Key('collect_confirm')));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('collect_error')), findsOneWidget);
    expect(container.read(shiftProvider).transactions, isEmpty);
  });
}

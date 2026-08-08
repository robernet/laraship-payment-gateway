import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/core_providers.dart';
import 'package:pos/features/payment_reference/presentation/reference_lookup_screen.dart';

import '../../support/fake_payment_gateway_service.dart';

PaymentReference _pending() => PaymentReference(
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
  testWidgets('a found, uncollected reference shows the amount due and a Collect button', (tester) async {
    PaymentReference? collected;
    final fake = FakePaymentGatewayService(lookupResult: _pending());
    final container = ProviderContainer(
      overrides: [paymentGatewayServiceProvider.overrideWithValue(fake)],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(
          home: ReferenceLookupScreen(onCollect: (reference) => collected = reference, onBackToMain: () {}, onScanBarcode: () async => null),
        ),
      ),
    );

    await tester.enterText(find.byKey(const Key('reference_input')), '77700112340000019');
    await tester.tap(find.byKey(const Key('reference_submit')));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('reference_amount_due')), findsOneWidget);

    await tester.tap(find.byKey(const Key('reference_collect')));
    expect(collected?.id, 'pr123');
  });

  testWidgets('a not-found reference shows an error, not a crash', (tester) async {
    final fake = FakePaymentGatewayService(
      lookupError: ApiException(statusCode: 404, message: 'Not found'),
    );
    final container = ProviderContainer(
      overrides: [paymentGatewayServiceProvider.overrideWithValue(fake)],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(home: ReferenceLookupScreen(onCollect: (_) {}, onBackToMain: () {}, onScanBarcode: () async => null)),
      ),
    );

    await tester.enterText(find.byKey(const Key('reference_input')), 'nope');
    await tester.tap(find.byKey(const Key('reference_submit')));
    await tester.pumpAndSettle();

    expect(find.text('Reference not found.'), findsOneWidget);
  });

  testWidgets('a non-pending reference informs the user, disables Collect, and offers back-to-main', (tester) async {
    final fake = FakePaymentGatewayService(lookupResult: _pending().copyWith(status: 'collected'));
    final container = ProviderContainer(
      overrides: [paymentGatewayServiceProvider.overrideWithValue(fake)],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(home: ReferenceLookupScreen(onCollect: (_) {}, onBackToMain: () {}, onScanBarcode: () async => null)),
      ),
    );

    await tester.enterText(find.byKey(const Key('reference_input')), '77700112340000019');
    await tester.tap(find.byKey(const Key('reference_submit')));
    await tester.pumpAndSettle();

    expect(find.text('This reference cannot be collected (status: collected).'), findsOneWidget);
    final collectButton = tester.widget<ElevatedButton>(find.byKey(const Key('reference_collect')));
    expect(collectButton.onPressed, isNull);

    await tester.tap(find.byKey(const Key('reference_reset_button')));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('reference_amount_due')), findsNothing);
    expect(find.byKey(const Key('reference_input')), findsOneWidget);
  });

  testWidgets('scanning a barcode fills the field and auto-submits the lookup', (tester) async {
    final fake = FakePaymentGatewayService(lookupResult: _pending());
    final container = ProviderContainer(
      overrides: [paymentGatewayServiceProvider.overrideWithValue(fake)],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(
          home: ReferenceLookupScreen(
            onCollect: (_) {},
            onBackToMain: () {},
            onScanBarcode: () async => '77700112340000019',
          ),
        ),
      ),
    );

    await tester.tap(find.byKey(const Key('reference_scan_button')));
    await tester.pumpAndSettle();

    expect(find.text('77700112340000019'), findsOneWidget);
    expect(find.byKey(const Key('reference_amount_due')), findsOneWidget);
  });
}

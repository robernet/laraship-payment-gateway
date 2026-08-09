import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/features/collect/presentation/receipt_screen.dart';

void main() {
  testWidgets('shows the reference, amount, folio, and wires both actions', (tester) async {
    final reference = PaymentReference(
      id: 'pr123',
      reference: '77700112340000019',
      issuerId: 'iss1',
      invoiceId: 'inv1',
      integrationMode: 'online',
      status: 'collected',
      amount: 15000,
      currency: 'MXN',
      dueDate: DateTime.parse('2026-09-01T00:00:00.000Z'),
      folio: 'FOL-20260804-000001',
      barcodeUrl: 'https://example.test/barcode.png',
      payFormatUrl: 'https://example.test/payformat.pdf',
      autopayEnabled: false,
    );
    final transaction = Transaction(
      id: 'txn1',
      paymentReferenceId: 'pr123',
      shiftId: 'sh1',
      amount: 15000,
      currency: 'MXN',
      collectedAt: DateTime.parse('2026-08-04T18:00:00.000Z'),
      status: 'collected',
    );
    var collectAnotherTapped = false;
    var viewShiftTapped = false;

    await tester.pumpWidget(
      MaterialApp(
        home: ReceiptScreen(
          reference: reference,
          transaction: transaction,
          onCollectAnother: () => collectAnotherTapped = true,
          onViewShift: () => viewShiftTapped = true,
        ),
      ),
    );

    expect(find.textContaining('77700112340000019'), findsOneWidget);
    expect(find.textContaining('FOL-20260804-000001'), findsOneWidget);
    expect(find.textContaining('15000'), findsWidgets);

    await tester.tap(find.byKey(const Key('receipt_collect_another')));
    expect(collectAnotherTapped, isTrue);

    await tester.tap(find.byKey(const Key('receipt_view_shift')));
    expect(viewShiftTapped, isTrue);
  });
}

import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/features/shift/domain/shift_state.dart';
import 'package:pos/features/shift/presentation/shift_transactions_screen.dart';

class _SeededShiftNotifier extends ShiftNotifier {
  _SeededShiftNotifier(this.seed);
  final ShiftState seed;

  @override
  ShiftState build() => seed;
}

void main() {
  testWidgets('lists the shift\'s accumulated transactions and wires close-shift', (tester) async {
    final transaction = Transaction(
      id: 'txn1',
      paymentReferenceId: 'pr123',
      shiftId: 'sh1',
      amount: 15000,
      currency: 'MXN',
      collectedAt: DateTime.parse('2026-08-04T18:00:00.000Z'),
      status: 'collected',
    );
    final shift = Shift(
      id: 'sh1',
      storeId: 's1',
      operatorId: 'op1',
      openedAt: DateTime.parse('2026-08-04T12:00:00.000Z'),
    );
    var closeTapped = false;
    final container = ProviderContainer(
      overrides: [
        shiftProvider.overrideWith(() => _SeededShiftNotifier(ShiftState(shift: shift, transactions: [transaction]))),
      ],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(home: ShiftTransactionsScreen(onCloseShift: () => closeTapped = true)),
      ),
    );

    expect(find.textContaining('15000'), findsOneWidget);

    await tester.tap(find.byKey(const Key('shift_close_button')));
    expect(closeTapped, isTrue);
  });

  testWidgets('shows an empty state when nothing has been collected yet', (tester) async {
    final shift = Shift(
      id: 'sh1',
      storeId: 's1',
      operatorId: 'op1',
      openedAt: DateTime.parse('2026-08-04T12:00:00.000Z'),
    );
    final container = ProviderContainer(
      overrides: [
        shiftProvider.overrideWith(() => _SeededShiftNotifier(ShiftState(shift: shift))),
      ],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(home: ShiftTransactionsScreen(onCloseShift: () {})),
      ),
    );

    expect(find.byKey(const Key('shift_transaction_list')), findsOneWidget);
    expect(find.byType(ListTile), findsNothing);
  });
}

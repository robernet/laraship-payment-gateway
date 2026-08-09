import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/core_providers.dart';
import 'package:pos/features/shift/domain/shift_state.dart';
import 'package:pos/features/shift/presentation/close_shift_screen.dart';

import '../../support/fake_payment_gateway_service.dart';

class _SeededShiftNotifier extends ShiftNotifier {
  _SeededShiftNotifier(this.seed);
  final ShiftState seed;

  @override
  ShiftState build() => seed;
}

Shift _openShift() => Shift(
      id: 'sh1',
      storeId: 's1',
      operatorId: 'op1',
      openedAt: DateTime.parse('2026-08-04T12:00:00.000Z'),
    );

void main() {
  testWidgets('closing the shift shows the server-computed discrepancy', (tester) async {
    final fake = FakePaymentGatewayService(
      closeShiftResult: Shift(
        id: 'sh1',
        storeId: 's1',
        operatorId: 'op1',
        openedAt: DateTime.parse('2026-08-04T12:00:00.000Z'),
        closedAt: DateTime.parse('2026-08-04T20:00:00.000Z'),
        countedAmountMinor: 49500,
        discrepancyMinor: -500,
      ),
    );
    final container = ProviderContainer(
      overrides: [
        paymentGatewayServiceProvider.overrideWithValue(fake),
        shiftProvider.overrideWith(() => _SeededShiftNotifier(ShiftState(shift: _openShift()))),
      ],
    );
    addTearDown(container.dispose);
    var doneTapped = false;

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(home: CloseShiftScreen(onDone: () => doneTapped = true)),
      ),
    );

    await tester.enterText(find.byKey(const Key('counted_amount_input')), '495.00');
    await tester.tap(find.byKey(const Key('close_shift_submit')));
    await tester.pumpAndSettle();

    expect(fake.lastCloseCountedAmountMinor, 49500);
    expect(find.textContaining('-500'), findsOneWidget);

    await tester.tap(find.byKey(const Key('close_shift_done')));
    expect(doneTapped, isTrue);
  });

  testWidgets('a close failure shows the error and stays on the input form', (tester) async {
    final fake = FakePaymentGatewayService(
      closeShiftError: ApiException(statusCode: 403, message: 'Not your shift.'),
    );
    final container = ProviderContainer(
      overrides: [
        paymentGatewayServiceProvider.overrideWithValue(fake),
        shiftProvider.overrideWith(() => _SeededShiftNotifier(ShiftState(shift: _openShift()))),
      ],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(home: CloseShiftScreen(onDone: () {})),
      ),
    );

    await tester.enterText(find.byKey(const Key('counted_amount_input')), '495.00');
    await tester.tap(find.byKey(const Key('close_shift_submit')));
    await tester.pumpAndSettle();

    expect(find.text('Not your shift.'), findsOneWidget);
    expect(find.byKey(const Key('counted_amount_input')), findsOneWidget);
  });
}

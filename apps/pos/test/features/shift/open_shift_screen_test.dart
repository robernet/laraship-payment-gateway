import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/core_providers.dart';
import 'package:pos/features/auth/domain/auth_state.dart';
import 'package:pos/features/shift/domain/shift_state.dart';
import 'package:pos/features/shift/presentation/open_shift_screen.dart';

import '../../support/fake_payment_gateway_service.dart';

class _SeededAuthNotifier extends AuthNotifier {
  _SeededAuthNotifier(this.seed);
  final PosSession? seed;

  @override
  PosSession? build() => seed;
}

void main() {
  testWidgets('opening a shift stores it in shiftProvider', (tester) async {
    final fake = FakePaymentGatewayService(
      openShiftResult: Shift(
        id: 'sh1',
        storeId: 's1',
        operatorId: 'op1',
        openedAt: DateTime.parse('2026-08-04T12:00:00.000Z'),
      ),
    );
    final container = ProviderContainer(
      overrides: [
        paymentGatewayServiceProvider.overrideWithValue(fake),
        authProvider.overrideWith(
          () => _SeededAuthNotifier(const PosSession(token: 't', abilities: [], storeId: 's1')),
        ),
      ],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: const MaterialApp(home: OpenShiftScreen()),
      ),
    );

    await tester.tap(find.byKey(const Key('open_shift_submit')));
    await tester.pumpAndSettle();

    expect(container.read(shiftProvider).shift?.id, 'sh1');
  });

  testWidgets('a failed open shows the error', (tester) async {
    final fake = FakePaymentGatewayService(
      openShiftError: ApiException(statusCode: 403, message: 'Not assigned to this store.'),
    );
    final container = ProviderContainer(
      overrides: [
        paymentGatewayServiceProvider.overrideWithValue(fake),
        authProvider.overrideWith(
          () => _SeededAuthNotifier(const PosSession(token: 't', abilities: [], storeId: 's1')),
        ),
      ],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: const MaterialApp(home: OpenShiftScreen()),
      ),
    );

    await tester.tap(find.byKey(const Key('open_shift_submit')));
    await tester.pumpAndSettle();

    expect(find.text('Not assigned to this store.'), findsOneWidget);
    expect(container.read(shiftProvider).shift, isNull);
  });
}

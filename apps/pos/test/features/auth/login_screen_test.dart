import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/core_providers.dart';
import 'package:pos/features/auth/domain/auth_state.dart';
import 'package:pos/features/auth/presentation/login_screen.dart';

import '../../support/fake_payment_gateway_service.dart';

Future<ProviderContainer> _pumpLogin(WidgetTester tester, FakePaymentGatewayService fake) async {
  final container = ProviderContainer(
    overrides: [paymentGatewayServiceProvider.overrideWithValue(fake)],
  );
  addTearDown(container.dispose);

  await tester.pumpWidget(
    UncontrolledProviderScope(
      container: container,
      child: const MaterialApp(home: LoginScreen()),
    ),
  );
  return container;
}

void main() {
  testWidgets('successful login stores the session', (tester) async {
    final fake = FakePaymentGatewayService(
      loginResult: const PosSession(token: 't', abilities: ['payment:lookup'], storeId: 's1'),
    );
    final container = await _pumpLogin(tester, fake);

    await tester.enterText(find.byKey(const Key('login_email')), 'op@example.test');
    await tester.enterText(find.byKey(const Key('login_password')), 'secret');
    await tester.enterText(find.byKey(const Key('login_store_id')), 's1');
    await tester.tap(find.byKey(const Key('login_submit')));
    await tester.pumpAndSettle();

    expect(container.read(authProvider)?.storeId, 's1');
  });

  testWidgets('failed login shows the error and leaves the session empty', (tester) async {
    final fake = FakePaymentGatewayService(
      loginError: ApiException(statusCode: 422, message: 'These credentials do not match our records.'),
    );
    final container = await _pumpLogin(tester, fake);

    await tester.enterText(find.byKey(const Key('login_email')), 'op@example.test');
    await tester.enterText(find.byKey(const Key('login_password')), 'wrong');
    await tester.enterText(find.byKey(const Key('login_store_id')), 's1');
    await tester.tap(find.byKey(const Key('login_submit')));
    await tester.pumpAndSettle();

    expect(find.text('These credentials do not match our records.'), findsOneWidget);
    expect(container.read(authProvider), isNull);
  });
}

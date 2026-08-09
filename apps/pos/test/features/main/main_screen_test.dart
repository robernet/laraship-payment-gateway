import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/features/main/presentation/main_screen.dart';

void main() {
  testWidgets('wires the lookup-reference, shift-transactions, end-shift and logout buttons', (tester) async {
    var lookupTapped = false;
    var shiftTapped = false;
    var endShiftTapped = false;
    var logoutTapped = false;

    await tester.pumpWidget(MaterialApp(
      home: MainScreen(
        onLookupReference: () => lookupTapped = true,
        onShiftTransactions: () => shiftTapped = true,
        onEndShift: () => endShiftTapped = true,
        onLogout: () => logoutTapped = true,
      ),
    ));

    await tester.tap(find.byKey(const Key('main_lookup_reference')));
    expect(lookupTapped, isTrue);

    await tester.tap(find.byKey(const Key('main_shift_transactions')));
    expect(shiftTapped, isTrue);

    await tester.tap(find.byKey(const Key('main_end_shift')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('end_shift_dialog_confirm')));
    await tester.pumpAndSettle();
    expect(endShiftTapped, isTrue);

    await tester.tap(find.byKey(const Key('main_logout')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('logout_dialog_confirm')));
    await tester.pumpAndSettle();
    expect(logoutTapped, isTrue);
    expect(find.text('Logged out'), findsOneWidget);
  });

  testWidgets('cancelling the end-shift or logout dialog does not fire the callback', (tester) async {
    var endShiftTapped = false;
    var logoutTapped = false;

    await tester.pumpWidget(MaterialApp(
      home: MainScreen(
        onLookupReference: () {},
        onShiftTransactions: () {},
        onEndShift: () => endShiftTapped = true,
        onLogout: () => logoutTapped = true,
      ),
    ));

    await tester.tap(find.byKey(const Key('main_end_shift')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('end_shift_dialog_cancel')));
    await tester.pumpAndSettle();
    expect(endShiftTapped, isFalse);

    await tester.tap(find.byKey(const Key('main_logout')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('logout_dialog_cancel')));
    await tester.pumpAndSettle();
    expect(logoutTapped, isFalse);
  });
}

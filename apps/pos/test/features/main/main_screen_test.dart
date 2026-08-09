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
    expect(endShiftTapped, isTrue);

    await tester.tap(find.byKey(const Key('main_logout')));
    expect(logoutTapped, isTrue);
  });
}

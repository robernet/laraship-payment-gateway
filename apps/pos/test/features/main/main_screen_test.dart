import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/features/main/presentation/main_screen.dart';

void main() {
  testWidgets('wires the lookup-reference and shift-transactions buttons', (tester) async {
    var lookupTapped = false;
    var shiftTapped = false;

    await tester.pumpWidget(MaterialApp(
      home: MainScreen(
        onLookupReference: () => lookupTapped = true,
        onShiftTransactions: () => shiftTapped = true,
      ),
    ));

    await tester.tap(find.byKey(const Key('main_lookup_reference')));
    expect(lookupTapped, isTrue);

    await tester.tap(find.byKey(const Key('main_shift_transactions')));
    expect(shiftTapped, isTrue);
  });
}

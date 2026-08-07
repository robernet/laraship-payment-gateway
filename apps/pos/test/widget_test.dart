import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/main.dart';

void main() {
  testWidgets('app boots to the placeholder home screen', (tester) async {
    await tester.pumpWidget(const ProviderScope(child: PosApp()));
    await tester.pumpAndSettle();

    expect(find.text('POS Simulator'), findsOneWidget);
  });
}

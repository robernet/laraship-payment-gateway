import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos/main.dart';

void main() {
  testWidgets('an unauthenticated app boots to the login screen', (tester) async {
    await tester.pumpWidget(const ProviderScope(child: PosApp()));
    await tester.pumpAndSettle();

    expect(find.text('Operator Login'), findsOneWidget);
  });
}

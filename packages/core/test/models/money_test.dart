import 'package:core/core.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('Money.format divides minor units by 100 and groups thousands', () {
    expect(Money.format(15230, 'MXN'), '152.30 MXN');
    expect(Money.format(123456789, 'MXN'), '1,234,567.89 MXN');
    expect(Money.format(5, 'USD'), '0.05 USD');
    expect(Money.format(-1234), '-12.34'); // discrepancy, no currency
    expect(Money.format(0), '0.00');
  });
}

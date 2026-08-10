import 'package:core/core.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('Shift round-trips through JSON, including nullable close fields', () {
    final openJson = {
      'id': 'sh1',
      'branch_id': 'b1',
      'store_id': 'st1',
      'operator_id': 'op1',
      'opened_at': '2026-08-04T12:00:00.000Z',
      'closed_at': null,
      'counted_amount_minor': null,
      'discrepancy_minor': null,
    };

    final open = Shift.fromJson(openJson);
    expect(open.branchId, 'b1');
    expect(open.storeId, 'st1');
    expect(open.closedAt, isNull);
    expect(open.discrepancyMinor, isNull);
    expect(Shift.fromJson(open.toJson()), open);

    final closedJson = {
      ...openJson,
      'closed_at': '2026-08-04T20:00:00.000Z',
      'counted_amount_minor': 50000,
      'discrepancy_minor': -500,
    };
    final closed = Shift.fromJson(closedJson);
    expect(closed.closedAt, DateTime.parse('2026-08-04T20:00:00.000Z'));
    expect(closed.discrepancyMinor, -500);
  });

  test('Shift round-trips a device-login shape (pos_id set, operator_id null)', () {
    final deviceJson = {
      'id': 'sh2',
      'branch_id': 'b1',
      'store_id': 'st1',
      'operator_id': null,
      'pos_id': 'pos1',
      'opened_at': '2026-08-04T12:00:00.000Z',
      'closed_at': null,
      'counted_amount_minor': null,
      'discrepancy_minor': null,
    };

    final device = Shift.fromJson(deviceJson);
    expect(device.operatorId, isNull);
    expect(device.posId, 'pos1');
    expect(Shift.fromJson(device.toJson()), device);
  });
}

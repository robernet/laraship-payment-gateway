import 'package:core/core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core_providers.dart';

class ShiftState {
  const ShiftState({this.shift, this.transactions = const []});

  final Shift? shift;
  final List<Transaction> transactions;

  ShiftState copyWith({Shift? shift, List<Transaction>? transactions}) => ShiftState(
        shift: shift ?? this.shift,
        transactions: transactions ?? this.transactions,
      );
}

class ShiftNotifier extends Notifier<ShiftState> {
  @override
  ShiftState build() => const ShiftState();

  Future<void> open({required String branchId}) async {
    final shift = await ref.read(paymentGatewayServiceProvider).openShift(branchId: branchId);
    state = ShiftState(shift: shift);
  }

  /// Backend has no `GET /transactions` list endpoint (see the design spec's
  /// "known gap" note) — the shift's transaction list is accumulated here as
  /// each collect succeeds, not fetched.
  void recordTransaction(Transaction transaction) {
    state = state.copyWith(transactions: [...state.transactions, transaction]);
  }

  Future<Shift> close({required int countedAmountMinor}) async {
    final current = state.shift;
    if (current == null) throw StateError('No open shift to close.');
    final closed = await ref.read(paymentGatewayServiceProvider).closeShift(
          shiftId: current.id,
          countedAmountMinor: countedAmountMinor,
        );
    state = state.copyWith(shift: closed);
    return closed;
  }

  void reset() => state = const ShiftState();
}

final shiftProvider = NotifierProvider<ShiftNotifier, ShiftState>(ShiftNotifier.new);

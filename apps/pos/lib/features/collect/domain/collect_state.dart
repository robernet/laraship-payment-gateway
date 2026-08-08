import 'package:core/core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core_providers.dart';
import '../../shift/domain/shift_state.dart';

class CollectNotifier extends Notifier<AsyncValue<Transaction>?> {
  @override
  AsyncValue<Transaction>? build() => null;

  Future<void> collect({
    required String paymentReferenceId,
    required int amount,
    required String currency,
  }) async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() async {
      final transaction = await ref.read(paymentGatewayServiceProvider).collectPayment(
            paymentReferenceId: paymentReferenceId,
            amount: amount,
            currency: currency,
          );
      ref.read(shiftProvider.notifier).recordTransaction(transaction);
      return transaction;
    });
  }

  void reset() => state = null;
}

final collectProvider = NotifierProvider<CollectNotifier, AsyncValue<Transaction>?>(CollectNotifier.new);

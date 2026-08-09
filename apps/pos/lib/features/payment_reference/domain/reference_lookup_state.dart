import 'package:core/core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core_providers.dart';

class ReferenceLookupNotifier extends Notifier<AsyncValue<PaymentReference>?> {
  @override
  AsyncValue<PaymentReference>? build() => null;

  Future<void> lookup(String reference) async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(
      () => ref.read(paymentGatewayServiceProvider).lookupReference(reference),
    );
  }

  void reset() => state = null;
}

final referenceLookupProvider =
    NotifierProvider<ReferenceLookupNotifier, AsyncValue<PaymentReference>?>(ReferenceLookupNotifier.new);

import 'package:core/core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'features/auth/domain/auth_state.dart';

final tokenStoreProvider = Provider<TokenStore>((ref) => TokenStore());

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient.create(
    tokens: ref.watch(tokenStoreProvider),
    onUnauthorized: () => ref.read(authProvider.notifier).logout(),
  );
});

final paymentGatewayServiceProvider = Provider<PaymentGatewayService>((ref) {
  return DioPaymentGatewayService(
    api: ref.watch(apiClientProvider),
    tokens: ref.watch(tokenStoreProvider),
  );
});

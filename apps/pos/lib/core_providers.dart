import 'package:core/core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'features/auth/domain/auth_state.dart';

final tokenStoreProvider = Provider<TokenStore>((ref) => TokenStore());

/// Backend base URL, loaded from `assets/config/api_config.json` at startup
/// and overridden into the ProviderScope in `main.dart` before `runApp`.
/// Defaults to empty (falls through to ApiClient's own dart-define lookup)
/// when nothing overrides it, e.g. in widget tests.
final apiBaseUrlProvider = Provider<String>((ref) => '');

final apiClientProvider = Provider<ApiClient>((ref) {
  final configuredBase = ref.watch(apiBaseUrlProvider);
  return ApiClient.create(
    baseUrl: configuredBase.isNotEmpty ? configuredBase : null,
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

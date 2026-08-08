import 'package:core/core.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'features/auth/domain/auth_state.dart';
import 'features/auth/presentation/login_screen.dart';
import 'features/main/presentation/main_screen.dart';
import 'features/shift/domain/shift_state.dart';
import 'features/shift/presentation/close_shift_screen.dart';
import 'features/shift/presentation/open_shift_screen.dart';
import 'features/shift/presentation/shift_transactions_screen.dart';
import 'features/payment_reference/domain/reference_lookup_state.dart';
import 'features/payment_reference/presentation/reference_lookup_screen.dart';
import 'features/payment_reference/presentation/barcode_scan_screen.dart';
import 'features/collect/domain/collect_state.dart';
import 'features/collect/presentation/collect_confirm_screen.dart';
import 'features/collect/presentation/receipt_screen.dart';

/// Notifies go_router's `refreshListenable` on auth/shift changes so
/// `redirect` re-evaluates without go_router disposing and rebuilding the
/// whole GoRouter (and its Navigator) on every state change.
class _RouterRefreshNotifier extends ChangeNotifier {
  _RouterRefreshNotifier(Ref ref) {
    ref.listen(authProvider, (_, _) => notifyListeners());
    ref.listen(shiftProvider, (_, _) => notifyListeners());
  }
}

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = _RouterRefreshNotifier(ref);
  ref.onDispose(refresh.dispose);

  return GoRouter(
    initialLocation: '/',
    refreshListenable: refresh,
    redirect: (context, state) {
      final loggedIn = ref.read(authProvider) != null;
      final loggingIn = state.matchedLocation == '/login';
      if (!loggedIn) return loggingIn ? null : '/login';
      if (loggingIn) return '/';

      final hasShift = ref.read(shiftProvider).shift != null;
      final openingShift = state.matchedLocation == '/shift/open';
      if (!hasShift) return openingShift ? null : '/shift/open';
      if (openingShift) return '/';

      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(path: '/shift/open', builder: (context, state) => const OpenShiftScreen()),
      GoRoute(
        path: '/',
        builder: (context, state) => MainScreen(
          onLookupReference: () {
            ref.read(referenceLookupProvider.notifier).reset();
            context.go('/reference-lookup');
          },
          onShiftTransactions: () => context.go('/shift/transactions'),
        ),
      ),
      GoRoute(
        path: '/reference-lookup',
        builder: (context, state) => ReferenceLookupScreen(
          onCollect: (reference) {
            ref.read(collectProvider.notifier).reset();
            context.push('/collect', extra: reference);
          },
          onBackToMain: () => context.go('/'),
          onScanBarcode: () => context.push<String>('/reference-lookup/scan'),
        ),
      ),
      GoRoute(
        path: '/reference-lookup/scan',
        builder: (context, state) => const BarcodeScanScreen(),
      ),
      GoRoute(
        path: '/collect',
        builder: (context, state) => CollectConfirmScreen(
          reference: state.extra! as PaymentReference,
          onCollected: (reference, transaction) =>
              context.go('/receipt', extra: (reference, transaction)),
          onBackToMain: () => context.go('/'),
        ),
      ),
      GoRoute(
        path: '/receipt',
        builder: (context, state) {
          final (reference, transaction) = state.extra! as (PaymentReference, Transaction);
          return ReceiptScreen(
            reference: reference,
            transaction: transaction,
            onCollectAnother: () {
              ref.read(referenceLookupProvider.notifier).reset();
              context.go('/reference-lookup');
            },
            onViewShift: () => context.go('/shift/transactions'),
          );
        },
      ),
      GoRoute(
        path: '/shift/transactions',
        builder: (context, state) => ShiftTransactionsScreen(
          onCloseShift: () => context.go('/shift/close'),
          onBackToMain: () => context.go('/'),
        ),
      ),
      GoRoute(
        path: '/shift/close',
        builder: (context, state) => CloseShiftScreen(
          onDone: () {
            ref.read(shiftProvider.notifier).reset();
            context.go('/');
          },
        ),
      ),
    ],
  );
});

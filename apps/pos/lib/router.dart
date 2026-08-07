import 'package:core/core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'features/auth/domain/auth_state.dart';
import 'features/auth/presentation/login_screen.dart';
import 'features/shift/domain/shift_state.dart';
import 'features/shift/presentation/close_shift_screen.dart';
import 'features/shift/presentation/open_shift_screen.dart';
import 'features/shift/presentation/shift_transactions_screen.dart';
import 'features/payment_reference/presentation/reference_lookup_screen.dart';
import 'features/collect/presentation/collect_confirm_screen.dart';
import 'features/collect/presentation/receipt_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final session = ref.watch(authProvider);
  final hasShift = ref.watch(shiftProvider).shift != null;

  return GoRouter(
    initialLocation: '/',
    redirect: (context, state) {
      final loggedIn = session != null;
      final loggingIn = state.matchedLocation == '/login';
      if (!loggedIn) return loggingIn ? null : '/login';
      if (loggingIn) return '/';

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
        builder: (context, state) => ReferenceLookupScreen(
          onCollect: (reference) => context.push('/collect', extra: reference),
        ),
      ),
      GoRoute(
        path: '/collect',
        builder: (context, state) => CollectConfirmScreen(
          reference: state.extra! as PaymentReference,
          onCollected: (reference, transaction) =>
              context.go('/receipt', extra: (reference, transaction)),
        ),
      ),
      GoRoute(
        path: '/receipt',
        builder: (context, state) {
          final (reference, transaction) = state.extra! as (PaymentReference, Transaction);
          return ReceiptScreen(
            reference: reference,
            transaction: transaction,
            onCollectAnother: () => context.go('/'),
            onViewShift: () => context.go('/shift/transactions'),
          );
        },
      ),
      GoRoute(
        path: '/shift/transactions',
        builder: (context, state) => ShiftTransactionsScreen(
          onCloseShift: () => context.go('/shift/close'),
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

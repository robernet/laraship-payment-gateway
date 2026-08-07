import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'features/collect/presentation/receipt_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/',
    routes: [
      GoRoute(
        path: '/',
        builder: (context, state) => const Scaffold(
          body: Center(child: Text('POS Simulator')),
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
    ],
  );
});

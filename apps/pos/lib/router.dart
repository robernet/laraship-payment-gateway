import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'features/auth/domain/auth_state.dart';
import 'features/auth/presentation/login_screen.dart';
import 'features/shift/domain/shift_state.dart';
import 'features/shift/presentation/open_shift_screen.dart';

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
        builder: (context, state) => const Scaffold(
          body: Center(child: Text('Shift open')),
        ),
      ),
    ],
  );
});

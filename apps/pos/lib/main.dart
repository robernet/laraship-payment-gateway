import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'router.dart';

void main() {
  runApp(const ProviderScope(child: PosApp()));
}

class PosApp extends ConsumerWidget {
  const PosApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return MaterialApp.router(
      title: 'POS Simulator',
      routerConfig: ref.watch(routerProvider),
    );
  }
}

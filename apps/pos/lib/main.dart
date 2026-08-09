import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core_providers.dart';
import 'router.dart';
import 'theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  final configJson = await rootBundle.loadString('assets/config/api_config.json');
  final apiBase = (jsonDecode(configJson) as Map<String, dynamic>)['api_base'] as String;

  runApp(ProviderScope(
    overrides: [apiBaseUrlProvider.overrideWithValue(apiBase)],
    child: const PosApp(),
  ));
}

class PosApp extends ConsumerWidget {
  const PosApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return MaterialApp.router(
      title: 'POS Simulator',
      theme: posTheme,
      routerConfig: ref.watch(routerProvider),
    );
  }
}

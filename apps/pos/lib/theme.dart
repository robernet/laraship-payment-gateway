import 'package:flutter/material.dart';

/// POS brand seed — DevKit's ecommerce lineage green (see apps/pos/ui-catalog.md).
const posSeedColor = Color(0xFF07AC12);

final posTheme = ThemeData(
  colorScheme: ColorScheme.fromSeed(seedColor: posSeedColor),
  useMaterial3: true,
);

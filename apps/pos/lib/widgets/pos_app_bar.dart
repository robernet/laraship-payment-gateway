import 'package:flutter/material.dart';

/// Branded app bar shared by every POS screen — DevKit's solid-color,
/// centered-title AppBar pattern (see apps/pos/ui-catalog.md) adapted to
/// this app's ColorScheme-driven theme instead of a hardcoded kit color.
class PosAppBar extends StatelessWidget implements PreferredSizeWidget {
  const PosAppBar({super.key, required this.title, this.leading, this.actions});

  final String title;
  final Widget? leading;
  final List<Widget>? actions;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    return AppBar(
      title: Text(title),
      centerTitle: true,
      backgroundColor: colorScheme.primary,
      foregroundColor: colorScheme.onPrimary,
      leading: leading,
      actions: actions,
    );
  }

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);
}

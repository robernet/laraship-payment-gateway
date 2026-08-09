import 'package:flutter/material.dart';

/// Confirm/cancel dialog shell shared by state-changing POS actions (end
/// shift, logout) — DevKit's AlertDialogPage pattern (see
/// apps/pos/ui-catalog.md) adapted to resolve a bool via Navigator.pop.
Future<bool> showPosConfirmDialog(
  BuildContext context, {
  required String title,
  required String message,
  required String confirmLabel,
  Key? confirmKey,
  Key? cancelKey,
}) async {
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (context) => AlertDialog(
      title: Text(title),
      content: Text(message),
      actions: [
        TextButton(
          key: cancelKey,
          onPressed: () => Navigator.of(context).pop(false),
          child: const Text('Cancel'),
        ),
        FilledButton(
          key: confirmKey,
          onPressed: () => Navigator.of(context).pop(true),
          child: Text(confirmLabel),
        ),
      ],
    ),
  );
  return confirmed ?? false;
}

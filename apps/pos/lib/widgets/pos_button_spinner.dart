import 'package:flutter/material.dart';

/// Small inline spinner sized to sit inside a FilledButton in place of its
/// label while an async POS action is in flight — DevKit's
/// progress-indicator pattern (see apps/pos/ui-catalog.md) adapted to
/// Material 3's own CircularProgressIndicator instead of a full shimmer
/// skeleton, which is overkill for a single button spinner.
class PosButtonSpinner extends StatelessWidget {
  const PosButtonSpinner({super.key});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 20,
      width: 20,
      child: CircularProgressIndicator(
        strokeWidth: 2,
        color: Theme.of(context).colorScheme.onPrimary,
      ),
    );
  }
}

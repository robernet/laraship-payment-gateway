import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../domain/collect_state.dart';

class CollectConfirmScreen extends ConsumerWidget {
  const CollectConfirmScreen({
    super.key,
    required this.reference,
    required this.onCollected,
    required this.onBackToMain,
  });

  final PaymentReference reference;
  final void Function(PaymentReference reference, Transaction transaction) onCollected;
  final VoidCallback onBackToMain;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final collect = ref.watch(collectProvider);

    ref.listen(collectProvider, (previous, next) {
      next?.whenData((transaction) => onCollected(reference, transaction));
    });

    final error = collect != null && collect.hasError ? collect.error : null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Collect payment'),
        leading: IconButton(
          key: const Key('back_to_main_button'),
          icon: const Icon(Icons.home),
          tooltip: 'Back to main',
          onPressed: onBackToMain,
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Text('Amount due: ${reference.amount} ${reference.currency}'),
            if (error != null)
              Text(
                error is ApiException ? (_firstErrorOrMessage(error)) : 'Something went wrong.',
                key: const Key('collect_error'),
              ),
            ElevatedButton(
              key: const Key('collect_confirm'),
              onPressed: collect != null && collect.isLoading
                  ? null
                  : () => ref.read(collectProvider.notifier).collect(
                        paymentReferenceId: reference.id,
                        amount: reference.amount,
                        currency: reference.currency,
                      ),
              child: const Text('Confirm collection'),
            ),
          ],
        ),
      ),
    );
  }

  /// Field-specific validation reasons (e.g. "overdue reference") are more
  /// actionable than the generic "The given data was invalid." wrapper
  /// message Laravel sends alongside them.
  String _firstErrorOrMessage(ApiException error) {
    for (final messages in error.errors.values) {
      if (messages.isNotEmpty) return messages.first;
    }
    return error.message;
  }
}

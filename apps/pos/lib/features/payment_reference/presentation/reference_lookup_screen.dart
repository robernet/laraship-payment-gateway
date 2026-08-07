import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../domain/reference_lookup_state.dart';

class ReferenceLookupScreen extends ConsumerStatefulWidget {
  const ReferenceLookupScreen({super.key, required this.onCollect});

  final void Function(PaymentReference reference) onCollect;

  @override
  ConsumerState<ReferenceLookupScreen> createState() => _ReferenceLookupScreenState();
}

class _ReferenceLookupScreenState extends ConsumerState<ReferenceLookupScreen> {
  final _reference = TextEditingController();

  @override
  void dispose() {
    _reference.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final result = ref.watch(referenceLookupProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Look up reference')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(
              controller: _reference,
              key: const Key('reference_input'),
              decoration: const InputDecoration(labelText: 'Payment reference'),
            ),
            ElevatedButton(
              key: const Key('reference_submit'),
              onPressed: () => ref.read(referenceLookupProvider.notifier).lookup(_reference.text),
              child: const Text('Look up'),
            ),
            const SizedBox(height: 16),
            if (result != null) _buildResult(result),
          ],
        ),
      ),
    );
  }

  Widget _buildResult(AsyncValue<PaymentReference> result) {
    return result.when(
      loading: () => const CircularProgressIndicator(),
      error: (error, _) {
        final message = error is ApiException && error.isNotFound
            ? 'Reference not found.'
            : error is ApiException
                ? error.message
                : 'Something went wrong.';
        return Text(message);
      },
      data: (reference) {
        if (reference.status == 'collected') {
          return const Text('This reference has already been collected.');
        }
        return Column(
          key: const Key('reference_amount_due'),
          children: [
            Text('Amount due: ${reference.amount} ${reference.currency}'),
            Text('Due date: ${reference.dueDate.toIso8601String()}'),
            ElevatedButton(
              key: const Key('reference_collect'),
              onPressed: () => widget.onCollect(reference),
              child: const Text('Collect'),
            ),
          ],
        );
      },
    );
  }
}

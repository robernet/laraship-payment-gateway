import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../widgets/pos_app_bar.dart';
import '../domain/reference_lookup_state.dart';

class ReferenceLookupScreen extends ConsumerStatefulWidget {
  const ReferenceLookupScreen({
    super.key,
    required this.onCollect,
    required this.onBackToMain,
    required this.onScanBarcode,
  });

  final void Function(PaymentReference reference) onCollect;
  final VoidCallback onBackToMain;
  final Future<String?> Function() onScanBarcode;

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

  Future<void> _scan() async {
    final scanned = await widget.onScanBarcode();
    if (scanned == null || scanned.isEmpty) return;
    _reference.text = scanned;
    ref.read(referenceLookupProvider.notifier).lookup(scanned);
  }

  @override
  Widget build(BuildContext context) {
    final result = ref.watch(referenceLookupProvider);

    return Scaffold(
      appBar: PosAppBar(
        title: 'Look up reference',
        leading: IconButton(
          key: const Key('back_to_main_button'),
          icon: const Icon(Icons.home),
          tooltip: 'Back to main',
          onPressed: widget.onBackToMain,
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(
              controller: _reference,
              key: const Key('reference_input'),
              decoration: const InputDecoration(labelText: 'Payment reference'),
            ),
            Row(
              children: [
                FilledButton(
                  key: const Key('reference_submit'),
                  onPressed: () => ref.read(referenceLookupProvider.notifier).lookup(_reference.text),
                  child: const Text('Look up'),
                ),
                const SizedBox(width: 8),
                IconButton(
                  key: const Key('reference_scan_button'),
                  icon: const Icon(Icons.qr_code_scanner),
                  tooltip: 'Scan barcode',
                  onPressed: _scan,
                ),
              ],
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
        final isPending = reference.status == 'pending';
        return Column(
          key: const Key('reference_amount_due'),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Amount due: ${reference.amount} ${reference.currency}'),
                    Text('Due date: ${reference.dueDate.toIso8601String()}'),
                    if (!isPending)
                      Text(
                        'This reference cannot be collected (status: ${reference.status}).',
                        key: const Key('reference_status_message'),
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            FilledButton(
              key: const Key('reference_collect'),
              onPressed: isPending ? () => widget.onCollect(reference) : null,
              child: const Text('Collect'),
            ),
            if (!isPending)
              OutlinedButton(
                key: const Key('reference_reset_button'),
                onPressed: () {
                  ref.read(referenceLookupProvider.notifier).reset();
                  _reference.clear();
                },
                child: const Text('Look up another reference'),
              ),
          ],
        );
      },
    );
  }
}

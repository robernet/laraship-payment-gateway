import 'package:core/core.dart';
import 'package:flutter/material.dart';

import '../../../widgets/pos_app_bar.dart';

class ReceiptScreen extends StatelessWidget {
  const ReceiptScreen({
    super.key,
    required this.reference,
    required this.transaction,
    required this.onCollectAnother,
    required this.onViewShift,
  });

  final PaymentReference reference;
  final Transaction transaction;
  final VoidCallback onCollectAnother;
  final VoidCallback onViewShift;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const PosAppBar(title: 'Payment collected'),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Reference: ${reference.reference}'),
                    Text('Amount: ${transaction.amount} ${transaction.currency}'),
                    Text('Folio: ${reference.folio}'),
                    Text('Collected at: ${transaction.collectedAt.toIso8601String()}'),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            FilledButton(
              key: const Key('receipt_collect_another'),
              onPressed: onCollectAnother,
              child: const Text('Collect another'),
            ),
            TextButton(
              key: const Key('receipt_view_shift'),
              onPressed: onViewShift,
              child: const Text('View shift'),
            ),
          ],
        ),
      ),
    );
  }
}

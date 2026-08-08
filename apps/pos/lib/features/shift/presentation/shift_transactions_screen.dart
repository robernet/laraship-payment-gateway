import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../domain/shift_state.dart';

class ShiftTransactionsScreen extends ConsumerWidget {
  const ShiftTransactionsScreen({super.key, required this.onCloseShift, required this.onBackToMain});

  final VoidCallback onCloseShift;
  final VoidCallback onBackToMain;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final transactions = ref.watch(shiftProvider).transactions;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Shift transactions'),
        leading: IconButton(
          key: const Key('back_to_main_button'),
          icon: const Icon(Icons.home),
          tooltip: 'Back to main',
          onPressed: onBackToMain,
        ),
      ),
      body: ListView(
        key: const Key('shift_transaction_list'),
        children: [
          for (final t in transactions)
            ListTile(
              title: Text('${t.amount} ${t.currency}'),
              subtitle: Text(t.collectedAt.toIso8601String()),
            ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        key: const Key('shift_close_button'),
        onPressed: onCloseShift,
        label: const Text('Close shift'),
      ),
    );
  }
}

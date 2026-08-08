import 'package:flutter/material.dart';

class MainScreen extends StatelessWidget {
  const MainScreen({super.key, required this.onLookupReference, required this.onShiftTransactions});

  final VoidCallback onLookupReference;
  final VoidCallback onShiftTransactions;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('POS')),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            ElevatedButton(
              key: const Key('main_lookup_reference'),
              onPressed: onLookupReference,
              child: const Text('Look up reference'),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              key: const Key('main_shift_transactions'),
              onPressed: onShiftTransactions,
              child: const Text('Shift transactions'),
            ),
          ],
        ),
      ),
    );
  }
}

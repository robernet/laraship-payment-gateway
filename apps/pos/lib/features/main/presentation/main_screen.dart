import 'package:flutter/material.dart';

import '../../../widgets/pos_app_bar.dart';

class MainScreen extends StatelessWidget {
  const MainScreen({
    super.key,
    required this.onLookupReference,
    required this.onShiftTransactions,
    required this.onEndShift,
    required this.onLogout,
  });

  final VoidCallback onLookupReference;
  final VoidCallback onShiftTransactions;
  final VoidCallback onEndShift;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: PosAppBar(
        title: 'POS',
        actions: [
          IconButton(
            key: const Key('main_end_shift'),
            onPressed: onEndShift,
            icon: const Icon(Icons.point_of_sale),
            tooltip: 'End shift',
          ),
          IconButton(
            key: const Key('main_logout'),
            onPressed: onLogout,
            icon: const Icon(Icons.logout),
            tooltip: 'Logout',
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            FilledButton(
              key: const Key('main_lookup_reference'),
              onPressed: onLookupReference,
              child: const Text('Look up reference'),
            ),
            const SizedBox(height: 16),
            FilledButton(
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

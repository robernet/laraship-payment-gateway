import 'package:flutter/material.dart';

import '../../../widgets/pos_app_bar.dart';
import '../../../widgets/pos_confirm_dialog.dart';

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

  Future<void> _confirmEndShift(BuildContext context) async {
    final confirmed = await showPosConfirmDialog(
      context,
      title: 'End shift?',
      message: 'This takes you to the shift close screen to count cash and reconcile.',
      confirmLabel: 'End shift',
      confirmKey: const Key('end_shift_dialog_confirm'),
      cancelKey: const Key('end_shift_dialog_cancel'),
    );
    if (confirmed) onEndShift();
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final confirmed = await showPosConfirmDialog(
      context,
      title: 'Log out?',
      message: "You'll need to log in again to continue.",
      confirmLabel: 'Log out',
      confirmKey: const Key('logout_dialog_confirm'),
      cancelKey: const Key('logout_dialog_cancel'),
    );
    if (!confirmed) return;
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Logged out')));
    }
    onLogout();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: PosAppBar(
        title: 'POS',
        actions: [
          IconButton(
            key: const Key('main_end_shift'),
            onPressed: () => _confirmEndShift(context),
            icon: const Icon(Icons.point_of_sale),
            tooltip: 'End shift',
          ),
          IconButton(
            key: const Key('main_logout'),
            onPressed: () => _confirmLogout(context),
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

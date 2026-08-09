import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../widgets/pos_app_bar.dart';
import '../../auth/domain/auth_state.dart';
import '../domain/shift_state.dart';

class OpenShiftScreen extends ConsumerStatefulWidget {
  const OpenShiftScreen({super.key});

  @override
  ConsumerState<OpenShiftScreen> createState() => _OpenShiftScreenState();
}

class _OpenShiftScreenState extends ConsumerState<OpenShiftScreen> {
  bool _loading = false;
  String? _error;

  Future<void> _open() async {
    final session = ref.read(authProvider);
    if (session == null) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await ref.read(shiftProvider.notifier).open(storeId: session.storeId);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const PosAppBar(title: 'Open shift'),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            if (_error != null) Text(_error!),
            FilledButton(
              key: const Key('open_shift_submit'),
              onPressed: _loading ? null : _open,
              child: _loading ? const CircularProgressIndicator() : const Text('Open shift'),
            ),
          ],
        ),
      ),
    );
  }
}

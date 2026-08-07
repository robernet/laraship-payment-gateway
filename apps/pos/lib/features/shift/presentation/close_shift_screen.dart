import 'package:core/core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../domain/shift_state.dart';

class CloseShiftScreen extends ConsumerStatefulWidget {
  const CloseShiftScreen({super.key, required this.onDone});

  final VoidCallback onDone;

  @override
  ConsumerState<CloseShiftScreen> createState() => _CloseShiftScreenState();
}

class _CloseShiftScreenState extends ConsumerState<CloseShiftScreen> {
  final _countedAmount = TextEditingController();
  Shift? _closed;
  String? _error;
  bool _loading = false;

  @override
  void dispose() {
    _countedAmount.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final major = double.parse(_countedAmount.text);
      // ponytail: rounds to the nearest cent — fine for a POS simulator's
      // manual cash-count entry, not a precision cash-handling parser.
      final minor = (major * 100).round();
      final closed = await ref.read(shiftProvider.notifier).close(countedAmountMinor: minor);
      setState(() => _closed = closed);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final closed = _closed;
    return Scaffold(
      appBar: AppBar(title: const Text('Close shift')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: closed == null ? _buildForm() : _buildResult(closed),
      ),
    );
  }

  Widget _buildForm() {
    return Column(
      children: [
        TextField(
          controller: _countedAmount,
          key: const Key('counted_amount_input'),
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: const InputDecoration(labelText: 'Counted cash'),
        ),
        if (_error != null) Text(_error!),
        ElevatedButton(
          key: const Key('close_shift_submit'),
          onPressed: _loading ? null : _submit,
          child: _loading ? const CircularProgressIndicator() : const Text('Close shift'),
        ),
      ],
    );
  }

  Widget _buildResult(Shift closed) {
    return Column(
      key: const Key('close_shift_result'),
      children: [
        Text('Discrepancy: ${closed.discrepancyMinor ?? 0}'),
        ElevatedButton(
          key: const Key('close_shift_done'),
          onPressed: widget.onDone,
          child: const Text('Start new shift'),
        ),
      ],
    );
  }
}

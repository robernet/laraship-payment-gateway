import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

class BarcodeScanScreen extends StatefulWidget {
  const BarcodeScanScreen({super.key});

  @override
  State<BarcodeScanScreen> createState() => _BarcodeScanScreenState();
}

class _BarcodeScanScreenState extends State<BarcodeScanScreen> {
  final _controller = MobileScannerController(formats: [BarcodeFormat.code128]);
  bool _handled = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (_handled) return;
    final rawValue = capture.barcodes.firstOrNull?.rawValue;
    if (rawValue == null || rawValue.isEmpty) return;
    _handled = true;
    Navigator.pop(context, rawValue);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Scan barcode'),
        leading: IconButton(
          key: const Key('back_to_main_button'),
          icon: const Icon(Icons.home),
          tooltip: 'Back to main',
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: MobileScanner(
        key: const Key('barcode_scanner_view'),
        controller: _controller,
        onDetect: _onDetect,
        errorBuilder: (context, error) => Center(
          child: Text(
            'Camera error: ${error.errorDetails?.message ?? error.errorCode.name}',
            key: const Key('barcode_scanner_error'),
          ),
        ),
      ),
    );
  }
}

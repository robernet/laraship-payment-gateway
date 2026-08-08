# POS Barcode Scanner (Reference Lookup) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a POS operator scan a Code 128 barcode with the device camera on the "Look up reference" screen instead of typing the reference by hand, and have a successful scan auto-trigger the lookup.

**Architecture:** Add the `mobile_scanner` package. A new, isolated `BarcodeScanScreen` owns all camera/detection code and pops its route with the scanned string. `ReferenceLookupScreen` stays camera-free — it gets a new `onScanBarcode` callback (same DI pattern as its existing `onCollect`/`onBackToMain`) wired up in `router.dart` to push/pop the new scan route.

**Tech Stack:** Flutter, Riverpod, go_router, `mobile_scanner` (new).

## Global Constraints

- Design spec: `docs/superpowers/specs/2026-08-08-pos-barcode-scan-design.md` — read it before starting; this plan implements it exactly.
- Scan mechanism: real camera scan via `mobile_scanner`, not a simulated button, not keyboard-wedge capture.
- Platforms: Android (primary) and web (dev/demo). No iOS config.
- Post-scan behavior: auto-fill the reference field AND auto-submit the lookup — no extra tap.
- Barcode format: Code 128 only.
- `ReferenceLookupScreen` must not import `mobile_scanner` or any camera type — it only sees `Future<String?> Function() onScanBarcode`.
- Every new required constructor parameter added to an existing widget must be added to every test call site that constructs it, in the same task.

---

### Task 1: Add the `mobile_scanner` dependency and Android camera permission

**Files:**
- Modify: `apps/pos/pubspec.yaml`
- Modify: `apps/pos/android/app/src/main/AndroidManifest.xml`

**Interfaces:**
- Produces: the `mobile_scanner` package available to `apps/pos/lib/**` (exact resolved version determined by `flutter pub add`, recorded in `pubspec.lock`).

- [ ] **Step 1: Add the dependency**

Run from `apps/pos/`:
```bash
flutter pub add mobile_scanner
```
This adds a `mobile_scanner: ^<resolved version>` line under `dependencies:` in `pubspec.yaml` and updates `pubspec.lock`.

- [ ] **Step 2: Add the Android camera permission**

In `apps/pos/android/app/src/main/AndroidManifest.xml`, add the permission as a direct child of `<manifest>`, before the `<application>` tag:

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <uses-permission android:name="android.permission.CAMERA"/>
    <application
```

- [ ] **Step 3: Verify the project still builds**

Run from `apps/pos/`:
```bash
flutter analyze
```
Expected: `No issues found!` (the new dependency isn't used anywhere yet, so this just confirms `pubspec.yaml`/manifest edits didn't break anything).

- [ ] **Step 4: Commit**

```bash
git add apps/pos/pubspec.yaml apps/pos/pubspec.lock apps/pos/android/app/src/main/AndroidManifest.xml
git commit -m "chore(pos): add mobile_scanner dependency and camera permission"
```

---

### Task 2: `BarcodeScanScreen`

**Files:**
- Create: `apps/pos/lib/features/payment_reference/presentation/barcode_scan_screen.dart`

**Interfaces:**
- Consumes: `mobile_scanner` package (`MobileScanner`, `MobileScannerController`, `BarcodeFormat`, `BarcodeCapture`).
- Produces: `class BarcodeScanScreen extends StatefulWidget` with a no-argument const constructor (`const BarcodeScanScreen({super.key})`). When pushed and popped, it returns `String?` via `Navigator.pop<String>(context, rawValue)` on success, or pops with no value (`null`) when the operator cancels via the back button.

This screen's camera/detection logic cannot run under `flutter test` (no camera in the test harness), so this task has no automated test — see Step 3 for the required manual check instead.

- [ ] **Step 1: Write the screen**

```dart
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
      appBar: AppBar(title: const Text('Scan barcode')),
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
```

- [ ] **Step 2: Verify it compiles**

Run from `apps/pos/`:
```bash
flutter analyze
```
Expected: `No issues found!`. If `MobileScannerController`, `BarcodeFormat`, `BarcodeCapture`, or the `errorBuilder` signature don't match (package API differs by version), open the installed package source to confirm the exact signature:
```bash
find ~/.pub-cache -path "*mobile_scanner*/lib/src/mobile_scanner.dart"
```
and adjust the constructor/parameter names in Step 1 to match, then re-run `flutter analyze`.

- [ ] **Step 3: Manual verification (required — not covered by `flutter test`)**

- Android: run `flutter run` on an Android device/emulator with a camera, navigate to this screen (it isn't wired into the app yet after this task alone, so temporarily set it as `home:` in `main.dart` or push it from a debug button), point the camera at any Code 128 barcode (e.g. the barcode PNG this backend already generates at a reference's `barcode_url`), and confirm the screen pops with the correct decoded string.
- Web: run `flutter run -d chrome`, repeat the same check, allowing the camera permission prompt.
- Revert any temporary wiring used for this manual check before moving on — Task 4 wires it properly.

- [ ] **Step 4: Commit**

```bash
git add apps/pos/lib/features/payment_reference/presentation/barcode_scan_screen.dart
git commit -m "feat(pos): add BarcodeScanScreen for camera-based reference scanning"
```

---

### Task 3: Wire `onScanBarcode` into `ReferenceLookupScreen`

**Files:**
- Modify: `apps/pos/lib/features/payment_reference/presentation/reference_lookup_screen.dart`
- Modify: `apps/pos/test/features/payment_reference/reference_lookup_screen_test.dart`

**Interfaces:**
- Consumes: nothing new from other files (no `mobile_scanner` import here).
- Produces: `ReferenceLookupScreen` constructor gains `required this.onScanBarcode` of type `Future<String?> Function()`. A new `Key('reference_scan_button')` `IconButton` appears next to the reference text field.

- [ ] **Step 1: Write the failing test**

Add to `apps/pos/test/features/payment_reference/reference_lookup_screen_test.dart`, inside `void main() { ... }`, after the existing tests:

```dart
  testWidgets('scanning a barcode fills the field and auto-submits the lookup', (tester) async {
    final fake = FakePaymentGatewayService(lookupResult: _pending());
    final container = ProviderContainer(
      overrides: [paymentGatewayServiceProvider.overrideWithValue(fake)],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp(
          home: ReferenceLookupScreen(
            onCollect: (_) {},
            onBackToMain: () {},
            onScanBarcode: () async => '77700112340000019',
          ),
        ),
      ),
    );

    await tester.tap(find.byKey(const Key('reference_scan_button')));
    await tester.pumpAndSettle();

    expect(find.text('77700112340000019'), findsOneWidget);
    expect(find.byKey(const Key('reference_amount_due')), findsOneWidget);
  });
```

Also update the 3 existing `ReferenceLookupScreen(...)` constructions in this file to pass `onScanBarcode: () async => null`, since the constructor parameter you're about to add in Step 3 is required:

- Line 39: `home: ReferenceLookupScreen(onCollect: (reference) => collected = reference, onBackToMain: () {}),` → add `onScanBarcode: () async => null,` before the closing `)`.
- Line 66: `child: MaterialApp(home: ReferenceLookupScreen(onCollect: (_) {}, onBackToMain: () {})),` → same addition.
- Line 87: `child: MaterialApp(home: ReferenceLookupScreen(onCollect: (_) {}, onBackToMain: () {})),` → same addition.

- [ ] **Step 2: Run the test file to verify the new test fails**

Run from `apps/pos/`:
```bash
flutter test test/features/payment_reference/reference_lookup_screen_test.dart
```
Expected: FAIL — compile error, `onScanBarcode` is not a parameter of `ReferenceLookupScreen` yet (or, if you skipped updating the other call sites, a missing-required-argument error on those instead). Both are expected at this point.

- [ ] **Step 3: Implement `onScanBarcode`**

In `apps/pos/lib/features/payment_reference/presentation/reference_lookup_screen.dart`:

Change the constructor (currently at lines 7-11):
```dart
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
```

Add a scan handler method to `_ReferenceLookupScreenState` (after `dispose()`):
```dart
  Future<void> _scan() async {
    final scanned = await widget.onScanBarcode();
    if (scanned == null || scanned.isEmpty) return;
    _reference.text = scanned;
    ref.read(referenceLookupProvider.notifier).lookup(scanned);
  }
```

Add the scan button next to the existing `reference_submit` button inside the `Column` in `build()` (currently lines 44-53), so the field/buttons block reads:
```dart
            TextField(
              controller: _reference,
              key: const Key('reference_input'),
              decoration: const InputDecoration(labelText: 'Payment reference'),
            ),
            Row(
              children: [
                ElevatedButton(
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
```

- [ ] **Step 4: Run the test file to verify it passes**

Run from `apps/pos/`:
```bash
flutter test test/features/payment_reference/reference_lookup_screen_test.dart
```
Expected: all tests (including the new one) PASS.

- [ ] **Step 5: Run analyze**

Run from `apps/pos/`:
```bash
flutter analyze
```
Expected: `No issues found!`

- [ ] **Step 6: Commit**

```bash
git add apps/pos/lib/features/payment_reference/presentation/reference_lookup_screen.dart apps/pos/test/features/payment_reference/reference_lookup_screen_test.dart
git commit -m "feat(pos): wire barcode scan callback into ReferenceLookupScreen"
```

---

### Task 4: Wire the scan route into `router.dart`

**Files:**
- Modify: `apps/pos/lib/router.dart`

**Interfaces:**
- Consumes: `BarcodeScanScreen` (Task 2), `ReferenceLookupScreen`'s `onScanBarcode` parameter (Task 3).
- Produces: route `/reference-lookup/scan`; `ReferenceLookupScreen`'s `onScanBarcode` is supplied as `() => context.push<String>('/reference-lookup/scan')`.

This task is router-only wiring with no new logic of its own (the logic it wires was already tested in Tasks 2 and 3), so there is no new automated test — verify with the manual check in Step 3.

- [ ] **Step 1: Add the import**

In `apps/pos/lib/router.dart`, add alongside the other `payment_reference` imports (currently lines 13-14):
```dart
import 'features/payment_reference/presentation/barcode_scan_screen.dart';
```

- [ ] **Step 2: Add the route and wire the callback**

Add a new `GoRoute` for the scanner (anywhere in the `routes:` list — place it right after the `/reference-lookup` route, currently lines 62-71):
```dart
      GoRoute(
        path: '/reference-lookup/scan',
        builder: (context, state) => const BarcodeScanScreen(),
      ),
```

Update the existing `/reference-lookup` route's `ReferenceLookupScreen(...)` builder (currently lines 62-71) to pass the new callback:
```dart
      GoRoute(
        path: '/reference-lookup',
        builder: (context, state) => ReferenceLookupScreen(
          onCollect: (reference) {
            ref.read(collectProvider.notifier).reset();
            context.push('/collect', extra: reference);
          },
          onBackToMain: () => context.go('/'),
          onScanBarcode: () => context.push<String>('/reference-lookup/scan'),
        ),
      ),
```

- [ ] **Step 3: Manual verification**

Run from `apps/pos/`:
```bash
flutter run -d chrome
```
Log in, open a shift, go to "Look up reference", tap the scan icon — confirm the camera screen opens, the back arrow returns to the lookup screen with nothing filled in, and scanning a Code 128 barcode (e.g. open a reference's `barcode_url` PNG on another device/monitor and point the camera at it) fills the field and immediately shows the lookup result.

- [ ] **Step 4: Run the full test suite**

Run from `apps/pos/`:
```bash
flutter analyze
flutter test
```
Expected: `No issues found!` and all tests PASS (this route wiring doesn't change behavior any existing test observes, so nothing should regress).

- [ ] **Step 5: Commit**

```bash
git add apps/pos/lib/router.dart
git commit -m "feat(pos): route the barcode scanner into reference lookup"
```

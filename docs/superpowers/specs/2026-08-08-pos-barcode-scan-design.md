# POS — Barcode scanner for reference lookup

## Problem

The "Look up reference" screen (`apps/pos/lib/features/payment_reference/presentation/reference_lookup_screen.dart`) only accepts a typed-in reference string. Operators should be able to scan a printed/on-screen Code 128 barcode with the device camera instead of typing the (up to 29-digit) reference by hand.

## Decisions

- **Scan mechanism**: real device-camera scanning (not a simulated/mock scan, not keyboard-wedge hardware capture).
- **Platforms**: both Android (this app's primary target) and web (used for local dev/demo via `flutter run -d chrome`).
- **Post-scan behavior**: auto-submit — a successful scan fills the reference field and immediately triggers the lookup, no extra tap required.

## Architecture

- **New dependency**: `mobile_scanner` — actively maintained, supports Android + web, decodes Code 128 (the format the backend's `barcode_url` already renders).
- **New screen**: `BarcodeScanScreen` (`lib/features/payment_reference/presentation/barcode_scan_screen.dart`) — full-screen `MobileScanner` preview filtered to `BarcodeFormat.code128`. On the first successful detection it pops the route with the scanned string. An AppBar back button lets the operator cancel (pops with no value).
- **New route**: `/reference-lookup/scan`, pushed via `context.push<String>(...)` from the router, following the existing `context.push('/collect', extra: reference)` pattern.
- **`ReferenceLookupScreen` change**: add `required this.onScanBarcode` (`Future<String?> Function()`), injected the same way as `onCollect`/`onBackToMain`, plus a camera icon button next to the reference text field. This keeps `MobileScanner` entirely out of `ReferenceLookupScreen`, so its existing camera-free tests are untouched.

## Data flow

Tap scan icon → push `BarcodeScanScreen` → camera detects a Code 128 barcode → screen pops with the raw decoded string → back on the lookup screen: the text field is filled and `referenceLookupProvider.lookup(...)` is called immediately → existing success/error/not-pending rendering (already built) handles the result exactly like a typed-in reference.

## Error handling

- **Permission denied**: `MobileScanner`'s `errorBuilder` shows a short inline message; the existing AppBar back button returns to manual entry.
- **Cancel before detection**: route pops with `null`; `ReferenceLookupScreen` does nothing (no lookup triggered, field untouched).

## Platform config

- Android: add `<uses-permission android:name="android.permission.CAMERA"/>` to `AndroidManifest.xml`.
- Web: works over `http://localhost` (current dev setup). A production HTTPS origin is required for a real deployment, but that's out of scope here.
- iOS is not a listed target for this app (per `apps/pos/CLAUDE.md`) — no iOS config changes.

## Testing scope

- `ReferenceLookupScreen`: inject a fake `onScanBarcode` returning a canned reference string; verify tapping the scan button fills the field and auto-triggers the lookup (same fake-service pattern already used in this app's tests).
- `BarcodeScanScreen`'s actual camera/detection glue is not unit-testable — no camera in the `flutter test` harness. Its logic is kept to a minimal "detect → pop" and is flagged for a manual check on an Android device/emulator and in Chrome after implementation, rather than covered by `flutter test`.

## Out of scope

- Simulated/mock scanning, keyboard-wedge hardware scanner support.
- iOS platform configuration.
- Non-Code-128 barcode formats.

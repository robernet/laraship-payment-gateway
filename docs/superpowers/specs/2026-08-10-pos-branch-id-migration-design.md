# POS app: migrate `store_id` → `branch_id`

## Context

The backend's PaymentGateway module was extended (branches `feature/store-branches`,
`fix/store-branches-followups`, both merged to `master`) to scope POS auth and
shifts to a **Branch** instead of a Store — a Store now has many Branches, and a
Branch owns POS terminals, operators, and shifts. `docs/api-contract.md`'s
Auth (POS) / Shift / Branch sections reflect this: `POST /pos/login`,
`POST /pos/device-login`, and `POST /shifts` now take `branch_id`, not
`store_id`.

The Flutter POS app (`apps/pos`, built on a separate branch
`worktree-payment-reference-generator` and merged into `master` independently)
was never updated for this — it is still 100% on the old `store_id` contract.
This spec covers bringing `packages/core` and `apps/pos` in line with the
current contract. No behavior beyond the rename/reshape is introduced; there is
still no `GET /branches` endpoint, so `branchId` stays a manually-entered
string on the login screen, exactly as `storeId` was.

## Scope

Confirmed by reading the current source (not assumed from history):

**Changed:**
- `packages/core/lib/src/paymentgateway/payment_gateway_service.dart` —
  `PosSession`, `PaymentGatewayService.login()`, `.openShift()`
- `packages/core/lib/src/paymentgateway/dio_payment_gateway_service.dart` —
  request bodies and response parsing
- `packages/core/lib/src/models/shift.dart` (+ regenerated `.freezed.dart` /
  `.g.dart`)
- `apps/pos/lib/features/auth/domain/auth_state.dart`
- `apps/pos/lib/features/auth/presentation/login_screen.dart`
- `apps/pos/lib/features/shift/domain/shift_state.dart`
- `apps/pos/lib/features/shift/presentation/open_shift_screen.dart`
- `apps/pos/test/support/fake_payment_gateway_service.dart`
- `apps/pos/test/features/auth/login_screen_test.dart`
- `apps/pos/test/features/shift/open_shift_screen_test.dart`
- `apps/pos/test/features/shift/close_shift_screen_test.dart`
- `apps/pos/test/features/shift/shift_transactions_screen_test.dart`
- `packages/core/test/paymentgateway/dio_payment_gateway_service_test.dart`
- `packages/core/test/models/shift_test.dart`
- `apps/pos/CLAUDE.md` — Auth line says "scoped to a single store" (stale)

**Explicitly not touched** (verified — these only ever consume `shift.id` /
`Transaction`, never `storeId` directly): collect/confirm flow, receipt
screen, close-shift screen (beyond constructing a session/shift in tests),
shift-transactions list, barcode scan.

## Design

### 1. Models mirror the contract fully

`PosSession` gains `branchId` (replaces the role `storeId` played operationally)
and keeps `storeId` alongside it, since `POST /pos/login` returns both:

```dart
class PosSession {
  const PosSession({
    required this.token,
    required this.abilities,
    required this.branchId,
    required this.storeId,
  });

  final String token;
  final List<String> abilities;
  final String branchId;
  final String storeId;
}
```

`Shift` gains `branchId` (required) and keeps `storeId` (required,
denormalized per the contract). `operatorId` becomes nullable and a new
nullable `posId` is added — matching the contract's `operator_id`/`pos_id`
nullability even though this app's own login flow (human operator only, no
device-login screen exists) always populates `operatorId` and never `posId`:

```dart
@freezed
abstract class Shift with _$Shift {
  const factory Shift({
    required String id,
    @JsonKey(name: 'branch_id') required String branchId,
    @JsonKey(name: 'store_id') required String storeId,
    @JsonKey(name: 'operator_id') String? operatorId,
    @JsonKey(name: 'pos_id') String? posId,
    @JsonKey(name: 'opened_at') required DateTime openedAt,
    @JsonKey(name: 'closed_at') DateTime? closedAt,
    @JsonKey(name: 'counted_amount_minor') int? countedAmountMinor,
    @JsonKey(name: 'discrepancy_minor') int? discrepancyMinor,
  }) = _Shift;

  factory Shift.fromJson(Map<String, dynamic> json) => _$ShiftFromJson(json);
}
```

Regenerate `.freezed.dart` / `.g.dart` via
`dart run build_runner build --delete-conflicting-outputs` in `packages/core`.

### 2. Service layer

`PaymentGatewayService`:
```dart
Future<PosSession> login({required String email, required String password, required String branchId});
Future<Shift> openShift({required String branchId});
```

`DioPaymentGatewayService`:
- `login()` sends `{'email': email, 'password': password, 'branch_id': branchId}`
  and reads `branch_id` + `store_id` off the response into `PosSession`.
- `openShift()` sends `{'branch_id': branchId}`.

### 3. UI

- `AuthNotifier.login({required String email, required String password, required String branchId})`
  calls `service.login(..., branchId: branchId)`.
- `login_screen.dart`: rename the third field — controller `_storeId` →
  `_branchId`, `Key('login_store_id')` → `Key('login_branch_id')`, label
  `'Store ID'` → `'Branch ID'`.
- `ShiftNotifier.open({required String branchId})` calls
  `service.openShift(branchId: branchId)`.
- `open_shift_screen.dart`: `ref.read(shiftProvider.notifier).open(branchId: session.branchId)`.

### 4. Tests

- `fake_payment_gateway_service.dart`: signatures follow the interface change
  (`login({..., required String branchId})`, `openShift({required String branchId})`).
- Each affected test file swaps `storeId` / `'store_id'` / `Key('login_store_id')`
  for the branch equivalents, and constructs `PosSession`/`Shift` fixtures with
  the new required (`branchId`) and now-nullable (`operatorId`) fields.
- `shift_test.dart` and `dio_payment_gateway_service_test.dart` fixtures/assertions
  updated to the new JSON shape (`branch_id`, `store_id`, nullable `operator_id`).

### 5. Docs

`apps/pos/CLAUDE.md`'s Auth line — "Sanctum token scoped to a single store,
carrying operator-level abilities only" — corrected to "single branch".

## Error handling

No new error paths. `branch_id` validation (`422` on an unassigned branch,
`403` on a mismatched `branch:{hashid}` ability at shift-open) is enforced
server-side per the contract; the client surfaces it the same way it already
surfaces any `ApiException` today (inline error text on the login/open-shift
screens) — no change needed there.

## Testing / verification plan

1. `dart run build_runner build --delete-conflicting-outputs` in `packages/core`
   after the model edit.
2. `flutter analyze` clean in both `packages/core` and `apps/pos`.
3. `flutter test` green in both packages.
4. Manual smoke check not required beyond the above — no `GET /branches`
   endpoint exists to exercise interactively, and the login/open-shift screens
   are otherwise unchanged in structure (one relabeled field).

# POS `store_id` → `branch_id` Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bring `packages/core` and `apps/pos` in line with the now-merged branch-scoped backend contract — `POST /pos/login` and `POST /shifts` take `branch_id`, not `store_id`.

**Architecture:** `packages/core`'s `Shift` model and `PaymentGatewayService` layer change first (they define the contract shape the rest of the app depends on), then `apps/pos`'s domain layer (`AuthNotifier`, `ShiftNotifier`) and the shared test fake, then the two presentation screens (`login_screen.dart`, `open_shift_screen.dart`) that read/write the renamed field.

**Tech Stack:** Flutter, Riverpod (plain `Notifier`, no codegen), Dio, freezed + json_serializable, go_router (unaffected here).

## Global Constraints

- Design spec: `docs/superpowers/specs/2026-08-10-pos-branch-id-migration-design.md` — read it before starting; this plan implements it exactly.
- No `GET /branches` endpoint exists and none is being added — `branchId` stays a manually-entered string on the login screen, exactly as `storeId` was. Do not build a branch picker.
- Every command in this plan runs from inside the named package directory (`packages/core` or `apps/pos`), per `docs/flutter-conventions.md`.
- After any change to `packages/core/lib/src/models/shift.dart`, regenerate with `dart run build_runner build --delete-conflicting-outputs` from `packages/core` before running tests.
- `flutter analyze` must be clean for every file a task finishes touching (analyze errors are expected and OK in files a *later* task will fix — each task calls out which).
- Ids are Hashid strings everywhere; money stays integer minor units — neither changes in this migration.
- Every new required constructor parameter added to an existing type must be added to every literal construction of that type in the same task that would otherwise leave it broken, per the task breakdown below.

---

### Task 1: `packages/core` — `Shift` model, `PosSession`, and the service layer

**Files:**
- Modify: `packages/core/lib/src/models/shift.dart`
- Modify: `packages/core/lib/src/paymentgateway/payment_gateway_service.dart`
- Modify: `packages/core/lib/src/paymentgateway/dio_payment_gateway_service.dart`
- Test: `packages/core/test/models/shift_test.dart`
- Test: `packages/core/test/paymentgateway/dio_payment_gateway_service_test.dart`

**Interfaces:**
- Consumes: nothing new — this is the root of the change.
- Produces:
  - `class PosSession { const PosSession({required String token, required List<String> abilities, required String branchId, required String storeId}); final String token; final List<String> abilities; final String branchId; final String storeId; }`
  - `Shift` (freezed) fields: `id` (String, required), `branchId` (String, required, json `branch_id`), `storeId` (String, required, json `store_id`), `operatorId` (String?, json `operator_id`), `posId` (String?, json `pos_id`), `openedAt` (DateTime, required, json `opened_at`), `closedAt` (DateTime?, json `closed_at`), `countedAmountMinor` (int?, json `counted_amount_minor`), `discrepancyMinor` (int?, json `discrepancy_minor`).
  - `abstract class PaymentGatewayService`: `Future<PosSession> login({required String email, required String password, required String branchId})`, `Future<Shift> openShift({required String branchId})` — all other methods unchanged.
  - `class DioPaymentGatewayService implements PaymentGatewayService`: sends `'branch_id'` (not `'store_id'`) in both the `/pos/login` and `/shifts` request bodies.

- [ ] **Step 1: Update the failing test fixtures in `shift_test.dart`**

Edit `packages/core/test/models/shift_test.dart` to add `branch_id` to the fixture and assert on the new field:

```dart
import 'package:core/core.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('Shift round-trips through JSON, including nullable close fields', () {
    final openJson = {
      'id': 'sh1',
      'branch_id': 'b1',
      'store_id': 'st1',
      'operator_id': 'op1',
      'opened_at': '2026-08-04T12:00:00.000Z',
      'closed_at': null,
      'counted_amount_minor': null,
      'discrepancy_minor': null,
    };

    final open = Shift.fromJson(openJson);
    expect(open.branchId, 'b1');
    expect(open.storeId, 'st1');
    expect(open.closedAt, isNull);
    expect(open.discrepancyMinor, isNull);
    expect(Shift.fromJson(open.toJson()), open);

    final closedJson = {
      ...openJson,
      'closed_at': '2026-08-04T20:00:00.000Z',
      'counted_amount_minor': 50000,
      'discrepancy_minor': -500,
    };
    final closed = Shift.fromJson(closedJson);
    expect(closed.closedAt, DateTime.parse('2026-08-04T20:00:00.000Z'));
    expect(closed.discrepancyMinor, -500);
  });
}
```

- [ ] **Step 2: Run it to verify it fails**

Run from `packages/core`: `flutter test test/models/shift_test.dart`
Expected: FAIL — `The getter 'branchId' isn't defined for the type 'Shift'` (compile error, since the model doesn't have the field yet).

- [ ] **Step 3: Update `shift.dart`**

Replace the `@freezed` class body in `packages/core/lib/src/models/shift.dart`:

```dart
import 'package:freezed_annotation/freezed_annotation.dart';

part 'shift.freezed.dart';
part 'shift.g.dart';

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

- [ ] **Step 4: Regenerate the freezed/json_serializable code**

Run from `packages/core`: `dart run build_runner build --delete-conflicting-outputs`
Expected: `shift.freezed.dart` and `shift.g.dart` rewritten with no build errors.

- [ ] **Step 5: Run the test again to verify it passes**

Run from `packages/core`: `flutter test test/models/shift_test.dart`
Expected: PASS

- [ ] **Step 6: Update `payment_gateway_service.dart`**

Replace the `PosSession` class and the `login`/`openShift` signatures in `packages/core/lib/src/paymentgateway/payment_gateway_service.dart`:

```dart
import '../models/payment_reference.dart';
import '../models/shift.dart';
import '../models/transaction.dart';

/// A logged-in POS operator's session: the bearer token (already persisted
/// to [TokenStore] by the time this is returned) plus what it's scoped to.
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

/// Every call the POS app makes against the PaymentGateway API surface.
/// Abstract so widget tests can substitute a hand-written fake instead of
/// mocking HTTP — see `apps/pos/test/support/fake_payment_gateway_service.dart`.
abstract class PaymentGatewayService {
  Future<PosSession> login({required String email, required String password, required String branchId});

  Future<PaymentReference> lookupReference(String reference);

  Future<Transaction> collectPayment({
    required String paymentReferenceId,
    required int amount,
    required String currency,
  });

  Future<Shift> openShift({required String branchId});

  Future<Shift> closeShift({required String shiftId, required int countedAmountMinor});
}
```

- [ ] **Step 7: Update the failing test fixtures in `dio_payment_gateway_service_test.dart`**

Edit `packages/core/test/paymentgateway/dio_payment_gateway_service_test.dart`: the `login` test, `openShift` test, and `closeShift` test each need `branch_id` added to their response fixtures, and the `login`/`openShift` tests need to call the new signature and assert on the new request body key:

```dart
  test('login returns a PosSession and persists the token', () async {
    final tokens = FakeTokenStore();
    final adapter = FakeHttpAdapter(200, {
      'data': {
        'token': 'plain-text-token',
        'abilities': ['payment:lookup', 'payment:collect'],
        'branch_id': 'b1',
        'store_id': 'st1',
      },
    });
    final service = _serviceWith(adapter, tokens: tokens);

    final session = await service.login(email: 'op@example.test', password: 'secret', branchId: 'b1');

    expect(session.token, 'plain-text-token');
    expect(session.abilities, ['payment:lookup', 'payment:collect']);
    expect(session.branchId, 'b1');
    expect(session.storeId, 'st1');
    expect(tokens.written, 'plain-text-token');
    expect(adapter.lastRequest!.path, '/pos/login');
    expect(adapter.lastRequest!.method, 'POST');
    expect(adapter.lastRequest!.data, {'email': 'op@example.test', 'password': 'secret', 'branch_id': 'b1'});
  });
```

```dart
  test('openShift POSTs to /shifts', () async {
    final adapter = FakeHttpAdapter(200, {
      'data': {
        'id': 'sh1',
        'branch_id': 'b1',
        'store_id': 'st1',
        'operator_id': 'op1',
        'opened_at': '2026-08-04T12:00:00.000Z',
        'closed_at': null,
        'counted_amount_minor': null,
        'discrepancy_minor': null,
      },
    });
    final service = _serviceWith(adapter);

    final shift = await service.openShift(branchId: 'b1');

    expect(shift.id, 'sh1');
    expect(adapter.lastRequest!.path, '/shifts');
    expect(adapter.lastRequest!.data, {'branch_id': 'b1'});
  });
```

```dart
  test('closeShift PATCHes /shifts/{id} and returns the computed discrepancy', () async {
    final adapter = FakeHttpAdapter(200, {
      'data': {
        'id': 'sh1',
        'branch_id': 'b1',
        'store_id': 'st1',
        'operator_id': 'op1',
        'opened_at': '2026-08-04T12:00:00.000Z',
        'closed_at': '2026-08-04T20:00:00.000Z',
        'counted_amount_minor': 49500,
        'discrepancy_minor': -500,
      },
    });
    final service = _serviceWith(adapter);

    final shift = await service.closeShift(shiftId: 'sh1', countedAmountMinor: 49500);

    expect(shift.discrepancyMinor, -500);
    expect(adapter.lastRequest!.path, '/shifts/sh1');
    expect(adapter.lastRequest!.method, 'PATCH');
    expect(adapter.lastRequest!.data, {'counted_amount': 49500});
  });
```

(The `lookupReference`, `collectPayment`, and the 422-surfacing tests are untouched — leave them as they are in the file.)

- [ ] **Step 8: Run the tests to verify they fail**

Run from `packages/core`: `flutter test test/paymentgateway/dio_payment_gateway_service_test.dart`
Expected: FAIL — `No named parameter with the name 'branchId'` (compile error against the still-unchanged `dio_payment_gateway_service.dart`).

- [ ] **Step 9: Update `dio_payment_gateway_service.dart`**

In `packages/core/lib/src/paymentgateway/dio_payment_gateway_service.dart`, replace the `login` and `openShift` methods:

```dart
  @override
  Future<PosSession> login({
    required String email,
    required String password,
    required String branchId,
  }) async {
    final json = await _api.post<Map<String, dynamic>>(
      '/pos/login',
      (m) => m,
      body: {'email': email, 'password': password, 'branch_id': branchId},
    );
    final session = PosSession(
      token: json['token'] as String,
      abilities: (json['abilities'] as List).cast<String>(),
      branchId: json['branch_id'] as String,
      storeId: json['store_id'] as String,
    );
    await _tokens.write(session.token);
    return session;
  }
```

```dart
  @override
  Future<Shift> openShift({required String branchId}) {
    return _api.post('/shifts', Shift.fromJson, body: {'branch_id': branchId});
  }
```

Leave `lookupReference`, `collectPayment`, and `closeShift` unchanged.

- [ ] **Step 10: Run the tests to verify they pass**

Run from `packages/core`: `flutter test test/paymentgateway/dio_payment_gateway_service_test.dart`
Expected: PASS

- [ ] **Step 11: Run the full `packages/core` test suite**

Run from `packages/core`: `flutter test`
Expected: all PASS (this also re-runs `test/models/payment_reference_test.dart`, `test/models/transaction_test.dart`, `test/network/api_exception_test.dart`, none of which touch `Shift`/`PosSession`).

- [ ] **Step 12: Run analyze and commit**

Run from `packages/core`: `flutter analyze`
Expected: no issues.

```bash
git add packages/core/lib/src/models/shift.dart packages/core/lib/src/models/shift.freezed.dart packages/core/lib/src/models/shift.g.dart packages/core/lib/src/paymentgateway/payment_gateway_service.dart packages/core/lib/src/paymentgateway/dio_payment_gateway_service.dart packages/core/test/models/shift_test.dart packages/core/test/paymentgateway/dio_payment_gateway_service_test.dart
git commit -m "feat(core): migrate Shift/PosSession/PaymentGatewayService to branch_id"
```

---

### Task 2: `apps/pos` domain layer + shared test fake

**Files:**
- Modify: `apps/pos/lib/features/auth/domain/auth_state.dart`
- Modify: `apps/pos/lib/features/shift/domain/shift_state.dart`
- Modify: `apps/pos/test/support/fake_payment_gateway_service.dart`
- Modify: `apps/pos/test/features/shift/close_shift_screen_test.dart`
- Modify: `apps/pos/test/features/shift/shift_transactions_screen_test.dart`
- Modify: `apps/pos/CLAUDE.md`

**Interfaces:**
- Consumes: `PaymentGatewayService.login({..., required String branchId})`, `.openShift({required String branchId})`, `PosSession.branchId`/`.storeId`, `Shift.branchId` (all from Task 1).
- Produces:
  - `AuthNotifier.login({required String email, required String password, required String branchId})`
  - `ShiftNotifier.open({required String branchId})`
  - `FakePaymentGatewayService` conforming to the new `PaymentGatewayService` interface (same public fields as before: `loginResult`, `loginError`, `openShiftResult`, `openShiftError`, etc. — unchanged).

**Note:** after this task, `apps/pos/lib/features/auth/presentation/login_screen.dart` and `apps/pos/lib/features/shift/presentation/open_shift_screen.dart` will fail `flutter analyze` (they still call `.login(storeId: ...)` / `.open(storeId: ...)`) — that's expected and fixed in Task 3 and Task 4.

- [ ] **Step 1: Update `AuthNotifier`**

In `apps/pos/lib/features/auth/domain/auth_state.dart`, replace the `login` method:

```dart
import 'package:core/core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core_providers.dart';

class AuthNotifier extends Notifier<PosSession?> {
  @override
  PosSession? build() => null;

  Future<void> login({required String email, required String password, required String branchId}) async {
    final session = await ref.read(paymentGatewayServiceProvider).login(
          email: email,
          password: password,
          branchId: branchId,
        );
    state = session;
  }

  void logout() => state = null;
}

final authProvider = NotifierProvider<AuthNotifier, PosSession?>(AuthNotifier.new);
```

- [ ] **Step 2: Update `ShiftNotifier.open`**

In `apps/pos/lib/features/shift/domain/shift_state.dart`, replace the `open` method:

```dart
  Future<void> open({required String branchId}) async {
    final shift = await ref.read(paymentGatewayServiceProvider).openShift(branchId: branchId);
    state = ShiftState(shift: shift);
  }
```

(The rest of `shift_state.dart` — `ShiftState`, `recordTransaction`, `close`, `reset`, `shiftProvider` — is unchanged.)

- [ ] **Step 3: Update `FakePaymentGatewayService`**

In `apps/pos/test/support/fake_payment_gateway_service.dart`, update the two method signatures to match:

```dart
  @override
  Future<PosSession> login({required String email, required String password, required String branchId}) async {
    if (loginError != null) throw loginError!;
    return loginResult!;
  }
```

```dart
  @override
  Future<Shift> openShift({required String branchId}) async {
    if (openShiftError != null) throw openShiftError!;
    return openShiftResult!;
  }
```

(Every other field/method in the file — `lookupReference`, `collectPayment`, `closeShift`, and all the stored `*Result`/`*Error` fields — is unchanged.)

- [ ] **Step 4: Fix the `Shift(...)` literals in `close_shift_screen_test.dart`**

In `apps/pos/test/features/shift/close_shift_screen_test.dart`, add `branchId: 'b1',` to both `Shift(...)` constructions (the `_openShift()` helper and the `closeShiftResult`):

```dart
Shift _openShift() => Shift(
      id: 'sh1',
      branchId: 'b1',
      storeId: 's1',
      operatorId: 'op1',
      openedAt: DateTime.parse('2026-08-04T12:00:00.000Z'),
    );
```

```dart
    final fake = FakePaymentGatewayService(
      closeShiftResult: Shift(
        id: 'sh1',
        branchId: 'b1',
        storeId: 's1',
        operatorId: 'op1',
        openedAt: DateTime.parse('2026-08-04T12:00:00.000Z'),
        closedAt: DateTime.parse('2026-08-04T20:00:00.000Z'),
        countedAmountMinor: 49500,
        discrepancyMinor: -500,
      ),
    );
```

- [ ] **Step 5: Fix the `Shift(...)` literals in `shift_transactions_screen_test.dart`**

In `apps/pos/test/features/shift/shift_transactions_screen_test.dart`, add `branchId: 'b1',` to both `Shift(...)` constructions (one per `testWidgets` block):

```dart
    final shift = Shift(
      id: 'sh1',
      branchId: 'b1',
      storeId: 's1',
      operatorId: 'op1',
      openedAt: DateTime.parse('2026-08-04T12:00:00.000Z'),
    );
```

(Apply the same `branchId: 'b1',` addition to the second `Shift(...)` in the file's second `testWidgets` block — it has the identical shape.)

- [ ] **Step 6: Fix the stale Auth line in `apps/pos/CLAUDE.md`**

Change:
```
- Auth: Sanctum token scoped to a single store, carrying operator-level abilities only — `payment:lookup`, `payment:collect`, `transaction:read-own`, `shift:manage`. No admin-panel abilities.
```
to:
```
- Auth: Sanctum token scoped to a single branch, carrying operator-level abilities only — `payment:lookup`, `payment:collect`, `transaction:read-own`, `shift:manage`. No admin-panel abilities.
```

- [ ] **Step 7: Run the tests this task can make green**

Run from `apps/pos`:
```bash
flutter test test/features/shift/close_shift_screen_test.dart test/features/shift/shift_transactions_screen_test.dart
```
Expected: all PASS.

Also run the two tests that import the shared fake but don't touch `storeId`/`branchId`, to confirm no regression:
```bash
flutter test test/features/payment_reference/reference_lookup_screen_test.dart test/features/collect/collect_confirm_screen_test.dart
```
Expected: all PASS (unchanged behavior).

`flutter test test/features/auth/login_screen_test.dart` and `flutter test test/features/shift/open_shift_screen_test.dart` are expected to FAIL at this point (compile errors — those files still construct `PosSession(..., storeId: ...)` without the new required `branchId`) — that's Task 3 and Task 4.

- [ ] **Step 8: Commit**

```bash
git add apps/pos/lib/features/auth/domain/auth_state.dart apps/pos/lib/features/shift/domain/shift_state.dart apps/pos/test/support/fake_payment_gateway_service.dart apps/pos/test/features/shift/close_shift_screen_test.dart apps/pos/test/features/shift/shift_transactions_screen_test.dart apps/pos/CLAUDE.md
git commit -m "feat(pos): migrate AuthNotifier/ShiftNotifier and test fake to branch_id"
```

---

### Task 3: `apps/pos` — login screen

**Files:**
- Modify: `apps/pos/lib/features/auth/presentation/login_screen.dart`
- Test: `apps/pos/test/features/auth/login_screen_test.dart`

**Interfaces:**
- Consumes: `AuthNotifier.login({..., required String branchId})` (Task 2), `PosSession.branchId` (Task 1).
- Produces: `LoginScreen` with a `Key('login_branch_id')` field labeled "Branch ID" (was `Key('login_store_id')` / "Store ID").

- [ ] **Step 1: Update the failing test in `login_screen_test.dart`**

Replace both `testWidgets` bodies' session construction, field key, and assertion in `apps/pos/test/features/auth/login_screen_test.dart`:

```dart
void main() {
  testWidgets('successful login stores the session', (tester) async {
    final fake = FakePaymentGatewayService(
      loginResult: const PosSession(token: 't', abilities: ['payment:lookup'], branchId: 'b1', storeId: 's1'),
    );
    final container = await _pumpLogin(tester, fake);

    await tester.enterText(find.byKey(const Key('login_email')), 'op@example.test');
    await tester.enterText(find.byKey(const Key('login_password')), 'secret');
    await tester.enterText(find.byKey(const Key('login_branch_id')), 'b1');
    await tester.tap(find.byKey(const Key('login_submit')));
    await tester.pumpAndSettle();

    expect(container.read(authProvider)?.branchId, 'b1');
  });

  testWidgets('failed login shows the error and leaves the session empty', (tester) async {
    final fake = FakePaymentGatewayService(
      loginError: ApiException(statusCode: 422, message: 'These credentials do not match our records.'),
    );
    final container = await _pumpLogin(tester, fake);

    await tester.enterText(find.byKey(const Key('login_email')), 'op@example.test');
    await tester.enterText(find.byKey(const Key('login_password')), 'wrong');
    await tester.enterText(find.byKey(const Key('login_branch_id')), 'b1');
    await tester.tap(find.byKey(const Key('login_submit')));
    await tester.pumpAndSettle();

    expect(find.text('These credentials do not match our records.'), findsOneWidget);
    expect(container.read(authProvider), isNull);
  });
}
```

(The `_pumpLogin` helper and imports above `main()` are unchanged.)

- [ ] **Step 2: Run it to verify it fails**

Run from `apps/pos`: `flutter test test/features/auth/login_screen_test.dart`
Expected: FAIL — `Key('login_branch_id')` finds nothing (the screen still renders `Key('login_store_id')`).

- [ ] **Step 3: Update `login_screen.dart`**

In `apps/pos/lib/features/auth/presentation/login_screen.dart`, rename the controller, key, label, and the call into `authProvider.notifier.login`:

```dart
class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _branchId = TextEditingController();
  bool _loading = false;
  String? _error;

  Future<void> _submit() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await ref.read(authProvider.notifier).login(
            email: _email.text,
            password: _password.text,
            branchId: _branchId.text,
          );
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    _branchId.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const PosAppBar(title: 'Operator Login'),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: _email,
              key: const Key('login_email'),
              decoration: const InputDecoration(labelText: 'Email'),
            ),
            TextField(
              controller: _password,
              key: const Key('login_password'),
              decoration: const InputDecoration(labelText: 'Password'),
              obscureText: true,
            ),
            TextField(
              controller: _branchId,
              key: const Key('login_branch_id'),
              decoration: const InputDecoration(labelText: 'Branch ID'),
            ),
            const SizedBox(height: 16),
            if (_error != null) Text(_error!),
            FilledButton(
              key: const Key('login_submit'),
              onPressed: _loading ? null : _submit,
              child: _loading ? const CircularProgressIndicator() : const Text('Log in'),
            ),
          ],
        ),
      ),
    );
  }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run from `apps/pos`: `flutter test test/features/auth/login_screen_test.dart`
Expected: PASS

- [ ] **Step 5: Analyze and commit**

Run from `apps/pos`: `flutter analyze lib/features/auth test/features/auth`
Expected: no issues.

```bash
git add apps/pos/lib/features/auth/presentation/login_screen.dart apps/pos/test/features/auth/login_screen_test.dart
git commit -m "feat(pos): relabel login screen's store field to Branch ID"
```

---

### Task 4: `apps/pos` — open-shift screen

**Files:**
- Modify: `apps/pos/lib/features/shift/presentation/open_shift_screen.dart`
- Test: `apps/pos/test/features/shift/open_shift_screen_test.dart`

**Interfaces:**
- Consumes: `ShiftNotifier.open({required String branchId})` (Task 2), `PosSession.branchId` (Task 1), `Shift.branchId` (Task 1).
- Produces: `OpenShiftScreen` calling `shiftProvider.notifier.open(branchId: session.branchId)`.

- [ ] **Step 1: Update the failing test in `open_shift_screen_test.dart`**

Replace both `testWidgets` bodies in `apps/pos/test/features/shift/open_shift_screen_test.dart` — the `Shift(...)` fixture and both `PosSession(...)` seeds need `branchId`:

```dart
void main() {
  testWidgets('opening a shift stores it in shiftProvider', (tester) async {
    final fake = FakePaymentGatewayService(
      openShiftResult: Shift(
        id: 'sh1',
        branchId: 'b1',
        storeId: 's1',
        operatorId: 'op1',
        openedAt: DateTime.parse('2026-08-04T12:00:00.000Z'),
      ),
    );
    final container = ProviderContainer(
      overrides: [
        paymentGatewayServiceProvider.overrideWithValue(fake),
        authProvider.overrideWith(
          () => _SeededAuthNotifier(const PosSession(token: 't', abilities: [], branchId: 'b1', storeId: 's1')),
        ),
      ],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: const MaterialApp(home: OpenShiftScreen()),
      ),
    );

    await tester.tap(find.byKey(const Key('open_shift_submit')));
    await tester.pumpAndSettle();

    expect(container.read(shiftProvider).shift?.id, 'sh1');
  });

  testWidgets('a failed open shows the error', (tester) async {
    final fake = FakePaymentGatewayService(
      openShiftError: ApiException(statusCode: 403, message: 'Not assigned to this store.'),
    );
    final container = ProviderContainer(
      overrides: [
        paymentGatewayServiceProvider.overrideWithValue(fake),
        authProvider.overrideWith(
          () => _SeededAuthNotifier(const PosSession(token: 't', abilities: [], branchId: 'b1', storeId: 's1')),
        ),
      ],
    );
    addTearDown(container.dispose);

    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: const MaterialApp(home: OpenShiftScreen()),
      ),
    );

    await tester.tap(find.byKey(const Key('open_shift_submit')));
    await tester.pumpAndSettle();

    expect(find.text('Not assigned to this store.'), findsOneWidget);
    expect(container.read(shiftProvider).shift, isNull);
  });
}
```

(The `_SeededAuthNotifier` class and imports above `main()` are unchanged. The error-message text itself — "Not assigned to this store." — is server copy the fake is echoing, not something this migration changes; leave it as-is.)

- [ ] **Step 2: Run it to verify it fails**

Run from `apps/pos`: `flutter test test/features/shift/open_shift_screen_test.dart`
Expected: FAIL — compile error, `open_shift_screen.dart` still calls `shiftProvider.notifier.open(storeId: session.storeId)` which no longer matches `ShiftNotifier.open`'s `branchId`-only signature from Task 2.

- [ ] **Step 3: Update `open_shift_screen.dart`**

In `apps/pos/lib/features/shift/presentation/open_shift_screen.dart`, update the `_open` method:

```dart
  Future<void> _open() async {
    final session = ref.read(authProvider);
    if (session == null) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await ref.read(shiftProvider.notifier).open(branchId: session.branchId);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
```

(The rest of the file — imports, class declaration, `build()` — is unchanged.)

- [ ] **Step 4: Run the test to verify it passes**

Run from `apps/pos`: `flutter test test/features/shift/open_shift_screen_test.dart`
Expected: PASS

- [ ] **Step 5: Analyze and commit**

Run from `apps/pos`: `flutter analyze lib/features/shift test/features/shift`
Expected: no issues.

```bash
git add apps/pos/lib/features/shift/presentation/open_shift_screen.dart apps/pos/test/features/shift/open_shift_screen_test.dart
git commit -m "feat(pos): open shifts against the operator's branch"
```

---

### Task 5: Full-repo verification

**Files:** none (verification only).

**Interfaces:**
- Consumes: everything produced by Tasks 1–4.
- Produces: nothing new — this task's only job is to confirm the migration is complete and nothing else regressed.

- [ ] **Step 1: Analyze `packages/core`**

Run from `packages/core`: `flutter analyze`
Expected: no issues.

- [ ] **Step 2: Analyze `apps/pos`**

Run from `apps/pos`: `flutter analyze`
Expected: no issues. (This is the first point in the plan where the *entire* `apps/pos` tree — not just the files touched so far — is checked at once; it will catch any stray `storeId` reference this plan's file list missed.)

- [ ] **Step 3: Run the full `packages/core` test suite**

Run from `packages/core`: `flutter test`
Expected: all PASS.

- [ ] **Step 4: Run the full `apps/pos` test suite**

Run from `apps/pos`: `flutter test`
Expected: all PASS.

- [ ] **Step 5: Grep for any remaining `store_id`/`storeId` reference in source (not test fixtures for unrelated resources)**

Run from the repo root:
```bash
grep -rn "storeId\|store_id" packages/core/lib apps/pos/lib
```
Expected: no output (every production-code reference to `storeId`/`store_id` was either removed or is now the intentionally-kept denormalized `PosSession.storeId` / `Shift.storeId` field from Task 1 — re-check any hit against the design spec before treating it as a bug).

No commit in this task — it's a checkpoint. If any step fails, fix it as part of whichever earlier task actually owns the broken file, then re-run this task's steps.

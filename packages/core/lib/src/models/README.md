# models

Shared DTOs every app deserializes into. Rules (from the API contract):
- `id` is a **String** — the Hashid. Never an int.
- Money uses `Money` (integer minor units + currency), never a double.
- Dates are UTC `DateTime`.
- Use **freezed + json_serializable**; add `part 'x.freezed.dart';` and
  `part 'x.g.dart';`, then run `dart run build_runner build --delete-conflicting-outputs`.

Add one file per resource here (e.g. `product.dart`) and export it from `../../core.dart`.
`paginated.dart` and `money.dart` are hand-written (no codegen) on purpose.

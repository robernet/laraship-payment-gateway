# packages/core

Shared Dart package every app in `apps/` depends on. Build the API plumbing
**once** here — never per app.

```
lib/
├── core.dart                      # barrel: import 'package:core/core.dart';
└── src/
    ├── network/
    │   ├── api_client.dart        # Dio + Sanctum bearer + envelope + 401 handling
    │   └── api_exception.dart     # typed { message, errors } error
    ├── auth/
    │   ├── auth_service.dart      # login / logout (Sanctum tokens)
    │   └── token_store.dart       # secure storage for the bearer token
    └── models/
        ├── paginated.dart         # { data, meta } list wrapper
        ├── money.dart             # integer minor units + currency
        └── README.md              # how to add freezed resource models
```

## Use it from an app
`apps/<app>/pubspec.yaml`:
```yaml
dependencies:
  core:
    path: ../../packages/core
```
Then:
```dart
import 'package:core/core.dart';

final tokens = TokenStore();
final api = ApiClient.create(tokens: tokens, onUnauthorized: () {/* route to login */});
final products = await api.getList('/products', Product.fromJson);
```

## Before first use
- `flutter pub get` (bump the dependency versions in `pubspec.yaml` if your Flutter SDK is newer).
- Add resource models under `src/models/` (freezed) and run
  `dart run build_runner build --delete-conflicting-outputs`.
- Confirm the auth route/field in `auth_service.dart` matches your Laraship setup.

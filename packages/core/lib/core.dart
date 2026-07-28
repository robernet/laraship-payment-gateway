/// Shared client, auth, and models for every app in this workspace.
///
/// Usage in an app:  import 'package:core/core.dart';
library core;

export 'src/network/api_client.dart';
export 'src/network/api_exception.dart';
export 'src/models/paginated.dart';
export 'src/models/money.dart';
export 'src/auth/auth_service.dart';
export 'src/auth/token_store.dart';

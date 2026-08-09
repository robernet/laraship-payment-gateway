import 'package:core/core.dart';

/// Overrides every method so no test touches the real secure-storage
/// platform channel.
class FakeTokenStore extends TokenStore {
  String? written;

  @override
  Future<String?> read() async => written;

  @override
  Future<void> write(String token) async => written = token;

  @override
  Future<void> clear() async => written = null;
}

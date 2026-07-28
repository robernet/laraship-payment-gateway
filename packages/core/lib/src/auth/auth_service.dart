import '../network/api_client.dart';
import 'token_store.dart';

/// Sanctum token auth. Login issues a personal access token server-side (via
/// `createToken`); we store the returned plain-text token and attach it to
/// every request. Logout revokes it server-side, then clears local storage.
class AuthService {
  AuthService({required ApiClient api, required TokenStore tokens})
      : _api = api,
        _tokens = tokens;

  final ApiClient _api;
  final TokenStore _tokens;

  Future<bool> get isAuthenticated async => (await _tokens.read()) != null;

  /// POSTs credentials and stores the returned token.
  /// Adjust the path/field to your Laraship auth route — commonly `/login`
  /// with the token at `data.token`.
  Future<void> login({required String email, required String password}) async {
    final res = await _api.raw.post('/login', data: {
      'email': email,
      'password': password,
    });
    final token = _extractToken(res.data);
    if (token == null) {
      throw StateError('Login response did not contain a token');
    }
    await _tokens.write(token);
  }

  /// Revokes the current token server-side, then clears it locally.
  Future<void> logout() async {
    try {
      await _api.raw.post('/logout');
    } finally {
      await _tokens.clear();
    }
  }

  String? _extractToken(Object? body) {
    if (body is Map) {
      final data = body['data'];
      if (data is Map && data['token'] is String) return data['token'] as String;
      if (body['token'] is String) return body['token'] as String;
    }
    return null;
  }
}

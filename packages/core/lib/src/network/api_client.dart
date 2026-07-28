import 'package:dio/dio.dart';

import '../auth/token_store.dart';
import '../models/paginated.dart';
import 'api_exception.dart';

/// Thin wrapper over Dio for the Laraship API.
/// - base URL from `--dart-define=API_BASE`, versioned at `/api/v1`
/// - attaches the Sanctum bearer token on every request
/// - unwraps the `{ data, meta }` envelope
/// - maps `{ message, errors }` to [ApiException] (callers never see DioException)
/// - on 401: clears the token and calls [onUnauthorized] — Sanctum tokens do
///   not refresh, so the app re-authenticates.
class ApiClient {
  ApiClient._(this._dio);

  final Dio _dio;

  /// Escape hatch for non-enveloped endpoints (e.g. auth). Prefer the typed
  /// helpers below for resource calls.
  Dio get raw => _dio;

  static const _envBase = String.fromEnvironment('API_BASE', defaultValue: '');

  factory ApiClient.create({
    required TokenStore tokens,
    String? baseUrl,
    void Function()? onUnauthorized,
  }) {
    final dio = Dio(BaseOptions(
      baseUrl: '${baseUrl ?? _envBase}/api/v1',
      headers: const {'Accept': 'application/json'},
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 20),
    ));

    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await tokens.read();
        if (token != null) options.headers['Authorization'] = 'Bearer $token';
        handler.next(options);
      },
      onError: (e, handler) async {
        if (e.response?.statusCode == 401) {
          await tokens.clear();
          onUnauthorized?.call();
        }
        handler.next(e); // _run() maps it to ApiException
      },
    ));

    return ApiClient._(dio);
  }

  Future<T> getOne<T>(
    String path,
    T Function(Map<String, dynamic>) fromJson, {
    Map<String, dynamic>? query,
  }) async {
    final res = await _run(() => _dio.get(path, queryParameters: query));
    return fromJson(_data(res.data));
  }

  Future<Paginated<T>> getList<T>(
    String path,
    T Function(Map<String, dynamic>) fromItem, {
    Map<String, dynamic>? query,
  }) async {
    final res = await _run(() => _dio.get(path, queryParameters: query));
    return Paginated<T>.fromJson(res.data as Map<String, dynamic>, fromItem);
  }

  Future<T> post<T>(
    String path,
    T Function(Map<String, dynamic>) fromJson, {
    Object? body,
  }) async {
    final res = await _run(() => _dio.post(path, data: body));
    return fromJson(_data(res.data));
  }

  Future<T> patch<T>(
    String path,
    T Function(Map<String, dynamic>) fromJson, {
    Object? body,
  }) async {
    final res = await _run(() => _dio.patch(path, data: body));
    return fromJson(_data(res.data));
  }

  Future<void> delete(String path) async {
    await _run(() => _dio.delete(path));
  }

  Future<Response> _run(Future<Response> Function() call) async {
    try {
      return await call();
    } on DioException catch (e) {
      throw ApiException.fromResponse(e.response?.statusCode, e.response?.data);
    }
  }

  /// Unwraps the `data` object from a single-resource envelope.
  Map<String, dynamic> _data(Object? body) {
    if (body is Map<String, dynamic> && body['data'] is Map<String, dynamic>) {
      return body['data'] as Map<String, dynamic>;
    }
    if (body is Map<String, dynamic>) return body;
    return <String, dynamic>{};
  }
}

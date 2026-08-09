/// Typed error mapped from the API's `{ "message", "errors" }` envelope.
///
/// Every [ApiClient] call throws this (never a raw DioException), so callers
/// can branch on [isValidation] / [isUnauthorized] and read field errors.
class ApiException implements Exception {
  ApiException({
    required this.statusCode,
    required this.message,
    this.errors = const {},
  });

  final int? statusCode;
  final String message;

  /// Field -> validation messages, from a 422 response.
  final Map<String, List<String>> errors;

  bool get isValidation => statusCode == 422;
  bool get isUnauthorized => statusCode == 401;
  bool get isForbidden => statusCode == 403;
  bool get isNotFound => statusCode == 404;

  /// First validation message for [field], if any.
  String? firstError(String field) =>
      (errors[field]?.isNotEmpty ?? false) ? errors[field]!.first : null;

  factory ApiException.fromResponse(int? status, Object? body) {
    if (body is Map<String, dynamic>) {
      final parsed = <String, List<String>>{};
      // Laravel's apiExceptionResponse() nests validation errors under
      // `data.errors`, not top-level `errors` as docs/api-contract.md
      // describes - a platform-wide (vendor, non-module) response shape, so
      // this reads the real one rather than the documented one.
      final data = body['data'];
      final raw = body['errors'] ?? (data is Map ? data['errors'] : null);
      if (raw is Map) {
        raw.forEach((key, value) {
          if (value is List) {
            parsed['$key'] = value.map((e) => '$e').toList();
          } else if (value != null) {
            parsed['$key'] = ['$value'];
          }
        });
      }
      return ApiException(
        statusCode: status,
        message: (body['message'] as String?) ?? 'Request failed',
        errors: parsed,
      );
    }
    return ApiException(statusCode: status, message: 'Request failed');
  }

  @override
  String toString() => 'ApiException($statusCode): $message';
}

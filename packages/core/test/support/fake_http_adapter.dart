import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';

/// Canned-response [HttpClientAdapter] — avoids pulling in an HTTP mocking
/// package for these unit tests. Records the last request for assertions.
class FakeHttpAdapter implements HttpClientAdapter {
  FakeHttpAdapter(this.statusCode, this.body);

  final int statusCode;
  final Map<String, dynamic> body;
  RequestOptions? lastRequest;

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    lastRequest = options;
    return ResponseBody.fromString(
      jsonEncode(body),
      statusCode,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

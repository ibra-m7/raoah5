import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../config/env_config.dart';
import '../error/exceptions.dart';
import 'api_exception.dart';

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  String? authToken;
  String? _workingBase;

  Map<String, String> _headers({bool auth = false}) {
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (auth && authToken != null && authToken!.isNotEmpty)
        'Authorization': 'Bearer $authToken',
    };
  }

  Future<Map<String, dynamic>> post(
    String path,
    Map<String, dynamic> body, {
    bool auth = false,
    Duration? timeout,
  }) {
    return _send(
      path,
      (uri) => http.post(
        uri,
        headers: _headers(auth: auth),
        body: jsonEncode(body),
      ),
      timeout: timeout,
    );
  }

  Future<Map<String, dynamic>> get(
    String path, {
    bool auth = true,
    Map<String, dynamic>? query,
    Duration? timeout,
  }) {
    return _send(
      path,
      (uri) => http.get(
        _withQuery(uri, query),
        headers: _headers(auth: auth),
      ),
      timeout: timeout,
    );
  }

  Future<Map<String, dynamic>> patch(
    String path,
    Map<String, dynamic> body, {
    bool auth = true,
  }) {
    return _send(
      path,
      (uri) => http.patch(
        uri,
        headers: _headers(auth: auth),
        body: jsonEncode(body),
      ),
    );
  }

  Future<Map<String, dynamic>> delete(
    String path, {
    bool auth = true,
    Map<String, dynamic>? query,
  }) {
    return _send(
      path,
      (uri) => http.delete(
        _withQuery(uri, query),
        headers: _headers(auth: auth),
      ),
    );
  }

  Uri _uri(String base, String path) {
    final normalized = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$base$normalized');
  }

  Uri _withQuery(Uri uri, Map<String, dynamic>? query) {
    if (query == null || query.isEmpty) return uri;
    final cleaned = <String, String>{};
    query.forEach((key, value) {
      if (value == null) return;
      final text = value.toString().trim();
      if (text.isEmpty) return;
      cleaned[key] = text;
    });
    return cleaned.isEmpty ? uri : uri.replace(queryParameters: {
      ...uri.queryParameters,
      ...cleaned,
    });
  }

  Future<Map<String, dynamic>> _send(
    String path,
    Future<http.Response> Function(Uri uri) request, {
    Duration? timeout,
  }) async {
    final bases = _workingBase != null
        ? <String>[_workingBase!, ...EnvConfig.apiBaseUrls]
        : EnvConfig.apiBaseUrls;
    final tried = <String>{};

    Object? lastError;
    for (final base in bases) {
      if (!tried.add(base)) continue;
      try {
        final json = await _sendOnce(
          request(_uri(base, path)),
          timeout: timeout,
        );
        if (_shouldTryNextBase(path, json) && bases.any((b) => !tried.contains(b))) {
          continue;
        }
        _workingBase = base;
        return json;
      } on TimeoutException catch (e) {
        lastError = e;
      } on SocketException catch (e) {
        lastError = e;
      } on HttpException catch (e) {
        lastError = e;
      }
    }

    throw NetworkException(
      message: lastError is TimeoutException
          ? 'انتهت مهلة الاتصال بالخادم. حاول مجدداً.'
          : 'تعذّر الاتصال بالخادم. تحقق من الإنترنت ثم حدّث الصفحة.',
    );
  }

  bool _shouldTryNextBase(String path, Map<String, dynamic> json) {
    final normalized = path.startsWith('/') ? path : '/$path';
    if (normalized != '/home') return false;

    final data = json['data'];
    if (data is! Map) return false;

    final products = data['products'];
    final categories = data['categories'];
    final displaySections = data['display_sections'];
    final hasProducts = products is List && products.isNotEmpty;
    final hasCategories = categories is List && categories.isNotEmpty;
    final hasDisplay =
        displaySections is List && displaySections.isNotEmpty;

    return !hasProducts && !hasCategories && !hasDisplay;
  }

  Future<Map<String, dynamic>> _sendOnce(
    Future<http.Response> request, {
    Duration? timeout,
  }) async {
    final response = await request.timeout(timeout ?? const Duration(seconds: 45));

    Map<String, dynamic> json = {};
    if (response.body.isNotEmpty) {
      try {
        final decoded = jsonDecode(response.body);
        if (decoded is Map<String, dynamic>) {
          json = decoded;
        }
      } catch (_) {
        throw const ServerException(message: 'استجابة غير صالحة من الخادم.');
      }
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return json;
    }

    final message = _extractMessage(json) ??
        (response.statusCode == 401
            ? 'انتهت الجلسة. سجّل دخولك مجدداً.'
            : 'حدث خطأ غير متوقع. حاول مجدداً.');

    throw ApiException(message, statusCode: response.statusCode);
  }

  String? _extractMessage(Map<String, dynamic> json) {
    final message = json['message'];
    if (message is String && message.trim().isNotEmpty) {
      return message;
    }

    final errors = json['errors'];
    if (errors is Map && errors.isNotEmpty) {
      final first = errors.values.first;
      if (first is List && first.isNotEmpty) {
        return first.first.toString();
      }
      if (first is String) return first;
    }

    return null;
  }
}

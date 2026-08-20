import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import '../config/env_config.dart';
import '../error/exceptions.dart';
import 'api_exception.dart';

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  String? authToken;
  String? _workingBase;

  VoidCallback? onUnauthorized;

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
  }) {
    return _send(
      path,
      (uri) => http.post(
        uri,
        headers: _headers(auth: auth),
        body: jsonEncode(body),
      ),
      auth: auth,
    );
  }

  Future<Map<String, dynamic>> get(String path, {bool auth = true}) {
    return _send(
      path,
      (uri) => http.get(uri, headers: _headers(auth: auth)),
      auth: auth,
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
      auth: auth,
    );
  }

  Uri _uri(String base, String path) {
    final normalized = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$base$normalized');
  }

  Future<Map<String, dynamic>> _send(
    String path,
    Future<http.Response> Function(Uri uri) request, {
    bool auth = false,
  }) async {
    final bases = _workingBase != null
        ? <String>[_workingBase!, ...EnvConfig.apiBaseUrls]
        : EnvConfig.apiBaseUrls;
    final tried = <String>{};

    Object? lastError;
    for (final base in bases) {
      if (!tried.add(base)) continue;
      try {
        final json = await _sendOnce(request(_uri(base, path)), auth: auth);
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

    final detail = lastError == null ? '' : ' (${lastError.runtimeType})';
    throw NetworkException(
      message:
          'تعذّر الاتصال بالخادم. تأكد أن الهاتف والكمبيوتر على نفس الواي فاي وأن الباك اند يعمل.$detail',
    );
  }

  Future<Map<String, dynamic>> _sendOnce(
    Future<http.Response> request, {
    bool auth = false,
  }) async {
    final response = await request.timeout(const Duration(seconds: 25));
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

    if (response.statusCode == 401 && auth && authToken != null) {
      final callback = onUnauthorized;
      if (callback != null) {
        Future.microtask(callback);
      }
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

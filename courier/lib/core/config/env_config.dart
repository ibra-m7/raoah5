import 'package:flutter_dotenv/flutter_dotenv.dart';

class EnvConfig {
  EnvConfig._();

  static String _trimBase(String url) {
    final trimmed = url.trim();
    return trimmed.endsWith('/')
        ? trimmed.substring(0, trimmed.length - 1)
        : trimmed;
  }

  static String get apiBaseUrl {
    return _trimBase(
      dotenv.env['API_BASE_URL'] ?? 'http://192.168.8.122:8088/api',
    );
  }

  static List<String> get apiBaseUrls {
    return {
      apiBaseUrl,
      'http://192.168.8.122:8088/api',
      'http://127.0.0.1:8000/api',
      'http://10.0.2.2:8000/api',
    }.toList();
  }
}

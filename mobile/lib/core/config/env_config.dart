import 'package:flutter_dotenv/flutter_dotenv.dart';

/// نقطة وصول مركزية لمتغيرات البيئة
class EnvConfig {
  EnvConfig._();

  static String get geminiApiKey {
    final key = dotenv.env['GEMINI_API_KEY'] ?? '';
    assert(
      key.isNotEmpty && key != 'your_gemini_api_key_here',
      '\n\n⚠️  GEMINI_API_KEY غير مُعيَّن في ملف .env\n'
      'أضف مفتاحك من: https://aistudio.google.com/app/apikey\n',
    );
    return key;
  }

  static bool get isConfigured {
    final key = dotenv.env['GEMINI_API_KEY'] ?? '';
    return key.isNotEmpty && key != 'your_gemini_api_key_here';
  }

  static String _trimBase(String url) {
    final trimmed = url.trim();
    return trimmed.endsWith('/')
        ? trimmed.substring(0, trimmed.length - 1)
        : trimmed;
  }

  /// عنوان الـ API بدون شرطة أخيرة.
  static String get apiBaseUrl {
    return _trimBase(
      dotenv.env['API_BASE_URL'] ?? 'http://192.168.8.122:8088/api',
    );
  }

  /// عناوين بديلة: الشبكة المحلية أولاً، ثم USB، ثم المحاكي.
  static List<String> get apiBaseUrls {
    return {
      apiBaseUrl,
      'http://192.168.8.122:8088/api',
      'http://127.0.0.1:8000/api',
      'http://10.0.2.2:8000/api',
    }.toList();
  }
}

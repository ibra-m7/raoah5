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
    // أزل المسافات الزائدة داخل الرابط (مثل http:// 192...)
    final cleaned = url.trim().replaceAll(RegExp(r'\s+'), '');
    return cleaned.endsWith('/')
        ? cleaned.substring(0, cleaned.length - 1)
        : cleaned;
  }

  /// الشبكة المحلية (نفس الواي فاي).
  static const localApi = 'http://172.20.2.63:8088/api';

  /// الإنتاج على Render.
  static const remoteApi = 'https://raoah5.onrender.com/api';

  /// العنوان المفضّل من `.env` — إن وُجد، وإلا Render.
  static String get apiBaseUrl {
    return _trimBase(dotenv.env['API_BASE_URL'] ?? remoteApi);
  }

  /// ترتيب المحاولة: `.env` ثم Render ثم المحلي.
  static List<String> get apiBaseUrls {
    return {
      apiBaseUrl,
      remoteApi,
      localApi,
    }.toList();
  }
}

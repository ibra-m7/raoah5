import 'package:flutter_dotenv/flutter_dotenv.dart';

/// نقطة وصول مركزية لمتغيرات البيئة وعنوان الـ API.
class EnvConfig {
  EnvConfig._();

  /// IP سيرفر AWS الحالي (Laragon — بدون دومين).
  static const productionHost = '16.171.249.18';

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
    final cleaned = url.trim().replaceAll(RegExp(r'\s+'), '');
    return cleaned.endsWith('/')
        ? cleaned.substring(0, cleaned.length - 1)
        : cleaned;
  }

  /// الشبكة المحلية (نفس الواي فاي) — للتطوير فقط.
  static const localApi = 'http://172.20.2.95:8088/api';

  /// الإنتاج على AWS EC2.
  static const remoteApi = 'http://$productionHost/api';

  /// العنوان من `.env` — وإلا سيرفر AWS.
  static String get apiBaseUrl {
    return _trimBase(dotenv.env['API_BASE_URL'] ?? remoteApi);
  }

  /// أصل السيرفر الفعلي بعد أول اتصال API ناجح.
  static String? _resolvedOrigin;

  static void noteWorkingApiBase(String base) {
    final trimmed = _trimBase(base);
    if (trimmed.isEmpty) {
      _resolvedOrigin = null;
      return;
    }
    final uri = Uri.tryParse(trimmed);
    if (uri == null || uri.host.isEmpty) return;
    _resolvedOrigin = uri.replace(path: '', query: null, fragment: null).toString();
  }

  /// أصل السيرفر بدون `/api` — لروابط الصور `/storage/...`.
  static String get apiOrigin {
    if (_resolvedOrigin != null && _resolvedOrigin!.isNotEmpty) {
      return _resolvedOrigin!;
    }
    final uri = Uri.tryParse(apiBaseUrl);
    if (uri == null || uri.host.isEmpty) return 'http://$productionHost';
    return uri.replace(path: '', query: null, fragment: null).toString();
  }

  static bool get usesProductionServer {
    final host = Uri.tryParse(apiBaseUrl)?.host ?? '';
    return host == productionHost;
  }

  /// في الإنتاج: سيرفر AWS فقط. محلياً: `.env` ثم AWS ثم LAN.
  static List<String> get apiBaseUrls {
    final primary = apiBaseUrl;
    if (usesProductionServer) {
      return [primary];
    }
    return {
      primary,
      remoteApi,
      localApi,
    }.toList();
  }
}

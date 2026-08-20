class ServerException implements Exception {
  final String message;
  const ServerException({this.message = 'حدث خطأ في الخادم'});
}

class CacheException implements Exception {
  final String message;
  const CacheException({this.message = 'خطأ في التخزين المحلي'});
}

class NetworkException implements Exception {
  final String message;
  const NetworkException({this.message = 'لا يوجد اتصال بالإنترنت'});
}

class GeminiException implements Exception {
  final String message;
  const GeminiException({this.message = 'خطأ في خدمة الذكاء الاصطناعي'});
}

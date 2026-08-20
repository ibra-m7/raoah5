class ServerException implements Exception {
  final String message;
  const ServerException({this.message = 'حدث خطأ في الخادم'});
}

class NetworkException implements Exception {
  final String message;
  const NetworkException({this.message = 'لا يوجد اتصال بالإنترنت'});
}

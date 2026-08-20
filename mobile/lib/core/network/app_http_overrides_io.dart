import 'dart:io';

void installAppHttpOverrides() {
  HttpOverrides.global = _AppHttpOverrides();
}

class _AppHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) {
    return super.createHttpClient(context)
      ..userAgent =
          'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36'
      ..maxConnectionsPerHost = 6
      ..idleTimeout = const Duration(seconds: 20)
      ..connectionTimeout = const Duration(seconds: 12);
  }
}

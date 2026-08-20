abstract class NetworkInfo {
  Future<bool> get isConnected;
}

class NetworkInfoImpl implements NetworkInfo {
  // يمكن استخدام حزمة connectivity_plus هنا
  @override
  Future<bool> get isConnected async => true;
}

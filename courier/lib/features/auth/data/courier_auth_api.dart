import '../../../core/network/api_client.dart';
import 'courier_session.dart';
import 'courier_user.dart';

class CourierAuthApi {
  CourierAuthApi._();
  static final CourierAuthApi instance = CourierAuthApi._();

  final _client = ApiClient.instance;

  Map<String, dynamic> _data(Map<String, dynamic> json) {
    final data = json['data'];
    return data is Map<String, dynamic> ? data : json;
  }

  CourierUser _userFrom(Map<String, dynamic> data) {
    final raw = data['courier'];
    if (raw is Map<String, dynamic>) {
      return CourierUser.fromJson(raw);
    }
    return CourierUser.fromJson(data);
  }

  Future<CourierUser> login({
    required String phone,
    required String password,
  }) async {
    final json = await _client.post('/courier/login', {
      'phone': phone,
      'password': password,
    });
    final data = _data(json);
    final token = (data['token'] ?? '').toString();
    final user = _userFrom(data);
    await CourierSession.instance.save(token: token, user: user);
    return user;
  }

  Future<CourierUser> me() async {
    final json = await _client.get('/courier/me');
    final user = _userFrom(_data(json));
    await CourierSession.instance.updateUser(user);
    return user;
  }

  Future<CourierUser> setOnline(bool online) async {
    final json = await _client.patch('/courier/availability', {
      'is_online': online,
    });
    final user = _userFrom(_data(json));
    await CourierSession.instance.updateUser(user);
    return user;
  }

  Future<CourierAccount> account() async {
    final json = await _client.get('/courier/account');
    final data = _data(json);
    final user = _userFrom(data);
    await CourierSession.instance.updateUser(user);
    final raw = data['entries'];
    final entries = raw is List
        ? raw
            .whereType<Map>()
            .map((item) => CourierLedgerEntry.fromJson(Map<String, dynamic>.from(item)))
            .toList()
        : const <CourierLedgerEntry>[];
    return CourierAccount(user: user, entries: entries);
  }

  Future<void> logout() async {
    try {
      await _client.post('/courier/logout', {}, auth: true);
    } catch (_) {}
    await CourierSession.instance.clear();
  }
}

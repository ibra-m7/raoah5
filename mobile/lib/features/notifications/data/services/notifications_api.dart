import '../../../../core/network/api_client.dart';
import '../../../../core/storage/offline_cache.dart';
import '../../../auth/data/models/auth_user.dart';
import '../../../auth/data/services/auth_session.dart';
import '../../../shop/data/models/category_model.dart';
import '../models/app_notification.dart';

class NotificationsPage {
  final List<AppNotification> items;
  final int unreadCount;

  const NotificationsPage({required this.items, required this.unreadCount});
}

class NotificationsApi {
  NotificationsApi._();
  static final NotificationsApi instance = NotificationsApi._();

  final _client = ApiClient.instance;
  final _cache = OfflineCache.instance;

  Future<NotificationsPage> list({int page = 1}) async {
    try {
      final json = await _client.get('/notifications', query: {'page': page});
      final items = jsonMapList(json['data'], AppNotification.fromJson);
      final meta = json['meta'];
      final unread =
          meta is Map ? (meta['unread_count'] as num?)?.toInt() ?? 0 : 0;
      if (page <= 1) {
        await _cache.saveList(
          OfflineCache.notifications,
          json['data'] is List ? json['data'] as List : const [],
        );
        await _cache.saveInt(OfflineCache.notificationsUnread, unread);
      }
      return NotificationsPage(items: items, unreadCount: unread);
    } catch (_) {
      if (page > 1) rethrow;
      final cached = await _cache.readList(OfflineCache.notifications);
      if (cached == null) rethrow;
      final unread = await _cache.readInt(OfflineCache.notificationsUnread);
      return NotificationsPage(
        items: jsonMapList(cached, AppNotification.fromJson),
        unreadCount: unread,
      );
    }
  }

  Future<void> markRead(String id) async {
    await _client.patch('/notifications/$id/read', {});
  }

  Future<void> markAllRead() async {
    await _client.post('/notifications/read-all', {}, auth: true);
  }

  Future<void> setEnabled(bool enabled) async {
    final json = await _client.patch('/auth/notifications', {'enabled': enabled});
    final data = (json['data'] as Map?)?.cast<String, dynamic>() ?? {};
    final userMap = (data['user'] as Map?)?.cast<String, dynamic>();
    if (userMap != null) {
      await AuthSession.instance.updateUser(AuthUser.fromJson(userMap));
    }
  }

  Future<void> registerToken(String token, String platform) async {
    await _client.post('/device-tokens', {
      'token': token,
      'platform': platform,
    }, auth: true);
  }

  Future<void> unregisterToken(String? token) async {
    await _client.delete(
      '/device-tokens',
      query: {if (token != null && token.isNotEmpty) 'token': token},
    );
  }
}

import 'dart:async';
import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../../../../core/router/app_router.dart';
import '../../../../firebase_options.dart';
import '../../../auth/data/services/auth_session.dart';
import '../../presentation/manager/notifications_cubit.dart';
import 'notifications_api.dart';

const _kChannelId = 'raoah_default';
const _kChannelName = 'تنبيهات روعة الخمسة';

Future<void> ensureFirebaseApp() async {
  if (Firebase.apps.isNotEmpty) return;
  try {
    await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  } on FirebaseException catch (e) {
    if (e.code == 'duplicate-app') return;
    rethrow;
  }
}

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await ensureFirebaseApp();
  if (message.notification != null) return;
  await _showBackgroundLocal(message);
}

Future<void> _showBackgroundLocal(RemoteMessage message) async {
  final plugin = FlutterLocalNotificationsPlugin();
  await plugin.initialize(
    const InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(),
    ),
  );
  final androidPlugin = plugin.resolvePlatformSpecificImplementation<
      AndroidFlutterLocalNotificationsPlugin>();
  await androidPlugin?.createNotificationChannel(
    const AndroidNotificationChannel(
      _kChannelId,
      _kChannelName,
      description: 'تنبيهات الطلبات والعروض',
      importance: Importance.max,
      playSound: true,
      enableVibration: true,
    ),
  );
  final title = message.notification?.title ??
      message.data['title']?.toString() ??
      'روعة الخمسة';
  final body =
      message.notification?.body ?? message.data['body']?.toString() ?? '';
  if (body.isEmpty && title == 'روعة الخمسة') return;
  await plugin.show(
    DateTime.now().millisecondsSinceEpoch.remainder(1000000),
    title,
    body,
    const NotificationDetails(
      android: AndroidNotificationDetails(
        _kChannelId,
        _kChannelName,
        channelDescription: 'تنبيهات الطلبات والعروض',
        importance: Importance.max,
        priority: Priority.max,
        playSound: true,
        enableVibration: true,
        icon: '@mipmap/ic_launcher',
        visibility: NotificationVisibility.public,
      ),
      iOS: DarwinNotificationDetails(
        presentAlert: true,
        presentBadge: true,
        presentSound: true,
      ),
    ),
  );
}

class PushService with WidgetsBindingObserver {
  PushService._();
  static final PushService instance = PushService._();

  final _messaging = FirebaseMessaging.instance;
  final _local = FlutterLocalNotificationsPlugin();
  final _api = NotificationsApi.instance;
  final _knownIds = <String>{};

  NotificationsCubit? _cubit;
  String? _token;
  bool _ready = false;
  bool _inboxPrimed = false;
  Timer? _poll;
  int _localId = 0;

  Future<void> initialize({required NotificationsCubit cubit}) async {
    _cubit = cubit;
    if (_ready) {
      await sync();
      return;
    }

    const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosInit = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );
    await _local.initialize(
      const InitializationSettings(android: androidInit, iOS: iosInit),
      onDidReceiveNotificationResponse: (response) {
        _openFromPayload(response.payload);
      },
    );

    final androidPlugin = _local.resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin>();
    await androidPlugin?.createNotificationChannel(
      const AndroidNotificationChannel(
        _kChannelId,
        _kChannelName,
        description: 'تنبيهات الطلبات والعروض',
        importance: Importance.max,
        playSound: true,
        enableVibration: true,
        showBadge: true,
      ),
    );
    await androidPlugin?.requestNotificationsPermission();

    await _messaging.setForegroundNotificationPresentationOptions(
      alert: true,
      badge: true,
      sound: true,
    );

    FirebaseMessaging.onMessage.listen(_onForeground);
    FirebaseMessaging.onMessageOpenedApp.listen((message) {
      _openFromData(message.data);
    });

    _messaging.onTokenRefresh.listen((token) async {
      _token = token;
      await _registerIfAllowed(token);
    });

    final initial = await _messaging.getInitialMessage();
    if (initial != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _openFromData(initial.data);
      });
    }

    WidgetsBinding.instance.addObserver(this);
    _ready = true;
    await sync();
    _startPoll();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      unawaited(sync());
      unawaited(_refreshInbox(announce: false));
      _startPoll();
      return;
    }
    if (state == AppLifecycleState.paused ||
        state == AppLifecycleState.inactive) {
      _poll?.cancel();
    }
  }

  Future<void> sync() async {
    _startPoll();
    if (!AuthSession.instance.isLoggedIn) return;
    if (AuthSession.instance.user?.notificationsEnabled == false) return;

    final settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      announcement: true,
    );
    if (settings.authorizationStatus == AuthorizationStatus.denied) {
      await _refreshInbox(announce: false);
      return;
    }

    try {
      final token = await _messaging.getToken();
      if (token != null && token.isNotEmpty) {
        _token = token;
        await _registerIfAllowed(token);
      }
    } catch (e) {
      debugPrint('[Push] token failed: $e');
    }
    await _refreshInbox(announce: false);
  }

  Future<void> setEnabled(bool enabled) async {
    await _api.setEnabled(enabled);
    if (!enabled) {
      await _api.unregisterToken(_token);
      _token = null;
      await _cubit?.load(silent: true);
      return;
    }
    await sync();
  }

  Future<void> unregisterForLogout() async {
    _poll?.cancel();
    _knownIds.clear();
    _inboxPrimed = false;
    try {
      await _api.unregisterToken(_token);
    } catch (_) {}
    _token = null;
  }

  Future<void> _registerIfAllowed(String token) async {
    if (!AuthSession.instance.isLoggedIn) return;
    if (AuthSession.instance.user?.notificationsEnabled == false) return;
    final platform = Platform.isIOS ? 'ios' : 'android';
    try {
      await _api.registerToken(token, platform);
    } catch (e) {
      debugPrint('[Push] register failed: $e');
    }
  }

  void _startPoll() {
    _poll?.cancel();
    _poll = Timer.periodic(const Duration(seconds: 8), (_) {
      unawaited(_refreshInbox(announce: true));
    });
  }

  Future<void> _onForeground(RemoteMessage message) async {
    await _cubit?.load(silent: true);
    _rememberCurrent();
    final notification = message.notification;
    final title = notification?.title ??
        message.data['title']?.toString() ??
        'روعة الخمسة';
    final body =
        notification?.body ?? message.data['body']?.toString() ?? '';
    await _showLocal(title, body, message.data);
  }

  Future<void> _refreshInbox({required bool announce}) async {
    if (!AuthSession.instance.isLoggedIn) return;
    try {
      await _cubit?.load(silent: true);
    } catch (_) {
      return;
    }
    final items = _cubit?.state.items ?? [];
    if (!_inboxPrimed) {
      _rememberCurrent();
      return;
    }
    final fresh = items
        .where((item) => !_knownIds.contains(item.id) && !item.read)
        .toList();
    _rememberCurrent();
    if (!announce || fresh.isEmpty) return;
    final newest = fresh.first;
    await _showLocal(newest.title, newest.body, newest.data);
  }

  void _rememberCurrent() {
    _knownIds.addAll((_cubit?.state.items ?? []).map((e) => e.id));
    _inboxPrimed = true;
  }

  Future<void> _showLocal(
    String title,
    String body,
    Map<String, dynamic> data,
  ) async {
    if (title.isEmpty && body.isEmpty) return;
    await _local.show(
      ++_localId,
      title,
      body,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          _kChannelId,
          _kChannelName,
          channelDescription: 'تنبيهات الطلبات والعروض',
          importance: Importance.max,
          priority: Priority.max,
          playSound: true,
          enableVibration: true,
          icon: '@mipmap/ic_launcher',
          visibility: NotificationVisibility.public,
          ticker: 'إشعار جديد',
        ),
        iOS: DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: true,
          presentSound: true,
        ),
      ),
      payload: _payloadFrom(data),
    );
  }

  void _openFromPayload(String? payload) {
    if (payload == null || payload.isEmpty) return;
    final parts = Uri.splitQueryString(payload);
    _openFromData(parts);
  }

  void _openFromData(Map<String, dynamic> data) {
    final type = data['type']?.toString();
    final nav = AppRouter.navigatorKey.currentState;
    if (nav == null) return;
    if (type == 'order') {
      nav.pushNamed(AppRouter.orders);
      return;
    }
    nav.pushNamed(AppRouter.notifications);
  }

  String _payloadFrom(Map<String, dynamic> data) {
    return data.entries
        .map((e) =>
            '${Uri.encodeQueryComponent(e.key)}=${Uri.encodeQueryComponent('${e.value}')}')
        .join('&');
  }
}

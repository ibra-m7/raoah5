import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/network/api_client.dart';
import '../models/auth_user.dart';

class AuthSession extends ChangeNotifier {
  AuthSession._();
  static final AuthSession instance = AuthSession._();

  static const _tokenKey = 'auth_token';
  static const _userKey = 'auth_user';
  static const _onboardingKey = 'onboarding_seen';

  String? token;
  AuthUser? user;
  bool onboardingSeen = false;

  /// بعد شرائح التعريف: افتح تسجيل الدخول فوق الرئيسية حتى الرجوع يعمل.
  bool offerLoginOnHome = false;

  /// بعد OTP: اطلب الاسم فوق الرئيسية حتى الرجوع يفتح المتجر.
  bool offerCompleteName = false;

  bool welcomeAfterLogin = false;

  bool get isLoggedIn => token != null && token!.isNotEmpty;

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    token = prefs.getString(_tokenKey);
    onboardingSeen = prefs.getBool(_onboardingKey) ?? false;
    final raw = prefs.getString(_userKey);
    if (raw != null && raw.isNotEmpty) {
      try {
        user = AuthUser.fromJson(jsonDecode(raw) as Map<String, dynamic>);
      } catch (_) {
        user = null;
      }
    }
    ApiClient.instance.authToken = token;
  }

  Future<void> save({required String token, required AuthUser user}) async {
    this.token = token;
    this.user = user;
    ApiClient.instance.authToken = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_userKey, jsonEncode(user.toJson()));
    notifyListeners();
  }

  Future<void> updateUser(AuthUser user) async {
    this.user = user;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user.toJson()));
    notifyListeners();
  }

  Future<void> markOnboardingSeen() async {
    onboardingSeen = true;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_onboardingKey, true);
  }

  Future<void> clear() async {
    token = null;
    user = null;
    ApiClient.instance.authToken = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
    notifyListeners();
  }
}

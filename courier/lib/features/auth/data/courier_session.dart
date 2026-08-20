import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../core/network/api_client.dart';
import 'courier_user.dart';

class CourierSession extends ChangeNotifier {
  CourierSession._();
  static final CourierSession instance = CourierSession._();

  static const _tokenKey = 'courier_token';
  static const _userKey = 'courier_user';

  String? token;
  CourierUser? user;

  bool get isLoggedIn => token != null && token!.isNotEmpty && user != null;

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    token = prefs.getString(_tokenKey);
    final raw = prefs.getString(_userKey);
    if (raw != null && raw.isNotEmpty) {
      try {
        user = CourierUser.fromJson(jsonDecode(raw) as Map<String, dynamic>);
      } catch (_) {
        user = null;
      }
    }
    ApiClient.instance.authToken = token;
  }

  Future<void> save({required String token, required CourierUser user}) async {
    this.token = token;
    this.user = user;
    ApiClient.instance.authToken = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_userKey, jsonEncode(user.toJson()));
    notifyListeners();
  }

  Future<void> updateUser(CourierUser user) async {
    this.user = user;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user.toJson()));
    notifyListeners();
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

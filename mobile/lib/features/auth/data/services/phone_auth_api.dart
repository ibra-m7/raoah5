import '../../../../core/network/api_client.dart';
import '../../../../core/network/api_exception.dart';
import '../../../notifications/data/services/push_service.dart';
import '../models/auth_user.dart';
import 'auth_session.dart';

class OtpRequestResult {
  final String phone;
  final String? fromPhone;
  final int expiresIn;
  final int resendIn;
  final String? debugCode;
  final bool otpRequired;
  final AuthUser? user;

  const OtpRequestResult({
    required this.phone,
    this.fromPhone,
    required this.expiresIn,
    required this.resendIn,
    this.debugCode,
    this.otpRequired = true,
    this.user,
  });
}

class PhoneAuthApi {
  PhoneAuthApi._();
  static final PhoneAuthApi instance = PhoneAuthApi._();

  final _client = ApiClient.instance;

  Future<OtpRequestResult> requestOtp(String phone) async {
    final json = await _client.post('/auth/otp/request', {'phone': phone});
    final data = (json['data'] as Map?)?.cast<String, dynamic>() ?? {};
    final token = data['token'] as String?;
    final userMap = (data['user'] as Map?)?.cast<String, dynamic>();
    if (token != null && userMap != null) {
      final user = AuthUser.fromJson(userMap);
      await AuthSession.instance.save(token: token, user: user);
      await PushService.instance.sync();
      return OtpRequestResult(
        phone: (data['phone'] as String?) ?? phone,
        expiresIn: 0,
        resendIn: 0,
        otpRequired: false,
        user: user,
      );
    }
    return OtpRequestResult(
      phone: (data['phone'] as String?) ?? phone,
      fromPhone: data['from_phone'] as String?,
      expiresIn: (data['expires_in'] as num?)?.toInt() ?? 300,
      resendIn: (data['resend_in'] as num?)?.toInt() ?? 60,
      debugCode: data['debug_code'] as String?,
    );
  }

  Future<AuthUser> verifyOtp({
    required String phone,
    required String code,
  }) async {
    final json = await _client.post('/auth/otp/verify', {
      'phone': phone,
      'code': code,
    });
    final data = (json['data'] as Map?)?.cast<String, dynamic>() ?? {};
    final token = data['token'] as String?;
    final userMap = (data['user'] as Map?)?.cast<String, dynamic>();
    if (token == null || userMap == null) {
      throw const ApiException('تعذّر إكمال تسجيل الدخول.');
    }
    final user = AuthUser.fromJson(userMap);
    await AuthSession.instance.save(token: token, user: user);
    await PushService.instance.sync();
    return user;
  }

  Future<AuthUser> me() async {
    final json = await _client.get('/auth/me');
    final data = (json['data'] as Map?)?.cast<String, dynamic>() ?? {};
    final userMap = (data['user'] as Map?)?.cast<String, dynamic>();
    if (userMap == null) {
      throw const ApiException('تعذّر تحميل الحساب.');
    }
    final user = AuthUser.fromJson(userMap);
    await AuthSession.instance.updateUser(user);
    return user;
  }

  Future<AuthUser> updateName(String name) async {
    final json = await _client.patch('/auth/me', {'name': name});
    return _saveUser(json, 'تعذّر حفظ الاسم.');
  }

  Future<AuthUser> saveLocation({
    required double latitude,
    required double longitude,
    String? city,
    String? district,
    String? street,
    String? details,
    String? label,
  }) async {
    final json = await _client.post(
      '/auth/location',
      {
        'latitude': latitude,
        'longitude': longitude,
        if (city != null && city.isNotEmpty) 'city': city,
        if (district != null && district.isNotEmpty) 'district': district,
        if (street != null && street.isNotEmpty) 'street': street,
        if (details != null) 'details': details,
        if (label != null) 'label': label,
      },
      auth: true,
    );
    return _saveUser(json, 'تعذّر حفظ الموقع.');
  }

  Future<AuthUser> _saveUser(Map<String, dynamic> json, String fallback) async {
    final data = (json['data'] as Map?)?.cast<String, dynamic>() ?? {};
    final userMap = (data['user'] as Map?)?.cast<String, dynamic>();
    if (userMap == null) {
      throw ApiException(fallback);
    }
    final user = AuthUser.fromJson(userMap);
    await AuthSession.instance.updateUser(user);
    return user;
  }

  Future<void> logout() async {
    try {
      await _client.post('/auth/logout', {}, auth: true);
    } catch (_) {
      // نمسح الجلسة محلياً حتى لو فشل الطلب
    }
    await AuthSession.instance.clear();
  }
}

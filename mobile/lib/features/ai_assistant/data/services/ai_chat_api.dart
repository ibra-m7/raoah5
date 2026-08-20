import 'dart:math';

import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/network/api_client.dart';
import '../../../shop/data/models/category_model.dart';
import '../../../shop/data/models/product_model.dart';

class AiConfig {
  final bool enabled;
  final bool guestsAllowed;
  final String name;
  final String welcome;
  final int maxProducts;

  const AiConfig({
    required this.enabled,
    required this.guestsAllowed,
    required this.name,
    required this.welcome,
    required this.maxProducts,
  });

  factory AiConfig.fromJson(Map<String, dynamic> json) {
    return AiConfig(
      enabled: json['enabled'] == true,
      guestsAllowed: json['guests_allowed'] != false,
      name: (json['name'] as String?)?.trim().isNotEmpty == true
          ? json['name'] as String
          : 'روعة',
      welcome: (json['welcome'] as String?)?.trim().isNotEmpty == true
          ? json['welcome'] as String
          : 'أهلاً بك في روعة الخمسة! كيف يمكنني مساعدتك؟',
      maxProducts: (json['max_products'] as num?)?.toInt() ?? 6,
    );
  }
}

class AiChatResult {
  final int conversationId;
  final String reply;
  final List<ProductModel> products;
  final String name;

  const AiChatResult({
    required this.conversationId,
    required this.reply,
    required this.products,
    required this.name,
  });
}

class AiChatApi {
  AiChatApi._();
  static final AiChatApi instance = AiChatApi._();

  static const _guestKey = 'ai_guest_token';
  final _client = ApiClient.instance;

  Future<String> guestToken() async {
    final prefs = await SharedPreferences.getInstance();
    var token = prefs.getString(_guestKey);
    if (token == null || token.length < 8) {
      token = _newToken();
      await prefs.setString(_guestKey, token);
    }
    return token;
  }

  Future<AiConfig> config() async {
    final json = await _client.get('/ai/config', auth: false);
    return AiConfig.fromJson(_dataMap(json));
  }

  Future<AiChatResult> chat({
    required String message,
    int? conversationId,
    String intent = 'chat',
    String? productId,
  }) async {
    final conversation = conversationId != null && conversationId > 0
        ? conversationId
        : null;
    final json = await _client.post(
      '/ai/chat',
      {
        'message': message,
        'conversation_id': ?conversation,
        'guest_token': await guestToken(),
        'intent': intent,
        'product_id': ?productId,
      },
      auth: true,
      timeout: const Duration(seconds: 45),
    );

    final data = _dataMap(json);
    return AiChatResult(
      conversationId: (data['conversation_id'] as num?)?.toInt() ?? 0,
      reply: (data['reply'] as String?)?.trim().isNotEmpty == true
          ? data['reply'] as String
          : 'تفضل هذه اختيارات من متجرنا.',
      products: jsonMapList(data['products'], ProductModel.fromJson),
      name: (data['name'] as String?) ?? 'روعة',
    );
  }

  Map<String, dynamic> _dataMap(Map<String, dynamic> json) {
    final data = json['data'];
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return Map<String, dynamic>.from(data);
    return {};
  }

  String _newToken() {
    final random = Random.secure();
    return List.generate(
      16,
      (_) => random.nextInt(256).toRadixString(16).padLeft(2, '0'),
    ).join();
  }
}

import '../../../../core/network/api_client.dart';
import '../../../../core/network/api_exception.dart';
import '../models/delivery_quote.dart';

class DeliveryApi {
  DeliveryApi._();
  static final DeliveryApi instance = DeliveryApi._();

  final _client = ApiClient.instance;

  Future<DeliveryQuote> quote({
    int? addressId,
    double subtotal = 0,
  }) async {
    final json = await _client.post(
      '/delivery/quote',
      {
        if (addressId != null) 'address_id': addressId,
        'subtotal': subtotal,
      },
      auth: true,
    );
    final data = json['data'];
    if (data is! Map) {
      throw ApiException(
        (json['message'] as String?) ?? 'تعذّر حساب رسوم التوصيل.',
      );
    }
    return DeliveryQuote.fromJson(Map<String, dynamic>.from(data));
  }
}

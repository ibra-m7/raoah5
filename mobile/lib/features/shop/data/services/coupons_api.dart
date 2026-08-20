import '../../../../core/network/api_client.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/entities/cart_item.dart';
import '../models/coupon_quote.dart';

class CouponsApi {
  CouponsApi._();
  static final CouponsApi instance = CouponsApi._();

  final _client = ApiClient.instance;

  Future<CouponQuote> preview({
    required String code,
    required List<CartItem> items,
    int? addressId,
  }) async {
    final json = await _client.post(
      '/coupons/preview',
      {
        'coupon_code': code,
        'items': items
            .map((item) => {
                  'product_id': int.tryParse(item.product.id) ?? item.product.id,
                  'quantity': item.quantity,
                })
            .toList(),
        if (addressId != null) 'address_id': addressId,
      },
      auth: true,
    );
    final data = json['data'];
    if (data is! Map) {
      throw ApiException((json['message'] as String?) ?? 'تعذّر تطبيق الكوبون.');
    }
    return CouponQuote.fromJson(Map<String, dynamic>.from(data));
  }
}

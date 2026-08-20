import '../../../../core/network/api_client.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/storage/offline_cache.dart';
import '../../domain/entities/cart_item.dart';
import '../../domain/entities/order_entity.dart';
import '../models/product_model.dart';

class OrdersApi {
  OrdersApi._();
  static final OrdersApi instance = OrdersApi._();

  final _client = ApiClient.instance;
  final _cache = OfflineCache.instance;

  Future<List<OrderEntity>> list() async {
    try {
      final json = await _client.get('/orders');
      final data = json['data'];
      if (data is List) {
        await _cache.saveList(OfflineCache.orders, data);
        return _parseList(data);
      }
      return [];
    } catch (e) {
      final cached = await _cache.readList(OfflineCache.orders);
      if (cached != null) {
        return _parseList(cached);
      }
      rethrow;
    }
  }

  List<OrderEntity> _parseList(List<dynamic> data) {
    return data.whereType<Map>().map((item) {
      try {
        return _fromJson(Map<String, dynamic>.from(item));
      } catch (_) {
        return null;
      }
    }).whereType<OrderEntity>().toList();
  }

  Future<OrderEntity> create({
    required List<CartItem> items,
    required String paymentMethod,
    int? addressId,
    String? notes,
    String? couponCode,
    String fulfillmentType = 'now',
    DateTime? scheduledAt,
  }) async {
    final json = await _client.post(
      '/orders',
      {
        'items': items
            .map((item) => {
                  'product_id': int.tryParse(item.product.id) ?? item.product.id,
                  'quantity': item.quantity,
                })
            .toList(),
        'payment_method': paymentMethod,
        if (addressId != null) 'address_id': addressId,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
        if (couponCode != null && couponCode.isNotEmpty) 'coupon_code': couponCode,
        'fulfillment_type': fulfillmentType,
        if (scheduledAt != null) 'scheduled_at': scheduledAt.toIso8601String(),
      },
      auth: true,
    );
    final data = json['data'];
    if (data is! Map) {
      throw const ApiException('تعذّر إنشاء الطلب.');
    }
    return _fromJson(Map<String, dynamic>.from(data));
  }

  Future<OrderEntity> cancel(String orderId, {String? reason}) async {
    final json = await _client.post(
      '/orders/$orderId/cancel',
      {if (reason != null && reason.isNotEmpty) 'reason': reason},
      auth: true,
    );
    final data = json['data'];
    if (data is! Map) {
      throw const ApiException('تعذّر إلغاء الطلب.');
    }
    return _fromJson(Map<String, dynamic>.from(data));
  }

  OrderEntity _fromJson(Map<String, dynamic> json) {
    final items = <CartItem>[];
    final rawItems = json['items'];
    if (rawItems is List) {
      for (final raw in rawItems.whereType<Map>()) {
        final map = Map<String, dynamic>.from(raw);
        final product = ProductModel(
          id: '${map['product_id'] ?? map['id'] ?? ''}',
          name: (map['name'] as String?) ?? '',
          description: '',
          price: (map['unit_price'] as num?)?.toDouble() ?? 0,
          imageUrl: (map['image_url'] as String?) ?? '',
          categoryId: '',
          stock: (map['quantity'] as num?)?.toInt() ?? 1,
        );
        items.add(CartItem(
          product: product,
          quantity: (map['quantity'] as num?)?.toInt() ?? 1,
        ));
      }
    }

    final subtotal = (json['subtotal'] as num?)?.toDouble();
    final shipping = (json['shipping_fee'] as num?)?.toDouble() ?? 0;
    final total = (json['total'] as num?)?.toDouble() ?? 0;
    final hasFree = _asBool(json['has_free_shipping']) || shipping == 0;

    return OrderEntity(
      id: '${json['order_number'] ?? json['id'] ?? ''}',
      items: items,
      subtotal: subtotal ?? (hasFree ? total : (total - shipping)),
      shippingFee: shipping,
      total: total,
      hasFreeShipping: hasFree,
      status: _status(json['status']?.toString()),
      createdAt: DateTime.tryParse('${json['created_at'] ?? ''}') ?? DateTime.now(),
      progressStep: _step(json['status']?.toString()),
      paymentMethod: (json['payment_method'] as String?) ?? 'cash',
      paymentMethodLabel:
          (json['payment_method_label'] as String?) ?? 'الدفع عند الاستلام',
      paymentStatusLabel:
          (json['payment_status_label'] as String?) ?? 'بانتظار الدفع',
      shippingCity: json['shipping_city'] as String?,
      shippingDetails: json['shipping_details'] as String?,
      couponCode: json['coupon_code'] as String?,
      discountAmount: (json['discount_amount'] as num?)?.toDouble() ?? 0,
      canCancel: _asBool(json['can_cancel']),
      cancelReason: json['cancel_reason'] as String?,
    );
  }

  bool _asBool(dynamic value) =>
      value == true || value == 1 || value == '1' || value == 'true';

  OrderStatus _status(String? value) {
    return switch (value) {
      'preparing' => OrderStatus.preparing,
      'on_the_way' => OrderStatus.onTheWay,
      'delivered' => OrderStatus.delivered,
      'cancelled' => OrderStatus.cancelled,
      _ => OrderStatus.pending,
    };
  }

  int _step(String? value) {
    return switch (value) {
      'preparing' => 1,
      'on_the_way' => 2,
      'delivered' => 3,
      _ => 0,
    };
  }
}

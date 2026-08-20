class CouponQuote {
  final String code;
  final String title;
  final String type;
  final String message;
  final double eligibleSubtotal;
  final double discountAmount;
  final bool freeShipping;
  final double subtotal;
  final double shippingFee;
  final double total;
  final bool hasFreeShipping;
  final String? deliveryLabel;

  const CouponQuote({
    required this.code,
    required this.title,
    required this.type,
    required this.message,
    required this.eligibleSubtotal,
    required this.discountAmount,
    required this.freeShipping,
    required this.subtotal,
    required this.shippingFee,
    required this.total,
    required this.hasFreeShipping,
    this.deliveryLabel,
  });

  factory CouponQuote.fromJson(Map<String, dynamic> json) {
    final delivery = json['delivery'];
    return CouponQuote(
      code: (json['code'] as String?) ?? '',
      title: (json['title'] as String?) ?? '',
      type: (json['type'] as String?) ?? '',
      message: (json['message'] as String?) ?? '',
      eligibleSubtotal: (json['eligible_subtotal'] as num?)?.toDouble() ?? 0,
      discountAmount: (json['discount_amount'] as num?)?.toDouble() ?? 0,
      freeShipping: json['free_shipping'] as bool? ?? false,
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0,
      shippingFee: (json['shipping_fee'] as num?)?.toDouble() ?? 0,
      total: (json['total'] as num?)?.toDouble() ?? 0,
      hasFreeShipping: json['has_free_shipping'] as bool? ?? false,
      deliveryLabel: delivery is Map ? delivery['label'] as String? : null,
    );
  }
}

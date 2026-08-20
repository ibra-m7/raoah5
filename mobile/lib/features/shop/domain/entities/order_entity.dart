import 'package:equatable/equatable.dart';
import 'cart_item.dart';

// ── حالة الطلب ──────────────────────────────────────────────────────────────
enum OrderStatus {
  pending,    // في انتظار التأكيد
  preparing,  // جاري التحضير
  onTheWay,   // في الطريق
  delivered,  // تم التسليم
  cancelled,  // ملغي
}

extension OrderStatusX on OrderStatus {
  String get label => switch (this) {
    OrderStatus.pending   => 'في انتظار التأكيد',
    OrderStatus.preparing => 'جاري التحضير',
    OrderStatus.onTheWay  => 'في الطريق',
    OrderStatus.delivered => 'تم التسليم',
    OrderStatus.cancelled => 'ملغي',
  };

  String get emoji => switch (this) {
    OrderStatus.pending   => '⏳',
    OrderStatus.preparing => '🍳',
    OrderStatus.onTheWay  => '🚚',
    OrderStatus.delivered => '✅',
    OrderStatus.cancelled => '❌',
  };

  bool get isActive => this != OrderStatus.cancelled;
}

// ════════════════════════════════════════════════════════════════════════════
class OrderEntity extends Equatable {
  final String id;
  final List<CartItem> items;
  final double subtotal;
  final double shippingFee;
  final double total;
  final bool hasFreeShipping;
  final OrderStatus status;
  final DateTime createdAt;
  final int progressStep;
  final String paymentMethod;
  final String paymentMethodLabel;
  final String paymentStatusLabel;
  final String? shippingCity;
  final String? shippingDetails;
  final String? couponCode;
  final double discountAmount;
  final bool canCancel;
  final String? cancelReason;

  const OrderEntity({
    required this.id,
    required this.items,
    required this.subtotal,
    required this.shippingFee,
    required this.total,
    required this.hasFreeShipping,
    required this.status,
    required this.createdAt,
    this.progressStep = 0,
    this.paymentMethod = 'cash',
    this.paymentMethodLabel = 'الدفع عند الاستلام',
    this.paymentStatusLabel = 'بانتظار الدفع',
    this.shippingCity,
    this.shippingDetails,
    this.couponCode,
    this.discountAmount = 0,
    this.canCancel = false,
    this.cancelReason,
  });

  OrderEntity copyWith({
    String? id,
    List<CartItem>? items,
    double? subtotal,
    double? shippingFee,
    double? total,
    bool? hasFreeShipping,
    OrderStatus? status,
    DateTime? createdAt,
    int? progressStep,
    String? paymentMethod,
    String? paymentMethodLabel,
    String? paymentStatusLabel,
    String? shippingCity,
    String? shippingDetails,
    String? couponCode,
    double? discountAmount,
    bool? canCancel,
    String? cancelReason,
  }) =>
      OrderEntity(
        id: id ?? this.id,
        items: items ?? this.items,
        subtotal: subtotal ?? this.subtotal,
        shippingFee: shippingFee ?? this.shippingFee,
        total: total ?? this.total,
        hasFreeShipping: hasFreeShipping ?? this.hasFreeShipping,
        status: status ?? this.status,
        createdAt: createdAt ?? this.createdAt,
        progressStep: progressStep ?? this.progressStep,
        paymentMethod: paymentMethod ?? this.paymentMethod,
        paymentMethodLabel: paymentMethodLabel ?? this.paymentMethodLabel,
        paymentStatusLabel: paymentStatusLabel ?? this.paymentStatusLabel,
        shippingCity: shippingCity ?? this.shippingCity,
        shippingDetails: shippingDetails ?? this.shippingDetails,
        couponCode: couponCode ?? this.couponCode,
        discountAmount: discountAmount ?? this.discountAmount,
        canCancel: canCancel ?? this.canCancel,
        cancelReason: cancelReason ?? this.cancelReason,
      );

  int get itemCount =>
      items.fold(0, (sum, i) => sum + i.quantity);

  @override
  List<Object?> get props =>
      [id, items, subtotal, shippingFee, total, hasFreeShipping, status, createdAt, progressStep, couponCode, discountAmount, canCancel];
}

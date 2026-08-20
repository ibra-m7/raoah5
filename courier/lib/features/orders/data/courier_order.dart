class CourierOrderItem {
  final String id;
  final String name;
  final String? imageUrl;
  final double unitPrice;
  final int quantity;
  final double lineTotal;

  const CourierOrderItem({
    required this.id,
    required this.name,
    this.imageUrl,
    required this.unitPrice,
    required this.quantity,
    required this.lineTotal,
  });

  factory CourierOrderItem.fromJson(Map<String, dynamic> json) {
    return CourierOrderItem(
      id: '${json['id'] ?? ''}',
      name: (json['name'] ?? '').toString(),
      imageUrl: json['image_url']?.toString(),
      unitPrice: (json['unit_price'] as num?)?.toDouble() ?? 0,
      quantity: (json['quantity'] as num?)?.toInt() ?? 1,
      lineTotal: (json['line_total'] as num?)?.toDouble() ?? 0,
    );
  }
}

class CourierOrder {
  final String id;
  final String orderNumber;
  final String status;
  final String statusLabel;
  final double total;
  final double subtotal;
  final double shippingFee;
  final String paymentMethodLabel;
  final String paymentStatusLabel;
  final String? shippingName;
  final String? shippingPhone;
  final String? shippingCity;
  final String? shippingDistrict;
  final String? shippingStreet;
  final String? shippingDetails;
  final String? notes;
  final String? mapsUrl;
  final int itemsCount;
  final List<CourierOrderItem> items;
  final bool assignedToMe;
  final bool canAccept;
  final bool canPickup;
  final bool canDeliver;
  final DateTime? createdAt;

  const CourierOrder({
    required this.id,
    required this.orderNumber,
    required this.status,
    required this.statusLabel,
    required this.total,
    required this.subtotal,
    required this.shippingFee,
    required this.paymentMethodLabel,
    required this.paymentStatusLabel,
    this.shippingName,
    this.shippingPhone,
    this.shippingCity,
    this.shippingDistrict,
    this.shippingStreet,
    this.shippingDetails,
    this.notes,
    this.mapsUrl,
    required this.itemsCount,
    required this.items,
    required this.assignedToMe,
    required this.canAccept,
    required this.canPickup,
    required this.canDeliver,
    this.createdAt,
  });

  String get streetLine {
    return [
      shippingStreet,
      shippingDistrict,
      shippingCity,
    ].where((part) => part != null && part.trim().isNotEmpty).join('، ');
  }

  String get detailsLine => (shippingDetails ?? '').trim();

  String get addressLine {
    return [
      if (detailsLine.isNotEmpty) detailsLine,
      if (streetLine.isNotEmpty) streetLine,
    ].join('، ');
  }

  String get formattedTime {
    final dt = createdAt?.toLocal();
    if (dt == null) return '';
    final hour = dt.hour;
    final h12 = hour % 12 == 0 ? 12 : hour % 12;
    final suffix = hour < 12 ? 'ص' : 'م';
    final mm = dt.minute.toString().padLeft(2, '0');
    final dd = dt.day.toString().padLeft(2, '0');
    final mo = dt.month.toString().padLeft(2, '0');
    return '$h12:$mm $suffix $dd/$mo/${dt.year}';
  }

  String get phoneDigits {
    return (shippingPhone ?? '').replaceAll(RegExp(r'\D'), '');
  }

  factory CourierOrder.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'];
    return CourierOrder(
      id: '${json['id'] ?? ''}',
      orderNumber: (json['order_number'] ?? '').toString(),
      status: (json['status'] ?? '').toString(),
      statusLabel: (json['status_label'] ?? '').toString(),
      total: (json['total'] as num?)?.toDouble() ?? 0,
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0,
      shippingFee: (json['shipping_fee'] as num?)?.toDouble() ?? 0,
      paymentMethodLabel: (json['payment_method_label'] ?? '').toString(),
      paymentStatusLabel: (json['payment_status_label'] ?? '').toString(),
      shippingName: json['shipping_name']?.toString(),
      shippingPhone: json['shipping_phone']?.toString(),
      shippingCity: json['shipping_city']?.toString(),
      shippingDistrict: json['shipping_district']?.toString(),
      shippingStreet: json['shipping_street']?.toString(),
      shippingDetails: json['shipping_details']?.toString(),
      notes: json['notes']?.toString(),
      mapsUrl: json['maps_url']?.toString(),
      itemsCount: (json['items_count'] as num?)?.toInt() ?? 0,
      items: rawItems is List
          ? rawItems
              .whereType<Map>()
              .map((item) => CourierOrderItem.fromJson(
                    Map<String, dynamic>.from(item),
                  ))
              .toList()
          : const [],
      assignedToMe: json['assigned_to_me'] == true,
      canAccept: json['can_accept'] == true,
      canPickup: json['can_pickup'] == true,
      canDeliver: json['can_deliver'] == true,
      createdAt: DateTime.tryParse('${json['created_at'] ?? ''}'),
    );
  }
}

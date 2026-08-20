class CourierUser {
  final String id;
  final String name;
  final String phone;
  final String phoneDisplay;
  final bool isOnline;
  final int deliveredCount;
  final String paymentMethodLabel;
  final double codCollected;
  final double settledTotal;
  final double owes;
  final double owed;

  const CourierUser({
    required this.id,
    required this.name,
    required this.phone,
    required this.phoneDisplay,
    required this.isOnline,
    required this.deliveredCount,
    required this.paymentMethodLabel,
    this.codCollected = 0,
    this.settledTotal = 0,
    this.owes = 0,
    this.owed = 0,
  });

  factory CourierUser.fromJson(Map<String, dynamic> json) {
    return CourierUser(
      id: '${json['id'] ?? ''}',
      name: (json['name'] ?? '').toString(),
      phone: (json['phone'] ?? '').toString(),
      phoneDisplay: (json['phone_display'] ?? json['phone'] ?? '').toString(),
      isOnline: json['is_online'] == true,
      deliveredCount: (json['delivered_count'] as num?)?.toInt() ?? 0,
      paymentMethodLabel:
          (json['payment_method_label'] ?? 'الدفع عند الاستلام').toString(),
      codCollected: (json['cod_collected'] as num?)?.toDouble() ?? 0,
      settledTotal: (json['settled_total'] as num?)?.toDouble() ?? 0,
      owes: (json['owes'] as num?)?.toDouble() ?? 0,
      owed: (json['owed'] as num?)?.toDouble() ?? 0,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'phone': phone,
        'phone_display': phoneDisplay,
        'is_online': isOnline,
        'delivered_count': deliveredCount,
        'payment_method_label': paymentMethodLabel,
        'cod_collected': codCollected,
        'settled_total': settledTotal,
        'owes': owes,
        'owed': owed,
      };

  CourierUser copyWith({
    bool? isOnline,
    int? deliveredCount,
    double? codCollected,
    double? settledTotal,
    double? owes,
    double? owed,
  }) {
    return CourierUser(
      id: id,
      name: name,
      phone: phone,
      phoneDisplay: phoneDisplay,
      isOnline: isOnline ?? this.isOnline,
      deliveredCount: deliveredCount ?? this.deliveredCount,
      paymentMethodLabel: paymentMethodLabel,
      codCollected: codCollected ?? this.codCollected,
      settledTotal: settledTotal ?? this.settledTotal,
      owes: owes ?? this.owes,
      owed: owed ?? this.owed,
    );
  }
}

class CourierLedgerEntry {
  final String id;
  final String type;
  final String typeLabel;
  final String direction;
  final double amount;
  final String? note;
  final String? orderNumber;
  final DateTime? createdAt;

  const CourierLedgerEntry({
    required this.id,
    required this.type,
    required this.typeLabel,
    required this.direction,
    required this.amount,
    this.note,
    this.orderNumber,
    this.createdAt,
  });

  factory CourierLedgerEntry.fromJson(Map<String, dynamic> json) {
    return CourierLedgerEntry(
      id: '${json['id'] ?? ''}',
      type: (json['type'] ?? '').toString(),
      typeLabel: (json['type_label'] ?? '').toString(),
      direction: (json['direction'] ?? '').toString(),
      amount: (json['amount'] as num?)?.toDouble() ?? 0,
      note: json['note']?.toString(),
      orderNumber: json['order_number']?.toString(),
      createdAt: DateTime.tryParse('${json['created_at'] ?? ''}'),
    );
  }
}

class CourierAccount {
  final CourierUser user;
  final List<CourierLedgerEntry> entries;

  const CourierAccount({required this.user, required this.entries});
}

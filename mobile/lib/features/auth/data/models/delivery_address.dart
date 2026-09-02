class DeliveryAddress {
  final int id;
  final String label;
  final String? details;
  final String? city;
  final String? district;
  final String? street;
  final double? latitude;
  final double? longitude;
  final bool isDefault;
  final String line;
  final int ordersCount;
  final DateTime? createdAt;

  const DeliveryAddress({
    required this.id,
    required this.label,
    this.details,
    this.city,
    this.district,
    this.street,
    this.latitude,
    this.longitude,
    this.isDefault = false,
    this.line = '',
    this.ordersCount = 0,
    this.createdAt,
  });

  String get headerName {
    final named = label.trim();
    if (named.isNotEmpty) return named;
    final area = (district ?? '').trim();
    if (area.isNotEmpty) return area;
    final town = (city ?? '').trim();
    if (town.isNotEmpty) return town;
    return 'المنزل';
  }

  String get chipText {
    final desc = (details ?? '').trim();
    if (desc.isEmpty) return label;
    return '$label  ·  $desc';
  }

  String get subtitle {
    if (line.trim().isNotEmpty) return line;
    return (details ?? '').trim();
  }

  factory DeliveryAddress.fromJson(Map<String, dynamic> json) {
    return DeliveryAddress(
      id: (json['id'] as num).toInt(),
      label: (json['label'] as String?)?.trim().isNotEmpty == true
          ? (json['label'] as String).trim()
          : 'المنزل',
      details: json['details'] as String?,
      city: json['city'] as String?,
      district: json['district'] as String?,
      street: json['street'] as String?,
      latitude: (json['latitude'] as num?)?.toDouble(),
      longitude: (json['longitude'] as num?)?.toDouble(),
      isDefault: json['is_default'] as bool? ?? false,
      line: (json['line'] as String?) ?? '',
      ordersCount: (json['orders_count'] as num?)?.toInt() ?? 0,
      createdAt: DateTime.tryParse(json['created_at'] as String? ?? ''),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'label': label,
        'details': details,
        'city': city,
        'district': district,
        'street': street,
        'latitude': latitude,
        'longitude': longitude,
        'is_default': isDefault,
        'line': line,
        'orders_count': ordersCount,
        'created_at': createdAt?.toIso8601String(),
      };
}

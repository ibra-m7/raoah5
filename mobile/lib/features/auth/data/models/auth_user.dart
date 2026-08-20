import 'delivery_address.dart';

class AuthUser {
  final int id;
  final String name;
  final String? phone;
  final String? email;
  final String? avatar;
  final bool needsName;
  final bool needsLocation;
  final bool notificationsEnabled;
  final List<DeliveryAddress> addresses;

  const AuthUser({
    required this.id,
    required this.name,
    this.phone,
    this.email,
    this.avatar,
    this.needsName = false,
    this.needsLocation = false,
    this.notificationsEnabled = true,
    this.addresses = const [],
  });

  DeliveryAddress? get defaultAddress {
    for (final address in addresses) {
      if (address.isDefault) return address;
    }
    return addresses.isEmpty ? null : addresses.first;
  }

  static bool _isPlaceholderName(String name) {
    final trimmed = name.trim();
    return trimmed.isEmpty || trimmed == 'عميل';
  }

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    final name = (json['name'] as String?) ?? 'عميل';
    final rawAddresses = json['addresses'];
    final addresses = <DeliveryAddress>[];
    if (rawAddresses is List) {
      for (final item in rawAddresses.whereType<Map>()) {
        addresses.add(
          DeliveryAddress.fromJson(Map<String, dynamic>.from(item)),
        );
      }
    } else if (json['default_address'] is Map) {
      addresses.add(
        DeliveryAddress.fromJson(
          Map<String, dynamic>.from(json['default_address'] as Map),
        ),
      );
    }
    return AuthUser(
      id: (json['id'] as num).toInt(),
      name: name,
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      avatar: json['avatar'] as String?,
      needsName: json['needs_name'] as bool? ?? _isPlaceholderName(name),
      needsLocation: json['needs_location'] as bool? ?? addresses.isEmpty,
      notificationsEnabled: json['notifications_enabled'] as bool? ?? true,
      addresses: addresses,
    );
  }

  /// بعد الدخول نفتح الرئيسية دائماً. إكمال الاسم يُعرض فوقها إن لزم.
  String get nextRoute => '/main';

  AuthUser copyWith({
    String? name,
    bool? needsName,
    bool? needsLocation,
    bool? notificationsEnabled,
    List<DeliveryAddress>? addresses,
  }) {
    return AuthUser(
      id: id,
      name: name ?? this.name,
      phone: phone,
      email: email,
      avatar: avatar,
      needsName: needsName ?? this.needsName,
      needsLocation: needsLocation ?? this.needsLocation,
      notificationsEnabled:
          notificationsEnabled ?? this.notificationsEnabled,
      addresses: addresses ?? this.addresses,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'phone': phone,
        'email': email,
        'avatar': avatar,
        'needs_name': needsName,
        'needs_location': needsLocation,
        'notifications_enabled': notificationsEnabled,
        'addresses': addresses.map((item) => item.toJson()).toList(),
      };
}

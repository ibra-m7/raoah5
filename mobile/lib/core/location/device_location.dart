import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';

class DeviceFix {
  final double latitude;
  final double longitude;
  final String? city;
  final String? district;
  final String? street;
  final String? details;

  const DeviceFix({
    required this.latitude,
    required this.longitude,
    this.city,
    this.district,
    this.street,
    this.details,
  });

  String get preview {
    final parts = [
      if (street != null && street!.isNotEmpty) street,
      if (district != null && district!.isNotEmpty) district,
      if (city != null && city!.isNotEmpty) city,
    ];
    return parts.join('، ');
  }
}

class DeviceLocation {
  DeviceLocation._();

  static const riyadh = DeviceFix(
    latitude: 24.7136,
    longitude: 46.6753,
    city: 'الرياض',
  );

  static Future<DeviceFix> current() async {
    final enabled = await Geolocator.isLocationServiceEnabled();
    if (!enabled) {
      await Geolocator.openLocationSettings();
      throw const DeviceLocationException(
        'خدمة الموقع مغلقة. فعّلها من إعدادات الجهاز ثم أعد المحاولة.',
      );
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied) {
      throw const DeviceLocationException(
        'نحتاج إذن الموقع لتحديد عنوان التوصيل.',
      );
    }
    if (permission == LocationPermission.deniedForever) {
      await Geolocator.openAppSettings();
      throw const DeviceLocationException(
        'إذن الموقع مرفوض. فعّله من إعدادات التطبيق.',
      );
    }

    final position = await Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
        timeLimit: Duration(seconds: 20),
      ),
    );

    return reverse(position.latitude, position.longitude);
  }

  static Future<DeviceFix?> search(String query) async {
    final q = query.trim();
    if (q.isEmpty) return null;
    final marks = await locationFromAddress(q);
    if (marks.isEmpty) return null;
    final first = marks.first;
    return reverse(first.latitude, first.longitude);
  }

  static Future<DeviceFix> reverse(double latitude, double longitude) async {
    String? city;
    String? district;
    String? street;
    try {
      final marks = await placemarkFromCoordinates(latitude, longitude);
      if (marks.isNotEmpty) {
        final place = marks.first;
        city = _firstNonEmpty([
          place.locality,
          place.subAdministrativeArea,
          place.administrativeArea,
        ]);
        district = _firstNonEmpty([
          place.subLocality,
          place.subAdministrativeArea,
        ]);
        street = _firstNonEmpty([place.street, place.thoroughfare]);
      }
    } catch (_) {
      city = 'السعودية';
    }

    return DeviceFix(
      latitude: latitude,
      longitude: longitude,
      city: city ?? 'السعودية',
      district: district,
      street: street,
      details: _firstNonEmpty([street, district, city]),
    );
  }

  static String? _firstNonEmpty(List<String?> values) {
    for (final value in values) {
      if (value != null && value.trim().isNotEmpty) return value.trim();
    }
    return null;
  }
}

class DeviceLocationException implements Exception {
  final String message;
  const DeviceLocationException(this.message);

  @override
  String toString() => message;
}

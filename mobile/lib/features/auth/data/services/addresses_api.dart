import '../../../../core/network/api_client.dart';
import '../models/delivery_address.dart';

class AddressesApi {
  AddressesApi._();
  static final AddressesApi instance = AddressesApi._();

  final _client = ApiClient.instance;

  Future<List<DeliveryAddress>> list() async {
    final json = await _client.get('/addresses');
    return _parseList(json['data']);
  }

  Future<List<DeliveryAddress>> create({
    required String label,
    String? details,
    required double latitude,
    required double longitude,
    String? city,
    String? district,
    String? street,
    bool isDefault = true,
  }) async {
    final json = await _client.post(
      '/addresses',
      {
        'label': label,
        'details': details ?? '',
        'latitude': latitude,
        'longitude': longitude,
        if (city != null && city.isNotEmpty) 'city': city,
        if (district != null && district.isNotEmpty) 'district': district,
        if (street != null && street.isNotEmpty) 'street': street,
        'is_default': isDefault,
      },
      auth: true,
    );
    return _parseList(_addressesOf(json['data']));
  }

  Future<List<DeliveryAddress>> update(
    int id, {
    String? label,
    String? details,
    double? latitude,
    double? longitude,
    String? city,
    String? district,
    String? street,
    bool? isDefault,
  }) async {
    final json = await _client.patch(
      '/addresses/$id',
      {
        'label': ?label,
        'details': ?details,
        'latitude': ?latitude,
        'longitude': ?longitude,
        'city': ?city,
        'district': ?district,
        'street': ?street,
        'is_default': ?isDefault,
      },
    );
    return _parseList(_addressesOf(json['data']));
  }

  Future<List<DeliveryAddress>> delete(int id) async {
    final json = await _client.delete('/addresses/$id');
    return _parseList(_addressesOf(json['data']));
  }

  dynamic _addressesOf(dynamic data) {
    if (data is Map && data['addresses'] != null) return data['addresses'];
    return data;
  }

  List<DeliveryAddress> _parseList(dynamic data) {
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((item) => DeliveryAddress.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }
}

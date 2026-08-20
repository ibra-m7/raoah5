import '../../../core/network/api_client.dart';
import 'courier_order.dart';

class CourierOrdersApi {
  CourierOrdersApi._();
  static final CourierOrdersApi instance = CourierOrdersApi._();

  final _client = ApiClient.instance;

  List<CourierOrder> _list(Map<String, dynamic> json) {
    final data = json['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((item) => CourierOrder.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  CourierOrder _one(Map<String, dynamic> json) {
    final data = json['data'];
    return CourierOrder.fromJson(
      data is Map<String, dynamic> ? data : json,
    );
  }

  Future<List<CourierOrder>> current() async {
    return _list(await _client.get('/courier/orders/current'));
  }

  Future<List<CourierOrder>> previous() async {
    return _list(await _client.get('/courier/orders/previous'));
  }

  Future<CourierOrder> show(String id) async {
    return _one(await _client.get('/courier/orders/$id'));
  }

  Future<CourierOrder> accept(String id) async {
    return _one(await _client.post('/courier/orders/$id/accept', {}, auth: true));
  }

  Future<CourierOrder> pickup(String id) async {
    return _one(await _client.post('/courier/orders/$id/pickup', {}, auth: true));
  }

  Future<CourierOrder> deliver(String id) async {
    return _one(await _client.post('/courier/orders/$id/deliver', {}, auth: true));
  }
}

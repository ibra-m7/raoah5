import '../../../../core/error/exceptions.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/storage/offline_cache.dart';
import '../models/category_model.dart';
import '../models/home_feed.dart';
import '../models/product_model.dart';

class CatalogApi {
  CatalogApi._();
  static final CatalogApi instance = CatalogApi._();

  final _client = ApiClient.instance;
  final _cache = OfflineCache.instance;

  Future<HomeFeed?> cachedHome() async {
    final data = await _cache.readMap(OfflineCache.homeFeed);
    if (data == null || data.isEmpty) return null;
    try {
      final feed = HomeFeed.fromJson(data);
      return feed.isEmpty ? null : feed;
    } catch (_) {
      return null;
    }
  }

  Future<HomeFeed> home() async {
    final json = await _client.get(
      '/home',
      auth: false,
      timeout: const Duration(seconds: 8),
    );
    final data = _dataMap(json);
    await _cache.saveMap(OfflineCache.homeFeed, data);
    return HomeFeed.fromJson(data);
  }

  Future<List<CategoryModel>> categories() async {
    final json = await _client.get('/categories', auth: false);
    return jsonMapList(_data(json), CategoryModel.fromJson);
  }

  Future<List<ProductModel>> products({
    String? categoryId,
    String? q,
    bool offers = false,
    int page = 1,
    int perPage = 40,
  }) async {
    final json = await _client.get(
      '/products',
      auth: false,
      query: {
        'category_id': categoryId,
        'q': q,
        'offers': offers ? '1' : null,
        'page': page,
        'per_page': perPage,
      },
    );
    return jsonMapList(_data(json), ProductModel.fromJson);
  }

  Future<ProductModel?> product(String id) async {
    try {
      final json = await _client.get('/products/$id', auth: false);
      final data = _dataMap(json);
      if (data.isEmpty) return null;
      return ProductModel.fromJson(data);
    } on ApiException catch (e) {
      if (e.statusCode == 404) return null;
      throw ServerException(message: e.message);
    }
  }

  Map<String, dynamic> _dataMap(Map<String, dynamic> json) {
    final data = json['data'];
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return Map<String, dynamic>.from(data);
    return {};
  }

  dynamic _data(Map<String, dynamic> json) => json['data'];
}

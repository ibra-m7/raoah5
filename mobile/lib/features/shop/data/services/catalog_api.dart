import '../../../../core/error/exceptions.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/storage/offline_cache.dart';
import '../models/category_model.dart';
import '../models/dynamic_page_model.dart';
import '../models/home_feed.dart';
import '../models/product_model.dart';
import '../models/recommendations.dart';

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
      auth: true,
      timeout: const Duration(seconds: 60),
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

  Future<DynamicPageModel?> dynamicPage(String id) async {
    try {
      final json = await _client.get('/pages/$id', auth: false);
      final data = _dataMap(json);
      if (data.isEmpty) return null;
      return DynamicPageModel.fromJson(data);
    } on ApiException catch (e) {
      if (e.statusCode == 404) return null;
      throw ServerException(message: e.message);
    }
  }

  Future<ProductRecommendations> productRecommendations(String id) async {
    final json = await _client.get(
      '/products/$id/recommendations',
      auth: false,
    );
    return ProductRecommendations.fromJson(_dataMap(json));
  }

  Future<CartRecommendations> cartRecommendations(List<String> ids) async {
    final json = await _client.post('/recommendations/cart', {
      'product_ids': ids,
    }, auth: true);
    return CartRecommendations.fromJson(_dataMap(json));
  }

  Future<void> logSearch({
    required String query,
    String? matchedProductId,
    int resultsCount = 0,
  }) async {
    final q = query.trim();
    if (q.isEmpty) return;
    try {
      await _client.post(
        '/search/log',
        {
          'query': q,
          'matched_product_id': int.tryParse(matchedProductId ?? ''),
          'results_count': resultsCount,
          'source': 'app',
        },
        auth: true,
      );
    } catch (_) {
      // لا نمنع البحث إذا فشل التسجيل.
    }
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

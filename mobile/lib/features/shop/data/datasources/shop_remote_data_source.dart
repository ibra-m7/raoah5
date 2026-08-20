import '../../../../core/error/exceptions.dart';
import '../../../../core/network/api_exception.dart';
import '../models/category_model.dart';
import '../models/product_model.dart';
import '../services/catalog_api.dart';

abstract class ShopRemoteDataSource {
  Future<List<ProductModel>> getProducts({int page, int pageSize});
  Future<List<ProductModel>> getProductsByCategory(String categoryId);
  Future<ProductModel> getProductById(String productId);
  Future<List<ProductModel>> searchProducts(String query);
  Future<List<CategoryModel>> getCategories();
}

class ShopRemoteDataSourceImpl implements ShopRemoteDataSource {
  ShopRemoteDataSourceImpl({CatalogApi? api}) : _api = api ?? CatalogApi.instance;

  final CatalogApi _api;

  @override
  Future<List<ProductModel>> getProducts({
    int page = 1,
    int pageSize = 20,
  }) {
    return _guard(() => _api.products(page: page, perPage: pageSize));
  }

  @override
  Future<List<ProductModel>> getProductsByCategory(String categoryId) {
    return _guard(() => _api.products(categoryId: categoryId));
  }

  @override
  Future<ProductModel> getProductById(String productId) async {
    final product = await _guard(() => _api.product(productId));
    if (product == null) {
      throw const ServerException(message: 'المنتج غير موجود');
    }
    return product;
  }

  @override
  Future<List<ProductModel>> searchProducts(String query) {
    return _guard(() => _api.products(q: query));
  }

  @override
  Future<List<CategoryModel>> getCategories() {
    return _guard(_api.categories);
  }

  Future<T> _guard<T>(Future<T> Function() run) async {
    try {
      return await run();
    } on ApiException catch (e) {
      throw ServerException(message: e.message);
    } on NetworkException {
      rethrow;
    }
  }
}

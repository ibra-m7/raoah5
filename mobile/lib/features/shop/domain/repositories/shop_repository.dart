import 'package:dartz/dartz.dart';
import '../../../../core/error/failures.dart';
import '../entities/product.dart';
import '../entities/category.dart';

abstract class ShopRepository {
  Future<Either<Failure, List<Product>>> getProducts({
    int page = 1,
    int pageSize = 20,
  });

  Future<Either<Failure, List<Product>>> getProductsByCategory(
    String categoryId,
  );

  Future<Either<Failure, Product>> getProductById(String productId);

  Future<Either<Failure, List<Product>>> searchProducts(String query);

  Future<Either<Failure, List<Category>>> getCategories();
}

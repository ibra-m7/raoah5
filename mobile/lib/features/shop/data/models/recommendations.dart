import 'category_model.dart';
import 'product_model.dart';

class ProductRecommendations {
  final List<ProductModel> boughtTogether;
  final List<ProductModel> similar;
  final List<ProductModel> suggested;

  const ProductRecommendations({
    this.boughtTogether = const [],
    this.similar = const [],
    this.suggested = const [],
  });

  factory ProductRecommendations.fromJson(Map<String, dynamic> json) {
    return ProductRecommendations(
      boughtTogether: jsonMapList(
        json['bought_together'],
        ProductModel.fromJson,
      ),
      similar: jsonMapList(json['similar'], ProductModel.fromJson),
      suggested: jsonMapList(json['suggested'], ProductModel.fromJson),
    );
  }
}

class CartRecommendations {
  final List<ProductModel> completeCart;
  final List<ProductModel> suggested;

  const CartRecommendations({
    this.completeCart = const [],
    this.suggested = const [],
  });

  factory CartRecommendations.fromJson(Map<String, dynamic> json) {
    return CartRecommendations(
      completeCart: jsonMapList(json['complete_cart'], ProductModel.fromJson),
      suggested: jsonMapList(json['suggested'], ProductModel.fromJson),
    );
  }
}

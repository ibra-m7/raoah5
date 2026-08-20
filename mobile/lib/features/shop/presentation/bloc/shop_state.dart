part of 'shop_bloc.dart';

abstract class ShopState extends Equatable {
  const ShopState();

  @override
  List<Object> get props => [];
}

class ShopInitial extends ShopState {}

class ShopLoading extends ShopState {}

class ShopProductsLoaded extends ShopState {
  final List<Product> products;
  final List<Category> categories;
  final String? selectedCategoryId;

  const ShopProductsLoaded({
    required this.products,
    required this.categories,
    this.selectedCategoryId,
  });

  @override
  List<Object> get props => [products, categories];
}

class ShopError extends ShopState {
  final String message;

  const ShopError({required this.message});

  @override
  List<Object> get props => [message];
}

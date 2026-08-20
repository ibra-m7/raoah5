import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../domain/entities/category.dart';
import '../../domain/entities/product.dart';
import '../../domain/usecases/get_categories.dart';
import '../../domain/usecases/get_products.dart';
import '../../domain/usecases/search_products.dart';
import '../../../../core/usecases/usecase.dart';

part 'shop_event.dart';
part 'shop_state.dart';

class ShopBloc extends Bloc<ShopEvent, ShopState> {
  final GetProducts getProducts;
  final GetCategories getCategories;
  final SearchProducts searchProducts;

  List<Category> _cachedCategories = [];

  ShopBloc({
    required this.getProducts,
    required this.getCategories,
    required this.searchProducts,
  }) : super(ShopInitial()) {
    on<LoadProductsEvent>(_onLoadProducts);
    on<LoadCategoriesEvent>(_onLoadCategories);
    on<FilterByCategoryEvent>(_onFilterByCategory);
    on<SearchProductsEvent>(_onSearchProducts);
  }

  Future<void> _onLoadProducts(
    LoadProductsEvent event,
    Emitter<ShopState> emit,
  ) async {
    emit(ShopLoading());
    final result = await getProducts(
      GetProductsParams(page: event.page, pageSize: event.pageSize),
    );
    result.fold(
      (failure) => emit(ShopError(message: failure.message)),
      (products) => emit(
        ShopProductsLoaded(
          products: products,
          categories: _cachedCategories,
        ),
      ),
    );
  }

  Future<void> _onLoadCategories(
    LoadCategoriesEvent event,
    Emitter<ShopState> emit,
  ) async {
    final result = await getCategories(NoParams());
    result.fold(
      (failure) => emit(ShopError(message: failure.message)),
      (categories) {
        _cachedCategories = categories;
      },
    );
  }

  Future<void> _onFilterByCategory(
    FilterByCategoryEvent event,
    Emitter<ShopState> emit,
  ) async {
    emit(ShopLoading());
    // TODO: implement filter by category usecase
  }

  Future<void> _onSearchProducts(
    SearchProductsEvent event,
    Emitter<ShopState> emit,
  ) async {
    emit(ShopLoading());
    final result = await searchProducts(
      SearchProductsParams(query: event.query),
    );
    result.fold(
      (failure) => emit(ShopError(message: failure.message)),
      (products) => emit(
        ShopProductsLoaded(
          products: products,
          categories: _cachedCategories,
        ),
      ),
    );
  }
}

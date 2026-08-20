part of 'shop_bloc.dart';

abstract class ShopEvent extends Equatable {
  const ShopEvent();

  @override
  List<Object> get props => [];
}

class LoadProductsEvent extends ShopEvent {
  final int page;
  final int pageSize;

  const LoadProductsEvent({this.page = 1, this.pageSize = 20});

  @override
  List<Object> get props => [page, pageSize];
}

class LoadCategoriesEvent extends ShopEvent {}

class FilterByCategoryEvent extends ShopEvent {
  final String categoryId;

  const FilterByCategoryEvent({required this.categoryId});

  @override
  List<Object> get props => [categoryId];
}

class SearchProductsEvent extends ShopEvent {
  final String query;

  const SearchProductsEvent({required this.query});

  @override
  List<Object> get props => [query];
}

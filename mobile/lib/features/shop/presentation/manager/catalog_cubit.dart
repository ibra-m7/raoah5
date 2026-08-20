import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/error/exceptions.dart';
import '../../../../core/network/api_exception.dart';
import '../../data/models/category_model.dart';
import '../../data/models/home_feed.dart';
import '../../data/models/product_model.dart';
import '../../data/services/catalog_api.dart';

class CatalogState extends Equatable {
  final bool loading;
  final bool refreshing;
  final bool offline;
  final String? error;
  final HomeFeed feed;

  const CatalogState({
    this.loading = true,
    this.refreshing = false,
    this.offline = false,
    this.error,
    this.feed = const HomeFeed(),
  });

  List<BannerModel> get banners => feed.banners;
  List<CategoryModel> get categories => feed.categories;
  List<ProductModel> get offers => feed.offers;
  List<HomeSectionModel> get sections => feed.sections;
  List<DisplaySectionModel> get displaySections => feed.displaySections;
  List<ProductModel> get products => feed.products;
  StoreConfig get store => feed.store;

  Map<String, ProductModel> get productsById {
    final map = <String, ProductModel>{
      for (final product in products) product.id: product,
      for (final product in offers) product.id: product,
    };
    for (final section in sections) {
      for (final product in section.products) {
        map[product.id] = product;
      }
    }
    return map;
  }

  List<CategoryModel> get allCategories {
    final map = <String, CategoryModel>{};
    void add(CategoryModel category) {
      map[category.id] = category;
      for (final child in category.children) {
        add(child);
      }
    }

    for (final category in categories) {
      add(category);
    }
    for (final section in displaySections) {
      for (final category in section.categories) {
        add(category);
      }
    }
    return map.values.toList();
  }

  Set<String> categoryTreeIds(String id) {
    final ids = <String>{id};
    var grew = true;
    while (grew) {
      grew = false;
      for (final category in allCategories) {
        final parent = category.parentId;
        if (parent != null && ids.contains(parent) && ids.add(category.id)) {
          grew = true;
        }
      }
    }
    return ids;
  }

  List<ProductModel> productsForCategory(String id) {
    final ids = categoryTreeIds(id);
    return products.where((p) => ids.contains(p.categoryId)).toList();
  }

  List<ProductModel> search(String query) {
    final q = query.trim().toLowerCase();
    if (q.isEmpty) return products;
    return products.where((p) {
      return p.name.toLowerCase().contains(q) ||
          p.description.toLowerCase().contains(q) ||
          p.keywords.any((k) => k.toLowerCase().contains(q)) ||
          p.benefits.any((b) => b.toLowerCase().contains(q));
    }).toList();
  }

  List<ProductModel> suggestions({
    required Set<String> excludeIds,
    Set<String> excludeCategoryIds = const {},
  }) {
    final other = products
        .where(
          (p) =>
              !excludeIds.contains(p.id) &&
              !excludeCategoryIds.contains(p.categoryId),
        )
        .toList();
    if (other.isNotEmpty) return other.take(6).toList();
    return products.where((p) => !excludeIds.contains(p.id)).take(6).toList();
  }

  ProductModel? productById(String id) => productsById[id];

  DisplaySectionModel? displayBySlug(String slug) {
    for (final section in displaySections) {
      if (section.slug == slug) return section;
    }
    return displaySections.isEmpty ? null : displaySections.first;
  }

  HomeSectionModel? sectionByKey(String key) {
    for (final section in sections) {
      if (section.key == key) return section;
    }
    return null;
  }

  CatalogState copyWith({
    bool? loading,
    bool? refreshing,
    bool? offline,
    String? error,
    HomeFeed? feed,
    bool clearError = false,
  }) {
    return CatalogState(
      loading: loading ?? this.loading,
      refreshing: refreshing ?? this.refreshing,
      offline: offline ?? this.offline,
      error: clearError ? null : error ?? this.error,
      feed: feed ?? this.feed,
    );
  }

  @override
  List<Object?> get props => [loading, refreshing, offline, error, feed];
}

class CatalogCubit extends Cubit<CatalogState> {
  CatalogCubit({CatalogApi? api})
      : _api = api ?? CatalogApi.instance,
        super(const CatalogState());

  final CatalogApi _api;

  Future<void> load({bool refresh = false}) async {
    final cached = await _api.cachedHome();
    if (cached != null) {
      emit(CatalogState(
        loading: false,
        refreshing: true,
        feed: cached,
      ));
    } else {
      emit(state.copyWith(
        loading: !refresh && state.feed.isEmpty,
        refreshing: refresh,
        clearError: true,
      ));
    }

    try {
      final feed = await _api.home();
      emit(CatalogState(loading: false, feed: feed));
    } on ApiException catch (e) {
      _emitFailure(cached, e.message);
    } on NetworkException catch (e) {
      _emitFailure(cached, e.message);
    } on ServerException catch (e) {
      _emitFailure(cached, e.message);
    } catch (_) {
      _emitFailure(cached, 'تعذّر تحميل المتجر. حاول مجدداً.');
    }
  }

  void _emitFailure(HomeFeed? cached, String message) {
    final feed = !state.feed.isEmpty
        ? state.feed
        : cached;
    if (feed != null && !feed.isEmpty) {
      emit(CatalogState(
        loading: false,
        offline: true,
        feed: feed,
      ));
      return;
    }
    emit(state.copyWith(
      loading: false,
      refreshing: false,
      error: message,
    ));
  }
}

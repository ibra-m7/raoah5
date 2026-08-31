import 'package:flutter/material.dart' show Color;

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/home_section_gradient.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../domain/entities/product.dart';
import '../models/bundle_model.dart';
import '../models/category_model.dart';
import '../models/dynamic_page_model.dart';
import '../models/product_model.dart';

class BannerModel {
  final String id;
  final String title;
  final bool showTitle;
  final String? subtitle;
  final String imageUrl;
  final String linkType;
  final String? linkId;
  final String? linkUrl;

  const BannerModel({
    required this.id,
    required this.title,
    this.showTitle = false,
    this.subtitle,
    required this.imageUrl,
    this.linkType = 'none',
    this.linkId,
    this.linkUrl,
  });

  factory BannerModel.fromJson(Map<String, dynamic> json) {
    return BannerModel(
      id: '${json['id']}',
      title: (json['title'] as String?) ?? '',
      showTitle: json['show_title'] == true,
      subtitle: json['subtitle'] as String?,
      imageUrl: (json['image_url'] as String?) ?? '',
      linkType: (json['link_type'] as String?) ?? 'none',
      linkId: json['link_id']?.toString(),
      linkUrl: json['link_url'] as String?,
    );
  }
}

class HomeSectionModel {
  final String id;
  final String key;
  final String contentType;
  final String title;
  final String? subtitle;
  final String? backgroundColor;
  final String? backgroundImageUrl;
  final String? titleColor;
  final String? subtitleColor;
  final bool autoScrollCards;
  final bool showTitleIcon;
  final bool emphasizeSubtitle;
  final List<ProductModel> products;
  final List<BundleModel> bundles;

  const HomeSectionModel({
    required this.id,
    required this.key,
    this.contentType = 'products',
    required this.title,
    this.subtitle,
    this.backgroundColor,
    this.backgroundImageUrl,
    this.titleColor,
    this.subtitleColor,
    this.autoScrollCards = false,
    this.showTitleIcon = false,
    this.emphasizeSubtitle = false,
    this.products = const [],
    this.bundles = const [],
  });

  bool get showsBundles => contentType == 'bundles';

  List<Color> get gradientColors =>
      HomeSectionGradient.colors(backgroundColor);

  factory HomeSectionModel.fromJson(Map<String, dynamic> json) {
    return HomeSectionModel(
      id: '${json['id']}',
      key: (json['key'] as String?) ?? '',
      contentType: (json['content_type'] as String?) ?? 'products',
      title: (json['title'] as String?) ?? '',
      subtitle: json['subtitle'] as String?,
      backgroundColor: json['background_color'] as String?,
      backgroundImageUrl: json['background_image_url'] as String?,
      titleColor: json['title_color'] as String?,
      subtitleColor: json['subtitle_color'] as String?,
      autoScrollCards: json['auto_scroll_cards'] == true,
      showTitleIcon: json['show_title_icon'] == true,
      emphasizeSubtitle: json['emphasize_subtitle'] == true,
      products: jsonMapList(json['products'], ProductModel.fromJson),
      bundles: jsonMapList(json['bundles'], BundleModel.fromJson),
    );
  }
}

class DisplaySectionModel {
  final String id;
  final String name;
  final String slug;
  final String? emoji;
  final List<CategoryModel> categories;

  const DisplaySectionModel({
    required this.id,
    required this.name,
    required this.slug,
    this.emoji,
    this.categories = const [],
  });

  factory DisplaySectionModel.fromJson(Map<String, dynamic> json) {
    return DisplaySectionModel(
      id: '${json['id']}',
      name: (json['name'] as String?) ?? '',
      slug: (json['slug'] as String?) ?? '',
      emoji: json['emoji'] as String?,
      categories: jsonMapList(json['categories'], CategoryModel.fromJson),
    );
  }
}

class HomeFeed {
  final List<BannerModel> banners;
  final List<CategoryModel> categories;
  final List<ProductModel> discounts;
  final List<ProductModel> offers;
  final List<HomeSectionModel> sections;
  final List<DisplaySectionModel> displaySections;
  final List<DynamicPageModel> dynamicPages;
  final List<ProductModel> products;
  final List<ProductModel> suggested;
  final StoreConfig store;

  const HomeFeed({
    this.banners = const [],
    this.categories = const [],
    this.discounts = const [],
    this.offers = const [],
    this.sections = const [],
    this.displaySections = const [],
    this.dynamicPages = const [],
    this.products = const [],
    this.suggested = const [],
    this.store = const StoreConfig(),
  });

  factory HomeFeed.fromJson(Map<String, dynamic> json) {
    final store = json['store'] is Map
        ? StoreConfig.fromJson(Map<String, dynamic>.from(json['store'] as Map))
        : const StoreConfig();
    Product.fallbackImageUrl = store.fallbackProductImageUrl;
    AppNetworkImage.fallbackUrl = store.fallbackProductImageUrl;
    return HomeFeed(
      banners: jsonMapList(json['banners'], BannerModel.fromJson),
      categories: jsonMapList(json['categories'], CategoryModel.fromJson),
      discounts: _parsePromoProducts(json, kind: _PromoFeedKind.discounts),
      offers: _parsePromoProducts(json, kind: _PromoFeedKind.offers),
      sections: jsonMapList(json['sections'], HomeSectionModel.fromJson),
      displaySections: jsonMapList(
        json['display_sections'],
        DisplaySectionModel.fromJson,
      ),
      dynamicPages: jsonMapList(
        json['dynamic_pages'],
        DynamicPageModel.fromJson,
      ),
      products: jsonMapList(json['products'], ProductModel.fromJson),
      suggested: jsonMapList(json['suggested'], ProductModel.fromJson),
      store: store,
    );
  }

  bool get isEmpty =>
      products.isEmpty && categories.isEmpty && displaySections.isEmpty;
}

class PaymentOption {
  final String id;
  final String label;
  final String hint;
  final String icon;
  final String iconUrl;

  const PaymentOption({
    required this.id,
    required this.label,
    this.hint = '',
    this.icon = '',
    this.iconUrl = '',
  });

  factory PaymentOption.fromJson(Map<String, dynamic> json) {
    return PaymentOption(
      id: (json['id'] as String?) ?? 'cash',
      label: (json['label'] as String?) ?? 'الدفع عند الاستلام',
      hint: (json['hint'] as String?) ?? '',
      icon: (json['icon'] as String?) ?? '',
      iconUrl: (json['icon_url'] as String?) ?? '',
    );
  }
}

class DeliveryRuleInfo {
  final int id;
  final String name;
  final double minKm;
  final double? maxKm;
  final String pricingType;
  final double amount;
  final String range;
  final String label;

  const DeliveryRuleInfo({
    this.id = 0,
    this.name = '',
    this.minKm = 0,
    this.maxKm,
    this.pricingType = 'free',
    this.amount = 0,
    this.range = '',
    this.label = '',
  });

  factory DeliveryRuleInfo.fromJson(Map<String, dynamic> json) {
    return DeliveryRuleInfo(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] as String?) ?? '',
      minKm: (json['min_km'] as num?)?.toDouble() ?? 0,
      maxKm: (json['max_km'] as num?)?.toDouble(),
      pricingType: (json['pricing_type'] as String?) ?? 'free',
      amount: (json['amount'] as num?)?.toDouble() ?? 0,
      range: (json['range'] as String?) ?? '',
      label: (json['label'] as String?) ?? '',
    );
  }
}

class DeliveryConfig {
  final bool enabled;
  final bool firstOrderFree;
  final bool hideDeliverySubtitle;
  final bool notesEnabled;
  final String generalNote;
  final bool hasStoreLocation;
  final double? storeLat;
  final double? storeLng;
  final String storeAddress;
  final double? maxKm;
  final double fallbackFee;
  final bool pickupEnabled;
  final List<DeliveryRuleInfo> rules;

  const DeliveryConfig({
    this.enabled = true,
    this.firstOrderFree = true,
    this.hideDeliverySubtitle = false,
    this.notesEnabled = false,
    this.generalNote = '',
    this.hasStoreLocation = false,
    this.storeLat,
    this.storeLng,
    this.storeAddress = '',
    this.maxKm,
    this.fallbackFee = 15,
    this.pickupEnabled = true,
    this.rules = const [],
  });

  String get policyHint {
    if (rules.isNotEmpty) {
      return rules.map((rule) => '${rule.name} (${rule.label})').join(' · ');
    }
    if (enabled) {
      return 'التوصيل يُحسب حسب المسافة من المتجر';
    }
    return '';
  }

  factory DeliveryConfig.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const DeliveryConfig();
    final rules = <DeliveryRuleInfo>[];
    final raw = json['rules'];
    if (raw is List) {
      for (final item in raw.whereType<Map>()) {
        rules.add(DeliveryRuleInfo.fromJson(Map<String, dynamic>.from(item)));
      }
    }
    return DeliveryConfig(
      enabled: json['enabled'] as bool? ?? true,
      firstOrderFree: json['first_order_free'] as bool? ?? true,
      hideDeliverySubtitle: json['hide_delivery_subtitle'] as bool? ?? false,
      notesEnabled: json['notes_enabled'] as bool? ?? false,
      generalNote: (json['general_note'] as String?) ?? '',
      hasStoreLocation: json['has_store_location'] as bool? ?? false,
      storeLat: (json['store_lat'] as num?)?.toDouble(),
      storeLng: (json['store_lng'] as num?)?.toDouble(),
      storeAddress: (json['store_address'] as String?) ?? '',
      maxKm: (json['max_km'] as num?)?.toDouble(),
      fallbackFee: (json['fallback_fee'] as num?)?.toDouble() ?? 15,
      pickupEnabled: json['pickup_enabled'] as bool? ?? true,
      rules: rules,
    );
  }
}

class StoreConfig {
  final String currency;
  final double shippingFee;
  final double freeShippingThreshold;
  final String bankIban;
  final String bankName;
  final List<PaymentOption> paymentMethods;
  final DeliveryConfig delivery;
  final String fallbackProductImageUrl;
  final List<String> searchPlaceholders;
  final List<String> searchSmartSuggestions;
  final List<String> searchTrending;

  const StoreConfig({
    this.currency = '\u{20C1}',
    this.shippingFee = 15,
    this.freeShippingThreshold = 150,
    this.bankIban = '',
    this.bankName = 'البنك الأهلي السعودي',
    this.delivery = const DeliveryConfig(),
    this.fallbackProductImageUrl = '',
    this.searchPlaceholders = const [],
    this.searchSmartSuggestions = const [],
    this.searchTrending = const [],
    this.paymentMethods = const [
      PaymentOption(
        id: 'cash',
        label: 'الدفع عند الاستلام',
        hint: 'ادفع كاش لمندوب التوصيل في السعودية',
      ),
      PaymentOption(
        id: 'mada',
        label: 'مدى',
        hint: 'بطاقة مدى السعودية — يُؤكد المتجر العملية',
      ),
      PaymentOption(
        id: 'apple_pay',
        label: 'Apple Pay',
        hint: 'ادفع عبر Apple Pay — يُؤكد المتجر العملية',
      ),
      PaymentOption(
        id: 'stc_pay',
        label: 'STC Pay',
        hint: 'محفظة STC Pay — يُؤكد المتجر العملية',
      ),
      PaymentOption(
        id: 'card',
        label: 'فيزا / ماستركارد',
        hint: 'ادفع ببطاقة فيزا أو ماستركارد — يُؤكد المتجر العملية',
      ),
    ],
  });

  List<String> get searchHintPhrases {
    final fromStore = searchPlaceholders
        .map((p) => p.trim())
        .where((p) => p.isNotEmpty)
        .toList();
    if (fromStore.isNotEmpty) return fromStore;
    return AppStrings.homeSearchHints;
  }

  List<String> get smartSearchSuggestions {
    final fromStore = searchSmartSuggestions
        .map((p) => p.trim())
        .where((p) => p.isNotEmpty)
        .toList();
    if (fromStore.isNotEmpty) return fromStore;
    return AppStrings.searchSmartFallback;
  }

  List<String> get trendingSearchTerms {
    final fromStore = searchTrending
        .map((p) => p.trim())
        .where((p) => p.isNotEmpty)
        .toList();
    if (fromStore.isNotEmpty) return fromStore;
    return AppStrings.searchTrendingFallback;
  }

  factory StoreConfig.fromJson(Map<String, dynamic> json) {
    final methods = <PaymentOption>[];
    final raw = json['payment_methods'];
    if (raw is List) {
      for (final item in raw.whereType<Map>()) {
        methods.add(PaymentOption.fromJson(Map<String, dynamic>.from(item)));
      }
    }
    final placeholders = <String>[];
    final rawHints = json['search_placeholders'];
    if (rawHints is List) {
      for (final item in rawHints) {
        final text = item?.toString().trim() ?? '';
        if (text.isNotEmpty) placeholders.add(text);
      }
    }
    final smart = <String>[];
    final rawSmart = json['search_smart_suggestions'];
    if (rawSmart is List) {
      for (final item in rawSmart) {
        final text = item?.toString().trim() ?? '';
        if (text.isNotEmpty) smart.add(text);
      }
    }
    final trending = <String>[];
    final rawTrending = json['search_trending'];
    if (rawTrending is List) {
      for (final item in rawTrending) {
        final text = item?.toString().trim() ?? '';
        if (text.isNotEmpty) trending.add(text);
      }
    }
    return StoreConfig(
      currency: _sarSymbol(json['currency'] as String?),
      shippingFee: (json['shipping_fee'] as num?)?.toDouble() ?? 15,
      freeShippingThreshold:
          (json['free_shipping_threshold'] as num?)?.toDouble() ?? 150,
      bankIban: (json['bank_iban'] as String?) ?? '',
      bankName: (json['bank_name'] as String?) ?? 'البنك الأهلي السعودي',
      paymentMethods: methods.isEmpty
          ? const StoreConfig().paymentMethods
          : methods,
      delivery: json['delivery'] is Map
          ? DeliveryConfig.fromJson(
              Map<String, dynamic>.from(json['delivery'] as Map),
            )
          : const DeliveryConfig(),
      fallbackProductImageUrl:
          (json['fallback_product_image_url'] as String?) ?? '',
      searchPlaceholders: placeholders,
      searchSmartSuggestions: smart,
      searchTrending: trending,
    );
  }
}

enum _PromoFeedKind { discounts, offers }

List<ProductModel> _parsePromoProducts(
  Map<String, dynamic> json, {
  required _PromoFeedKind kind,
}) {
  if (json['discounts'] != null) {
    final source = kind == _PromoFeedKind.discounts
        ? json['discounts']
        : json['offers'];
    return jsonMapList(source, ProductModel.fromJson);
  }

  final legacy = jsonMapList(json['offers'], ProductModel.fromJson);
  if (kind == _PromoFeedKind.discounts) {
    return legacy.where((product) => product.isDiscountPromo).toList();
  }
  return legacy.where((product) => product.isOfferPromo).toList();
}

String _sarSymbol(String? raw) {
  final value = (raw ?? '').trim();
  if (value.isEmpty ||
      value.contains('ر') ||
      value.toUpperCase() == 'SAR' ||
      value.contains('ريال')) {
    return '\u{20C1}';
  }
  return value;
}

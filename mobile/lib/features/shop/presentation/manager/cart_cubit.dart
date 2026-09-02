import 'dart:convert';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../data/models/bundle_model.dart';
import '../../data/models/product_model.dart';
import '../../domain/entities/cart_bundle_line.dart';
import '../../domain/entities/cart_item.dart';
import '../../domain/entities/product.dart';
import '../widgets/cart_display_entries.dart';

part 'cart_state.dart';

const _kCartKeyV1 = 'cart_items_v1';
const _kCartKeyV2 = 'cart_items_v2';

class CartCubit extends Cubit<CartState> {
  CartCubit() : super(const CartState());

  Future<void> restorePersisted() async {
    final prefs = await SharedPreferences.getInstance();
    final rawV2 = prefs.getString(_kCartKeyV2);
    if (rawV2 != null && rawV2.isNotEmpty) {
      _restoreV2(rawV2);
      return;
    }
    final rawV1 = prefs.getString(_kCartKeyV1);
    if (rawV1 != null && rawV1.isNotEmpty) {
      _restoreV1(rawV1);
    }
  }

  void _restoreV2(String raw) {
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map) return;
      final map = Map<String, dynamic>.from(decoded);
      final items = _parseItems(map['items']);
      final bundles = _parseBundles(map['bundles']);
      final displayOrder = (map['display_order'] as List?)
              ?.map((e) => e.toString())
              .where((e) => e.isNotEmpty)
              .toList() ??
          const <String>[];
      if (items.isEmpty && bundles.isEmpty) return;
      emit(CartState(
        items: items,
        bundles: bundles,
        displayOrder: displayOrder,
      ));
    } catch (_) {}
  }

  void _restoreV1(String raw) {
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! List) return;
      final items = <CartItem>[];
      for (final item in decoded.whereType<Map>()) {
        final map = Map<String, dynamic>.from(item);
        final productRaw = map['product'];
        if (productRaw is! Map) continue;
        items.add(CartItem(
          product: ProductModel.fromJson(Map<String, dynamic>.from(productRaw)),
          quantity: (map['quantity'] as num?)?.toInt() ?? 1,
          isGift: map['is_gift'] == true,
          giftForProductId: map['gift_for_product_id']?.toString(),
        ));
      }
      if (items.isNotEmpty) {
        emit(CartState(
          items: items,
          displayOrder: items
              .where((i) => !i.isGift)
              .map((i) => 'product:${i.product.id}')
              .toList(),
        ));
      }
    } catch (_) {}
  }

  List<CartItem> _parseItems(dynamic raw) {
    if (raw is! List) return const [];
    final items = <CartItem>[];
    for (final item in raw.whereType<Map>()) {
      final map = Map<String, dynamic>.from(item);
      final productRaw = map['product'];
      if (productRaw is! Map) continue;
      items.add(CartItem(
        product: ProductModel.fromJson(Map<String, dynamic>.from(productRaw)),
        quantity: (map['quantity'] as num?)?.toInt() ?? 1,
        isGift: map['is_gift'] == true,
        giftForProductId: map['gift_for_product_id']?.toString(),
        bundleLineId: map['bundle_line_id']?.toString(),
        isBundleChild: map['is_bundle_child'] == true,
      ));
    }
    return items;
  }

  List<CartBundleLine> _parseBundles(dynamic raw) {
    if (raw is! List) return const [];
    return raw
        .whereType<Map>()
        .map((item) => CartBundleLine.fromJson(Map<String, dynamic>.from(item)))
        .where((line) => line.id.isNotEmpty && line.bundle.id.isNotEmpty)
        .toList();
  }

  void addToCart(Product product) {
    final items = List<CartItem>.from(state.items);
    final paidKey = 'paid:${product.id}';
    final idx = items.indexWhere((i) => i.cartKey == paidKey);

    if (idx >= 0) {
      items[idx] = items[idx].copyWith(quantity: items[idx].quantity + 1);
    } else {
      items.add(CartItem(product: ProductModel.fromEntity(product)));
    }

    _syncGiftForParent(items, product);

    final displayOrder = List<String>.from(state.displayOrder);
    if (!displayOrder.contains('product:${product.id}')) {
      displayOrder.add('product:${product.id}');
    }

    emit(state.copyWith(
      items: items,
      displayOrder: displayOrder,
      lastAddedProductName: product.name,
    ));
    _persist();
  }

  void addBundleToCart(BundleModel bundle) {
    if (!bundle.isAvailable || bundle.items.isEmpty) return;

    final bundles = List<CartBundleLine>.from(state.bundles);
    final displayOrder = List<String>.from(state.displayOrder);
    final existingIdx =
        bundles.indexWhere((line) => line.bundle.id == bundle.id);

    if (existingIdx >= 0) {
      final existing = bundles[existingIdx];
      bundles[existingIdx] =
          existing.copyWith(quantity: existing.quantity + 1);
    } else {
      final lineId = '${bundle.id}-${DateTime.now().microsecondsSinceEpoch}';
      bundles.add(CartBundleLine(
        id: lineId,
        bundle: bundle,
      ));
      displayOrder.add('bundle:$lineId');
    }

    emit(state.copyWith(
      bundles: bundles,
      displayOrder: displayOrder,
      lastAddedProductName: bundle.name,
    ));
    _persist();
  }

  void removeBundle(String bundleLineId) {
    final bundles = List<CartBundleLine>.from(state.bundles)
      ..removeWhere((line) => line.id == bundleLineId);
    final displayOrder = List<String>.from(state.displayOrder)
      ..remove('bundle:$bundleLineId');

    emit(state.copyWith(bundles: bundles, displayOrder: displayOrder));
    _persist();
  }

  void updateBundleQuantity(String bundleLineId, int quantity) {
    if (quantity <= 0) {
      removeBundle(bundleLineId);
      return;
    }

    final bundles = List<CartBundleLine>.from(state.bundles);
    final idx = bundles.indexWhere((line) => line.id == bundleLineId);
    if (idx < 0) return;

    bundles[idx] = bundles[idx].copyWith(quantity: quantity);
    emit(state.copyWith(bundles: bundles));
    _persist();
  }

  void removeFromCart(String productId) {
    final items = List<CartItem>.from(state.items)
      ..removeWhere(
        (i) =>
            i.cartKey == 'paid:$productId' ||
            (i.isGift && i.giftForProductId == productId),
      );
    final displayOrder = List<String>.from(state.displayOrder)
      ..remove('product:$productId');

    emit(state.copyWith(items: items, displayOrder: displayOrder));
    _persist();
  }

  void updateQuantity(String productId, int quantity) {
    if (quantity <= 0) {
      removeFromCart(productId);
      return;
    }

    final items = List<CartItem>.from(state.items);
    final paidKey = 'paid:$productId';
    final idx = items.indexWhere((i) => i.cartKey == paidKey);
    if (idx < 0) return;

    final parent = items[idx].product;
    items[idx] = items[idx].copyWith(quantity: quantity);
    _syncGiftForParent(items, parent, parentQty: quantity);

    emit(state.copyWith(items: items));
    _persist();
  }

  void clearCart() {
    emit(const CartState());
    _persist();
  }

  void dismissNotification() =>
      emit(state.copyWith(clearNotification: true));

  void _syncGiftForParent(
    List<CartItem> items,
    Product parent, {
    int? parentQty,
    String? bundleLineId,
  }) {
    final gift = parent.giftProduct;
    final paidKey = bundleLineId == null
        ? 'paid:${parent.id}'
        : 'bundle:$bundleLineId:paid:${parent.id}';
    final qty = parentQty ??
        items
            .where((i) => i.cartKey == paidKey)
            .fold<int>(0, (sum, i) => sum + i.quantity);

    final giftKey = bundleLineId == null
        ? parent.id
        : '$bundleLineId:${parent.id}';

    if (gift == null || !gift.isAvailable || qty < 1) {
      items.removeWhere(
        (i) => i.isGift && i.giftForProductId == giftKey,
      );
      return;
    }

    final giftIdx = items.indexWhere(
      (i) => i.isGift && i.giftForProductId == giftKey,
    );
    final giftProduct = ProductModel(
      id: gift.id,
      name: gift.name,
      description: '',
      price: 0,
      imageUrl: gift.imageUrl,
      categoryId: parent.categoryId,
      stock: qty,
    );

    if (giftIdx >= 0) {
      items[giftIdx] = items[giftIdx].copyWith(quantity: qty);
    } else {
      items.add(
        CartItem(
          product: giftProduct,
          quantity: qty,
          isGift: true,
          giftForProductId: giftKey,
          bundleLineId: bundleLineId,
          isBundleChild: bundleLineId != null,
        ),
      );
    }
  }

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    final payload = {
      'items': state.items.map((item) {
        final product = ProductModel.fromEntity(item.product);
        return {
          'quantity': item.quantity,
          'is_gift': item.isGift,
          if (item.giftForProductId != null)
            'gift_for_product_id': item.giftForProductId,
          if (item.bundleLineId != null) 'bundle_line_id': item.bundleLineId,
          'is_bundle_child': item.isBundleChild,
          'product': product.toJson(),
        };
      }).toList(),
      'bundles': state.bundles.map((line) => line.toJson()).toList(),
      'display_order': state.displayOrder,
    };
    await prefs.setString(_kCartKeyV2, jsonEncode(payload));
  }
}

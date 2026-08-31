import 'dart:convert';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../domain/entities/cart_item.dart';
import '../../domain/entities/product.dart';
import '../../data/models/bundle_model.dart';
import '../../data/models/product_model.dart';

part 'cart_state.dart';

const _kCartKey = 'cart_items_v1';

class CartCubit extends Cubit<CartState> {
  CartCubit() : super(const CartState());

  Future<void> restorePersisted() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_kCartKey);
    if (raw == null || raw.isEmpty) return;
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
      if (items.isNotEmpty) emit(state.copyWith(items: items));
    } catch (_) {}
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

    emit(state.copyWith(
      items: items,
      lastAddedProductName: product.name,
    ));
    _persist();
  }

  void addBundleToCart(BundleModel bundle) {
    if (!bundle.isAvailable || bundle.items.isEmpty) return;

    final items = List<CartItem>.from(state.items);
    for (final item in bundle.items) {
      final product = item.product;
      final paidKey = 'paid:${product.id}';
      final idx = items.indexWhere((i) => i.cartKey == paidKey);

      if (idx >= 0) {
        items[idx] = items[idx].copyWith(
          quantity: items[idx].quantity + item.quantity,
        );
      } else {
        items.add(
          CartItem(
            product: ProductModel.fromEntity(product),
            quantity: item.quantity,
          ),
        );
      }
      _syncGiftForParent(items, product);
    }

    emit(state.copyWith(
      items: items,
      lastAddedProductName: bundle.name,
    ));
    _persist();
  }

  void removeFromCart(String productId) {
    final items = List<CartItem>.from(state.items)
      ..removeWhere(
        (i) =>
            i.cartKey == 'paid:$productId' ||
            (i.isGift && i.giftForProductId == productId),
      );
    emit(state.copyWith(items: items));
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
  }) {
    final gift = parent.giftProduct;
    final paidKey = 'paid:${parent.id}';
    final qty = parentQty ??
        items
            .where((i) => i.cartKey == paidKey)
            .fold<int>(0, (sum, i) => sum + i.quantity);

    if (gift == null || !gift.isAvailable || qty < 1) {
      items.removeWhere((i) => i.isGift && i.giftForProductId == parent.id);
      return;
    }

    final giftIdx =
        items.indexWhere((i) => i.isGift && i.giftForProductId == parent.id);
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
          giftForProductId: parent.id,
        ),
      );
    }
  }

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    final payload = state.items.map((item) {
      final product = ProductModel.fromEntity(item.product);
      return {
        'quantity': item.quantity,
        'is_gift': item.isGift,
        if (item.giftForProductId != null)
          'gift_for_product_id': item.giftForProductId,
        'product': product.toJson(),
      };
    }).toList();
    await prefs.setString(_kCartKey, jsonEncode(payload));
  }
}

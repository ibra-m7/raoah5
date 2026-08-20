import 'dart:convert';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../domain/entities/cart_item.dart';
import '../../domain/entities/product.dart';
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
        ));
      }
      if (items.isNotEmpty) emit(state.copyWith(items: items));
    } catch (_) {}
  }

  void addToCart(Product product) {
    final items = List<CartItem>.from(state.items);
    final idx = items.indexWhere((i) => i.product.id == product.id);

    if (idx >= 0) {
      items[idx] = items[idx].copyWith(quantity: items[idx].quantity + 1);
    } else {
      items.add(CartItem(product: ProductModel.fromEntity(product)));
    }

    emit(state.copyWith(
      items: items,
      lastAddedProductName: product.name,
    ));
    _persist();
  }

  void removeFromCart(String productId) {
    final items = state.items.where((i) => i.product.id != productId).toList();
    emit(state.copyWith(items: items));
    _persist();
  }

  void updateQuantity(String productId, int quantity) {
    if (quantity <= 0) {
      removeFromCart(productId);
      return;
    }
    final items = state.items.map((i) {
      return i.product.id == productId ? i.copyWith(quantity: quantity) : i;
    }).toList();
    emit(state.copyWith(items: items));
    _persist();
  }

  void clearCart() {
    emit(const CartState());
    _persist();
  }

  void dismissNotification() =>
      emit(state.copyWith(clearNotification: true));

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    final payload = state.items.map((item) {
      final product = ProductModel.fromEntity(item.product);
      return {
        'quantity': item.quantity,
        'product': product.toJson(),
      };
    }).toList();
    await prefs.setString(_kCartKey, jsonEncode(payload));
  }
}

part of 'cart_cubit.dart';

class CartState extends Equatable {
  /// قائمة المنتجات المختارة مع كمياتها
  final List<CartItem> items;

  /// اسم آخر منتج أُضيف — يُستخدم لإطلاق SnackBar ثم يُمسح
  final String? lastAddedProductName;

  const CartState({
    this.items = const [],
    this.lastAddedProductName,
  });

  // ── Computed ──────────────────────────────────────────────────────────────

  /// عدد القطع الإجمالي (مجموع الكميات)
  int get count => items.fold(0, (sum, i) => sum + i.quantity);

  /// عدد أصناف مختلفة في السلة
  int get distinctCount => items.length;

  /// إجمالي السعر
  double get total => items.fold(0.0, (sum, i) => sum + i.totalPrice);

  bool get isEmpty => items.isEmpty;
  bool get isNotEmpty => items.isNotEmpty;

  // ── copyWith ──────────────────────────────────────────────────────────────

  CartState copyWith({
    List<CartItem>? items,
    String? lastAddedProductName,
    bool clearNotification = false,
  }) {
    return CartState(
      items: items ?? this.items,
      lastAddedProductName: clearNotification
          ? null
          : (lastAddedProductName ?? this.lastAddedProductName),
    );
  }

  @override
  List<Object?> get props => [items, lastAddedProductName];
}

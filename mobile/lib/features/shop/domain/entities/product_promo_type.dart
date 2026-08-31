enum ProductPromoType {
  none,
  discount,
  offer;

  static ProductPromoType fromApi(String? value, {required bool hasDiscount}) {
    switch (value) {
      case 'offer':
        return ProductPromoType.offer;
      case 'discount':
        return ProductPromoType.discount;
      default:
        return hasDiscount ? ProductPromoType.discount : ProductPromoType.none;
    }
  }

  String? badgeLabel({required bool hasDiscount, required int discountPercent}) {
    if (!hasDiscount || this == ProductPromoType.none) return null;
    if (this == ProductPromoType.offer) return 'عرض خاص';
    if (discountPercent > 0) return 'خصم $discountPercent%';
    return 'خصم';
  }
}

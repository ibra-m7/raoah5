import 'package:flutter/material.dart';

import '../../../../core/constants/app_strings.dart';

/// سعر يتكيّف مع العرض المتاح حتى لا يحدث overflow في البطاقات الضيقة.
class PriceLine extends StatelessWidget {
  final double price;
  final double? originalPrice;
  final Color color;
  final double priceSize;
  final double currencySize;
  final AlignmentGeometry alignment;

  const PriceLine({
    super.key,
    required this.price,
    this.originalPrice,
    this.color = const Color(0xFFE53935),
    this.priceSize = 14,
    this.currencySize = 0,
    this.alignment = AlignmentDirectional.centerEnd,
  });

  bool get _hasDiscount =>
      originalPrice != null && originalPrice! > price;

  double get _sarSize =>
      currencySize > 0 ? currencySize : priceSize * 1.28;

  @override
  Widget build(BuildContext context) {
    return FittedBox(
      fit: BoxFit.scaleDown,
      alignment: alignment,
      child: Row(
        mainAxisSize: MainAxisSize.min,
        textDirection: TextDirection.rtl,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Text(
            price.toStringAsFixed(2),
            style: TextStyle(
              fontSize: priceSize,
              fontWeight: FontWeight.w900,
              color: color,
              height: 1.1,
            ),
          ),
          Padding(
            padding: const EdgeInsetsDirectional.only(start: 4),
            child: Text(
              AppStrings.currency,
              style: TextStyle(
                fontFamily: 'SaudiRiyal',
                fontSize: _sarSize,
                fontWeight: FontWeight.w400,
                color: color,
                height: 1,
              ),
            ),
          ),
          if (_hasDiscount) ...[
            const SizedBox(width: 6),
            Text(
              originalPrice!.toStringAsFixed(2),
              style: TextStyle(
                fontSize: priceSize * 0.82,
                color: const Color(0xFF6B7280).withValues(alpha: 0.75),
                decoration: TextDecoration.lineThrough,
                fontWeight: FontWeight.w500,
                height: 1.1,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

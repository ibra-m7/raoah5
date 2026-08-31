import 'package:flutter/material.dart';

import '../../domain/entities/product.dart';
import '../../domain/entities/product_promo_type.dart';

class PromoBadge extends StatelessWidget {
  final Product product;
  final double fontSize;

  const PromoBadge({
    super.key,
    required this.product,
    this.fontSize = 8,
  });

  @override
  Widget build(BuildContext context) {
    final label = product.promoBadgeLabel;
    if (label == null) return const SizedBox.shrink();

    final isOffer = product.promoType == ProductPromoType.offer;
    final background = isOffer ? const Color(0xFFFF8A3D) : Colors.redAccent;
    final foreground = Colors.white;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: foreground,
          fontSize: fontSize,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

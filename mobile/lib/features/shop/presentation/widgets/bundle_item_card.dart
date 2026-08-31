import 'package:flutter/material.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_scale.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../data/models/bundle_model.dart';
import '../../data/models/product_model.dart';

/// بطاقة منتج داخل صفحة السلة — عرض فقط بدون أزرار تفاعل.
class BundleItemCard extends StatelessWidget {
  final BundleItemModel item;

  const BundleItemCard({super.key, required this.item});

  String _title(ProductModel product) {
    final label = product.quantityLabel.trim();
    if (label.isNotEmpty) return '${product.name} $label';
    return product.name;
  }

  @override
  Widget build(BuildContext context) {
    final scale = AppScale.of(context);
    final product = item.product;

    return Container(
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE0E0E0), width: 1.2),
        boxShadow: const [
          BoxShadow(
            color: AppTheme.cardShadow,
            blurRadius: 6,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Padding(
        padding: EdgeInsets.fromLTRB(
          scale.s(10),
          scale.s(12),
          scale.s(10),
          scale.s(14),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(child: _DualBadge(price: product.effectivePrice, qty: item.quantity)),
            SizedBox(height: scale.s(10)),
            Text(
              _title(product),
              textAlign: TextAlign.center,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: scale.s(12.5),
                fontWeight: FontWeight.w800,
                color: AppTheme.darkText,
                height: 1.25,
              ),
            ),
            SizedBox(height: scale.s(10)),
            Expanded(
              child: Container(
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: AppTheme.productImageWell,
                  borderRadius: BorderRadius.circular(scale.s(8)),
                ),
                child: Padding(
                  padding: EdgeInsets.all(scale.s(8)),
                  child: AppNetworkImage(
                    product.displayImage,
                    fit: BoxFit.contain,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DualBadge extends StatelessWidget {
  final double price;
  final int qty;

  const _DualBadge({required this.price, required this.qty});

  @override
  Widget build(BuildContext context) {
    final scale = AppScale.of(context);
    final radius = BorderRadius.circular(scale.s(8));

    return ClipRRect(
      borderRadius: radius,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: EdgeInsets.symmetric(
              horizontal: scale.s(10),
              vertical: scale.s(5),
            ),
            color: AppTheme.surface,
            child: Row(
              mainAxisSize: MainAxisSize.min,
              textDirection: TextDirection.rtl,
              children: [
                Text(
                  AppStrings.currency,
                  style: TextStyle(
                    fontFamily: 'SaudiRiyal',
                    fontSize: scale.s(11),
                    color: AppTheme.mutedText,
                    height: 1,
                  ),
                ),
                SizedBox(width: scale.s(4)),
                Text(
                  price.toStringAsFixed(2),
                  style: TextStyle(
                    fontSize: scale.s(13),
                    fontWeight: FontWeight.w900,
                    color: AppTheme.badgeNumber,
                    height: 1,
                  ),
                ),
              ],
            ),
          ),
          Container(
            width: double.infinity,
            padding: EdgeInsets.symmetric(vertical: scale.s(4)),
            color: AppTheme.primarySurface,
            alignment: Alignment.center,
            child: Text(
              AppStrings.bundleItemQty(qty),
              style: TextStyle(
                fontSize: scale.s(11),
                fontWeight: FontWeight.w700,
                color: AppTheme.mutedText,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

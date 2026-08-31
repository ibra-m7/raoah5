import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_scale.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../data/models/product_model.dart';
import 'card_cart_control.dart';
import 'celebrate_anchors.dart';
import 'price_line.dart';
import 'product_gift_overlay.dart';
import 'product_preview_sheet.dart';
import 'promo_badge.dart';
import 'quantity_label_chip.dart';
import 'sold_proof_line.dart';

class ProductCard extends StatefulWidget {
  final ProductModel product;
  final String heroTag;
  final VoidCallback? onOpened;
  final bool circularCartButton;

  /// لون خلفية حاوية الصورة فقط (وليس قسم النص).
  final Color? imageWellColor;

  /// هامش داخل حاوية الصورة (أصغر = صورة أكبر).
  final double? imageInset;

  /// عرض الصورة كنسبة من حاوية الصورة (مثلاً 0.9 = 90%).
  final double? imageWidthFactor;

  /// تكبير عرض الصورة داخل الحاوية (مع [imageWidthFactor]).
  final double? imageWidthBoost;

  /// تكبير ارتفاع الصورة داخل الحاوية (مع [imageWidthFactor]).
  final double? imageHeightBoost;

  /// تكبير بصري للصورة داخل نفس حجم الكارد.
  final double imageScale;

  /// نقطة ارتكاز التكبير (مثلاً من الأعلى).
  final Alignment imageScaleAlignment;

  /// تقليل مساحة النص لإعطاء الصورة مساحة أكبر (بدون تكبير الكارد).
  final bool compactFooter;

  /// شريط وصندوق الهدية أصغر (مثلاً كروت البحث الضيقة).
  final bool compactGiftOverlay;

  /// نقطة ارتكاز تكبير الصورة داخل الحاوية (مع [imageWidthFactor]).
  final Alignment imageBoostAlignment;

  /// سحب الصورة للأعلى داخل الحاوية (مع [imageWidthFactor]).
  final double? imageTopLift;

  const ProductCard({
    super.key,
    required this.product,
    required this.heroTag,
    this.onOpened,
    this.circularCartButton = false,
    Color? imageWellColor,
    Color? cardColor,
    this.imageInset,
    this.imageWidthFactor,
    this.imageWidthBoost,
    this.imageHeightBoost,
    this.imageTopLift,
    this.imageBoostAlignment = Alignment.topCenter,
    this.imageScale = 1,
    this.imageScaleAlignment = Alignment.center,
    this.compactFooter = false,
    this.compactGiftOverlay = false,
  }) : imageWellColor = imageWellColor ?? cardColor;

  @override
  State<ProductCard> createState() => _ProductCardState();
}

class _ProductCardState extends State<ProductCard> {
  final Object _giftCelebrateAnchor = Object();
  final Object _productImageAnchor = Object();

  void _openDetails(BuildContext context) {
    widget.onOpened?.call();
    showProductPreview(
      context,
      widget.product,
      heroTag: widget.heroTag,
    );
  }

  static const _cartButtonInset = 6.0;
  static const _cartButtonBottom = 32.0;

  double _compactFooterHeight(AppScale scale, {required bool hasQuantityLabel}) {
    return scale.s(1) +
        scale.s(12) +
        scale.s(1) +
        scale.s(15) +
        (hasQuantityLabel ? scale.s(12) : 0) +
        scale.s(28) +
        scale.s(2);
  }

  static const _defaultImageInset = 8.0;

  Widget _buildProductImage({
    required AppScale scale,
    required String heroTag,
    required ProductModel product,
    required Color wellColor,
  }) {
    return CelebrateAnchor(
      anchor: _productImageAnchor,
      child: Hero(
        tag: heroTag,
        child: Padding(
          padding: EdgeInsets.all(scale.s(widget.imageInset ?? _defaultImageInset)),
          child: AppNetworkImage(
            product.displayImage,
            fit: BoxFit.cover,
            alignment: Alignment.center,
            placeholder: Shimmer.fromColors(
              baseColor: const Color(0xFFEEEEEE),
              highlightColor: const Color(0xFFFAFAFA),
              child: Container(color: wellColor),
            ),
            error: ColoredBox(
              color: wellColor,
              child: const Icon(
                Icons.image_not_supported_outlined,
                color: AppTheme.mutedText,
                size: 28,
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildPriceRow({
    required AppScale scale,
    required ProductModel product,
    required double priceH,
    required VoidCallback onOpenDetails,
  }) {
    return SizedBox(
      height: priceH,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(
            child: GestureDetector(
              onTap: onOpenDetails,
              behavior: HitTestBehavior.opaque,
              child: PriceLine(
                price: product.effectivePrice,
                originalPrice: product.hasDiscount ? product.price : null,
                alignment: AlignmentDirectional.centerStart,
                priceSize: scale.s(widget.compactFooter ? 17 : 18),
                maxHeight: priceH,
              ),
            ),
          ),
          if (product.displayPieceCount > 1) ...[
            SizedBox(width: scale.s(4)),
            Container(
              padding: EdgeInsets.symmetric(
                horizontal: scale.s(6),
                vertical: scale.s(3),
              ),
              decoration: BoxDecoration(
                color: const Color(0xFFFF8A3D),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                product.packDisplayLabel,
                style: TextStyle(
                  fontSize: scale.s(9),
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                  height: 1.1,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildCardFooter({
    required AppScale scale,
    required ProductModel product,
    required double soldH,
    required double nameH,
    required double quantityH,
    required double priceH,
    required VoidCallback onOpenDetails,
  }) {
    final quantityLabel = product.quantityLabel.trim();

    return Padding(
      padding: EdgeInsets.fromLTRB(
        scale.s(6),
        scale.s(widget.compactFooter ? 1 : 4),
        scale.s(6),
        scale.s(widget.compactFooter ? 2 : 8),
      ),
      child: GestureDetector(
        onTap: onOpenDetails,
        behavior: HitTestBehavior.opaque,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (widget.compactFooter || product.soldCount > 0) ...[
              SizedBox(
                height: soldH,
                child: product.soldCount > 0
                    ? SoldProofLine(
                        soldCount: product.soldCount,
                        fontSize: scale.s(9),
                      )
                    : const SizedBox.shrink(),
              ),
              SizedBox(height: scale.s(widget.compactFooter ? 1 : 4)),
            ],
            SizedBox(
              height: nameH,
              child: ProductNameText(
                product.name,
                baseSize: scale.s(widget.compactFooter ? 12 : 13),
                maxLines: widget.compactFooter ? 1 : 2,
              ),
            ),
            if (quantityLabel.isNotEmpty) ...[
              SizedBox(height: scale.s(widget.compactFooter ? 0 : 2)),
              SizedBox(
                height: quantityH,
                child: QuantityLabelChip(
                  product: product,
                  fontSize: scale.s(widget.compactFooter ? 9 : 10),
                  compact: true,
                ),
              ),
            ],
            SizedBox(height: scale.s(widget.compactFooter ? 0 : 2)),
            _buildPriceRow(
              scale: scale,
              product: product,
              priceH: priceH,
              onOpenDetails: onOpenDetails,
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = widget.product;
    final heroTag = widget.heroTag;
    final scale = AppScale.of(context);
    final soldH = scale.s(widget.compactFooter ? 12 : 16);
    final nameH = scale.s(widget.compactFooter ? 15 : 19);
    final quantityH = scale.s(widget.compactFooter ? 12 : 14);
    final priceH = scale.s(widget.compactFooter ? 28 : 34);
    final hasQuantityLabel = p.quantityLabel.trim().isNotEmpty;
    final hasGift = p.hasGiftProduct;
    final wellRadius = BorderRadius.circular(scale.s(8));
    final wellColor = widget.imageWellColor ?? AppTheme.productImageWell;
    final giftBadgeSize = widget.compactGiftOverlay ? 20.0 : 26.0;
    final giftStripHeight =
        widget.compactGiftOverlay ? 13.0 : ProductGiftCardStrip.stripHeight;
    final giftStripFontSize = widget.compactGiftOverlay ? 7.0 : 8.0;
    final giftStripIconSize = widget.compactGiftOverlay ? 9.0 : 12.0;
    final giftOverlayInset = widget.compactGiftOverlay ? 4.0 : 6.0;

    return RepaintBoundary(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Expanded(
            child: Stack(
              clipBehavior: Clip.none,
              children: [
                GestureDetector(
                  onTap: () => _openDetails(context),
                  behavior: HitTestBehavior.opaque,
                  child: SizedBox(
                    width: double.infinity,
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        borderRadius: wellRadius,
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.05),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                            spreadRadius: -1,
                          ),
                        ],
                      ),
                      child: ClipRRect(
                        borderRadius: wellRadius,
                        child: Stack(
                          fit: StackFit.expand,
                          clipBehavior: Clip.hardEdge,
                          children: [
                            ColoredBox(color: wellColor),
                            _buildProductImage(
                              scale: scale,
                              heroTag: heroTag,
                              product: p,
                              wellColor: wellColor,
                            ),
                            if (p.hasDiscount)
                              PositionedDirectional(
                                top: giftOverlayInset,
                                start: giftOverlayInset,
                                child: PromoBadge(product: p),
                              ),
                            if (hasGift && p.giftProduct != null)
                              PositionedDirectional(
                                top: giftOverlayInset,
                                end: giftOverlayInset,
                                child: ProductGiftBadge(
                                  positionAnchor: _giftCelebrateAnchor,
                                  gift: p.giftProduct!,
                                  size: giftBadgeSize,
                                ),
                              ),
                            if (!p.isAvailable)
                              Positioned.fill(
                                child: ColoredBox(
                                  color: Colors.black.withValues(alpha: 0.4),
                                  child: const Center(
                                    child: Text(
                                      AppStrings.productOutOfStock,
                                      style: TextStyle(
                                        color: Colors.white,
                                        fontWeight: FontWeight.w800,
                                        fontSize: 11,
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            Positioned(
                              left: 0,
                              right: 0,
                              bottom: 0,
                              height: scale.s(5),
                              child: IgnorePointer(
                                child: DecoratedBox(
                                  decoration: BoxDecoration(
                                    gradient: LinearGradient(
                                      begin: Alignment.topCenter,
                                      end: Alignment.bottomCenter,
                                      colors: [
                                        Colors.black.withValues(alpha: 0.04),
                                        Colors.black.withValues(alpha: 0),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            ),
                            if (hasGift)
                              Positioned(
                                left: 0,
                                right: 0,
                                bottom: 0,
                                child: ProductGiftCardStrip(
                                  fullWidth: true,
                                  embedded: true,
                                  height: giftStripHeight,
                                  fontSize: giftStripFontSize,
                                  iconSize: giftStripIconSize,
                                  horizontalPadding:
                                      widget.compactGiftOverlay ? 4 : 6,
                                  borderRadius: BorderRadius.zero,
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
                PositionedDirectional(
                  start: scale.s(_cartButtonInset),
                  bottom: scale.s(_cartButtonBottom),
                  child: CardCartControl(
                    product: p,
                    circular: widget.circularCartButton,
                    productImageAnchor: _productImageAnchor,
                    giftCelebrateAnchor:
                        hasGift ? _giftCelebrateAnchor : null,
                  ),
                ),
              ],
            ),
          ),
          if (widget.compactFooter)
            SizedBox(
              height: _compactFooterHeight(
                scale,
                hasQuantityLabel: hasQuantityLabel,
              ),
              child: ClipRect(
                child: _buildCardFooter(
                  scale: scale,
                  product: p,
                  soldH: soldH,
                  nameH: nameH,
                  quantityH: quantityH,
                  priceH: priceH,
                  onOpenDetails: () => _openDetails(context),
                ),
              ),
            )
          else
            _buildCardFooter(
              scale: scale,
              product: p,
              soldH: soldH,
              nameH: nameH,
              quantityH: quantityH,
              priceH: priceH,
              onOpenDetails: () => _openDetails(context),
            ),
        ],
      ),
    );
  }
}

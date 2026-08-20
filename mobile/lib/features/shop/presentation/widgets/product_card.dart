import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shimmer/shimmer.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../data/models/product_model.dart';
import '../manager/cart_cubit.dart';
import '../manager/favorite_cubit.dart';
import 'price_line.dart';
import 'product_preview_sheet.dart';
import 'sold_proof_line.dart';

class ProductCard extends StatefulWidget {
  final ProductModel product;
  final String heroTag;

  const ProductCard({
    super.key,
    required this.product,
    required this.heroTag,
  });

  @override
  State<ProductCard> createState() => _ProductCardState();
}

class _ProductCardState extends State<ProductCard>
    with SingleTickerProviderStateMixin {
  late final AnimationController _addCtrl;

  @override
  void initState() {
    super.initState();
    _addCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 150),
      lowerBound: 0.88,
      upperBound: 1.0,
      value: 1.0,
    );
  }

  @override
  void dispose() {
    _addCtrl.dispose();
    super.dispose();
  }

  void _onAddToCart(BuildContext ctx) async {
    await _addCtrl.reverse();
    await _addCtrl.forward();
    if (ctx.mounted) {
      ctx.read<CartCubit>().addToCart(widget.product);
      ScaffoldMessenger.of(ctx)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(
          content: Text(
            AppStrings.productAddedToCart(widget.product.name),
            textAlign: TextAlign.center,
          ),
          backgroundColor: AppTheme.primaryDark,
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 2),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          margin: const EdgeInsets.fromLTRB(16, 0, 16, 80),
        ));
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = widget.product;
    return GestureDetector(
      onTap: () => showProductPreview(
        context,
        p,
        heroTag: widget.heroTag,
      ),
      child: Container(
        decoration: BoxDecoration(
          color: AppTheme.surface,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.07),
              blurRadius: 14,
              offset: const Offset(0, 5),
            ),
          ],
        ),
        child: Stack(
          clipBehavior: Clip.none,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(
                  flex: 7,
                  child: Stack(
                    clipBehavior: Clip.none,
                    children: [
                      Positioned.fill(
                        child: ClipRRect(
                          borderRadius: const BorderRadius.vertical(
                            top: Radius.circular(16),
                          ),
                          child: Hero(
                            tag: widget.heroTag,
                            child: AppNetworkImage(
                              p.imageUrl,
                              fit: BoxFit.cover,
                              placeholder: Shimmer.fromColors(
                                baseColor: const Color(0xFFE8F8EC),
                                highlightColor: const Color(0xFFF5FFF9),
                                child: Container(color: Colors.white),
                              ),
                              error: Container(
                                color: AppTheme.background,
                                child: Icon(
                                  Icons.image_not_supported_outlined,
                                  color: AppTheme.mutedText,
                                  size: 28,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                      if (p.hasDiscount)
                        PositionedDirectional(
                          top: 6,
                          start: 6,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.redAccent,
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: const Text(
                              'عرض',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 8,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ),
                        ),
                      PositionedDirectional(
                        top: 6,
                        end: 6,
                        child: Material(
                          color: Colors.white.withValues(alpha: 0.92),
                          shape: const CircleBorder(),
                          clipBehavior: Clip.antiAlias,
                          child: InkWell(
                            onTap: () {
                              HapticFeedback.selectionClick();
                              context.read<FavoriteCubit>().toggle(p.id);
                            },
                            child: Padding(
                              padding: const EdgeInsets.all(5),
                              child: BlocBuilder<FavoriteCubit, FavoriteState>(
                                buildWhen: (prev, curr) =>
                                    prev.contains(p.id) != curr.contains(p.id),
                                builder: (context, fav) {
                                  final on = fav.contains(p.id);
                                  return Icon(
                                    on
                                        ? Icons.favorite_rounded
                                        : Icons.favorite_border_rounded,
                                    size: 15,
                                    color: on
                                        ? Colors.redAccent
                                        : AppTheme.darkText,
                                  );
                                },
                              ),
                            ),
                          ),
                        ),
                      ),
                      if (!p.isAvailable)
                        Positioned.fill(
                          child: ClipRRect(
                            borderRadius: const BorderRadius.vertical(
                              top: Radius.circular(16),
                            ),
                            child: Container(
                              color: Colors.black.withValues(alpha: 0.4),
                              alignment: Alignment.center,
                              child: const Text(
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
                    ],
                  ),
                ),
                Expanded(
                  flex: 5,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(7, 14, 7, 6),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        if (p.soldCount > 0) ...[
                          SoldProofLine(soldCount: p.soldCount, fontSize: 9),
                          const SizedBox(height: 2),
                        ],
                        Flexible(
                          fit: FlexFit.loose,
                          child: ProductNameText(
                            p.name,
                            baseSize: 12,
                            maxLines: 2,
                          ),
                        ),
                        if (p.quantityLabel.isNotEmpty) ...[
                          const SizedBox(height: 1),
                          Text(
                            p.quantityLabel,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: AppTheme.mutedText,
                              height: 1.15,
                            ),
                          ),
                        ],
                        const SizedBox(height: 4),
                        PriceLine(
                          price: p.effectivePrice,
                          originalPrice: p.hasDiscount ? p.price : null,
                          alignment: AlignmentDirectional.centerStart,
                          priceSize: 13,
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
            Align(
              alignment: const AlignmentDirectional(-1, 0.18),
              child: Padding(
                padding: const EdgeInsetsDirectional.only(start: 6),
                child: ScaleTransition(
                  scale: _addCtrl,
                  child: Material(
                    color: p.isAvailable
                        ? const Color(0xFF2E9B57)
                        : const Color(0xFFC5D4CB),
                    borderRadius: BorderRadius.circular(8),
                    elevation: 3,
                    shadowColor: Colors.black26,
                    child: InkWell(
                      onTap: p.isAvailable ? () => _onAddToCart(context) : null,
                      borderRadius: BorderRadius.circular(8),
                      child: const SizedBox(
                        width: 26,
                        height: 26,
                        child: Icon(
                          Icons.add_rounded,
                          color: Colors.white,
                          size: 18,
                        ),
                      ),
                    ),
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

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/theme/app_scale.dart';
import '../../../../core/theme/app_theme.dart';
import '../../data/models/bundle_model.dart';
import '../manager/cart_cubit.dart';
import '../pages/bundle_details_screen.dart';
import 'bundle_cover_images.dart';
import 'celebrate_anchors.dart';
import 'price_line.dart';
import 'product_fly_overlay.dart';

class BundleCard extends StatefulWidget {
  final BundleModel bundle;
  final double width;

  const BundleCard({
    super.key,
    required this.bundle,
    required this.width,
  });

  @override
  State<BundleCard> createState() => _BundleCardState();
}

class _BundleCardState extends State<BundleCard> {
  final Object _imageAnchor = Object();

  void _openDetails() {
    Navigator.of(context).pushNamed(
      AppRouter.bundleDetails,
      arguments: BundleDetailsArgs(bundle: widget.bundle),
    );
  }

  void _addToCart() {
    final bundle = widget.bundle;
    if (!bundle.isAvailable) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text(AppStrings.bundleUnavailable)),
      );
      return;
    }

    HapticFeedback.lightImpact();
    final imageUrl = bundle.flyImageUrl;
    if (imageUrl.isNotEmpty) {
      ProductFlyController.play(
        context: context,
        imageUrl: imageUrl,
        productAnchor: _imageAnchor,
      );
    }
    context.read<CartCubit>().addBundleToCart(bundle);
  }

  @override
  Widget build(BuildContext context) {
    final scale = AppScale.of(context);
    final bundle = widget.bundle;
    final discount = bundle.displayDiscountPercent;
    final hasSummary =
        bundle.summary != null && bundle.summary!.trim().isNotEmpty;

    return SizedBox(
      width: widget.width,
      child: Material(
        color: AppTheme.surface,
        elevation: 0,
        shadowColor: AppTheme.cardShadow,
        borderRadius: BorderRadius.circular(AppTheme.radiusMd),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppTheme.radiusMd),
            border: Border.all(
              color: const Color(0xFFE8E8E8),
              width: 1,
            ),
            boxShadow: const [
              BoxShadow(
                color: AppTheme.cardShadow,
                blurRadius: 8,
                offset: Offset(0, 3),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(AppTheme.radiusMd),
            child: Stack(
              children: [
                Positioned.fill(
                  child: InkWell(
                    onTap: _openDetails,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Expanded(
                          child: Stack(
                            clipBehavior: Clip.hardEdge,
                            children: [
                              const Positioned.fill(
                                child: ColoredBox(
                                  color: AppTheme.productImageWell,
                                ),
                              ),
                              Center(
                                child: CelebrateAnchor(
                                  anchor: _imageAnchor,
                                  child: BundleCoverImages(bundle: bundle),
                                ),
                              ),
                              if (discount > 0)
                                PositionedDirectional(
                                  top: scale.s(6),
                                  start: scale.s(6),
                                  child: _DiscountBadge(percent: discount),
                                ),
                            ],
                          ),
                        ),
                        Padding(
                          padding: EdgeInsets.fromLTRB(
                            scale.s(8),
                            scale.s(6),
                            scale.s(8),
                            scale.s(36),
                          ),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(
                                bundle.name,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: scale.s(12.5),
                                  fontWeight: FontWeight.w900,
                                  color: AppTheme.darkText,
                                  height: 1.15,
                                ),
                              ),
                              if (hasSummary) ...[
                                SizedBox(height: scale.s(2)),
                                Text(
                                  bundle.summary!,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontSize: scale.s(10),
                                    fontWeight: FontWeight.w600,
                                    color: AppTheme.mutedText,
                                    height: 1.15,
                                  ),
                                ),
                              ],
                              SizedBox(height: scale.s(4)),
                              PriceLine(
                                price: bundle.bundlePrice,
                                originalPrice: bundle.hasDiscount
                                    ? bundle.originalPrice
                                    : null,
                                color: AppTheme.badgeNumber,
                                priceSize: 13,
                                currencySize: 16,
                                alignment: AlignmentDirectional.centerStart,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                PositionedDirectional(
                  start: scale.s(6),
                  bottom: scale.s(6),
                  child: Material(
                    color: AppTheme.primarySurface,
                    borderRadius: BorderRadius.circular(scale.s(8)),
                    child: InkWell(
                      onTap: _addToCart,
                      borderRadius: BorderRadius.circular(scale.s(8)),
                      child: Container(
                        padding: EdgeInsetsDirectional.only(
                          start: scale.s(7),
                          end: scale.s(5),
                          top: scale.s(4),
                          bottom: scale.s(4),
                        ),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(scale.s(8)),
                          border: Border.all(
                            color: AppTheme.primaryDark,
                            width: 1.2,
                          ),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          textDirection: TextDirection.rtl,
                          children: [
                            Icon(
                              Icons.add_rounded,
                              size: scale.s(14),
                              color: AppTheme.primaryDark,
                            ),
                            SizedBox(width: scale.s(2)),
                            Text(
                              AppStrings.bundleAddToCart,
                              style: TextStyle(
                                fontSize: scale.s(9.5),
                                fontWeight: FontWeight.w900,
                                color: AppTheme.primaryDark,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DiscountBadge extends StatelessWidget {
  final int percent;

  const _DiscountBadge({required this.percent});

  @override
  Widget build(BuildContext context) {
    final scale = AppScale.of(context);
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: scale.s(7),
        vertical: scale.s(3),
      ),
      decoration: BoxDecoration(
        color: AppTheme.primaryDark,
        borderRadius: BorderRadius.circular(AppTheme.radiusPill),
      ),
      child: Text(
        AppStrings.bundleDiscount(percent),
        style: TextStyle(
          color: Colors.white,
          fontSize: scale.s(10),
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

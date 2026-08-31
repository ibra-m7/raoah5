import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_scale.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/circle_back_button.dart';
import '../../data/models/bundle_model.dart';
import '../manager/cart_cubit.dart';
import '../widgets/bundle_item_card.dart';
import '../widgets/celebrate_anchors.dart';
import '../widgets/price_line.dart';
import '../widgets/product_fly_overlay.dart';

class BundleDetailsArgs {
  final BundleModel bundle;

  const BundleDetailsArgs({required this.bundle});
}

class BundleDetailsScreen extends StatefulWidget {
  static const routeName = '/bundle-details';

  final BundleModel bundle;

  const BundleDetailsScreen({super.key, required this.bundle});

  @override
  State<BundleDetailsScreen> createState() => _BundleDetailsScreenState();
}

class _BundleDetailsScreenState extends State<BundleDetailsScreen> {
  final Object _imageAnchor = Object();

  void _addToCart() {
    final bundle = widget.bundle;
    if (!bundle.isAvailable) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text(AppStrings.bundleUnavailable)),
      );
      return;
    }

    HapticFeedback.mediumImpact();
    final imageUrl = bundle.flyImageUrl;
    if (imageUrl.isNotEmpty) {
      ProductFlyController.play(
        context: context,
        imageUrl: imageUrl,
        productAnchor: _imageAnchor,
        pingDetailsCart: true,
      );
    }
    context.read<CartCubit>().addBundleToCart(bundle);
  }

  @override
  Widget build(BuildContext context) {
    final scale = AppScale.of(context);
    final bundle = widget.bundle;
    final count = bundle.itemCount > 0
        ? bundle.itemCount
        : bundle.items.fold<int>(0, (sum, item) => sum + item.quantity);

    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(
        backgroundColor: AppTheme.surface,
        elevation: 0,
        centerTitle: true,
        leading: const CircleBackButton(size: 30),
        title: Text(
          AppStrings.bundleDetailsTitle,
          style: TextStyle(
            fontSize: scale.s(16),
            fontWeight: FontWeight.w900,
            color: AppTheme.darkText,
          ),
        ),
      ),
      body: Column(
        children: [
          Expanded(
            child: CustomScrollView(
              slivers: [
                SliverToBoxAdapter(
                  child: Padding(
                    padding: EdgeInsets.fromLTRB(
                      scale.pagePad,
                      scale.s(12),
                      scale.pagePad,
                      scale.s(8),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        CelebrateAnchor(
                          anchor: _imageAnchor,
                          child: const SizedBox.shrink(),
                        ),
                        Text(
                          bundle.name,
                          style: TextStyle(
                            fontSize: scale.s(18),
                            fontWeight: FontWeight.w900,
                            color: AppTheme.darkText,
                          ),
                        ),
                        if (bundle.summary != null &&
                            bundle.summary!.trim().isNotEmpty) ...[
                          SizedBox(height: scale.s(6)),
                          Text(
                            bundle.summary!,
                            style: TextStyle(
                              fontSize: scale.s(13),
                              fontWeight: FontWeight.w600,
                              color: AppTheme.mutedText,
                              height: 1.35,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
                SliverPadding(
                  padding: EdgeInsets.fromLTRB(
                    scale.pagePad,
                    scale.s(8),
                    scale.pagePad,
                    scale.s(100),
                  ),
                  sliver: SliverGrid(
                    gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      mainAxisSpacing: scale.s(12),
                      crossAxisSpacing: scale.s(12),
                      childAspectRatio: 0.72,
                    ),
                    delegate: SliverChildBuilderDelegate(
                      (context, index) =>
                          BundleItemCard(item: bundle.items[index]),
                      childCount: bundle.items.length,
                    ),
                  ),
                ),
              ],
            ),
          ),
          _BundleBottomBar(
            bundlePrice: bundle.bundlePrice,
            originalPrice: bundle.originalPrice,
            itemCount: count,
            enabled: bundle.isAvailable,
            onAdd: _addToCart,
          ),
        ],
      ),
    );
  }
}

class _BundleBottomBar extends StatelessWidget {
  final double bundlePrice;
  final double originalPrice;
  final int itemCount;
  final bool enabled;
  final VoidCallback onAdd;

  const _BundleBottomBar({
    required this.bundlePrice,
    required this.originalPrice,
    required this.itemCount,
    required this.enabled,
    required this.onAdd,
  });

  @override
  Widget build(BuildContext context) {
    final scale = AppScale.of(context);
    final showStrike = originalPrice > bundlePrice;

    return Container(
      decoration: BoxDecoration(
        color: AppTheme.surface,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 12,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            scale.pagePad,
            scale.s(10),
            scale.pagePad,
            scale.s(10),
          ),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    PriceLine(
                      price: bundlePrice,
                      color: AppTheme.badgeNumber,
                      priceSize: 18,
                      alignment: AlignmentDirectional.centerStart,
                    ),
                    SizedBox(height: scale.s(4)),
                    Row(
                      children: [
                        if (showStrike) ...[
                          Text(
                            originalPrice.toStringAsFixed(2),
                            style: TextStyle(
                              fontSize: scale.s(11.5),
                              color: AppTheme.mutedText.withValues(alpha: 0.8),
                              decoration: TextDecoration.lineThrough,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          SizedBox(width: scale.s(8)),
                        ],
                        Container(
                          padding: EdgeInsets.symmetric(
                            horizontal: scale.s(8),
                            vertical: scale.s(3),
                          ),
                          decoration: BoxDecoration(
                            color: AppTheme.primarySurface,
                            borderRadius: BorderRadius.circular(AppTheme.radiusPill),
                          ),
                          child: Text(
                            AppStrings.bundleItemCount(itemCount),
                            style: TextStyle(
                              fontSize: scale.s(10.5),
                              fontWeight: FontWeight.w700,
                              color: AppTheme.primaryDark,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              SizedBox(width: scale.s(12)),
              Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: enabled ? onAdd : null,
                  borderRadius: BorderRadius.circular(14),
                  child: Ink(
                    height: 46,
                    padding: EdgeInsets.symmetric(horizontal: scale.s(18)),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(14),
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: enabled
                            ? [AppTheme.primary, AppTheme.primaryDark]
                            : [
                                AppTheme.mutedText.withValues(alpha: 0.4),
                                AppTheme.mutedText.withValues(alpha: 0.5),
                              ],
                      ),
                    ),
                    child: Center(
                      child: Text(
                        AppStrings.bundleAddAll,
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: scale.s(13.5),
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

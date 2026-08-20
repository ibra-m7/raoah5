import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../data/models/product_model.dart';
import '../manager/cart_cubit.dart';
import '../manager/catalog_cubit.dart';
import '../manager/favorite_cubit.dart';
import 'price_line.dart';
import 'sold_proof_line.dart';

Future<void> showProductPreview(
  BuildContext context,
  ProductModel product, {
  String? heroTag,
}) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    builder: (ctx) => ProductPreviewSheet(
      product: product,
      heroTag: heroTag ?? 'preview_${product.id}',
    ),
  );
}

class ProductPreviewSheet extends StatefulWidget {
  final ProductModel product;
  final String heroTag;

  const ProductPreviewSheet({
    super.key,
    required this.product,
    required this.heroTag,
  });

  @override
  State<ProductPreviewSheet> createState() => _ProductPreviewSheetState();
}

class _ProductPreviewSheetState extends State<ProductPreviewSheet> {
  bool _added = false;
  late ProductModel _product;

  ProductModel get p => _product;

  @override
  void initState() {
    super.initState();
    _product = widget.product;
  }

  Future<void> _addToCart() async {
    HapticFeedback.mediumImpact();
    context.read<CartCubit>().addToCart(p);
    setState(() => _added = true);
    Future<void>.delayed(const Duration(seconds: 1), () {
      if (mounted) setState(() => _added = false);
    });
  }

  @override
  Widget build(BuildContext context) {
    final height = MediaQuery.sizeOf(context).height;
    final similar = context.select((CatalogCubit cubit) {
      return cubit.state.products
          .where((item) => item.id != p.id && item.categoryId == p.categoryId)
          .take(8)
          .toList();
    });

    return Align(
      alignment: Alignment.bottomCenter,
      child: Container(
        height: height * 0.94,
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: Column(
          children: [
            const SizedBox(height: 8),
            Container(
              width: 42,
              height: 4,
              decoration: BoxDecoration(
                color: const Color(0xFFE5E7EB),
                borderRadius: BorderRadius.circular(99),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 0),
              child: Row(
                children: [
                  _RoundIconButton(
                    icon: Icons.close_rounded,
                    onTap: () => Navigator.pop(context),
                  ),
                  const Spacer(),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(20),
                    child: AspectRatio(
                      aspectRatio: 1.15,
                      child: Hero(
                        tag: widget.heroTag,
                        child: AppNetworkImage(
                          p.imageUrl,
                          fit: BoxFit.contain,
                          error: Container(
                            color: AppTheme.primarySurface,
                            child: const Icon(Icons.image_not_supported_outlined, size: 48),
                          ),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            ProductNameText(
                              p.name,
                              baseSize: 20,
                              maxLines: 3,
                              fontWeight: FontWeight.w900,
                            ),
                            if (p.quantityLabel.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                p.quantityLabel,
                                style: const TextStyle(
                                  fontSize: 14,
                                  color: AppTheme.mutedText,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                            if (p.soldCount > 0) ...[
                              const SizedBox(height: 8),
                              SoldProofLine(soldCount: p.soldCount, fontSize: 13),
                            ],
                          ],
                        ),
                      ),
                      BlocBuilder<FavoriteCubit, FavoriteState>(
                        buildWhen: (prev, curr) =>
                            prev.contains(p.id) != curr.contains(p.id),
                        builder: (context, fav) {
                          final on = fav.contains(p.id);
                          return IconButton(
                            onPressed: () =>
                                context.read<FavoriteCubit>().toggle(p.id),
                            icon: Icon(
                              on ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                              color: on ? Colors.redAccent : AppTheme.darkText,
                            ),
                          );
                        },
                      ),
                    ],
                  ),
                  if (p.description.trim().isNotEmpty) ...[
                    const SizedBox(height: 12),
                    Text(
                      p.description,
                      style: const TextStyle(
                        fontSize: 14,
                        height: 1.7,
                        color: AppTheme.bodyText,
                      ),
                    ),
                  ],
                  if (p.benefits.isNotEmpty) ...[
                    const SizedBox(height: 20),
                    const Text(
                      'المميزات',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.darkText,
                      ),
                    ),
                    const SizedBox(height: 8),
                    ...p.benefits.map(
                      (item) => Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Padding(
                              padding: EdgeInsets.only(top: 2),
                              child: Icon(
                                Icons.check_circle_rounded,
                                size: 18,
                                color: AppTheme.primary,
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                item,
                                style: const TextStyle(
                                  fontSize: 14,
                                  height: 1.55,
                                  color: AppTheme.bodyText,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                  if (p.usageInstructions.trim().isNotEmpty) ...[
                    const SizedBox(height: 12),
                    const Text(
                      'طريقة الاستخدام',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.darkText,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      p.usageInstructions,
                      style: const TextStyle(
                        fontSize: 14,
                        height: 1.7,
                        color: AppTheme.bodyText,
                      ),
                    ),
                  ],
                  if (similar.isNotEmpty) ...[
                    const SizedBox(height: 22),
                    const Text(
                      'منتجات مشابهة',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.darkText,
                      ),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      height: 148,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: similar.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 10),
                        itemBuilder: (context, index) {
                          final item = similar[index];
                          return GestureDetector(
                            onTap: () => setState(() {
                              _product = item;
                              _added = false;
                            }),
                            child: SizedBox(
                              width: 110,
                              child: Column(
                                children: [
                                  Expanded(
                                    child: ClipRRect(
                                      borderRadius: BorderRadius.circular(16),
                                      child: AppNetworkImage(
                                        item.imageUrl,
                                        fit: BoxFit.contain,
                                        error: Container(
                                          color: AppTheme.primarySurface,
                                        ),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    item.name,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    textAlign: TextAlign.center,
                                    style: const TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                ],
              ),
            ),
            Container(
              padding: EdgeInsets.fromLTRB(
                16,
                12,
                16,
                12 + MediaQuery.paddingOf(context).bottom,
              ),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.06),
                    blurRadius: 16,
                    offset: const Offset(0, -4),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (p.hasDiscount)
                          Container(
                            margin: const EdgeInsets.only(bottom: 4),
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: const Color(0xFFE53935),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Text(
                              'عرض',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ),
                        PriceLine(
                          price: p.effectivePrice,
                          originalPrice: p.hasDiscount ? p.price : null,
                          priceSize: 22,
                          alignment: AlignmentDirectional.centerStart,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton(
                      onPressed: p.isAvailable ? _addToCart : null,
                      style: FilledButton.styleFrom(
                        backgroundColor: AppTheme.primaryDark,
                        foregroundColor: Colors.white,
                        minimumSize: const Size.fromHeight(52),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      child: Text(
                        _added ? 'تمت الإضافة' : '${AppStrings.productAddToCart} +',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RoundIconButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;

  const _RoundIconButton({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: const Color(0xFFF3F4F6),
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(8),
          child: Icon(icon, size: 22, color: AppTheme.darkText),
        ),
      ),
    );
  }
}

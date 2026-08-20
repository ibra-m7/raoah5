import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../data/models/product_model.dart';
import '../manager/catalog_cubit.dart';
import '../manager/favorite_cubit.dart';
import '../widgets/price_line.dart';
import '../widgets/product_preview_sheet.dart';
import '../widgets/sold_proof_line.dart';

/// شاشة المنتجات المفضلة — تعتمد على كتالوج الباك اند.
class FavoritesScreen extends StatelessWidget {
  static const routeName = '/favorites';

  const FavoritesScreen({super.key});

  static List<ProductModel> _productsForIds(
    BuildContext context,
    List<String> ids,
  ) {
    final byId = context.read<CatalogCubit>().state.productsById;
    return ids.map((id) => byId[id]).whereType<ProductModel>().toList();
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppTheme.background,
        appBar: AppBar(
          title: const Text(AppStrings.favoritesTitle),
          centerTitle: true,
          backgroundColor: AppTheme.background,
          foregroundColor: AppTheme.darkText,
          elevation: 0,
          scrolledUnderElevation: 1,
        ),
        body: BlocBuilder<FavoriteCubit, FavoriteState>(
          builder: (context, fav) {
            if (fav.orderedIds.isEmpty) {
              return Center(
                child: Padding(
                  padding: const EdgeInsets.all(32),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.favorite_border_rounded,
                        size: 72,
                        color: AppTheme.mutedText.withValues(alpha: 0.45),
                      ),
                      const SizedBox(height: 20),
                      Text(
                        AppStrings.favoritesEmpty,
                        textAlign: TextAlign.center,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.w800,
                              color: AppTheme.darkText,
                            ),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        AppStrings.favoritesEmptyHint,
                        textAlign: TextAlign.center,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: AppTheme.mutedText,
                              height: 1.45,
                            ),
                      ),
                    ],
                  ),
                ),
              );
            }

            final products = _productsForIds(context, fav.orderedIds);
            if (products.isEmpty) {
              return Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Text(
                    AppStrings.favoritesEmpty,
                    textAlign: TextAlign.center,
                    style: TextStyle(color: AppTheme.mutedText),
                  ),
                ),
              );
            }

            return ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
              physics: const BouncingScrollPhysics(),
              itemCount: products.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, i) {
                final p = products[i];
                return _FavoriteTile(product: p);
              },
            );
          },
        ),
      ),
    );
  }
}

class _FavoriteTile extends StatelessWidget {
  final ProductModel product;

  const _FavoriteTile({required this.product});

  @override
  Widget build(BuildContext context) {
    final p = product;
    return Material(
      color: AppTheme.surface,
      borderRadius: BorderRadius.circular(18),
      clipBehavior: Clip.antiAlias,
      elevation: 0,
      shadowColor: Colors.transparent,
      child: InkWell(
        onTap: () => showProductPreview(context, p),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: SizedBox(
                  width: 88,
                  height: 88,
                  child: AppNetworkImage(
                    p.imageUrl,
                    fit: BoxFit.cover,
                    error: Container(
                      color: AppTheme.background,
                      alignment: Alignment.center,
                      child: Icon(
                        Icons.image_not_supported_outlined,
                        color: AppTheme.mutedText,
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    ProductNameText(
                      p.name,
                      baseSize: 14,
                      maxLines: 2,
                    ),
                    if (p.soldCount > 0) ...[
                      const SizedBox(height: 6),
                      SoldProofLine(soldCount: p.soldCount, fontSize: 11),
                    ],
                    const SizedBox(height: 8),
                    PriceLine(
                      price: p.effectivePrice,
                      originalPrice: p.hasDiscount ? p.price : null,
                      alignment: AlignmentDirectional.centerStart,
                    ),
                  ],
                ),
              ),
              IconButton(
                onPressed: () {
                  HapticFeedback.lightImpact();
                  context.read<FavoriteCubit>().remove(p.id);
                },
                icon: Icon(
                  Icons.favorite_rounded,
                  color: Colors.redAccent.shade200,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

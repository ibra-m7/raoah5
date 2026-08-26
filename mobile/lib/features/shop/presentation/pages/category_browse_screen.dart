import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../data/models/category_model.dart';
import '../../data/models/product_model.dart';
import '../manager/catalog_cubit.dart';
import '../widgets/product_card.dart';
import '../widgets/product_card_shimmer.dart';

class CategoryBrowseArgs {
  final String categoryId;
  final CategoryModel? initial;

  const CategoryBrowseArgs({
    required this.categoryId,
    this.initial,
  });
}

class CategoryBrowseScreen extends StatelessWidget {
  static const routeName = '/category-browse';

  final CategoryBrowseArgs args;

  const CategoryBrowseScreen({
    super.key,
    required this.args,
  });

  static Color? _parseColor(String? hex) {
    if (hex == null || hex.isEmpty) return null;
    var value = hex.replaceFirst('#', '').trim();
    if (value.length == 6) value = 'FF$value';
    if (value.length != 8) return null;
    return Color(int.parse(value, radix: 16));
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<CatalogCubit, CatalogState>(
      builder: (context, catalog) {
        final category =
            args.initial ?? catalog.categoryById(args.categoryId);
        final title = category?.name ?? '';
        final products = catalog.productsForCategory(args.categoryId);
        final bgColor = _parseColor(category?.color) ?? const Color(0xFF1B3A2D);
        final heroImage = category?.backgroundImageUrl.isNotEmpty == true
            ? category!.backgroundImageUrl
            : (category?.displayImage.isNotEmpty == true
                ? category!.displayImage
                : '');

        return _CategoryBrowseBody(
          title: title,
          products: products,
          heroImage: heroImage,
          bgColor: bgColor,
          loading: catalog.loading && products.isEmpty,
        );
      },
    );
  }
}

class _CategoryBrowseBody extends StatelessWidget {
  final String title;
  final List<ProductModel> products;
  final String heroImage;
  final Color bgColor;
  final bool loading;

  const _CategoryBrowseBody({
    required this.title,
    required this.products,
    required this.heroImage,
    required this.bgColor,
    required this.loading,
  });

  @override
  Widget build(BuildContext context) {
    final topPad = MediaQuery.paddingOf(context).top;
    const heroH = 236.0;
    const overlap = 26.0;

    return Directionality(
      textDirection: TextDirection.rtl,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.light.copyWith(
          statusBarColor: Colors.transparent,
        ),
        child: Scaffold(
          backgroundColor: AppTheme.background,
          body: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(
              parent: BouncingScrollPhysics(),
            ),
            slivers: [
              SliverToBoxAdapter(
                child: SizedBox(
                  height: topPad + heroH - overlap,
                  child: OverflowBox(
                    maxHeight: topPad + heroH,
                    alignment: Alignment.topCenter,
                    child: SizedBox(
                      height: topPad + heroH,
                      child: Stack(
                        fit: StackFit.expand,
                        children: [
                          if (heroImage.isNotEmpty)
                            AppNetworkImage(
                              heroImage,
                              fit: BoxFit.cover,
                              width: 900,
                              placeholder: ColoredBox(color: bgColor),
                              error: ColoredBox(color: bgColor),
                            )
                          else
                            ColoredBox(color: bgColor),
                          const DecoratedBox(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                begin: Alignment.topCenter,
                                end: Alignment.bottomCenter,
                                colors: [
                                  Color(0x66000000),
                                  Color(0x14000000),
                                  Color(0x33000000),
                                ],
                                stops: [0, 0.45, 1],
                              ),
                            ),
                          ),
                          Positioned(
                            top: topPad + 8,
                            right: 12,
                            child: Material(
                              color: Colors.white.withValues(alpha: 0.94),
                              shape: const CircleBorder(),
                              child: InkWell(
                                onTap: () => Navigator.of(context).maybePop(),
                                customBorder: const CircleBorder(),
                                child: const SizedBox(
                                  width: 38,
                                  height: 38,
                                  child: Icon(
                                    Icons.arrow_back_ios_new_rounded,
                                    size: 18,
                                    color: AppTheme.darkText,
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
              ),
              SliverToBoxAdapter(
                child: DecoratedBox(
                  decoration: const BoxDecoration(
                    color: AppTheme.background,
                    borderRadius: BorderRadius.vertical(
                      top: Radius.circular(28),
                    ),
                    border: Border(
                      top: BorderSide(
                        color: Color(0x6688D498),
                        width: 1.4,
                      ),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Color(0x14000000),
                        blurRadius: 18,
                        offset: Offset(0, -6),
                      ),
                    ],
                  ),
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
                    child: Column(
                      children: [
                        Container(
                          width: 42,
                          height: 4,
                          decoration: BoxDecoration(
                            color: const Color(0x3388D498),
                            borderRadius: BorderRadius.circular(99),
                          ),
                        ),
                        if (title.isNotEmpty) ...[
                          const SizedBox(height: 14),
                          Text(
                            title,
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w900,
                              color: AppTheme.darkText,
                              height: 1.25,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              ),
              if (loading)
                const SliverPadding(
                  padding: EdgeInsets.fromLTRB(16, 0, 16, 24),
                  sliver: ProductShimmerSliverGrid(itemCount: 6),
                )
              else if (products.isEmpty)
                const SliverFillRemaining(
                  hasScrollBody: false,
                  child: Center(
                    child: Text(
                      AppStrings.dynamicPageEmpty,
                      style: TextStyle(
                        color: AppTheme.mutedText,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                )
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 28),
                  sliver: SliverGrid(
                    delegate: SliverChildBuilderDelegate(
                      (context, i) => ProductCard(
                        product: products[i],
                        heroTag: 'category_${title}_${products[i].id}',
                      ),
                      childCount: products.length,
                    ),
                    gridDelegate: ProductShimmerGrid.sliverDelegate,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

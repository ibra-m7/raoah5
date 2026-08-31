import 'dart:ui' show lerpDouble;

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/theme/app_scale.dart';
import '../../../../core/theme/app_theme.dart';
import '../../data/models/home_feed.dart';
import '../manager/catalog_cubit.dart';
import '../widgets/header_search_bar.dart';
import '../widgets/mint_to_white_blend.dart';
import '../widgets/product_card_shimmer.dart';
import '../widgets/scroll_refresh_shell.dart';
import '../widgets/section_title_row.dart';
import 'groceries_section_screen.dart';

/// تبويب الأقسام — رأس يتمدد عند أعلى الصفحة وينكمش مع التمرير.
class CategoriesScreen extends StatefulWidget {
  const CategoriesScreen({super.key});

  @override
  State<CategoriesScreen> createState() => _CategoriesScreenState();
}

class _CategoriesScreenState extends State<CategoriesScreen> {
  late final ScrollController _scroll;

  @override
  void initState() {
    super.initState();
    _scroll = ScrollController();
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  List<GroceriesSubcategoryItem> _itemsOf(DisplaySectionModel section) {
    return section.categories
        .map(GroceriesSubcategoryItem.fromCategory)
        .toList();
  }

  void _openSearch(BuildContext context) {
    Navigator.of(context).pushNamed(AppRouter.search);
  }

  /// عنوان + شبكة 4 أعمدة لفئة رئيسية (مثيل قسم المقاضي وغيرها).
  List<Widget> _categorySubsectors(
    BuildContext context, {
    required double headerTopInset,
    required String titleText,
    required String emoji,
    required VoidCallback onViewAll,
    required List<GroceriesSubcategoryItem> items,
    required String sectionId,
  }) {
    return [
      SliverToBoxAdapter(
        child: SectionTitleRow(
          title: titleText,
          emoji: emoji,
          padding: EdgeInsets.fromLTRB(16, headerTopInset, 16, 12),
          showViewAllLabel: true,
          titleFontSize: 13,
          titleFontWeight: FontWeight.w700,
          onTap: onViewAll,
        ),
      ),
      SliverPadding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
        sliver: SliverGrid(
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 4,
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 0.78,
          ),
          delegate: SliverChildBuilderDelegate(
            (context, index) {
              final item = items[index];
              return GroceriesCategoryCircleTile(
                label: item.label,
                imageUrl: item.imageUrl,
                onTap: () {
                  Navigator.of(context).pushNamed(
                    AppRouter.categorySubcategoriesBrowse,
                    arguments: CategorySubcategoriesBrowseArgs(
                      sectionId: sectionId,
                      initialCategoryId: item.categoryId,
                      appBarTitle: titleText,
                      items: items,
                    ),
                  );
                },
              );
            },
            childCount: items.length > 8 ? 8 : items.length,
          ),
        ),
      ),
      const SliverToBoxAdapter(child: SizedBox(height: 4)),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final topPad = MediaQuery.paddingOf(context).top;
    final scale = AppScale.of(context);
    final searchH = scale.searchH.roundToDouble();
    final blendH = scale.s(28);

    return BlocBuilder<CatalogCubit, CatalogState>(
      buildWhen: (prev, next) =>
          prev.loading != next.loading ||
          prev.refreshing != next.refreshing ||
          prev.feed != next.feed,
      builder: (context, catalog) {
        final showCategoriesLoading =
            (catalog.loading && catalog.displaySections.isEmpty) ||
            catalog.refreshing;

        final contentSlivers = <Widget>[];

        if (showCategoriesLoading) {
          contentSlivers.add(const SliverToBoxAdapter(child: SizedBox(height: 8)));
          contentSlivers.add(const SliverPadding(
            padding: EdgeInsets.fromLTRB(16, 0, 16, 110),
            sliver: CategoryShimmerSliverGrid(itemCount: 8),
          ));
        } else {
          var isFirst = true;
          for (final section in catalog.displaySections) {
            final items = _itemsOf(section);
            contentSlivers.addAll(
              _categorySubsectors(
                context,
                headerTopInset: isFirst ? 8 : 16,
                titleText: section.name,
                emoji: section.emoji ?? '',
                sectionId: section.id,
                onViewAll: () {
                  Navigator.of(context).pushNamed(
                    AppRouter.categorySubcategoriesBrowse,
                    arguments: CategorySubcategoriesBrowseArgs(
                      sectionId: section.id,
                      appBarTitle: section.name,
                      items: items,
                    ),
                  );
                },
                items: items,
              ),
            );
            isFirst = false;
          }
          contentSlivers.add(const SliverToBoxAdapter(child: SizedBox(height: 110)));
        }

        return Directionality(
          textDirection: TextDirection.rtl,
          child: Scaffold(
            backgroundColor: AppTheme.background,
            body: TopPullRefreshDetector(
              scrollController: _scroll,
              onRefresh: () => context.read<CatalogCubit>().load(refresh: true),
              child: CustomScrollView(
                controller: _scroll,
                physics: noGapScrollPhysics,
                slivers: [
                  SliverPersistentHeader(
                    pinned: true,
                    delegate: _CategoriesHeaderDelegate(
                      topPad: topPad,
                      searchH: searchH,
                      blendHeight: blendH,
                      onSearchTap: () => _openSearch(context),
                    ),
                  ),
                  DecoratedSliver(
                    decoration: const BoxDecoration(color: Colors.white),
                    sliver: SliverMainAxisGroup(slivers: contentSlivers),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}

class _CategoriesHeaderDelegate extends SliverPersistentHeaderDelegate {
  final double topPad;
  final double searchH;
  final double blendHeight;
  final VoidCallback onSearchTap;

  static const double _titleH = 30;
  static const double _titleGap = 22;

  const _CategoriesHeaderDelegate({
    required this.topPad,
    required this.searchH,
    required this.blendHeight,
    required this.onSearchTap,
  });

  static double computeMaxExtent({
    required double topPad,
    required double searchH,
    required double blendHeight,
  }) {
    return (topPad + 12 + _titleH + _titleGap + searchH + 16 + blendHeight)
        .roundToDouble();
  }

  /// موسّع: عنوان كامل + مسافة واضحة تحته ثم البحث والموقع.
  @override
  double get maxExtent => computeMaxExtent(
        topPad: topPad,
        searchH: searchH,
        blendHeight: blendHeight,
      );

  /// منكمش: حقل البحث + جسر التدرج السفلي.
  @override
  double get minExtent =>
      (topPad + 6 + searchH + 8 + blendHeight).roundToDouble();

  @override
  bool shouldRebuild(covariant _CategoriesHeaderDelegate old) {
    return topPad != old.topPad ||
        searchH != old.searchH ||
        blendHeight != old.blendHeight ||
        onSearchTap != old.onSearchTap;
  }

  @override
  Widget build(
    BuildContext context,
    double shrinkOffset,
    bool overlapsContent,
  ) {
    final span = (maxExtent - minExtent).clamp(1.0, 400.0);
    final t = (shrinkOffset / span).clamp(0.0, 1.0);
    final p = Curves.easeOutCubic.transform(t);
    // العنوان والموقع والـ AppBar يختفون مع التمرير لأسفل.
    final chrome = (1.0 - p * 1.55).clamp(0.0, 1.0);

    final topInset = lerpDouble(12, 6, p)!;
    final titleBlockH = _titleH * chrome;
    final gap = _titleGap * chrome;
    final bottomInset = lerpDouble(16, 8, p)!;
    // خلفية خضراء فاتحة متناسقة مع الصفحة — تبقى واضحة عند التمرير.
    final bg = AppTheme.background.withValues(
      alpha: lerpDouble(1.0, 0.97, p)!,
    );

    return SizedBox.expand(
      child: Material(
        color: Colors.transparent,
        child: Stack(
          fit: StackFit.expand,
          children: [
            DecoratedBox(
              decoration: BoxDecoration(color: bg),
              child: const SizedBox.expand(),
            ),
            Positioned(
              left: 16,
              right: 16,
              top: topPad + topInset,
              bottom: blendHeight + bottomInset,
              child: ClipRect(
                child: Align(
                  alignment: Alignment.bottomCenter,
                  child: LayoutBuilder(
                    builder: (context, constraints) {
                      final titleH = titleBlockH > 0.5 ? titleBlockH : 0.0;
                      final gapH = gap > 0.5 ? gap : 0.0;
                      final searchRowH = (constraints.maxHeight - titleH - gapH)
                          .clamp(0.0, searchH);

                      return Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          if (titleH > 0)
                            SizedBox(
                              height: titleH,
                              child: Opacity(
                                opacity: chrome,
                                child: Center(
                                  child: Text(
                                    AppStrings.navCategories,
                                    textAlign: TextAlign.center,
                                    style: Theme.of(context)
                                        .textTheme
                                        .titleLarge
                                        ?.copyWith(
                                          fontWeight: FontWeight.w800,
                                          color: AppTheme.darkText,
                                          fontSize: 22,
                                          height: 1.25,
                                        ),
                                  ),
                                ),
                              ),
                            ),
                          if (gapH > 0) SizedBox(height: gapH),
                          SizedBox(
                            height: searchRowH,
                            child: HeaderSearchRow(
                              onSearchTap: onSearchTap,
                              chromeVisibility: chrome,
                              searchBorderRadius: 14,
                              glassAmount: p,
                              glassMode: HeaderSearchGlassMode.categories,
                            ),
                          ),
                        ],
                      );
                    },
                  ),
                ),
              ),
            ),
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              height: blendHeight,
              child: MintToWhiteBlend(height: blendHeight),
            ),
          ],
        ),
      ),
    );
  }
}

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
import '../widgets/product_card_shimmer.dart';
import 'category_browse_screen.dart';
import 'groceries_section_screen.dart';

/// تبويب الأقسام — رأس يتمدد عند أعلى الصفحة وينكمش مع التمرير.
class CategoriesScreen extends StatelessWidget {
  const CategoriesScreen({super.key});

  static const Color _sectionGreen = Color(0xFF88D498);

  List<GroceriesSubcategoryItem> _itemsOf(DisplaySectionModel section) {
    return section.categories
        .map(GroceriesSubcategoryItem.fromCategory)
        .toList();
  }

  void _openSearch(BuildContext context) {
    Navigator.of(context).pushNamed(AppRouter.search);
  }

  void _openCategoryBrowse(BuildContext context, String categoryId) {
    final catalog = context.read<CatalogCubit>().state;
    Navigator.of(context).pushNamed(
      AppRouter.categoryBrowse,
      arguments: CategoryBrowseArgs(
        categoryId: categoryId,
        initial: catalog.categoryById(categoryId),
      ),
    );
  }

  /// عنوان + شبكة 4 أعمدة لفئة رئيسية (مثيل قسم المقاضي وغيرها).
  List<Widget> _categorySubsectors(
    BuildContext context, {
    required double headerTopInset,
    required String titleText,
    required String emoji,
    required VoidCallback onViewAll,
    required VoidCallback onTitleTap,
    required List<GroceriesSubcategoryItem> items,
    required String sectionId,
  }) {
    return [
      SliverToBoxAdapter(
        child: Padding(
          padding: EdgeInsets.fromLTRB(16, headerTopInset, 16, 12),
          child: Row(
            children: [
              Expanded(
                child: GestureDetector(
                  onTap: onTitleTap,
                  behavior: HitTestBehavior.opaque,
                  child: Row(
                    children: [
                      Flexible(
                        child: Text(
                          titleText,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style:
                              Theme.of(context).textTheme.titleMedium?.copyWith(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 18,
                                    height: 1.2,
                                    color: AppTheme.darkText,
                                  ),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(emoji, style: const TextStyle(fontSize: 22)),
                    ],
                  ),
                ),
              ),
              TextButton(
                onPressed: onViewAll,
                style: TextButton.styleFrom(
                  foregroundColor: _sectionGreen,
                  padding: const EdgeInsetsDirectional.only(start: 8),
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      AppStrings.viewAll,
                      style:
                          Theme.of(context).textTheme.labelLarge?.copyWith(
                                fontWeight: FontWeight.w700,
                                color: _sectionGreen,
                                fontSize: 13,
                              ),
                    ),
                    const Icon(
                      Icons.chevron_right_rounded,
                      size: 18,
                      color: _sectionGreen,
                    ),
                  ],
                ),
              ),
            ],
          ),
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
    final searchH = AppScale.of(context).searchH;

    return BlocBuilder<CatalogCubit, CatalogState>(
      builder: (context, catalog) {
        final slivers = <Widget>[
          SliverPersistentHeader(
            pinned: true,
            delegate: _CategoriesHeaderDelegate(
              topPad: topPad,
              searchH: searchH,
              onSearchTap: () => _openSearch(context),
            ),
          ),
        ];

        if (catalog.loading && catalog.displaySections.isEmpty) {
          slivers.add(const SliverToBoxAdapter(child: SizedBox(height: 16)));
          slivers.add(const SliverPadding(
            padding: EdgeInsets.fromLTRB(16, 0, 16, 110),
            sliver: CategoryShimmerSliverGrid(itemCount: 8),
          ));
        } else {
          for (final section in catalog.displaySections) {
            final items = _itemsOf(section);
            slivers.addAll(
              _categorySubsectors(
                context,
                headerTopInset: 16,
                titleText: section.name,
                emoji: section.emoji ?? '',
                sectionId: section.id,
                onTitleTap: () => _openCategoryBrowse(context, section.id),
                onViewAll: () => _openCategoryBrowse(context, section.id),
                items: items,
              ),
            );
          }
          slivers.add(const SliverToBoxAdapter(child: SizedBox(height: 110)));
        }

        return Directionality(
          textDirection: TextDirection.rtl,
          child: Scaffold(
            backgroundColor: AppTheme.background,
            body: CustomScrollView(
              physics: const BouncingScrollPhysics(),
              slivers: slivers,
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
  final VoidCallback onSearchTap;

  static const double _titleH = 30;
  static const double _titleGap = 22;

  const _CategoriesHeaderDelegate({
    required this.topPad,
    required this.searchH,
    required this.onSearchTap,
  });

  /// موسّع: عنوان كامل + مسافة واضحة تحته ثم البحث والموقع.
  @override
  double get maxExtent => topPad + 12 + _titleH + _titleGap + searchH + 16;

  /// منكمش: حقل البحث فقط بدون شريط AppBar.
  @override
  double get minExtent => topPad + 6 + searchH + 8;

  @override
  bool shouldRebuild(covariant _CategoriesHeaderDelegate old) {
    return topPad != old.topPad ||
        searchH != old.searchH ||
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
    // خلفية الـ AppBar تختفي عند الانكماش — يبقى البحث فقط.
    final bgAlpha = (1.0 - p).clamp(0.0, 1.0);
    final bg = AppTheme.background.withValues(alpha: bgAlpha);

    return Material(
      color: Colors.transparent,
      child: DecoratedBox(
        decoration: BoxDecoration(color: bg),
        child: Padding(
          padding: EdgeInsets.fromLTRB(16, topPad + topInset, 16, bottomInset),
          child: Column(
            children: [
              if (titleBlockH > 0.5)
                SizedBox(
                  height: titleBlockH,
                  child: Opacity(
                    opacity: chrome,
                    child: Center(
                      child: Text(
                        AppStrings.navCategories,
                        textAlign: TextAlign.center,
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.w800,
                              color: AppTheme.darkText,
                              fontSize: 22,
                              height: 1.25,
                            ),
                      ),
                    ),
                  ),
                ),
              if (gap > 0.5) SizedBox(height: gap),
              HeaderSearchRow(
                onSearchTap: onSearchTap,
                chromeVisibility: chrome,
                searchBorderRadius: 14,
                glassAmount: p,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

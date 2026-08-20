import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/theme/app_theme.dart';
import '../../data/models/home_feed.dart';
import '../../data/models/product_model.dart';
import '../manager/catalog_cubit.dart';
import '../widgets/product_card.dart';
import 'groceries_section_screen.dart';

/// تبويب الأقسام — رأس [SliverAppBar] مع عنوان وشريح بحث بأسلوب كيو (#88D498).
class CategoriesScreen extends StatefulWidget {
  const CategoriesScreen({super.key});

  @override
  State<CategoriesScreen> createState() => _CategoriesScreenState();
}

class _CategoriesScreenState extends State<CategoriesScreen> {
  final _searchCtrl = TextEditingController();
  String _query = '';

  static const Color _sectionGreen = Color(0xFF88D498);

  List<GroceriesSubcategoryItem> _itemsOf(DisplaySectionModel section) {
    return section.categories
        .map(GroceriesSubcategoryItem.fromCategory)
        .toList();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
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
        child: Padding(
          padding:
              EdgeInsets.fromLTRB(16, headerTopInset, 16, 12),
          child: Row(
            children: [
              Expanded(
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
              TextButton(
                onPressed: onViewAll,
                style: TextButton.styleFrom(
                  foregroundColor: _sectionGreen,
                  padding:
                      const EdgeInsetsDirectional.only(start: 8),
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
                      Icons.chevron_left_rounded,
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
          gridDelegate:
              const SliverGridDelegateWithFixedCrossAxisCount(
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

    return BlocBuilder<CatalogCubit, CatalogState>(
      builder: (context, catalog) {
        final isSearching = _query.trim().isNotEmpty;
        final searchResults = isSearching
            ? catalog.search(_query)
            : const <ProductModel>[];
        final slivers = <Widget>[
          SliverAppBar(
            pinned: true,
            primary: true,
            automaticallyImplyLeading: false,
            elevation: 0,
            scrolledUnderElevation: 2,
            shadowColor: Colors.black.withValues(alpha: 0.1),
            backgroundColor: AppTheme.background,
            surfaceTintColor: Colors.transparent,
            expandedHeight: topPad + 108,
            collapsedHeight: topPad + 76,
            flexibleSpace: FlexibleSpaceBar(
              collapseMode: CollapseMode.pin,
              background: Align(
                alignment: Alignment.bottomCenter,
                child: Padding(
                  padding: EdgeInsets.fromLTRB(16, topPad + 8, 16, 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.end,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        AppStrings.navCategories,
                        style:
                            Theme.of(context).textTheme.titleLarge?.copyWith(
                                  fontWeight: FontWeight.w900,
                                  color: AppTheme.darkText,
                                  fontSize: 22,
                                  height: 1.2,
                                ),
                      ),
                      const SizedBox(height: 12),
                      _CategoriesSearchBar(
                        height: 50,
                        controller: _searchCtrl,
                        onChanged: (v) => setState(() => _query = v),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ];

        if (catalog.loading && catalog.displaySections.isEmpty) {
          slivers.add(const SliverToBoxAdapter(
            child: Padding(
              padding: EdgeInsets.only(top: 80),
              child: Center(child: CircularProgressIndicator()),
            ),
          ));
        } else if (isSearching) {
          if (searchResults.isEmpty) {
            slivers.add(
              SliverFillRemaining(
                hasScrollBody: false,
                child: Center(
                  child: Text(
                    AppStrings.homeNoResults(_query.trim()),
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: AppTheme.mutedText,
                        ),
                  ),
                ),
              ),
            );
          } else {
            slivers.add(
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                  child: Text(
                    '${searchResults.length} نتيجة',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                      color: AppTheme.mutedText,
                    ),
                  ),
                ),
              ),
            );
            slivers.add(
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 110),
                sliver: SliverGrid(
                  gridDelegate:
                      const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 3,
                    mainAxisSpacing: 10,
                    crossAxisSpacing: 10,
                    childAspectRatio: 0.56,
                  ),
                  delegate: SliverChildBuilderDelegate(
                    (context, i) {
                      final p = searchResults[i];
                      return ProductCard(
                        product: p,
                        heroTag: 'cat_search_${p.id}_$i',
                      );
                    },
                    childCount: searchResults.length,
                  ),
                ),
              ),
            );
          }
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

// ── شريط بحث بأسلوب كيو (ظل ناعم، زوايا دائرية، أيقونة خضراء) ──────────────
class _CategoriesSearchBar extends StatelessWidget {
  final double height;
  final TextEditingController controller;
  final ValueChanged<String> onChanged;

  const _CategoriesSearchBar({
    required this.height,
    required this.controller,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: height,
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: TextField(
        controller: controller,
        onChanged: onChanged,
        textDirection: TextDirection.rtl,
        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: AppTheme.darkText,
              fontWeight: FontWeight.w500,
            ),
        decoration: InputDecoration(
          hintText: AppStrings.homeSearchInApp,
          hintStyle: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: AppTheme.mutedText.withValues(alpha: 0.75),
              ),
          prefixIcon:
              Icon(Icons.search_rounded, color: AppTheme.primary, size: 22),
          suffixIcon: ValueListenableBuilder<TextEditingValue>(
            valueListenable: controller,
            builder: (_, val, _) => val.text.isNotEmpty
                ? IconButton(
                    onPressed: () {
                      controller.clear();
                      onChanged('');
                    },
                    icon: Icon(
                      Icons.close_rounded,
                      color: AppTheme.mutedText,
                      size: 18,
                    ),
                  )
                : const SizedBox.shrink(),
          ),
          border: InputBorder.none,
          isDense: true,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 12,
          ),
        ),
      ),
    );
  }
}

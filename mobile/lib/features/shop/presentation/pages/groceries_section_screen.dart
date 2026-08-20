import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../data/models/category_model.dart';
import '../../data/models/home_feed.dart';
import '../../data/models/product_model.dart';
import '../manager/cart_cubit.dart';
import '../manager/catalog_cubit.dart';
import '../widgets/product_card.dart';
import '../widgets/product_preview_sheet.dart';

/// وسيط المسار — منتجات قسم فرعي.
class GroceriesSubcategoryProductsArgs {
  final String sectionTitle;
  final String categoryId;

  const GroceriesSubcategoryProductsArgs({
    required this.sectionTitle,
    required this.categoryId,
  });
}

/// عنصر فرعي في أقسام العرض (صورة + تسمية).
class GroceriesSubcategoryItem {
  final String label;
  final String imageUrl;
  final String categoryId;

  const GroceriesSubcategoryItem({
    required this.label,
    required this.imageUrl,
    required this.categoryId,
  });

  factory GroceriesSubcategoryItem.fromCategory(CategoryModel category) {
    return GroceriesSubcategoryItem(
      label: category.name,
      imageUrl: category.displayImage,
      categoryId: category.id,
    );
  }
}

/// وسيط مسار تصفّح قسم مع تبويبات وتصنيف شرائح.
class CategorySubcategoriesBrowseArgs {
  final String sectionId;
  final String? initialCategoryId;
  final String appBarTitle;
  final List<GroceriesSubcategoryItem> items;

  const CategorySubcategoriesBrowseArgs({
    required this.sectionId,
    this.initialCategoryId,
    required this.appBarTitle,
    this.items = const [],
  });
}

/// شاشة القسم: تبويبات أعلى + شرائح تصفية + شبكة منتجات.
class CategorySubcategoriesBrowseScreen extends StatefulWidget {
  static const routeName = '/category-subcategories-browse';

  final CategorySubcategoriesBrowseArgs args;

  const CategorySubcategoriesBrowseScreen({super.key, required this.args});

  @override
  State<CategorySubcategoriesBrowseScreen> createState() =>
      _CategorySubcategoriesBrowseScreenState();
}

class _CategorySubcategoriesBrowseScreenState
    extends State<CategorySubcategoriesBrowseScreen> {
  static const _accent = Color(0xFF2E9B57);

  late String? _tabId;
  String? _chipId;
  bool _searchOpen = false;
  final _searchCtrl = TextEditingController();
  String _query = '';

  @override
  void initState() {
    super.initState();
    _tabId = widget.args.initialCategoryId;
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  DisplaySectionModel? _sectionOf(CatalogState catalog) {
    for (final section in catalog.displaySections) {
      if (section.id == widget.args.sectionId) return section;
    }
    return catalog.displayBySlug(widget.args.sectionId);
  }

  List<CategoryModel> _tabs(DisplaySectionModel? section) {
    if (section != null && section.categories.isNotEmpty) {
      return section.categories;
    }
    return widget.args.items
        .map(
          (item) => CategoryModel(
            id: item.categoryId,
            name: item.label,
            iconUrl: item.imageUrl,
            imageUrl: item.imageUrl,
          ),
        )
        .toList();
  }

  CategoryModel? _tabOf(List<CategoryModel> tabs) {
    if (tabs.isEmpty) return null;
    for (final tab in tabs) {
      if (tab.id == _tabId) return tab;
    }
    return tabs.first;
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: BlocBuilder<CatalogCubit, CatalogState>(
        builder: (context, catalog) {
          final section = _sectionOf(catalog);
          final tabs = _tabs(section);
          final tab = _tabOf(tabs);
          final chips = tab?.children ?? const <CategoryModel>[];
          final filterId = _chipId ?? tab?.id;
          var products = filterId == null
              ? const <ProductModel>[]
              : catalog.productsForCategory(filterId);
          final q = _query.trim().toLowerCase();
          if (q.isNotEmpty) {
            products = catalog.search(_query).where((p) {
              final sectionIds = <String>{};
              for (final t in tabs) {
                sectionIds.addAll(catalog.categoryTreeIds(t.id));
              }
              return sectionIds.contains(p.categoryId);
            }).toList();
          }
          final title = section?.name ?? widget.args.appBarTitle;
          final heading = () {
            if (_chipId != null) {
              for (final c in chips) {
                if (c.id == _chipId) return c.name;
              }
            }
            return tab?.name;
          }();

          return Scaffold(
            backgroundColor: AppTheme.background,
            appBar: AppBar(
              backgroundColor: AppTheme.background,
              foregroundColor: AppTheme.darkText,
              elevation: 0,
              centerTitle: true,
              title: Text(
                title,
                style: const TextStyle(fontWeight: FontWeight.w900),
              ),
              actions: [
                IconButton(
                  tooltip: 'بحث',
                  onPressed: () => setState(() {
                    _searchOpen = !_searchOpen;
                    if (!_searchOpen) {
                      _searchCtrl.clear();
                      _query = '';
                    }
                  }),
                  icon: Icon(
                    _searchOpen ? Icons.close_rounded : Icons.search_rounded,
                  ),
                ),
              ],
            ),
            body: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (_searchOpen)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                    child: TextField(
                      controller: _searchCtrl,
                      autofocus: true,
                      onChanged: (v) => setState(() => _query = v),
                      decoration: InputDecoration(
                        hintText: 'ابحث في $title',
                        prefixIcon: const Icon(Icons.search_rounded),
                        filled: true,
                        fillColor: Colors.white,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                  ),
                if (q.isEmpty) ...[
                  _CategoryTabs(
                    tabs: tabs,
                    selectedId: tab?.id,
                    onSelect: (id) => setState(() {
                      _tabId = id;
                      _chipId = null;
                    }),
                  ),
                  if (chips.isNotEmpty)
                    _SubcategoryChips(
                      chips: chips,
                      selectedId: _chipId,
                      onSelect: (id) => setState(() => _chipId = id),
                    ),
                  if (heading != null && heading.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 10, 16, 8),
                      child: Text(
                        heading,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: _accent,
                        ),
                      ),
                    ),
                ] else
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
                    child: Text(
                      '${products.length} نتيجة',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.mutedText,
                      ),
                    ),
                  ),
                Expanded(
                  child: products.isEmpty
                      ? Center(
                          child: Text(
                            q.isNotEmpty
                                ? AppStrings.homeNoResults(_query)
                                : AppStrings.homeNoProductsInCategory,
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: AppTheme.mutedText),
                          ),
                        )
                      : GridView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 88),
                          gridDelegate:
                              const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 3,
                            mainAxisSpacing: 10,
                            crossAxisSpacing: 10,
                            childAspectRatio: 0.56,
                          ),
                          itemCount: products.length,
                          itemBuilder: (context, i) {
                            final p = products[i];
                            return ProductCard(
                              product: p,
                              heroTag: 'section_${filterId}_${p.id}_$i',
                            );
                          },
                        ),
                ),
              ],
            ),
            bottomNavigationBar: const _MiniCartBar(),
          );
        },
      ),
    );
  }
}

class _CategoryTabs extends StatelessWidget {
  final List<CategoryModel> tabs;
  final String? selectedId;
  final ValueChanged<String> onSelect;

  const _CategoryTabs({
    required this.tabs,
    required this.selectedId,
    required this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 46,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        itemCount: tabs.length,
        separatorBuilder: (_, __) => const SizedBox(width: 6),
        itemBuilder: (context, i) {
          final tab = tabs[i];
          final selected = tab.id == selectedId;
          return GestureDetector(
            onTap: () => onSelect(tab.id),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 160),
              padding: const EdgeInsets.symmetric(horizontal: 10),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                border: Border(
                  bottom: BorderSide(
                    color: selected
                        ? const Color(0xFF2E9B57)
                        : Colors.transparent,
                    width: 2.5,
                  ),
                ),
              ),
              child: Text(
                tab.name,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
                  color: selected
                      ? const Color(0xFF2E9B57)
                      : AppTheme.mutedText,
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _SubcategoryChips extends StatelessWidget {
  final List<CategoryModel> chips;
  final String? selectedId;
  final ValueChanged<String?> onSelect;

  const _SubcategoryChips({
    required this.chips,
    required this.selectedId,
    required this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    final items = <({String? id, String label})>[
      (id: null, label: 'الكل'),
      ...chips.map((c) => (id: c.id, label: c.name)),
    ];
    return SizedBox(
      height: 46,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (context, i) {
          final item = items[i];
          final selected = selectedId == item.id;
          return GestureDetector(
            onTap: () => onSelect(item.id),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 160),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                  color: selected
                      ? const Color(0xFF2E9B57)
                      : const Color(0xFFD7E8DC),
                  width: selected ? 1.6 : 1,
                ),
              ),
              child: Text(
                item.label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: selected
                      ? const Color(0xFF2E9B57)
                      : AppTheme.darkText,
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _MiniCartBar extends StatelessWidget {
  const _MiniCartBar();

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<CartCubit, CartState>(
      builder: (context, cart) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
            child: Material(
              color: const Color(0xFF3A3A3A),
              borderRadius: BorderRadius.circular(18),
              child: InkWell(
                onTap: () => Navigator.of(context).pushNamed(AppRouter.checkout),
                borderRadius: BorderRadius.circular(18),
                child: Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  child: Row(
                    children: [
                      Text(
                        '${cart.total.toStringAsFixed(2)} ${AppStrings.currency}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 15,
                        ),
                      ),
                      const Spacer(),
                      const Icon(Icons.shopping_bag_outlined,
                          color: Colors.white, size: 20),
                      const SizedBox(width: 8),
                      const Text(
                        'عرض السلة',
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}

/// قائمة منتجات قسم فرعي ضمن المقاضي.
class GroceriesSubcategoryProductsScreen extends StatelessWidget {
  static const routeName = '/groceries-subcategory-products';

  final GroceriesSubcategoryProductsArgs args;

  const GroceriesSubcategoryProductsScreen({super.key, required this.args});

  void _openProduct(BuildContext context, ProductModel product) {
    showProductPreview(context, product);
  }

  @override
  Widget build(BuildContext context) {
    final products = context.select(
      (CatalogCubit cubit) => cubit.state.productsForCategory(args.categoryId),
    );

    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(title: Text(args.sectionTitle)),
      body: products.isEmpty
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  AppStrings.homeNoProductsInCategory,
                  textAlign: TextAlign.center,
                  style: Theme.of(context)
                      .textTheme
                      .bodyMedium
                      ?.copyWith(color: AppTheme.mutedText),
                ),
              ),
            )
          : ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: products.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final p = products[index];
                return Material(
                  color: AppTheme.surface,
                  borderRadius: BorderRadius.circular(16),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(16),
                    onTap: () => _openProduct(context, p),
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Row(
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: AppNetworkImage(
                              p.imageUrl,
                              width: 72,
                              height: 72,
                              fit: BoxFit.cover,
                              error: Container(
                                width: 72,
                                height: 72,
                                color: AppTheme.primarySurface,
                                child: Icon(
                                  Icons.inventory_2_outlined,
                                  color: AppTheme.mutedText,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  p.name,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: Theme.of(context)
                                      .textTheme
                                      .titleSmall
                                      ?.copyWith(
                                        fontWeight: FontWeight.w700,
                                        color: AppTheme.darkText,
                                      ),
                                ),
                                const SizedBox(height: 6),
                                FittedBox(
                                  fit: BoxFit.scaleDown,
                                  alignment: AlignmentDirectional.centerStart,
                                  child: Text(
                                    '${p.effectivePrice.toStringAsFixed(2)} ${AppStrings.currency}',
                                    style: Theme.of(context)
                                        .textTheme
                                        .labelLarge
                                        ?.copyWith(
                                          fontWeight: FontWeight.w800,
                                          color: AppTheme.primary,
                                        ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Icon(
                            Icons.chevron_left_rounded,
                            color: AppTheme.mutedText,
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
    );
  }
}

/// شاشة «عرض الكل» لقسم المقاضي — تُفتح عبر [AppRouter.groceriesSection].
class GroceriesSectionScreen extends StatelessWidget {
  static const routeName = '/groceries-section';

  const GroceriesSectionScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final catalog = context.watch<CatalogCubit>().state;
    final section = catalog.displayBySlug('groceries');
    final items = (section?.categories ?? const [])
        .map(GroceriesSubcategoryItem.fromCategory)
        .toList();

    return CategorySubcategoriesBrowseScreen(
      args: CategorySubcategoriesBrowseArgs(
        sectionId: section?.id ?? 'groceries',
        appBarTitle: section?.name ?? AppStrings.categoriesGroceriesSection,
        items: items,
      ),
    );
  }
}

/// مربع موحّد + صورة + تسمية — حجم الكارد ثابت مهما اختلفت أبعاد الصورة.
class GroceriesCategoryCircleTile extends StatelessWidget {
  final String label;
  final String imageUrl;
  final VoidCallback? onTap;

  const GroceriesCategoryCircleTile({
    super.key,
    required this.label,
    required this.imageUrl,
    this.onTap,
  });

  static const _tileColor = Color(0xFFE4F6EA);
  static const _radius = 18.0;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Expanded(
            child: ClipRRect(
              borderRadius: BorderRadius.circular(_radius),
              child: ColoredBox(
                color: _tileColor,
                child: Padding(
                  padding: const EdgeInsets.all(8),
                  child: LayoutBuilder(
                    builder: (context, constraints) {
                      final w = constraints.maxWidth;
                      final h = constraints.maxHeight;
                      return AppNetworkImage(
                        imageUrl,
                        width: w,
                        height: h,
                        fit: BoxFit.contain,
                        placeholder: SizedBox(
                          width: w,
                          height: h,
                          child: Center(
                            child: SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: AppTheme.primary,
                              ),
                            ),
                          ),
                        ),
                        error: SizedBox(
                          width: w,
                          height: h,
                          child: Icon(
                            Icons.image_not_supported_outlined,
                            color: AppTheme.mutedText,
                            size: 28,
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 6),
          SizedBox(
            height: 28,
            child: Text(
              label,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.labelMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                    color: AppTheme.darkText,
                    height: 1.2,
                    fontSize: 11,
                  ),
            ),
          ),
        ],
      ),
    );
  }
}

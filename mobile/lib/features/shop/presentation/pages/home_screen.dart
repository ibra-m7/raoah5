import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:lottie/lottie.dart';
import 'package:shimmer/shimmer.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../../../core/widgets/brand_logo.dart';
import '../../../../core/widgets/marquee_text.dart';
import '../../../../core/widgets/typing_placeholder.dart';
import '../../../../features/ai_assistant/presentation/pages/chat_screen.dart';
import '../../../../features/auth/data/services/auth_session.dart';
import '../../../../features/auth/presentation/manager/address_cubit.dart';
import '../../../../features/auth/presentation/pages/profile_screen.dart';
import '../../../../features/notifications/presentation/manager/notifications_cubit.dart';
import '../../../../features/notifications/data/services/push_service.dart';
import '../../../../features/auth/presentation/widgets/delivery_addresses_sheet.dart';
import '../../data/models/category_model.dart';
import '../../data/models/home_feed.dart';
import '../../data/models/product_model.dart';
import '../manager/cart_cubit.dart';
import '../manager/catalog_cubit.dart';
import '../manager/favorite_cubit.dart';
import '../widgets/main_bottom_nav_bar.dart';
import '../widgets/main_shell_scope.dart';
import '../widgets/price_line.dart';
import '../widgets/product_card.dart';
import '../widgets/product_preview_sheet.dart';
import '../widgets/sold_proof_line.dart';
import 'categories_screen.dart';
import 'checkout_screen.dart';

// ── قص موجي لخلفيات الأقسام الترويجية (حافة علوية/سفلية ناعمة كمرجع كيو) ───
class _SectionWaveClipper extends CustomClipper<Path> {
  _SectionWaveClipper({this.curveTop = false, this.curveBottom = true});
  final bool curveTop;
  final bool curveBottom;
  static const double _amp = 16;

  @override
  Path getClip(Size size) {
    final w = size.width;
    final h = size.height;
    final path = Path();
    if (curveTop) {
      path.moveTo(0, _amp);
      path.quadraticBezierTo(w / 2, 0, w, _amp);
    } else {
      path.moveTo(0, 0);
      path.lineTo(w, 0);
    }
    path.lineTo(w, curveBottom ? h - _amp : h);
    if (curveBottom) {
      path.quadraticBezierTo(w / 2, h, 0, h - _amp);
    } else {
      path.lineTo(0, h);
    }
    path.close();
    return path;
  }

  @override
  bool shouldReclip(covariant _SectionWaveClipper old) =>
      old.curveTop != curveTop || old.curveBottom != curveBottom;
}

// ── شريط موقع التوصيل — اسم العنوان فقط ───────────────────────────────────
class _HomeLocationChip extends StatelessWidget {
  const _HomeLocationChip();

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AddressCubit, AddressState>(
      builder: (context, state) {
        final name = state.selected?.label ?? AppStrings.homeLocationHome;
        return Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: () => DeliveryAddressesSheet.show(context),
            borderRadius: BorderRadius.circular(20),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 2, vertical: 4),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                textDirection: TextDirection.ltr,
                children: [
                  ConstrainedBox(
                    constraints: BoxConstraints(
                      maxWidth: MediaQuery.sizeOf(context).width * 0.46,
                    ),
                    child: MarqueeText(
                      text: name,
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w800,
                            color: AppTheme.darkText,
                            fontSize: 15,
                          ),
                    ),
                  ),
                  const Icon(
                    Icons.location_on_rounded,
                    color: AppTheme.primary,
                    size: 22,
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

class _HomeBrandMark extends StatelessWidget {
  const _HomeBrandMark();

  @override
  Widget build(BuildContext context) {
    return const BrandLogoMark(size: 92);
  }
}

// ── شريط البحث للرأس — بيضاوي بظل ────────────────────────────────────────────
class _HeaderSearchBar extends StatelessWidget {
  final double height;
  final TextEditingController controller;
  final ValueChanged<String> onChanged;

  const _HeaderSearchBar({
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
      child: ValueListenableBuilder<TextEditingValue>(
        valueListenable: controller,
        builder: (context, value, _) {
          final hintStyle = Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: AppTheme.mutedText.withValues(alpha: 0.75),
              );
          return Stack(
            alignment: Alignment.center,
            children: [
              TextField(
                controller: controller,
                onChanged: onChanged,
                textDirection: TextDirection.rtl,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: AppTheme.darkText,
                      fontWeight: FontWeight.w500,
                    ),
                decoration: InputDecoration(
                  hintText: '',
                  prefixIcon: Icon(
                    Icons.search_rounded,
                    color: AppTheme.primary,
                    size: 22,
                  ),
                  suffixIcon: value.text.isNotEmpty
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
                  border: InputBorder.none,
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 12,
                  ),
                ),
              ),
              if (value.text.isEmpty)
                Positioned.fill(
                  child: IgnorePointer(
                    child: Padding(
                      padding: const EdgeInsetsDirectional.only(
                        start: 48,
                        end: 16,
                      ),
                      child: Align(
                        alignment: AlignmentDirectional.centerStart,
                        child: TypingPlaceholder(
                          phrases: AppStrings.homeSearchHints,
                          style: hintStyle,
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

// ── رأس الرئيسية الثابت — البحث + «المنزل» + المفضلة ─────────────────────────
class _HomeStickyTopBar extends StatelessWidget {
  final double topPad;
  final TextEditingController searchController;
  final ValueChanged<String> onSearchChanged;

  const _HomeStickyTopBar({
    required this.topPad,
    required this.searchController,
    required this.onSearchChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppTheme.background,
      elevation: 0,
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: AppTheme.background,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              offset: const Offset(0, 3),
              blurRadius: 10,
              spreadRadius: 0,
            ),
          ],
        ),
        child: Padding(
          padding: EdgeInsets.fromLTRB(16, topPad + 8, 12, 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  const _HomeBrandMark(),
                  const Spacer(),
                  const _HomeLocationChip(),
                  BlocBuilder<NotificationsCubit, NotificationsState>(
                    builder: (context, state) {
                      return IconButton(
                        tooltip: 'الإشعارات',
                        onPressed: () {
                          unawaited(
                            context.read<NotificationsCubit>().load(silent: true),
                          );
                          Navigator.of(context).pushNamed(
                            AppRouter.notifications,
                          );
                        },
                        icon: Badge(
                          isLabelVisible: state.unreadCount > 0,
                          label: Text(
                            state.unreadCount > 99
                                ? '99+'
                                : '${state.unreadCount}',
                            style: const TextStyle(fontSize: 10),
                          ),
                          child: const Icon(
                            Icons.notifications_none_rounded,
                            color: AppTheme.darkText,
                            size: 26,
                          ),
                        ),
                      );
                    },
                  ),
                  BlocBuilder<FavoriteCubit, FavoriteState>(
                    builder: (context, fav) {
                      final catalog = context.watch<CatalogCubit>().state;
                      final count = fav.orderedIds
                          .where(catalog.productsById.containsKey)
                          .length;
                      return IconButton(
                        onPressed: () => Navigator.of(context).pushNamed(
                          AppRouter.favorites,
                        ),
                        icon: Badge(
                          isLabelVisible: count > 0,
                          label: Text(
                            count > 99 ? '99+' : '$count',
                            style: const TextStyle(fontSize: 10),
                          ),
                          child: SvgPicture.asset(
                            'assets/images/heart.svg',
                            width: 26,
                            height: 26,
                            fit: BoxFit.contain,
                          ),
                        ),
                      );
                    },
                  ),
                ],
              ),
              const SizedBox(height: 10),
              _HeaderSearchBar(
                height: 50,
                controller: searchController,
                onChanged: onSearchChanged,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── قسم ترويجي بخلفية منحنية + قائمة منتجات أفقية (مرجع كيو) ─────────────────
class _CurvedProductCarouselSection extends StatelessWidget {
  final String title;
  final String? subtitle;
  /// عند ضبطه يُستبدل ستايل [subtitle] الافتراضي (مثلاً سطر أساسي بلون الهوية).
  final TextStyle? subtitleStyle;
  /// يُرسَم قبل عنوان القسم في الصفّ (مثل أيقونة Lottie).
  final Widget? titleLeading;
  final List<ProductModel> products;
  final List<Color> gradientColors;
  final bool curveTop;
  final bool curveBottom;

  const _CurvedProductCarouselSection({
    required this.title,
    this.subtitle,
    this.subtitleStyle,
    this.titleLeading,
    required this.products,
    required this.gradientColors,
    this.curveTop = false,
    this.curveBottom = true,
  });

  @override
  Widget build(BuildContext context) {
    if (products.isEmpty) return const SizedBox.shrink();

    return ClipPath(
      clipper: _SectionWaveClipper(
        curveTop: curveTop,
        curveBottom: curveBottom,
      ),
      child: Container(
        width: double.infinity,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topRight,
            end: Alignment.bottomLeft,
            colors: gradientColors,
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.only(top: 20, bottom: 18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            mainAxisSize: MainAxisSize.min,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (titleLeading != null) ...[
                      titleLeading!,
                      const SizedBox(width: 10),
                    ],
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            title,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style:
                                Theme.of(context).textTheme.titleLarge?.copyWith(
                                      fontWeight: FontWeight.w900,
                                      color: AppTheme.primaryDark,
                                      height: 1.15,
                                    ),
                          ),
                          if (subtitle != null && subtitle!.isNotEmpty) ...[
                            const SizedBox(height: 3),
                            Text(
                              subtitle!,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: subtitleStyle ??
                                  Theme.of(context)
                                      .textTheme
                                      .labelSmall
                                      ?.copyWith(
                                        fontSize: 11,
                                        color: AppTheme.mutedText,
                                        fontWeight: FontWeight.w600,
                                        height: 1.25,
                                      ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    TextButton(
                      onPressed: () {},
                      style: TextButton.styleFrom(
                        foregroundColor: AppTheme.primaryDark,
                        padding: const EdgeInsetsDirectional.only(start: 8),
                        minimumSize: Size.zero,
                        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                      child: FittedBox(
                        fit: BoxFit.scaleDown,
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              AppStrings.homeShowAll,
                            style: Theme.of(context)
                                .textTheme
                                .labelLarge
                                ?.copyWith(
                                  fontWeight: FontWeight.w700,
                                  color: AppTheme.primary,
                                ),
                          ),
                          const Icon(
                            Icons.chevron_left_rounded,
                            size: 18,
                            color: AppTheme.primary,
                          ),
                        ],
                      ),
                    ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              SizedBox(
                height: 268,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  physics: const BouncingScrollPhysics(),
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: products.length,
                  separatorBuilder: (_, __) => const SizedBox(width: 10),
                  itemBuilder: (_, i) => SizedBox(
                    width: 150,
                    child: ProductCard(
                      product: products[i],
                      heroTag: 'home_carousel_${title}_${i}_${products[i].id}',
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

class _SectionFireIcon extends StatelessWidget {
  const _SectionFireIcon();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 38,
      height: 38,
      child: Lottie.asset(
        'assets/lottie/fire.json',
        fit: BoxFit.contain,
        repeat: true,
      ),
    );
  }
}

// ── شريط أقسام سريعة — دوائر بيضاء + صور شبكية ─────────────────────────────
class _ExploreCategoriesStrip extends StatelessWidget {
  final String? selectedId;
  final ValueChanged<String> onSelect;
  final List<CategoryModel> categories;
  final VoidCallback? onViewAll;

  const _ExploreCategoriesStrip({
    required this.selectedId,
    required this.onSelect,
    required this.categories,
    this.onViewAll,
  });

  @override
  Widget build(BuildContext context) {
    final all = [
      const CategoryModel(
        id: '__all__',
        name: AppStrings.homeCategoryAll,
        iconUrl: '',
      ),
      ...categories,
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  AppStrings.homeExploreSections,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w900,
                        color: AppTheme.primaryDark,
                      ),
                ),
              ),
              TextButton(
                onPressed: onViewAll,
                style: TextButton.styleFrom(
                  foregroundColor: AppTheme.primary,
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                child: FittedBox(
                  fit: BoxFit.scaleDown,
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        AppStrings.viewAll,
                        style: Theme.of(context).textTheme.labelLarge?.copyWith(
                              fontWeight: FontWeight.w700,
                              color: AppTheme.primary,
                            ),
                      ),
                      const Icon(
                        Icons.chevron_left_rounded,
                        size: 18,
                        color: AppTheme.primary,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
        SizedBox(
          height: _exploreStripHeight(context),
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: all.length,
            itemBuilder: (_, i) {
              final cat = all[i];
              final isAll = cat.id == '__all__';
              final isSelected =
                  isAll ? selectedId == null : selectedId == cat.id;

              return Padding(
                padding: EdgeInsetsDirectional.only(
                  start: i == 0 ? 0 : 10,
                ),
                child: GestureDetector(
                  onTap: () => onSelect(isAll ? '__all__' : cat.id),
                  behavior: HitTestBehavior.opaque,
                  child: SizedBox(
                    width: 78,
                    child: Column(
                      children: [
                        AnimatedContainer(
                          duration: const Duration(milliseconds: 220),
                          width: 72,
                          height: 72,
                          decoration: BoxDecoration(
                            color: AppTheme.surface,
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: isSelected
                                  ? AppTheme.primary
                                  : AppTheme.primaryLight
                                      .withValues(alpha: 0.85),
                              width: isSelected ? 2.2 : 1,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.05),
                                blurRadius: 8,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          child: ClipOval(
                            child: Padding(
                              padding: const EdgeInsets.all(10),
                              child: DecoratedBox(
                                decoration: BoxDecoration(
                                  color: AppTheme.primarySurface,
                                  shape: BoxShape.circle,
                                ),
                                child: Padding(
                                  padding: const EdgeInsets.all(6),
                                  child: isAll
                                      ? Icon(
                                          Icons.apps_rounded,
                                          color: AppTheme.primaryDark,
                                          size: 28,
                                        )
                                      : AppNetworkImage(
                                          cat.displayImage.isNotEmpty
                                              ? cat.displayImage
                                              : cat.iconUrl,
                                          fit: BoxFit.contain,
                                          error: Icon(
                                            Icons.category_rounded,
                                            color: AppTheme.mutedText,
                                            size: 26,
                                          ),
                                        ),
                                ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 6),
                        Expanded(
                          child: Align(
                            alignment: Alignment.topCenter,
                            child: Text(
                              isAll
                                  ? AppStrings.homeCategoryAll
                                  : _shortCategoryLabel(cat.name),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              textAlign: TextAlign.center,
                              style: Theme.of(context)
                                  .textTheme
                                  .labelMedium
                                  ?.copyWith(
                                    fontWeight: isSelected
                                        ? FontWeight.w700
                                        : FontWeight.w500,
                                    color: isSelected
                                        ? AppTheme.primaryDark
                                        : AppTheme.darkText,
                                    height: 1.15,
                                  ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        ),
        const SizedBox(height: 8),
      ],
    );
  }

  static double _exploreStripHeight(BuildContext context) {
    final scaler = MediaQuery.textScalerOf(context);
    const circle = 72.0;
    const gap = 6.0;
    final twoLines = scaler.scale(13) * 2;
    return circle + gap + twoLines + 8;
  }

  static String _shortCategoryLabel(String name) {
    if (name.contains('منظفات')) return 'منظفات';
    if (name.contains('إلكترونيات')) return 'إلكترونيات';
    if (name.contains('غذائية')) return 'مواد غذائية';
    return name;
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// MainScreen — الـ Shell الرئيسي مع Bottom Navigation
// ══════════════════════════════════════════════════════════════════════════════
class MainScreen extends StatefulWidget {
  static const routeName = '/main';
  final int initialIndex;

  const MainScreen({super.key, this.initialIndex = 0});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  static const Duration _tabAnimDuration = Duration(milliseconds: 320);
  static const Curve _tabAnimCurve = Curves.easeOutCubic;

  late int _currentIndex;
  late final PageController _pageController;

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex.clamp(0, 3);
    _pageController = PageController(initialPage: _currentIndex);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      context.read<AddressCubit>().load();
      context.read<NotificationsCubit>().load(silent: true);
      unawaited(PushService.instance.sync());
      final catalog = context.read<CatalogCubit>().state;
      if (!catalog.loading && catalog.productsById.isNotEmpty) {
        context.read<FavoriteCubit>().retainExisting(
              catalog.productsById.keys.toSet(),
            );
      }
      final session = AuthSession.instance;
      if (session.offerLoginOnHome && !session.isLoggedIn) {
        session.offerLoginOnHome = false;
        Navigator.of(context).pushNamed(AppRouter.phoneLogin);
        return;
      }
      if (session.offerCompleteName && session.isLoggedIn) {
        session.offerCompleteName = false;
        Navigator.of(context).pushNamed(AppRouter.completeName);
      }
      if (session.welcomeAfterLogin) {
        session.welcomeAfterLogin = false;
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text(
            AppStrings.guestWelcomeSnack,
            textAlign: TextAlign.center,
          ),
          behavior: SnackBarBehavior.floating,
        ));
      }
    });
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _goToTab(int i) {
    final idx = i.clamp(0, 3);
    setState(() => _currentIndex = idx);
    void animateIfNeeded() {
      if (!_pageController.hasClients) return;
      final cur = _pageController.page?.round() ?? _currentIndex;
      if (cur == idx) return;
      _pageController.animateToPage(
        idx,
        duration: _tabAnimDuration,
        curve: _tabAnimCurve,
      );
    }

    if (_pageController.hasClients) {
      animateIfNeeded();
    } else {
      WidgetsBinding.instance.addPostFrameCallback((_) => animateIfNeeded());
    }
  }

  Future<void> _openCartTab() async {
    _goToTab(2);
  }

  void _openAiChat() {
    Navigator.of(context, rootNavigator: true).push<void>(
      MaterialPageRoute<void>(
        builder: (_) => const ChatScreen(useHeroMic: true),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    // 0 رئيسية، 1 أقسام، 2 سلة، 3 حسابي — زر المساعد يفتح الشات كصفحة كاملة
    final tabs = <Widget>[
      KeyedSubtree(
        key: const ValueKey<int>(0),
        child: _HomeTab(onOpenCart: _openCartTab),
      ),
      const KeyedSubtree(
        key: ValueKey<int>(1),
        child: CategoriesScreen(),
      ),
      const KeyedSubtree(
        key: ValueKey<int>(2),
        child: CartScreen(),
      ),
      const KeyedSubtree(
        key: ValueKey<int>(3),
        child: _ProfileTab(),
      ),
    ];

    return Directionality(
      textDirection: TextDirection.rtl,
      child: BlocListener<CatalogCubit, CatalogState>(
        listenWhen: (prev, next) =>
            prev.loading && !next.loading && next.productsById.isNotEmpty,
        listener: (context, catalog) {
          context.read<FavoriteCubit>().retainExisting(
                catalog.productsById.keys.toSet(),
              );
        },
        child: MainShellScope(
        openCheckout: _openCartTab,
        selectTab: _goToTab,
        child: AnnotatedRegion<SystemUiOverlayStyle>(
          value: SystemUiOverlayStyle.dark.copyWith(
            statusBarColor: Colors.transparent,
          ),
          child: Scaffold(
            backgroundColor: AppTheme.background,
            body: PageView(
              controller: _pageController,
              physics: const NeverScrollableScrollPhysics(),
              children: tabs,
            ),
            bottomNavigationBar: MainBottomNavBar(
              currentTabIndex: _currentIndex,
              cartScreenActive: false,
              wrapAiCenterHero: true,
              onTabTap: _goToTab,
              onAiAssistantTap: _openAiChat,
            ),
          ),
        ),
      ),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// Home Tab
// ══════════════════════════════════════════════════════════════════════════════
class _HomeTab extends StatefulWidget {
  final VoidCallback onOpenCart;
  const _HomeTab({required this.onOpenCart});

  @override
  State<_HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends State<_HomeTab> {
  final _searchCtrl = TextEditingController();
  String _searchQuery = '';
  String? _selectedCategory;

  List<ProductModel> _filteredProducts(CatalogState catalog) {
    final q = _searchQuery.trim();
    if (q.isNotEmpty) return catalog.search(q);
    var list = catalog.products;
    if (_selectedCategory != null) {
      final ids = catalog.categoryTreeIds(_selectedCategory!);
      list = list.where((p) => ids.contains(p.categoryId)).toList();
    }
    return list;
  }

  List<Color> _gradientFor(String key, int index) {
    return switch (key) {
      'fresh_groceries' => const [Color(0xFFD8F2E4), Color(0xFFF0FAF3)],
      'best_prices' => const [Color(0xFFFFF3EB), Color(0xFFFFFBF9)],
      _ => index.isEven
          ? const [Color(0xFFE8F8EC), Color(0xFFF5FCF7)]
          : const [Color(0xFFD8F2E4), Color(0xFFF0FAF3)],
    };
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final topPad = MediaQuery.paddingOf(context).top;

    return BlocBuilder<CatalogCubit, CatalogState>(
      builder: (context, catalog) {
        final isLoading = catalog.loading && catalog.products.isEmpty;
        final filtered = _filteredProducts(catalog);
        final isSearching = _searchQuery.trim().isNotEmpty;
        final browsingHome =
            !isSearching && _selectedCategory == null;

        return MediaQuery.withClampedTextScaling(
          maxScaleFactor: 1.25,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
            _HomeStickyTopBar(
              topPad: topPad,
              searchController: _searchCtrl,
              onSearchChanged: (v) => setState(() => _searchQuery = v),
            ),
            Expanded(
              child: RefreshIndicator(
                color: AppTheme.primary,
                onRefresh: () async {
                  await Future.wait([
                    context.read<CatalogCubit>().load(refresh: true),
                    context.read<NotificationsCubit>().load(silent: true),
                  ]);
                },
                child: CustomScrollView(
                  physics: const AlwaysScrollableScrollPhysics(
                    parent: BouncingScrollPhysics(),
                  ),
                  slivers: [
                    if (catalog.error != null && catalog.products.isEmpty)
                      SliverFillRemaining(
                        hasScrollBody: false,
                        child: _EmptyState(
                          query: catalog.error!,
                          onClear: () =>
                              context.read<CatalogCubit>().load(),
                        ),
                      )
                    else if (isSearching) ...[
                      if (filtered.isEmpty)
                        SliverFillRemaining(
                          hasScrollBody: false,
                          child: _EmptyState(
                            query: _searchQuery.trim(),
                            onClear: () => setState(() {
                              _searchQuery = '';
                              _searchCtrl.clear();
                            }),
                          ),
                        )
                      else ...[
                        SliverToBoxAdapter(
                          child: Padding(
                            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                            child: Text(
                              '${filtered.length} نتيجة',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w800,
                                color: AppTheme.mutedText,
                              ),
                            ),
                          ),
                        ),
                        SliverPadding(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 110),
                          sliver: SliverGrid(
                            delegate: SliverChildBuilderDelegate(
                              (ctx, i) => ProductCard(
                                product: filtered[i],
                                heroTag:
                                    'home_search_${i}_${filtered[i].id}',
                              ),
                              childCount: filtered.length,
                            ),
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 3,
                              mainAxisSpacing: 10,
                              crossAxisSpacing: 10,
                              childAspectRatio: 0.56,
                            ),
                          ),
                        ),
                      ],
                    ]
                    else ...[
                      if (catalog.offline)
                        const SliverToBoxAdapter(child: _OfflineBanner()),
                      SliverToBoxAdapter(
                        child: isLoading
                            ? const _PromoShimmer()
                            : _PromotionBanner(
                                banners: catalog.banners,
                                offerProducts: catalog.offers,
                                productsById: catalog.productsById,
                              ),
                      ),
                      if (isLoading)
                        const SliverToBoxAdapter(child: _CategoriesShimmer())
                      else
                        SliverToBoxAdapter(
                          child: _ExploreCategoriesStrip(
                            selectedId: _selectedCategory,
                            categories: catalog.categories,
                            onViewAll: () =>
                                MainShellScope.read(context).selectTab(1),
                            onSelect: (id) => setState(() {
                              if (id == '__all__') {
                                _selectedCategory = null;
                              } else {
                                _selectedCategory =
                                    _selectedCategory == id ? null : id;
                              }
                            }),
                          ),
                        ),
                      if (!isLoading && browsingHome)
                        ...catalog.sections.asMap().entries.map((entry) {
                          final i = entry.key;
                          final section = entry.value;
                          return SliverToBoxAdapter(
                            child: _CurvedProductCarouselSection(
                              title: section.title,
                              subtitle: section.subtitle,
                              subtitleStyle: section.key == 'best_prices'
                                  ? const TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w800,
                                      color: AppTheme.primary,
                                      height: 1.25,
                                    )
                                  : null,
                              titleLeading: section.key == 'best_prices' ||
                                      section.key == 'most_requested'
                                  ? const _SectionFireIcon()
                                  : null,
                              products: section.products,
                              gradientColors: _gradientFor(section.key, i),
                              curveTop: i == 0,
                              curveBottom: true,
                            ),
                          );
                        }),
                      if (isLoading)
                        const SliverPadding(
                          padding: EdgeInsets.fromLTRB(16, 0, 16, 16),
                          sliver: _ProductGridShimmer(),
                        )
                      else if (filtered.isEmpty)
                        SliverToBoxAdapter(
                          child: _EmptyState(
                            query: _searchQuery,
                            onClear: () => setState(() {
                              _searchQuery = '';
                              _searchCtrl.clear();
                              _selectedCategory = null;
                            }),
                          ),
                        )
                      else
                        SliverPadding(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                          sliver: SliverGrid(
                            delegate: SliverChildBuilderDelegate(
                              (ctx, i) =>
                                  ProductCard(
                                    product: filtered[i],
                                    heroTag: 'home_grid_${i}_${filtered[i].id}',
                                  ),
                              childCount: filtered.length,
                            ),
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 3,
                              mainAxisSpacing: 10,
                              crossAxisSpacing: 10,
                              childAspectRatio: 0.56,
                            ),
                          ),
                        ),
                      if (!isLoading)
                        SliverToBoxAdapter(
                          child: Padding(
                            padding: const EdgeInsets.fromLTRB(16, 4, 16, 110),
                            child: Container(
                              decoration: BoxDecoration(
                                color: AppTheme.surface,
                                borderRadius: BorderRadius.circular(25),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.05),
                                    blurRadius: 16,
                                    offset: const Offset(0, 4),
                                  ),
                                ],
                              ),
                              child: Row(
                                children: [
                                  const Expanded(
                                    child: Padding(
                                      padding: EdgeInsets.symmetric(
                                        horizontal: 16,
                                        vertical: 14,
                                      ),
                                      child: Text(
                                        'شوف كل المنتجات اللي تناسبك!',
                                        style: TextStyle(
                                          fontSize: 13,
                                          color: AppTheme.mutedText,
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                    ),
                                  ),
                                  Padding(
                                    padding: const EdgeInsets.all(6),
                                    child: ElevatedButton(
                                      onPressed: () => setState(() {
                                        _searchQuery = '';
                                        _searchCtrl.clear();
                                        _selectedCategory = null;
                                      }),
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: AppTheme.primaryDark,
                                        foregroundColor: Colors.white,
                                        elevation: 0,
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 18,
                                          vertical: 14,
                                        ),
                                        minimumSize: Size.zero,
                                        shape: RoundedRectangleBorder(
                                          borderRadius:
                                              BorderRadius.circular(20),
                                        ),
                                        textStyle: const TextStyle(
                                          fontWeight: FontWeight.w700,
                                          fontSize: 13,
                                        ),
                                      ),
                                      child: const Text('اكتشف كل العروض!'),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                    ],
                  ],
                ),
              ),
            ),
          ],
          ),
        );
      },
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// Promotion Banner — كروت بيضاء تعرض منتجات مخفّضة مع زرين
// ══════════════════════════════════════════════════════════════════════════════
class _PromotionBanner extends StatefulWidget {
  final List<BannerModel> banners;
  final List<ProductModel> offerProducts;
  final Map<String, ProductModel> productsById;

  const _PromotionBanner({
    required this.banners,
    required this.offerProducts,
    required this.productsById,
  });

  @override
  State<_PromotionBanner> createState() => _PromotionBannerState();
}

class _PromotionBannerState extends State<_PromotionBanner> {
  final _controller = PageController(viewportFraction: 0.92);
  int _current = 0;
  Timer? _timer;

  List<ProductModel> get _promoProducts {
    if (widget.banners.isNotEmpty) {
      final linked = <ProductModel>[];
      for (final banner in widget.banners) {
        final product = banner.linkType == 'product' && banner.linkId != null
            ? widget.productsById[banner.linkId!]
            : null;
        if (product != null) {
          linked.add(product);
        }
      }
      if (linked.isNotEmpty) return linked;
    }
    return widget.offerProducts.take(4).toList();
  }

  @override
  void initState() {
    super.initState();
    _startAutoScroll();
  }

  void _startAutoScroll() {
    _timer = Timer.periodic(const Duration(seconds: 5), (_) {
      if (!mounted) return;
      final products = _promoProducts;
      if (products.isEmpty) return;
      final next = (_current + 1) % products.length;
      _controller.animateToPage(
        next,
        duration: const Duration(milliseconds: 650),
        curve: Curves.easeInOutCubic,
      );
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final products = _promoProducts;
    if (products.isEmpty) return const SizedBox.shrink();

    return Column(
      children: [
        const SizedBox(height: 16),
        SizedBox(
          height: 200,
          child: PageView.builder(
            controller: _controller,
            itemCount: products.length,
            onPageChanged: (i) => setState(() => _current = i),
            itemBuilder: (_, i) {
              return AnimatedScale(
                scale: _current == i ? 1.0 : 0.96,
                duration: const Duration(milliseconds: 300),
                child: _PromoBannerCard(product: products[i]),
              );
            },
          ),
        ),
        const SizedBox(height: 12),
        // Dot Indicators
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(
            products.length,
            (i) => AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              margin: const EdgeInsets.symmetric(horizontal: 3),
              width: _current == i ? 22 : 7,
              height: 7,
              decoration: BoxDecoration(
                color: _current == i
                    ? AppTheme.primaryDark
                    : AppTheme.mutedText.withValues(alpha: 0.3),
                borderRadius: BorderRadius.circular(4),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

// ── كارت العرض البارز — أبيض، زرين، صورة ──────────────────────────────────
class _PromoBannerCard extends StatelessWidget {
  final ProductModel product;
  const _PromoBannerCard({required this.product});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(25),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.07),
            blurRadius: 20,
            offset: const Offset(0, 6),
          ),
        ],
      ),
        child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
        child: Row(
          children: [
            // ── الجانب الأيمن: تفاصيل + أزرار ────────────────────────────
            Expanded(
              flex: 3,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  // شارة "عرض السوبر"
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 9,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFF3E0),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: const Color(0xFFFFCC80),
                        width: 0.8,
                      ),
                    ),
                    child: const Text(
                      '🔥  عرض السوبر',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFBF360C),
                      ),
                    ),
                  ),

                  // اسم المنتج
                  Flexible(
                    child: ProductNameText(
                      product.name,
                      baseSize: 13,
                      maxLines: 2,
                    ),
                  ),

                  PriceLine(
                    price: product.effectivePrice,
                    originalPrice: product.hasDiscount ? product.price : null,
                    color: AppTheme.primaryDark,
                    priceSize: 15,
                    alignment: AlignmentDirectional.centerStart,
                  ),

                  // الأزرار
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () => showProductPreview(context, product),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppTheme.darkText,
                            side: BorderSide(
                              color: AppTheme.mutedText.withValues(alpha: 0.35),
                            ),
                            padding:
                                const EdgeInsets.symmetric(vertical: 7),
                            minimumSize: Size.zero,
                            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(20),
                            ),
                            textStyle: const TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          child: const Text('عرض التفاصيل'),
                        ),
                      ),
                      const SizedBox(width: 7),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () {
                            context.read<CartCubit>().addToCart(product);
                            ScaffoldMessenger.of(context)
                              ..hideCurrentSnackBar()
                              ..showSnackBar(SnackBar(
                                content: Text(
                                  AppStrings.productAddedToCart(product.name),
                                  textAlign: TextAlign.center,
                                ),
                                backgroundColor: AppTheme.primaryDark,
                                behavior: SnackBarBehavior.floating,
                                duration: const Duration(seconds: 2),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                margin: const EdgeInsets.fromLTRB(
                                    16, 0, 16, 80),
                              ));
                          },
                          icon: const Icon(Icons.add_rounded, size: 14),
                          label: const Text('اضافة للسلة'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppTheme.primaryDark,
                            foregroundColor: Colors.white,
                            padding:
                                const EdgeInsets.symmetric(vertical: 7),
                            minimumSize: Size.zero,
                            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                            elevation: 0,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(20),
                            ),
                            textStyle: const TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(width: 10),

            // ── الجانب الأيسر: صورة المنتج ────────────────────────────────
            ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: AppNetworkImage(
                product.imageUrl,
                width: 105,
                height: 130,
                fit: BoxFit.cover,
                placeholder: Container(
                  width: 105,
                  height: 130,
                  color: const Color(0xFFEDF9F2),
                  child: Center(
                    child: CircularProgressIndicator(
                      color: AppTheme.primary,
                      strokeWidth: 2,
                    ),
                  ),
                ),
                error: Container(
                  width: 105,
                  height: 130,
                  color: const Color(0xFFEDF9F2),
                  child: Icon(
                    Icons.image_not_supported_outlined,
                    color: AppTheme.mutedText,
                    size: 32,
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

// ══════════════════════════════════════════════════════════════════════════════
// Profile Tab
// ══════════════════════════════════════════════════════════════════════════════
class _ProfileTab extends StatelessWidget {
  const _ProfileTab();


  @override
  Widget build(BuildContext context) => const ProfileScreen();
}

// ══════════════════════════════════════════════════════════════════════════════
// Shimmer Skeletons
// ══════════════════════════════════════════════════════════════════════════════
class _ShimmerBox extends StatelessWidget {
  final double width;
  final double height;
  final double radius;

  const _ShimmerBox({
    required this.width,
    required this.height,
    this.radius = 12,
  });

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: const Color(0xFFDCF5EA),
      highlightColor: const Color(0xFFF0FFF6),
      child: Container(
        width: width,
        height: height,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(radius),
        ),
      ),
    );
  }
}

class _PromoShimmer extends StatelessWidget {
  const _PromoShimmer();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
      child: Shimmer.fromColors(
        baseColor: const Color(0xFFDCF5EA),
        highlightColor: const Color(0xFFF0FFF6),
        child: Container(
          height: 190,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(25),
          ),
        ),
      ),
    );
  }
}

class _CategoriesShimmer extends StatelessWidget {
  const _CategoriesShimmer();

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 20),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: _ShimmerBox(width: 80, height: 16, radius: 8),
        ),
        const SizedBox(height: 14),
        SizedBox(
          height: 96,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12),
            itemCount: 5,
            itemBuilder: (_, __) => Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Shimmer.fromColors(
                    baseColor: const Color(0xFFDCF5EA),
                    highlightColor: const Color(0xFFF0FFF6),
                    child: Container(
                      width: 58,
                      height: 58,
                      decoration: const BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  _ShimmerBox(width: 50, height: 10, radius: 4),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(height: 20),
      ],
    );
  }
}

class _ProductGridShimmer extends StatelessWidget {
  const _ProductGridShimmer();

  @override
  Widget build(BuildContext context) {
    return SliverGrid(
      delegate: SliverChildBuilderDelegate(
        (_, __) => const _ProductCardShimmer(),
        childCount: 9,
      ),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        mainAxisSpacing: 10,
        crossAxisSpacing: 10,
        childAspectRatio: 0.56,
      ),
    );
  }
}

class _ProductCardShimmer extends StatelessWidget {
  const _ProductCardShimmer();

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: const Color(0xFFDCF5EA),
      highlightColor: const Color(0xFFF0FFF6),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
        ),
      ),
    );
  }
}

class _OfflineBanner extends StatelessWidget {
  const _OfflineBanner();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: const Color(0xFFFFF8E1),
          borderRadius: BorderRadius.circular(14),
        ),
        child: const Text(
          'تعمل بدون اتصال — تُعرض البيانات المحفوظة على الجهاز',
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: Color(0xFF8D6E00),
          ),
        ),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// Empty State
// ══════════════════════════════════════════════════════════════════════════════
class _EmptyState extends StatelessWidget {
  final String query;
  final VoidCallback onClear;

  const _EmptyState({required this.query, required this.onClear});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 60),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.search_off_rounded,
            size: 72,
            color: AppTheme.mutedText.withValues(alpha: 0.35),
          ),
          const SizedBox(height: 16),
          Text(
            query.isNotEmpty
                ? 'لا نتائج لـ "$query"'
                : 'لا توجد منتجات في هذا القسم',
            style: TextStyle(
              fontSize: 15,
              color: AppTheme.mutedText,
              fontWeight: FontWeight.w500,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          OutlinedButton.icon(
            onPressed: onClear,
            icon: const Icon(Icons.refresh_rounded),
            label: const Text(AppStrings.viewAll),
            style: OutlinedButton.styleFrom(
              foregroundColor: AppTheme.primaryDark,
              side: BorderSide(color: AppTheme.primaryDark),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

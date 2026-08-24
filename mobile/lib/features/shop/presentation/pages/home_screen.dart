import 'dart:async';
import 'dart:ui' show lerpDouble;
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:lottie/lottie.dart';
import 'package:shimmer/shimmer.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/theme/app_scale.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../../../core/widgets/brand_logo.dart';
import '../../../../core/widgets/typing_placeholder.dart';
import '../../../../features/auth/data/services/auth_session.dart';
import '../../../../features/auth/presentation/manager/address_cubit.dart';
import '../../../../features/auth/presentation/pages/profile_screen.dart';
import '../../../../features/notifications/presentation/manager/notifications_cubit.dart';
import '../../../../features/notifications/data/services/push_service.dart';
import '../../../../features/auth/presentation/widgets/delivery_addresses_sheet.dart';
import '../../data/models/category_model.dart';
import '../../data/models/dynamic_page_model.dart';
import '../../data/models/product_model.dart';
import 'custom_dynamic_page_screen.dart';
import '../manager/catalog_cubit.dart';
import '../manager/favorite_cubit.dart';
import '../manager/orders_cubit.dart';
import '../widgets/main_bottom_nav_bar.dart';
import '../widgets/main_shell_scope.dart';
import '../widgets/price_line.dart';
import '../widgets/product_card.dart';
import '../widgets/product_card_shimmer.dart';
import '../widgets/product_preview_sheet.dart';
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

const _homeAppBarColor = Color(0xFFFAFFFC);

// ── زر الموقع بجانب البحث — أيقونة + اسم العنوان ──────────────────────────
class _HomeLocationButton extends StatelessWidget {
  const _HomeLocationButton({this.onImage = false});

  final bool onImage;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AddressCubit, AddressState>(
      builder: (context, state) {
        final name = state.selected?.headerName ?? AppStrings.homeLocationHome;
        final style = Theme.of(context).textTheme.labelLarge?.copyWith(
          fontWeight: FontWeight.w800,
          color: AppTheme.darkText,
          fontSize: 12.5,
        );
        return GestureDetector(
          onTap: () => DeliveryAddressesSheet.show(context),
          behavior: HitTestBehavior.opaque,
          child: SizedBox(
            height: AppScale.of(context).searchH,
            child: DecoratedBox(
              decoration: BoxDecoration(
                color: onImage ? AppTheme.surface : Colors.transparent,
                borderRadius: BorderRadius.circular(22),
              ),
              child: Padding(
                padding: const EdgeInsetsDirectional.only(start: 2, end: 2),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.location_on_rounded,
                      color: AppTheme.primary,
                      size: 20,
                    ),
                    const SizedBox(width: 3),
                    Flexible(
                      child: _MarqueeText(text: name, style: style),
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}

/// شريط اسم متحرك عندما يفيض النص عن العرض المتاح.
class _MarqueeText extends StatefulWidget {
  final String text;
  final TextStyle? style;
  final bool alwaysScroll;

  const _MarqueeText({
    required this.text,
    this.style,
    this.alwaysScroll = false,
  });

  @override
  State<_MarqueeText> createState() => _MarqueeTextState();
}

class _MarqueeTextState extends State<_MarqueeText>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(vsync: this);
  }

  @override
  void didUpdateWidget(_MarqueeText oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.text != widget.text ||
        oldWidget.alwaysScroll != widget.alwaysScroll) {
      _stop();
    }
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  void _run(double overflowWidth) {
    final ms = (2800 + overflowWidth * 28).round().clamp(4000, 16000);
    final next = Duration(milliseconds: ms);
    if (_ctrl.duration != next) {
      _ctrl.duration = next;
    }
    if (!_ctrl.isAnimating) {
      _ctrl.repeat();
    }
  }

  void _stop() {
    if (_ctrl.isAnimating) {
      _ctrl.stop();
      _ctrl.reset();
    }
  }

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final painter = TextPainter(
          text: TextSpan(text: widget.text, style: widget.style),
          maxLines: 1,
          textDirection: Directionality.of(context),
          textScaler: MediaQuery.textScalerOf(context),
        )..layout();

        final maxW = constraints.maxWidth;
        if (!widget.alwaysScroll && painter.width <= maxW + 1) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (mounted) _stop();
          });
          return Text(
            widget.text,
            maxLines: 1,
            softWrap: false,
            overflow: TextOverflow.clip,
            style: widget.style,
          );
        }

        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) _run(painter.width);
        });
        const gap = 36.0;
        final loop = painter.width + gap;

        return ClipRect(
          child: AnimatedBuilder(
            animation: _ctrl,
            builder: (context, _) {
              final dx = _ctrl.value * loop;
              return Stack(
                clipBehavior: Clip.hardEdge,
                children: [
                  Transform.translate(
                    offset: Offset(dx, 0),
                    child: Text(
                      widget.text,
                      maxLines: 1,
                      softWrap: false,
                      style: widget.style,
                    ),
                  ),
                  Transform.translate(
                    offset: Offset(dx - loop, 0),
                    child: Text(
                      widget.text,
                      maxLines: 1,
                      softWrap: false,
                      style: widget.style,
                    ),
                  ),
                ],
              );
            },
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
    return const BrandLogoMark(size: 78);
  }
}

// ── شريط البحث للرأس — بيضاوي بظل ────────────────────────────────────────────
class _HeaderSearchBar extends StatelessWidget {
  final double height;
  final VoidCallback onTap;

  const _HeaderSearchBar({required this.height, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final hintStyle = Theme.of(context).textTheme.bodyMedium?.copyWith(
      color: AppTheme.mutedText.withValues(alpha: 0.75),
      fontSize: 13,
    );
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: height,
        decoration: BoxDecoration(
          color: AppTheme.surface,
          borderRadius: BorderRadius.circular(21),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.07),
              blurRadius: 12,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Stack(
          alignment: Alignment.center,
          children: [
            const Align(
              alignment: AlignmentDirectional.centerStart,
              child: Padding(
                padding: EdgeInsetsDirectional.only(start: 12),
                child: Icon(
                  Icons.search_rounded,
                  color: AppTheme.primary,
                  size: 20,
                ),
              ),
            ),
            Positioned.fill(
              child: Padding(
                padding: const EdgeInsetsDirectional.only(start: 40, end: 16),
                child: Align(
                  alignment: AlignmentDirectional.centerStart,
                  child: TypingPlaceholder(
                    phrases: AppStrings.homeSearchHints,
                    style: hintStyle,
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

const double _kHomeLogoRow = 58;
const double _kHomeSearchH = 42;
const double _kHomeBannerH = 236;
const double _kHomeBannerBlend = 14;

class _PromoSlide {
  final String imageUrl;
  final String title;
  final String? subtitle;
  final ProductModel? product;
  final String? pageId;

  const _PromoSlide({
    required this.imageUrl,
    required this.title,
    this.subtitle,
    this.product,
    this.pageId,
  });

  @override
  bool operator ==(Object other) =>
      other is _PromoSlide &&
      other.imageUrl == imageUrl &&
      other.title == title &&
      other.product?.id == product?.id &&
      other.pageId == pageId;

  @override
  int get hashCode => Object.hash(imageUrl, title, product?.id, pageId);
}

void _openPromoSlide(BuildContext context, _PromoSlide slide) {
  final pageId = slide.pageId;
  if (pageId != null && pageId.isNotEmpty) {
    final initial = context.read<CatalogCubit>().state.pageById(pageId);
    Navigator.of(context).pushNamed(
      AppRouter.dynamicPage,
      arguments: DynamicPageArgs(pageId: pageId, initial: initial),
    );
    return;
  }
  if (slide.product != null) {
    showProductPreview(context, slide.product!);
  }
}

List<_PromoSlide> _promoSlidesFor(CatalogState catalog) {
  final slides = <_PromoSlide>[];
  for (final banner in catalog.banners) {
    final product = banner.linkType == 'product' && banner.linkId != null
        ? catalog.productsById[banner.linkId!]
        : null;
    DynamicPageModel? page;
    if (banner.linkType == 'page' && banner.linkId != null) {
      page = catalog.pageById(banner.linkId!);
    }
    final image = banner.imageUrl.trim().isNotEmpty
        ? banner.imageUrl
        : (page?.bannerImageUrl.isNotEmpty == true
              ? page!.bannerImageUrl
              : (product?.imageUrl ?? ''));
    if (image.isEmpty &&
        banner.title.trim().isEmpty &&
        product == null &&
        page == null) {
      continue;
    }
    slides.add(
      _PromoSlide(
        imageUrl: image,
        title: banner.title.trim().isNotEmpty
            ? banner.title
            : (page?.title ?? product?.name ?? ''),
        subtitle: banner.subtitle ?? product?.quantityLabel,
        product: product,
        pageId: page?.id ?? (banner.linkType == 'page' ? banner.linkId : null),
      ),
    );
  }
  if (slides.isNotEmpty) return slides;
  return [
    for (final product in catalog.offers.take(4))
      _PromoSlide(
        imageUrl: product.imageUrl,
        title: product.name,
        subtitle: product.quantityLabel,
        product: product,
      ),
  ];
}

class _HomeNotificationsButton extends StatelessWidget {
  const _HomeNotificationsButton();

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<NotificationsCubit, NotificationsState>(
      builder: (context, state) {
        return IconButton(
          tooltip: 'الإشعارات',
          onPressed: () {
            unawaited(context.read<NotificationsCubit>().load(silent: true));
            Navigator.of(context).pushNamed(AppRouter.notifications);
          },
          icon: Badge(
            isLabelVisible: state.unreadCount > 0,
            label: Text(
              state.unreadCount > 99 ? '99+' : '${state.unreadCount}',
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
    );
  }
}

class _HomeSearchRow extends StatelessWidget {
  final VoidCallback onSearchTap;
  final bool onImage;
  final double chromeVisibility;

  const _HomeSearchRow({
    required this.onSearchTap,
    this.onImage = false,
    this.chromeVisibility = 1,
  });

  @override
  Widget build(BuildContext context) {
    final showLocation = chromeVisibility > 0.02;
    return LayoutBuilder(
      builder: (context, constraints) {
        return Row(
          children: [
            Expanded(
              child: _HeaderSearchBar(
                height: AppScale.of(context).searchH,
                onTap: onSearchTap,
              ),
            ),
            if (showLocation) ...[
              const SizedBox(width: 4),
              ConstrainedBox(
                constraints: BoxConstraints(
                  maxWidth: constraints.maxWidth * 0.25,
                ),
                child: IgnorePointer(
                  ignoring: chromeVisibility < 0.2,
                  child: Opacity(
                    opacity: chromeVisibility,
                    child: _HomeLocationButton(onImage: onImage),
                  ),
                ),
              ),
            ],
          ],
        );
      },
    );
  }
}

class _HomeMagicHeaderDelegate extends SliverPersistentHeaderDelegate {
  final double topPad;
  final double bannerH;
  final double searchH;
  final double logoRow;
  final double bannerBlend;
  final VoidCallback onSearchTap;
  final List<_PromoSlide> slides;
  final bool loading;

  const _HomeMagicHeaderDelegate({
    required this.topPad,
    required this.bannerH,
    required this.searchH,
    required this.logoRow,
    required this.bannerBlend,
    required this.onSearchTap,
    required this.slides,
    required this.loading,
  });

  bool get _hasBanner => loading || slides.isNotEmpty;

  double get _expandedTail =>
      8 +
      logoRow +
      10 +
      searchH +
      (_hasBanner ? 14 + bannerH + bannerBlend : 12);

  @override
  double get maxExtent => topPad + _expandedTail;

  @override
  double get minExtent => _hasBanner ? bannerH + 2 : topPad + 8 + searchH + 10;

  @override
  bool shouldRebuild(covariant _HomeMagicHeaderDelegate old) {
    return topPad != old.topPad ||
        bannerH != old.bannerH ||
        searchH != old.searchH ||
        logoRow != old.logoRow ||
        bannerBlend != old.bannerBlend ||
        loading != old.loading ||
        slides != old.slides;
  }

  @override
  Widget build(
    BuildContext context,
    double shrinkOffset,
    bool overlapsContent,
  ) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final span = (maxExtent - minExtent).clamp(1.0, 900.0);
        final t = (shrinkOffset / span).clamp(0.0, 1.0);
        final p = Curves.easeOutCubic.transform(t);
        final chrome = (1.0 - p * 1.25).clamp(0.0, 1.0);
        final logoOpacity = (1.0 - p * 1.15).clamp(0.0, 1.0);
        final barOpacity = (1.0 - p).clamp(0.0, 1.0);
        final bannerTop = lerpDouble(
          topPad + 8 + logoRow + 8 + searchH + 14,
          0,
          p,
        )!;
        final bannerInset = lerpDouble(14, 0, p)!;
        final bannerTopRadius = lerpDouble(28, 0, p)!;
        final bannerBottomRadius = lerpDouble(28, 22, p)!;
        final blendH = lerpDouble(bannerBlend, 2, p)!;
        final blendOpacity = (1.0 - p).clamp(0.0, 1.0);
        final searchTop = lerpDouble(topPad + 8 + logoRow + 6, topPad + 8, p)!;
        final searchInset = lerpDouble(12, 12, p)!;
        final logoScale = lerpDouble(1.0, 0.72, p)!;

        return AnnotatedRegion<SystemUiOverlayStyle>(
          value: p > 0.55
              ? SystemUiOverlayStyle.light.copyWith(
                  statusBarColor: Colors.transparent,
                )
              : SystemUiOverlayStyle.dark.copyWith(
                  statusBarColor: Colors.transparent,
                ),
          child: ClipRect(
            child: Stack(
              fit: StackFit.expand,
              children: [
                ColoredBox(
                  color: AppTheme.background.withValues(alpha: barOpacity),
                ),
                if (_hasBanner && p > 0.2)
                  Positioned(
                    left: 0,
                    right: 0,
                    bottom: 0,
                    height: bannerBottomRadius + 4,
                    child: const IgnorePointer(
                      child: ColoredBox(color: AppTheme.background),
                    ),
                  ),
                if (_hasBanner)
                  Positioned(
                    left: bannerInset,
                    right: bannerInset,
                    top: bannerTop,
                    height: bannerH,
                    child: RepaintBoundary(
                      child: ClipRRect(
                        borderRadius: BorderRadius.only(
                          topLeft: Radius.circular(bannerTopRadius),
                          topRight: Radius.circular(bannerTopRadius),
                          bottomLeft: Radius.circular(bannerBottomRadius),
                          bottomRight: Radius.circular(bannerBottomRadius),
                        ),
                        child: loading
                            ? const _PromoShimmer(fill: true)
                            : _HomePromoSlider(
                                key: ValueKey(
                                  slides
                                      .map((s) => s.imageUrl)
                                      .join('|'),
                                ),
                                slides: slides,
                                contentOpacity: 1,
                                showDots: slides.length > 1,
                              ),
                      ),
                    ),
                  ),
                if (_hasBanner && blendOpacity > 0.02)
                  Positioned(
                    left: 0,
                    right: 0,
                    top: bannerTop + bannerH,
                    height: blendH,
                    child: IgnorePointer(
                      child: Opacity(
                        opacity: blendOpacity,
                        child: const DecoratedBox(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                              colors: [Color(0x1488D498), Color(0x00F0FAF3)],
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                Positioned(
                  top: 0,
                  left: 0,
                  right: 0,
                  height: topPad + 8 + logoRow + 8 + searchH,
                  child: IgnorePointer(
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            _homeAppBarColor.withValues(alpha: barOpacity),
                            _homeAppBarColor.withValues(
                              alpha: barOpacity * 0.85,
                            ),
                            _homeAppBarColor.withValues(alpha: 0),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
                Positioned(
                  top: topPad + 2,
                  left: 10,
                  right: 12,
                  height: logoRow,
                  child: IgnorePointer(
                    ignoring: chrome < 0.15,
                    child: Row(
                      textDirection: TextDirection.rtl,
                      children: [
                        Opacity(
                          opacity: logoOpacity,
                          child: Transform.translate(
                            offset: Offset(0, -10 * p),
                            child: Transform.scale(
                              alignment: Alignment.centerRight,
                              scale: logoScale,
                              child: const Align(
                                alignment: Alignment.centerRight,
                                child: _HomeBrandMark(),
                              ),
                            ),
                          ),
                        ),
                        const Spacer(),
                        Opacity(
                          opacity: chrome,
                          child: Transform.translate(
                            offset: Offset(0, -12 * p),
                            child: const _HomeNotificationsButton(),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                Positioned(
                  top: searchTop,
                  left: searchInset,
                  right: searchInset,
                  height: searchH,
                  child: _HomeSearchRow(
                    onSearchTap: onSearchTap,
                    onImage: p > 0.4,
                    chromeVisibility: chrome,
                  ),
                ),
                if (p > 0.35)
                  Positioned(
                    left: 0,
                    right: 0,
                    bottom: 0,
                    height: 8,
                    child: IgnorePointer(
                      child: DecoratedBox(
                        decoration: BoxDecoration(
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.06 * p),
                              blurRadius: 8,
                              offset: const Offset(0, 3),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),
        );
      },
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
  final bool ticker;

  const _CurvedProductCarouselSection({
    required this.title,
    this.subtitle,
    this.subtitleStyle,
    this.titleLeading,
    required this.products,
    required this.gradientColors,
    this.curveTop = false,
    this.curveBottom = true,
    this.ticker = false,
  });

  @override
  Widget build(BuildContext context) {
    if (products.isEmpty) return const SizedBox.shrink();
    final scale = AppScale.of(context);

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
          padding: EdgeInsets.only(top: scale.s(20), bottom: scale.s(18)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            mainAxisSize: MainAxisSize.min,
            children: [
              Padding(
                padding: EdgeInsets.symmetric(horizontal: scale.pagePad),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.center,
                            children: [
                              if (titleLeading != null) ...[
                                titleLeading!,
                                SizedBox(width: scale.s(8)),
                              ],
                              Flexible(
                                child: Text(
                                  title,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: Theme.of(context).textTheme.titleLarge
                                      ?.copyWith(
                                        fontSize: scale.s(16),
                                        fontWeight: FontWeight.w900,
                                        color: AppTheme.primaryDark,
                                        height: 1.15,
                                      ),
                                ),
                              ),
                            ],
                          ),
                          if (subtitle != null && subtitle!.isNotEmpty) ...[
                            const SizedBox(height: 3),
                            Padding(
                              padding: EdgeInsetsDirectional.only(
                                start: titleLeading != null ? scale.s(46) : 0,
                              ),
                              child: Text(
                                subtitle!,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style:
                                    subtitleStyle ??
                                    Theme.of(
                                      context,
                                    ).textTheme.labelSmall?.copyWith(
                                      fontSize: 11,
                                      color: AppTheme.mutedText,
                                      fontWeight: FontWeight.w600,
                                      height: 1.25,
                                    ),
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
                              style: Theme.of(context).textTheme.labelLarge
                                  ?.copyWith(
                                    fontWeight: FontWeight.w700,
                                    color: AppTheme.primary,
                                  ),
                            ),
                            const Icon(
                              Icons.chevron_right_rounded,
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
              SizedBox(height: scale.s(16)),
              SizedBox(
                height: scale.productCardHeight,
                child: ticker
                    ? _TickerProductRow(products: products, title: title)
                    : ListView.separated(
                        scrollDirection: Axis.horizontal,
                        physics: const BouncingScrollPhysics(),
                        cacheExtent: 200,
                        padding: EdgeInsets.symmetric(
                          horizontal: scale.pagePad,
                        ),
                        itemCount: products.length,
                        separatorBuilder: (_, _) =>
                            SizedBox(width: scale.s(10)),
                        itemBuilder: (_, i) => SizedBox(
                          width: scale.productCardWidth,
                          child: ProductCard(
                            product: products[i],
                            heroTag:
                                'home_carousel_${title}_${i}_${products[i].id}',
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

class _TickerProductRow extends StatefulWidget {
  final List<ProductModel> products;
  final String title;

  const _TickerProductRow({required this.products, required this.title});

  @override
  State<_TickerProductRow> createState() => _TickerProductRowState();
}

class _TickerProductRowState extends State<_TickerProductRow>
    with SingleTickerProviderStateMixin {
  late final ScrollController _scroll;
  late final Ticker _ticker;
  Duration _lastElapsed = Duration.zero;
  Timer? _resumeTimer;
  double _loopWidth = 0;
  bool _userHolding = false;

  static const _pixelsPerSecond = 34.0;
  static const _resumeAfter = Duration(seconds: 2);

  @override
  void initState() {
    super.initState();
    _scroll = ScrollController();
    _ticker = createTicker(_onTick);
  }

  @override
  void dispose() {
    _resumeTimer?.cancel();
    _ticker.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _onTick(Duration elapsed) {
    if (!_scroll.hasClients || _loopWidth <= 0 || _userHolding) {
      _lastElapsed = elapsed;
      return;
    }

    final dt = (elapsed - _lastElapsed).inMicroseconds / 1e6;
    _lastElapsed = elapsed;
    if (dt <= 0 || dt > 0.08) return;

    var next = _scroll.offset + _pixelsPerSecond * dt;
    if (next >= _loopWidth) {
      next -= _loopWidth;
    }
    final max = _scroll.position.maxScrollExtent;
    if (next > max) next = 0;
    if (next < 0) next = 0;
    _scroll.jumpTo(next);
  }

  void _ensureRunning(double loopWidth) {
    if (loopWidth <= 0) return;
    _loopWidth = loopWidth;
    if (!_ticker.isActive) {
      _lastElapsed = Duration.zero;
      _ticker.start();
    }
  }

  void _pauseForUser() {
    if (_userHolding) return;
    _userHolding = true;
    _resumeTimer?.cancel();
  }

  void _scheduleResume() {
    _resumeTimer?.cancel();
    _resumeTimer = Timer(_resumeAfter, () {
      if (!mounted) return;
      if (_scroll.hasClients &&
          _loopWidth > 0 &&
          _scroll.offset >= _loopWidth) {
        _scroll.jumpTo(_scroll.offset % _loopWidth);
      }
      _userHolding = false;
      _lastElapsed = Duration.zero;
      if (!_ticker.isActive) _ticker.start();
    });
  }

  @override
  Widget build(BuildContext context) {
    if (widget.products.isEmpty) return const SizedBox.shrink();

    final scale = AppScale.of(context);
    final gap = scale.s(10);
    final cardW = scale.productCardWidth;
    final loop = widget.products.length * (cardW + gap);
    final copies = widget.products.length <= 3 ? 4 : 2;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _ensureRunning(loop);
    });

    return Listener(
      behavior: HitTestBehavior.translucent,
      onPointerDown: (_) => _pauseForUser(),
      onPointerUp: (_) => _scheduleResume(),
      onPointerCancel: (_) => _scheduleResume(),
      child: NotificationListener<ScrollNotification>(
        onNotification: (notification) {
          if (notification is ScrollStartNotification &&
              notification.dragDetails != null) {
            _pauseForUser();
          } else if (notification is ScrollEndNotification && _userHolding) {
            _scheduleResume();
          }
          return false;
        },
        child: ListView.separated(
          controller: _scroll,
          scrollDirection: Axis.horizontal,
          physics: const BouncingScrollPhysics(
            parent: AlwaysScrollableScrollPhysics(),
          ),
          cacheExtent: 200,
          padding: EdgeInsets.symmetric(horizontal: scale.pagePad),
          itemCount: widget.products.length * copies,
          separatorBuilder: (_, _) => SizedBox(width: gap),
          itemBuilder: (_, i) {
            final product = widget.products[i % widget.products.length];
            return SizedBox(
              width: cardW,
              child: ProductCard(
                product: product,
                heroTag: 'home_ticker_${widget.title}_${i}_${product.id}',
              ),
            );
          },
        ),
      ),
    );
  }
}

class _HomeRestProductsDivider extends StatelessWidget {
  const _HomeRestProductsDivider();

  @override
  Widget build(BuildContext context) {
    final scale = AppScale.of(context);
    return Padding(
      padding: EdgeInsets.fromLTRB(
        scale.pagePad,
        scale.s(8),
        scale.pagePad,
        scale.s(16),
      ),
      child: Row(
        children: [
          Expanded(
            child: Container(
              height: 1,
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Color(0x00000000),
                    Color(0x3388D498),
                    Color(0x6688D498),
                  ],
                ),
              ),
            ),
          ),
          Padding(
            padding: EdgeInsets.symmetric(horizontal: scale.s(12)),
            child: Text(
              'منتجاتنا',
              style: Theme.of(context).textTheme.labelMedium?.copyWith(
                fontWeight: FontWeight.w800,
                color: AppTheme.primaryDark,
                fontSize: scale.s(12),
              ),
            ),
          ),
          Expanded(
            child: Container(
              height: 1,
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Color(0x6688D498),
                    Color(0x3388D498),
                    Color(0x00000000),
                  ],
                ),
              ),
            ),
          ),
        ],
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

// ── شريط أقسام سريعة — دائرة ممتلئة بالصورة بدون تشويه ─────────────────────
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
    final scale = AppScale.of(context);
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
          padding: EdgeInsets.fromLTRB(
            scale.pagePad,
            scale.s(6),
            scale.pagePad,
            scale.s(8),
          ),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  AppStrings.homeExploreSections,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontSize: 15,
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
                        Icons.chevron_right_rounded,
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
          height: scale.categoryStripHeight,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            physics: const BouncingScrollPhysics(),
            cacheExtent: 200,
            padding: EdgeInsets.symmetric(horizontal: scale.pagePad),
            itemCount: all.length,
            separatorBuilder: (_, _) => SizedBox(width: scale.s(12)),
            itemBuilder: (_, i) {
              final cat = all[i];
              final isAll = cat.id == '__all__';
              final isSelected = isAll
                  ? selectedId == null
                  : selectedId == cat.id;
              return _ExploreCategoryTile(
                name: isAll
                    ? AppStrings.homeCategoryAll
                    : _shortCategoryLabel(cat.name),
                imageUrl: isAll
                    ? ''
                    : (cat.displayImage.isNotEmpty
                          ? cat.displayImage
                          : cat.iconUrl),
                isAll: isAll,
                isSelected: isSelected,
                onTap: () => onSelect(isAll ? '__all__' : cat.id),
              );
            },
          ),
        ),
        SizedBox(height: scale.s(6)),
      ],
    );
  }

  static String _shortCategoryLabel(String name) {
    if (name.contains('منظفات')) return 'منظفات';
    if (name.contains('إلكترونيات')) return 'إلكترونيات';
    if (name.contains('غذائية')) return 'مواد غذائية';
    return name;
  }
}

class _ExploreCategoryTile extends StatelessWidget {
  final String name;
  final String imageUrl;
  final bool isAll;
  final bool isSelected;
  final VoidCallback onTap;

  const _ExploreCategoryTile({
    required this.name,
    required this.imageUrl,
    required this.isAll,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final scale = AppScale.of(context);
    final size = scale.categoryCircle;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: SizedBox(
        width: scale.categoryItemWidth,
        child: Column(
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              padding: EdgeInsets.all(scale.categoryRing),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: isSelected
                      ? AppTheme.primary
                      : const Color(0x22000000),
                  width: isSelected ? 2 : 1,
                ),
              ),
              child: Container(
                width: size,
                height: size,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: AppTheme.primarySurface,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.07),
                      blurRadius: 8,
                      offset: const Offset(0, 3),
                    ),
                  ],
                ),
                clipBehavior: Clip.antiAlias,
                child: isAll
                    ? Icon(
                        Icons.apps_rounded,
                        color: AppTheme.primaryDark,
                        size: scale.s(26),
                      )
                    : AppNetworkImage(
                        imageUrl,
                        fit: BoxFit.cover,
                        width: size,
                        height: size,
                        error: ColoredBox(
                          color: AppTheme.primarySurface,
                          child: Icon(
                            Icons.category_rounded,
                            color: AppTheme.mutedText,
                            size: scale.s(24),
                          ),
                        ),
                      ),
              ),
            ),
            SizedBox(height: scale.s(6)),
            Expanded(
              child: Text(
                name,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.labelMedium?.copyWith(
                  fontSize: scale.s(11),
                  fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                  color: isSelected ? AppTheme.primaryDark : AppTheme.darkText,
                  height: 1.15,
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
  static const Duration _tabAnimDuration = Duration(milliseconds: 280);
  static const Curve _tabAnimCurve = Curves.easeOutCubic;

  late int _currentIndex;
  late final PageController _pageController;
  final Set<int> _visitedTabs = {0};

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex.clamp(0, 3);
    _visitedTabs.add(_currentIndex);
    _pageController = PageController(initialPage: _currentIndex);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      // Deferred secondary loads — after first Home frame.
      unawaited(context.read<AddressCubit>().load());
      unawaited(context.read<OrdersCubit>().load());
      unawaited(context.read<NotificationsCubit>().load(silent: true));
      unawaited(PushService.instance.sync());
      unawaited(
        precacheImage(const AssetImage('assets/images/cart.png'), context),
      );
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
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              AppStrings.guestWelcomeSnack,
              textAlign: TextAlign.center,
            ),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    });
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  Future<void> _openCartTab() async {
    await Navigator.of(context).pushNamed(AppRouter.checkout);
  }

  void _goToTab(int i) {
    if (i == 2) {
      unawaited(_openCartTab());
      return;
    }
    final idx = i.clamp(0, 3);
    setState(() {
      _currentIndex = idx;
      _visitedTabs.add(idx);
    });
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

  void _openAiChat() {
    Navigator.of(context).pushNamed(
      AppRouter.chat,
      arguments: true, // useHeroMic
    );
  }

  Widget _tabAt(int index) {
    if (!_visitedTabs.contains(index)) {
      return const SizedBox.shrink();
    }
    return switch (index) {
      0 => KeyedSubtree(
        key: const ValueKey<int>(0),
        child: _HomeTab(onOpenCart: _openCartTab),
      ),
      1 => const KeyedSubtree(key: ValueKey<int>(1), child: CategoriesScreen()),
      2 => const KeyedSubtree(key: ValueKey<int>(2), child: CartScreen()),
      _ => const KeyedSubtree(key: ValueKey<int>(3), child: _ProfileTab()),
    };
  }

  @override
  Widget build(BuildContext context) {
    // Lazy tabs: only mount a tab the first time the user opens it.
    final tabs = List<Widget>.generate(4, _tabAt);

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
  String? _selectedCategory;
  bool _didPrecache = false;
  String _precacheSig = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _precacheVisible());
  }

  void _precacheVisible() {
    if (!mounted) return;
    final catalog = context.read<CatalogCubit>().state;
    if (catalog.products.isEmpty && catalog.banners.isEmpty) return;
    final sig = catalog.banners.map((b) => b.imageUrl).join('|');
    if (_didPrecache && sig == _precacheSig) return;
    _didPrecache = true;
    _precacheSig = sig;
    final urls = <String>{};
    for (final b in catalog.banners) {
      if (b.imageUrl.trim().isNotEmpty) urls.add(b.imageUrl);
    }
    for (final p in catalog.offers.take(4)) {
      if (p.imageUrl.trim().isNotEmpty) urls.add(p.imageUrl);
    }
    for (final p in catalog.products.take(8)) {
      if (p.imageUrl.trim().isNotEmpty) urls.add(p.imageUrl);
    }
    for (final url in urls) {
      final resolved = AppNetworkImage.resolveUrl(url);
      final provider = CachedNetworkImageProvider(
        resolved,
        headers: AppNetworkImage.headersFor(resolved),
      );
      unawaited(
        precacheImage(
          provider,
          context,
          onError: (_, _) {
            // Local/storage 403 or missing file — card handles fallback.
          },
        ),
      );
    }
  }

  List<ProductModel> _filteredProducts(CatalogState catalog) {
    var list = catalog.products;
    if (_selectedCategory != null) {
      final ids = catalog.categoryTreeIds(_selectedCategory!);
      return list.where((p) => ids.contains(p.categoryId)).toList();
    }
    return list;
  }

  void _openSearch() {
    Navigator.of(context).pushNamed(AppRouter.search);
  }

  List<Color> _gradientFor(String key, int index) {
    return switch (key) {
      'fresh_groceries' => const [Color(0xFFD8F2E4), Color(0xFFF0FAF3)],
      'best_prices' => const [Color(0xFFFFF3EB), Color(0xFFFFFBF9)],
      _ =>
        index.isEven
            ? const [Color(0xFFE8F8EC), Color(0xFFF5FCF7)]
            : const [Color(0xFFD8F2E4), Color(0xFFF0FAF3)],
    };
  }

  @override
  Widget build(BuildContext context) {
    final topPad = MediaQuery.paddingOf(context).top;
    final scale = AppScale.of(context);

    return MediaQuery.withClampedTextScaling(
      maxScaleFactor: 1.25,
      child: BlocBuilder<CatalogCubit, CatalogState>(
        buildWhen: (prev, next) =>
            prev.loading != next.loading ||
            prev.refreshing != next.refreshing ||
            prev.offline != next.offline ||
            prev.error != next.error ||
            prev.feed != next.feed,
        builder: (context, catalog) {
          if (catalog.products.isNotEmpty || catalog.banners.isNotEmpty) {
            WidgetsBinding.instance.addPostFrameCallback(
              (_) => _precacheVisible(),
            );
          }
          final isLoading = catalog.loading && catalog.products.isEmpty;
          final filtered = _filteredProducts(catalog);
          final browsingHome = _selectedCategory == null;
          final slides = _promoSlidesFor(catalog);

          return RefreshIndicator(
            color: AppTheme.primary,
            edgeOffset: topPad + scale.searchH + 12,
            onRefresh: () async {
              await Future.wait([
                context.read<CatalogCubit>().load(refresh: true),
                context.read<NotificationsCubit>().load(silent: true),
              ]);
            },
            child: CustomScrollView(
              cacheExtent: 480,
              physics: const AlwaysScrollableScrollPhysics(
                parent: BouncingScrollPhysics(),
              ),
              slivers: [
                SliverPersistentHeader(
                  pinned: true,
                  delegate: _HomeMagicHeaderDelegate(
                    topPad: topPad,
                    bannerH: scale.bannerHeight,
                    searchH: scale.searchH,
                    logoRow: scale.logoRow,
                    bannerBlend: scale.bannerBlend,
                    onSearchTap: _openSearch,
                    slides: slides,
                    loading: isLoading,
                  ),
                ),
                if (catalog.error != null && catalog.products.isEmpty)
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: _EmptyState(
                      query: catalog.error!,
                      onClear: () => context.read<CatalogCubit>().load(),
                    ),
                  )
                else ...[
                  if (catalog.offline)
                    const SliverToBoxAdapter(child: _OfflineBanner()),
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
                            _selectedCategory = _selectedCategory == id
                                ? null
                                : id;
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
                          titleLeading:
                              section.key == 'best_prices' ||
                                  section.key == 'most_requested'
                              ? const _SectionFireIcon()
                              : null,
                          ticker: section.key == 'most_requested',
                          products: section.products,
                          gradientColors: _gradientFor(section.key, i),
                          curveTop: i == 0,
                          curveBottom: true,
                        ),
                      );
                    }),
                  if (!isLoading &&
                      browsingHome &&
                      catalog.suggested.isNotEmpty)
                    SliverToBoxAdapter(
                      child: _CurvedProductCarouselSection(
                        title: 'منتجات مقترحة لك',
                        subtitle: 'اختيارات تناسب سلتك وعادات الشراء',
                        products: catalog.suggested,
                        gradientColors: const [
                          Color(0xFFEDE7F6),
                          Color(0xFFF8F5FC),
                        ],
                        curveTop: catalog.sections.isEmpty,
                        curveBottom: true,
                      ),
                    ),
                  if (!isLoading && browsingHome && filtered.isNotEmpty)
                    const SliverToBoxAdapter(child: _HomeRestProductsDivider()),
                  if (isLoading)
                    const SliverPadding(
                      padding: EdgeInsets.fromLTRB(16, 0, 16, 16),
                      sliver: _ProductGridShimmer(),
                    )
                  else if (filtered.isEmpty && !browsingHome)
                    SliverToBoxAdapter(
                      child: _EmptyState(
                        query: '',
                        onClear: () => setState(() {
                          _selectedCategory = null;
                        }),
                      ),
                    )
                  else if (filtered.isNotEmpty)
                    SliverPadding(
                      padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                      sliver: SliverGrid(
                        delegate: SliverChildBuilderDelegate(
                          (ctx, i) => ProductCard(
                            product: filtered[i],
                            heroTag: 'home_grid_${i}_${filtered[i].id}',
                          ),
                          childCount: filtered.length,
                        ),
                        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: AppScale.homeGridCrossAxisCount,
                          mainAxisSpacing: scale.s(10),
                          crossAxisSpacing: scale.s(10),
                          childAspectRatio: AppScale.homeGridCardAspect,
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
                                      borderRadius: BorderRadius.circular(20),
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
          );
        },
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// Promotion Banner — صورة خلفية كاملة + تدرج الهوية
// ══════════════════════════════════════════════════════════════════════════════
class _HomePromoSlider extends StatefulWidget {
  final List<_PromoSlide> slides;
  final double contentOpacity;
  final bool showDots;

  const _HomePromoSlider({
    super.key,
    required this.slides,
    required this.contentOpacity,
    required this.showDots,
  });

  @override
  State<_HomePromoSlider> createState() => _HomePromoSliderState();
}

class _HomePromoSliderState extends State<_HomePromoSlider> {
  final _controller = PageController();
  int _current = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _startAutoScroll();
  }

  @override
  void didUpdateWidget(covariant _HomePromoSlider oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.slides.length != widget.slides.length) {
      _current = 0;
    }
  }

  void _startAutoScroll() {
    _timer = Timer.periodic(const Duration(seconds: 5), (_) {
      if (!mounted || widget.slides.length < 2) return;
      final next = (_current + 1) % widget.slides.length;
      _controller.animateToPage(
        next,
        duration: const Duration(milliseconds: 620),
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
    final slides = widget.slides;
    if (slides.isEmpty) return const SizedBox.shrink();

    return Stack(
      fit: StackFit.expand,
      children: [
        PageView.builder(
          controller: _controller,
          allowImplicitScrolling: true,
          itemCount: slides.length,
          onPageChanged: (i) => setState(() => _current = i),
          itemBuilder: (_, i) => _PromoBannerCard(slide: slides[i]),
        ),
        if (widget.contentOpacity > 0.02)
          Positioned(
            left: 16,
            right: 16,
            bottom: widget.showDots ? 28 : 16,
            child: IgnorePointer(
              child: Opacity(
                opacity: widget.contentOpacity,
                child: _PromoSlideCaption(
                  slide: slides[_current.clamp(0, slides.length - 1)],
                ),
              ),
            ),
          ),
        if (widget.showDots && slides.length > 1)
          Positioned(
            left: 0,
            right: 0,
            bottom: 10,
            child: Opacity(
              opacity: widget.contentOpacity.clamp(0.0, 1.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(slides.length, (i) {
                  final active = _current == i;
                  return AnimatedContainer(
                    duration: const Duration(milliseconds: 280),
                    margin: const EdgeInsets.symmetric(horizontal: 3),
                    width: active ? 20 : 7,
                    height: 7,
                    decoration: BoxDecoration(
                      color: active
                          ? Colors.white
                          : Colors.white.withValues(alpha: 0.45),
                      borderRadius: BorderRadius.circular(4),
                    ),
                  );
                }),
              ),
            ),
          ),
      ],
    );
  }
}

class _PromoBannerCard extends StatelessWidget {
  final _PromoSlide slide;

  const _PromoBannerCard({required this.slide});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: slide.pageId != null || slide.product != null
          ? () => _openPromoSlide(context, slide)
          : null,
      child: Stack(
        fit: StackFit.expand,
        children: [
          ColoredBox(
            color: AppTheme.primary.withValues(alpha: 0.18),
            child: slide.imageUrl.trim().isEmpty
                ? const SizedBox.expand()
                : AppNetworkImage(
                    slide.imageUrl,
                    fit: BoxFit.cover,
                    width: 720,
                    placeholder: const ColoredBox(color: Color(0xFFD8F2E4)),
                    error: const ColoredBox(color: Color(0xFFD8F2E4)),
                  ),
          ),
          const DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Color(0x5988D498),
                  Color(0x2488D498),
                  Color(0x0088D498),
                ],
                stops: [0.0, 0.16, 0.4],
              ),
            ),
          ),
          const DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.bottomCenter,
                end: Alignment.topCenter,
                colors: [
                  Color(0x661B3A2D),
                  Color(0x29000000),
                  Color(0x00000000),
                ],
                stops: [0.0, 0.3, 0.58],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _PromoSlideCaption extends StatelessWidget {
  final _PromoSlide slide;

  const _PromoSlideCaption({required this.slide});

  @override
  Widget build(BuildContext context) {
    final product = slide.product;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (product?.hasDiscount == true)
          Container(
            margin: const EdgeInsets.only(bottom: 8),
            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.92),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Text(
              'عرض السوبر',
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w800,
                color: Color(0xFFBF360C),
              ),
            ),
          ),
        if (slide.title.isNotEmpty)
          Text(
            slide.title,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w800,
              fontSize: AppScale.of(context).s(16),
              height: 1.25,
              shadows: const [Shadow(color: Color(0x66000000), blurRadius: 8)],
            ),
          ),
        if (product != null) ...[
          const SizedBox(height: 6),
          PriceLine(
            price: product.effectivePrice,
            originalPrice: product.hasDiscount ? product.price : null,
            color: Colors.white,
            priceSize: 15,
            alignment: AlignmentDirectional.centerStart,
          ),
        ],
      ],
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
  final bool fill;

  const _PromoShimmer({this.fill = false});

  @override
  Widget build(BuildContext context) {
    final box = Shimmer.fromColors(
      baseColor: const Color(0xFFDCF5EA),
      highlightColor: const Color(0xFFF0FFF6),
      child: const ColoredBox(color: Colors.white),
    );
    if (fill) return SizedBox.expand(child: box);
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(25),
        child: SizedBox(height: 190, width: double.infinity, child: box),
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
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: 16),
          child: _ShimmerBox(width: 80, height: 16, radius: 8),
        ),
        const SizedBox(height: 14),
        SizedBox(
          height: 96,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12),
            itemCount: 5,
            itemBuilder: (_, _) => Padding(
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
                  const _ShimmerBox(width: 50, height: 10, radius: 4),
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
  Widget build(BuildContext context) => const ProductShimmerSliverGrid();
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
            style: const TextStyle(
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
              side: const BorderSide(color: AppTheme.primaryDark),
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

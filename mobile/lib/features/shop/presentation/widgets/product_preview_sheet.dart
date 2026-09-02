import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:share_plus/share_plus.dart';

import '../../../../core/constants/app_strings.dart';
import 'cart_sheet.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_count_badge.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../../../core/widgets/product_thumbnail.dart';
import '../../data/models/product_model.dart';
import '../../data/services/catalog_api.dart';
import '../manager/cart_cubit.dart';
import '../manager/catalog_cubit.dart';
import '../manager/favorite_cubit.dart';
import 'card_cart_control.dart';
import 'celebrate_anchors.dart';
import 'price_line.dart';
import 'product_fly_overlay.dart';
import 'product_gift_overlay.dart';
import 'quantity_label_chip.dart';

Future<void> showProductPreview(
  BuildContext context,
  ProductModel product, {
  String? heroTag,
}) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    enableDrag: true,
    showDragHandle: false,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    useSafeArea: true,
    builder: (ctx) => ProductPreviewSheet(
      product: product,
      heroTag: heroTag ?? 'preview_${product.id}',
    ),
  );
}

/// يفتح شيت تفاصيل منتج مكدّس فوق الحالي — ينزلق من اليسار.
void pushStackedProductPreview(BuildContext context, ProductModel product) {
  Navigator.of(context, rootNavigator: true).push(
    _StackedPreviewRoute(product: product),
  );
}

class _StackedPreviewRoute extends PageRouteBuilder<void> {
  _StackedPreviewRoute({required ProductModel product})
      : super(
          opaque: false,
          barrierDismissible: false,
          barrierColor: Colors.transparent,
          transitionDuration: const Duration(milliseconds: 400),
          reverseTransitionDuration: const Duration(milliseconds: 360),
          pageBuilder: (context, animation, secondaryAnimation) =>
              Material(
            type: MaterialType.transparency,
            child: ProductPreviewSheet(
              product: product,
              heroTag:
                  'stacked_preview_${product.id}_${DateTime.now().microsecondsSinceEpoch}',
              isStacked: true,
            ),
          ),
          transitionsBuilder:
              (context, animation, secondaryAnimation, child) =>
                  _StackedPreviewTransition(animation: animation, child: child),
        );
}

class _StackedPreviewTransition extends AnimatedWidget {
  final Widget child;

  const _StackedPreviewTransition({
    required Animation<double> animation,
    required this.child,
  }) : super(listenable: animation);

  @override
  Widget build(BuildContext context) {
    final animation = listenable as Animation<double>;
    final t = animation.value;
    final size = MediaQuery.sizeOf(context);

    if (animation.status != AnimationStatus.reverse) {
      final slide = 1.0 - Curves.easeOutCubic.transform(t);
      return Transform.translate(
        offset: Offset(-size.width * slide, 0),
        child: child,
      );
    }

    final foldT = Curves.easeIn.transform(1.0 - t);
    final scale = 1.0 - foldT * 0.82;
    final tx = -size.width * 0.36 * foldT;
    final ty = size.height * 0.36 * foldT;

    return Opacity(
      opacity: (1.0 - foldT * 0.55).clamp(0.0, 1.0),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(26 * foldT),
        child: Transform(
          alignment: Alignment.center,
          transform: Matrix4.identity()
            ..translateByDouble(tx, ty, 0.0, 1.0)
            ..rotateZ(-foldT * 0.10)
            ..scaleByDouble(scale, scale, 1.0, 1.0),
          child: child,
        ),
      ),
    );
  }
}

class ProductPreviewSheet extends StatefulWidget {
  final ProductModel product;
  final String heroTag;
  final bool isStacked;

  const ProductPreviewSheet({
    super.key,
    required this.product,
    required this.heroTag,
    this.isStacked = false,
  });

  @override
  State<ProductPreviewSheet> createState() => _ProductPreviewSheetState();
}

class _ProductPreviewSheetState extends State<ProductPreviewSheet>
    with TickerProviderStateMixin {
  late ProductModel _product;
  late final PageController _pageCtrl;
  late final ScrollController _scrollCtrl;
  late final AnimationController _cartBounceCtrl;
  late final Animation<double> _cartBounceScale;
  late final AnimationController _cartReleaseCtrl;
  late final Animation<double> _cartReleaseScale;
  final GlobalKey _sheetKey = GlobalKey();
  final GlobalKey _topCartKey = GlobalKey();
  final Object _productImageAnchor = Object();
  int _page = 0;
  double _dismissDy = 0;
  List<ProductModel> _boughtTogether = const [];
  List<ProductModel> _similar = const [];
  List<ProductModel> _suggested = const [];
  bool _recsLoaded = false;

  ProductModel get p => _product;

  List<String> get _images {
    final urls = p.displayImages;
    if (urls.isEmpty) return [''];
    return urls;
  }

  @override
  void initState() {
    super.initState();
    _product = widget.product;
    _pageCtrl = PageController();
    _scrollCtrl = ScrollController();
    _cartBounceCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 420),
    );
    _cartBounceScale = Tween<double>(begin: 1, end: 1.26).animate(
      CurvedAnimation(parent: _cartBounceCtrl, curve: Curves.elasticOut),
    );
    _cartReleaseCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 200),
    );
    _cartReleaseScale = TweenSequence<double>([
      TweenSequenceItem(
        tween: Tween<double>(begin: 1, end: 0.92),
        weight: 50,
      ),
      TweenSequenceItem(
        tween: Tween<double>(begin: 0.92, end: 1),
        weight: 50,
      ),
    ]).animate(
      CurvedAnimation(parent: _cartReleaseCtrl, curve: Curves.easeInOut),
    );
    CartNavAnchor.detailsBounce.addListener(_onCartPing);
    CartNavAnchor.detailsRelease.addListener(_onCartRelease);
    CartNavAnchor.detailsBoundsKey = _sheetKey;
    _refreshProduct();
    _loadRecommendations();
    WidgetsBinding.instance.addPostFrameCallback((_) => _bindTopCart());
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (ModalRoute.of(context)?.isCurrent == true) {
      CartNavAnchor.detailsBoundsKey = _sheetKey;
      WidgetsBinding.instance.addPostFrameCallback((_) => _bindTopCart());
    }
  }

  void _onCartPing() {
    if (!mounted) return;
    _cartBounceCtrl.forward(from: 0);
  }

  void _onCartRelease() {
    if (!mounted) return;
    _cartReleaseCtrl.forward(from: 0);
  }

  void _bindTopCart() {
    if (!mounted) return;
    CelebratePositions.bind(
      CartNavAnchor.detailsCartAnchor,
      () => celebrateGlobalCenter(_topCartKey),
    );
  }

  Future<void> _refreshProduct() async {
    try {
      final latest = await CatalogApi.instance.product(p.id);
      if (!mounted || latest == null) return;
      setState(() => _product = latest);
    } catch (_) {}
  }

  @override
  void dispose() {
    CartNavAnchor.detailsBounce.removeListener(_onCartPing);
    CartNavAnchor.detailsRelease.removeListener(_onCartRelease);
    if (CartNavAnchor.detailsBoundsKey == _sheetKey) {
      CartNavAnchor.detailsBoundsKey = null;
    }
    CelebratePositions.unbind(CartNavAnchor.detailsCartAnchor);
    _cartReleaseCtrl.dispose();
    _cartBounceCtrl.dispose();
    _pageCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _showProduct(ProductModel next) {
    if (next.id == p.id) return;
    pushStackedProductPreview(context, next);
  }

  Future<void> _loadRecommendations() async {
    final id = p.id;
    try {
      final recs = await CatalogApi.instance.productRecommendations(id);
      if (!mounted || p.id != id) return;
      setState(() {
        _boughtTogether = recs.boughtTogether;
        _similar = recs.similar;
        _suggested = recs.suggested;
        _recsLoaded = true;
      });
    } catch (_) {
      if (!mounted || p.id != id) return;
      setState(() => _recsLoaded = true);
    }
  }

  Future<void> _addToCart([ProductModel? item]) async {
    final product = item ?? p;
    if (!product.isAvailable) return;
    HapticFeedback.mediumImpact();
    context.read<CartCubit>().addToCart(product);

    if (item != null) return;

    final cartTop = resolveFlyEnd(
      context,
      preferTopCart: true,
      topCartKey: _topCartKey,
      boundsKey: _sheetKey,
    );
    var imageStart = CelebratePositions.read(_productImageAnchor);
    if (imageStart == null ||
        !isPointInsideBounds(imageStart, _sheetKey, margin: 20)) {
      final box = _sheetKey.currentContext?.findRenderObject();
      if (box is RenderBox && box.hasSize) {
        final topLeft = box.localToGlobal(Offset.zero);
        imageStart = Offset(
          topLeft.dx + box.size.width / 2,
          topLeft.dy + box.size.height - 72,
        );
      } else {
        imageStart = Offset(
          MediaQuery.sizeOf(context).width / 2,
          MediaQuery.sizeOf(context).height * 0.72,
        );
      }
    }
    ProductFlyController.play(
      context: context,
      imageUrl: product.displayImage,
      productAnchor: _productImageAnchor,
      fallbackStart: imageStart,
      overrideEnd: cartTop,
      pingDetailsCart: true,
    );
  }

  void _removeFromCart() {
    if (!p.isAvailable) return;
    final cart = context.read<CartCubit>().state;
    final qty = cart.items
        .where((i) => i.product.id == p.id)
        .fold<int>(0, (sum, i) => sum + i.quantity);
    if (qty <= 0) return;

    context.read<CartCubit>().updateQuantity(p.id, qty - 1);

    final fallbackEnd = fallbackProductFlyEnd(context, boundsKey: _sheetKey);
    ProductFlyController.playReverse(
      context: context,
      imageUrl: p.displayImage,
      productAnchor: _productImageAnchor,
      fallbackEnd: fallbackEnd,
      flyFromTopCart: true,
      topCartKey: _topCartKey,
      boundsKey: _sheetKey,
      releaseDetailsCart: true,
    );
  }

  Future<void> _share() async {
    await Share.share('${p.name}\n${AppStrings.appName}', subject: p.name);
  }

  bool _tracksDismiss(Offset delta) {
    if (delta.dx.abs() > delta.dy.abs() && _dismissDy == 0) return false;
    final atTop = !_scrollCtrl.hasClients || _scrollCtrl.offset <= 0.5;
    return atTop || _dismissDy > 0;
  }

  void _onDismissPointerMove(PointerMoveEvent event) {
    if (!_tracksDismiss(event.delta)) return;
    final next = (_dismissDy + event.delta.dy).clamp(0.0, 640.0);
    if (next == _dismissDy) return;
    setState(() => _dismissDy = next);
  }

  void _onDismissPointerEnd(PointerEvent event) {
    if (_dismissDy >= 90) {
      Navigator.of(context).pop();
      return;
    }
    if (_dismissDy != 0) {
      setState(() => _dismissDy = 0);
    }
  }

  void _openCart() {
    final rootNav = Navigator.of(context, rootNavigator: true);
    rootNav.pop();
    showCartSheet(rootNav.context);
  }

  @override
  Widget build(BuildContext context) {
    final height = MediaQuery.sizeOf(context).height;
    final catalog = context.watch<CatalogCubit>().state;
    final boughtTogether = _boughtTogether;
    final similar = _similar.isNotEmpty
        ? _similar
        : (!_recsLoaded
              ? catalog.products
                    .where(
                      (item) =>
                          item.id != p.id && item.categoryId == p.categoryId,
                    )
                    .take(8)
                    .toList()
              : const <ProductModel>[]);
    final suggested = _suggested.isNotEmpty
        ? _suggested
        : (!_recsLoaded
              ? catalog.suggestions(excludeIds: {p.id}).take(8).toList()
              : const <ProductModel>[]);
    final images = _images;

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Align(
        alignment: Alignment.bottomCenter,
        child: Listener(
          onPointerMove: _onDismissPointerMove,
          onPointerUp: _onDismissPointerEnd,
          onPointerCancel: _onDismissPointerEnd,
          child: AnimatedSlide(
            offset: Offset(0, _dismissDy / height),
            duration: _dismissDy == 0
                ? const Duration(milliseconds: 180)
                : Duration.zero,
            curve: Curves.easeOutCubic,
            child: Container(
              key: _sheetKey,
              height: height * 0.86,
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              clipBehavior: Clip.hardEdge,
              child: Stack(
                clipBehavior: Clip.hardEdge,
                children: [
                  Column(
                    children: [
                      Expanded(
                        child: CardStepperScrollScope(
                          child: ListView(
                          controller: _scrollCtrl,
                          physics: _dismissDy > 8
                              ? const NeverScrollableScrollPhysics()
                              : const BouncingScrollPhysics(),
                          padding: const EdgeInsets.only(bottom: 24),
                          children: [
                            Padding(
                              padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                        AspectRatio(
                          aspectRatio: 1.22,
                          child: Stack(
                            fit: StackFit.expand,
                            children: [
                              PageView.builder(
                                controller: _pageCtrl,
                                itemCount: images.length,
                                onPageChanged: (i) =>
                                    setState(() => _page = i),
                                itemBuilder: (context, i) {
                                  final image = AppNetworkImage(
                                    images[i],
                                    fit: BoxFit.cover,
                                    width: 900,
                                    error: const ColoredBox(
                                      color: AppTheme.primarySurface,
                                      child: Icon(
                                        Icons.image_not_supported_outlined,
                                        size: 48,
                                      ),
                                    ),
                                  );
                                  if (i == 0) {
                                    return CelebrateAnchor(
                                      anchor: _productImageAnchor,
                                      child: Hero(
                                        tag: widget.heroTag,
                                        child: image,
                                      ),
                                    );
                                  }
                                  return image;
                                },
                              ),
                              if (images.length > 1)
                                Positioned(
                                  left: 0,
                                  right: 0,
                                  bottom: 12,
                                  child: _PageDots(
                                    count: images.length,
                                    index: _page,
                                  ),
                                ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 18),
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            Expanded(
                              child: FittedBox(
                                fit: BoxFit.scaleDown,
                                alignment: AlignmentDirectional.centerStart,
                                child: Text(
                                  p.name,
                                  maxLines: 1,
                                  softWrap: false,
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w500,
                                    color: AppTheme.darkText,
                                    height: 1.2,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            BlocBuilder<FavoriteCubit, FavoriteState>(
                              buildWhen: (prev, curr) =>
                                  prev.contains(p.id) != curr.contains(p.id),
                              builder: (context, fav) {
                                final on = fav.contains(p.id);
                                return Material(
                                  color: Colors.transparent,
                                  child: InkWell(
                                    onTap: () {
                                      HapticFeedback.lightImpact();
                                      context
                                          .read<FavoriteCubit>()
                                          .toggle(p.id);
                                    },
                                    borderRadius: BorderRadius.circular(20),
                                    child: Padding(
                                      padding: const EdgeInsets.all(4),
                                      child: Icon(
                                        on
                                            ? Icons.favorite_rounded
                                            : Icons.favorite_border_rounded,
                                        size: 24,
                                        color: on
                                            ? Colors.redAccent
                                            : AppTheme.mutedText,
                                      ),
                                    ),
                                  ),
                                );
                              },
                            ),
                          ],
                        ),
                        if (p.quantityLabel.trim().isNotEmpty) ...[
                          const SizedBox(height: 8),
                          QuantityLabelChip(product: p),
                        ],
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
                        if (p.hasGiftProduct) ...[
                          const SizedBox(height: 12),
                          ProductGiftDetailCard(gift: p.giftProduct!),
                        ],
                            ],
                          ),
                        ),
                        if (boughtTogether.isNotEmpty)
                          _RecommendRow(
                            title: 'يُشترى معه',
                            products: boughtTogether,
                            onOpen: _showProduct,
                            highlighted: true,
                          ),
                        if (similar.isNotEmpty)
                          _RecommendRow(
                            title: 'منتجات مشابهة',
                            products: similar,
                            onOpen: _showProduct,
                          ),
                        if (suggested.isNotEmpty)
                          _RecommendRow(
                            title: 'منتجات مقترحة',
                            products: suggested,
                            onOpen: _showProduct,
                          ),
                      ],
                    ),
                    ),
                  ),
                  const _StickyCartBar(),
                    ],
                  ),
                  Positioned(
                    top: 12,
                    left: 12,
                    child: Row(
                      children: [
                        ScaleTransition(
                          scale: _cartReleaseScale,
                          child: ScaleTransition(
                            scale: _cartBounceScale,
                            child: BlocBuilder<CartCubit, CartState>(
                              buildWhen: (a, b) => a.count != b.count,
                              builder: (context, cart) => _RoundIconButton(
                                measureKey: _topCartKey,
                                icon: Icons.shopping_bag_outlined,
                                tooltip: AppStrings.cartTitle,
                                badge: cart.count,
                                onTap: _openCart,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        _RoundIconButton(
                          icon: Icons.ios_share_rounded,
                          tooltip: 'مشاركة',
                          onTap: _share,
                        ),
                      ],
                    ),
                  ),
                  Positioned(
                    top: 12,
                    right: 12,
                    child: _RoundIconButton(
                      icon: widget.isStacked
                          ? Icons.arrow_back_ios_new_rounded
                          : Icons.close_rounded,
                      tooltip: widget.isStacked ? 'رجوع' : AppStrings.close,
                      onTap: () => Navigator.pop(context),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _StickyCartBar extends StatelessWidget {
  const _StickyCartBar();

  @override
  Widget build(BuildContext context) {
    final state = context.findAncestorStateOfType<_ProductPreviewSheetState>()!;
    final p = state.p;

    return BlocBuilder<CartCubit, CartState>(
      builder: (context, cart) {
        final qty = cart.items
            .where((i) => i.product.id == p.id)
            .fold<int>(0, (sum, i) => sum + i.quantity);
        final shownQty = qty > 0 ? qty : 1;
        final total = p.effectivePrice * shownQty;

        return Container(
          padding: EdgeInsets.fromLTRB(
            16,
            12,
            16,
            12 + MediaQuery.paddingOf(context).bottom,
          ),
          decoration: const BoxDecoration(
            color: Colors.white,
            boxShadow: [
              BoxShadow(
                color: Color(0x14000000),
                blurRadius: 16,
                offset: Offset(0, -4),
              ),
            ],
          ),
          child: Row(
            children: [
              _CartActionControl(
                product: p,
                quantity: qty,
                onAdd: () => state._addToCart(),
                onRemove: state._removeFromCart,
              ),
              const Spacer(),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (p.hasPackPieces)
                    Container(
                      margin: const EdgeInsets.only(bottom: 4),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFF8A3D),
                        borderRadius: BorderRadius.circular(99),
                      ),
                      child: Text(
                        p.packDisplayLabel,
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  PriceLine(
                    price: total,
                    originalPrice: p.hasDiscount ? p.price * shownQty : null,
                    priceSize: 20,
                    currencySize: 22,
                    alignment: AlignmentDirectional.centerEnd,
                    swapPrices: true,
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}

class _CartActionControl extends StatelessWidget {
  final ProductModel product;
  final int quantity;
  final VoidCallback onAdd;
  final VoidCallback onRemove;

  const _CartActionControl({
    required this.product,
    required this.quantity,
    required this.onAdd,
    required this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    if (!product.isAvailable) {
      return const SizedBox(
        width: 132,
        height: 42,
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: Color(0xFFC5D4CB),
            borderRadius: BorderRadius.all(Radius.circular(14)),
          ),
          child: Center(
            child: Text(
              AppStrings.productOutOfStock,
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w800,
                fontSize: 12,
              ),
            ),
          ),
        ),
      );
    }

    if (quantity <= 0) {
      return SizedBox(
        height: 42,
        child: FilledButton.icon(
          onPressed: onAdd,
          iconAlignment: IconAlignment.end,
          icon: const Icon(Icons.add_rounded, size: 18),
          label: const Text(
            'إضافة للسلة',
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
          ),
          style: FilledButton.styleFrom(
            backgroundColor: AppTheme.primaryDark,
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 14),
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
            visualDensity: VisualDensity.compact,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
        ),
      );
    }

    return SizedBox(
      width: 128,
      height: 42,
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: AppTheme.primaryDark,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          children: [
            _StepperIcon(
              icon: Icons.add_rounded,
              onTap: quantity < product.stock ? onAdd : null,
            ),
            Expanded(
              child: Text(
                '$quantity',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
            _StepperIcon(
              icon: quantity == 1
                  ? Icons.delete_outline_rounded
                  : Icons.remove_rounded,
              onTap: onRemove,
            ),
          ],
        ),
      ),
    );
  }
}

class _StepperIcon extends StatelessWidget {
  final IconData icon;
  final VoidCallback? onTap;

  const _StepperIcon({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: SizedBox(
        width: 40,
        height: 42,
        child: Icon(
          icon,
          color: onTap == null
              ? Colors.white.withValues(alpha: 0.35)
              : Colors.white,
          size: 18,
        ),
      ),
    );
  }
}

class _RecommendRow extends StatelessWidget {
  final String title;
  final List<ProductModel> products;
  final ValueChanged<ProductModel> onOpen;
  final bool highlighted;

  const _RecommendRow({
    required this.title,
    required this.products,
    required this.onOpen,
    this.highlighted = false,
  });

  @override
  Widget build(BuildContext context) {
    final content = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: AppTheme.darkText,
            height: 1.2,
          ),
        ),
        const SizedBox(height: 10),
        SizedBox(
          height: 188,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            cacheExtent: 200,
            itemCount: products.length,
            separatorBuilder: (_, _) => const SizedBox(width: 10),
            itemBuilder: (context, index) {
              final product = products[index];
              return _RelatedTile(
                product: product,
                onOpen: () => onOpen(product),
              );
            },
          ),
        ),
      ],
    );

    if (!highlighted) {
      return Padding(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 4),
        child: content,
      );
    }

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(top: 18, bottom: 10),
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
      decoration: const BoxDecoration(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            Color(0xFFD8F2E4),
            Color(0xFFE8F8EC),
            Color(0x00F0FAF3),
          ],
          stops: [0, 0.45, 1],
        ),
      ),
      child: content,
    );
  }
}

class _RelatedTile extends StatefulWidget {
  final ProductModel product;
  final VoidCallback onOpen;

  const _RelatedTile({required this.product, required this.onOpen});

  @override
  State<_RelatedTile> createState() => _RelatedTileState();
}

class _RelatedTileState extends State<_RelatedTile> {
  final Object _productImageAnchor = Object();

  @override
  Widget build(BuildContext context) {
    final product = widget.product;
    return SizedBox(
      width: 118,
      child: Material(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(16),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: widget.onOpen,
          child: Padding(
            padding: const EdgeInsets.all(8),
            child: Column(
              children: [
                Expanded(
                  child: Stack(
                    children: [
                      Positioned.fill(
                        child: CelebrateAnchor(
                          anchor: _productImageAnchor,
                          child: ProductThumbnail(
                            imageUrl: product.displayImage,
                            backgroundColor: AppTheme.productImageWell,
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                      ),
                      PositionedDirectional(
                        start: 0,
                        bottom: 0,
                        child: CardCartControl(
                          product: product,
                          productImageAnchor: _productImageAnchor,
                          flyToTopCart: true,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  product.name,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w500,
                    height: 1.25,
                  ),
                ),
                const SizedBox(height: 4),
                PriceLine(
                  price: product.effectivePrice,
                  originalPrice: product.hasDiscount ? product.price : null,
                  priceSize: 13,
                  currencySize: 15,
                  alignment: Alignment.center,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _PageDots extends StatelessWidget {
  final int count;
  final int index;

  const _PageDots({required this.count, required this.index});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(count, (i) {
        final active = i == index;
        return AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          margin: const EdgeInsets.symmetric(horizontal: 3),
          width: active ? 16 : 7,
          height: 7,
          decoration: BoxDecoration(
            color: active ? AppTheme.primaryDark : const Color(0xFFD9E6DC),
            borderRadius: BorderRadius.circular(99),
          ),
        );
      }),
    );
  }
}

class _RoundIconButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;
  final String? tooltip;
  final int badge;
  final GlobalKey? measureKey;

  const _RoundIconButton({
    required this.icon,
    required this.onTap,
    this.tooltip,
    this.badge = 0,
    this.measureKey,
  });

  @override
  Widget build(BuildContext context) {
    final button = DecoratedBox(
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.10),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 2,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Material(
        key: measureKey,
        color: Colors.white.withValues(alpha: 0.96),
        shape: const CircleBorder(),
        child: InkWell(
          customBorder: const CircleBorder(),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(6),
            child: Icon(icon, size: 16, color: AppTheme.darkText),
          ),
        ),
      ),
    );

    return Tooltip(
      message: tooltip ?? '',
      child: AppCountBadge.wrap(
        count: badge,
        child: button,
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:share_plus/share_plus.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../data/models/product_model.dart';
import '../../data/services/catalog_api.dart';
import '../manager/cart_cubit.dart';
import '../manager/catalog_cubit.dart';
import '../manager/favorite_cubit.dart';
import 'card_cart_control.dart';
import 'price_line.dart';
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
  late ProductModel _product;
  late final PageController _pageCtrl;
  late final ScrollController _scrollCtrl;
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
    _refreshProduct();
    _loadRecommendations();
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
    _pageCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _showProduct(ProductModel next) {
    if (next.id == p.id) return;
    setState(() {
      _product = next;
      _page = 0;
      _boughtTogether = const [];
      _similar = const [];
      _suggested = const [];
      _recsLoaded = false;
    });
    if (_pageCtrl.hasClients) {
      _pageCtrl.jumpToPage(0);
    }
    _loadRecommendations();
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
    } catch (_) {}
  }

  Future<void> _addToCart([ProductModel? item]) async {
    final product = item ?? p;
    if (!product.isAvailable) return;
    HapticFeedback.mediumImpact();
    context.read<CartCubit>().addToCart(product);
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
    final nav = Navigator.of(context);
    nav.pop();
    nav.pushNamed(AppRouter.checkout);
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
              height: height * 0.94,
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
              ),
              clipBehavior: Clip.antiAlias,
              child: Column(
                children: [
                  Expanded(
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
                        DecoratedBox(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(22),
                            border: Border.all(
                              color: const Color(0x3388D498),
                              width: 1,
                            ),
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(21),
                            child: AspectRatio(
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
                                        return Hero(
                                          tag: widget.heroTag,
                                          child: image,
                                        );
                                      }
                                      return image;
                                    },
                                  ),
                                  Positioned(
                                    top: 12,
                                    right: 12,
                                    child: _RoundIconButton(
                                      icon: Icons.close_rounded,
                                      tooltip: AppStrings.close,
                                      onTap: () => Navigator.pop(context),
                                    ),
                                  ),
                                  Positioned(
                                    top: 12,
                                    left: 12,
                                    child: Row(
                                      children: [
                                        BlocBuilder<CartCubit, CartState>(
                                          buildWhen: (a, b) =>
                                              a.count != b.count,
                                          builder: (context, cart) {
                                            return _RoundIconButton(
                                              icon: Icons.shopping_bag_outlined,
                                              tooltip: AppStrings.cartTitle,
                                              badge: cart.count,
                                              onTap: _openCart,
                                            );
                                          },
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
                                    fontWeight: FontWeight.w900,
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
                  const _StickyCartBar(),
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
                onRemove: () {
                  HapticFeedback.selectionClick();
                  context.read<CartCubit>().updateQuantity(p.id, qty - 1);
                },
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

class _RelatedTile extends StatelessWidget {
  final ProductModel product;
  final VoidCallback onOpen;

  const _RelatedTile({required this.product, required this.onOpen});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 118,
      child: Material(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(16),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onOpen,
          child: Padding(
            padding: const EdgeInsets.all(8),
            child: Column(
              children: [
                Expanded(
                  child: Stack(
                    children: [
                      Positioned.fill(
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: AppNetworkImage(
                            product.displayImage,
                            fit: BoxFit.cover,
                            error: const ColoredBox(
                              color: AppTheme.primarySurface,
                            ),
                          ),
                        ),
                      ),
                      PositionedDirectional(
                        start: 0,
                        bottom: 0,
                        child: CardCartControl(product: product),
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
                    fontWeight: FontWeight.w700,
                    height: 1.25,
                  ),
                ),
                const SizedBox(height: 4),
                PriceLine(
                  price: product.effectivePrice,
                  originalPrice: product.hasDiscount ? product.price : null,
                  priceSize: 12,
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

  const _RoundIconButton({
    required this.icon,
    required this.onTap,
    this.tooltip,
    this.badge = 0,
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
      child: badge > 0
          ? Badge(
              label: Text(
                '$badge',
                style: const TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                ),
              ),
              backgroundColor: AppTheme.primaryDark,
              child: button,
            )
          : button,
    );
  }
}

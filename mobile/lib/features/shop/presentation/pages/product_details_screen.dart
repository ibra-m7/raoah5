import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shimmer/shimmer.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../data/models/product_model.dart';
import '../manager/cart_cubit.dart';
import '../manager/favorite_cubit.dart';

// ── ثوابت ─────────────────────────────────────────────────────────────────────
const _kGreen      = Color(0xFF2E7D32);
const _kGreenLight = Color(0xFF4CAF50);
const _kGreenBg    = Color(0xFFF1F8F1);
const _kText       = Color(0xFF1A2E1A);
const _kSubtext    = Color(0xFF6B7B6B);
const _kSurface    = Color(0xFFFFFFFF);

/// وسيط فتح تفاصيل المنتج مع وسم Hero فريد حتى لا يتكرر نفس المنتج في الرئيسية.
class ProductDetailsArgs {
  final ProductModel product;
  final String? heroTag;

  const ProductDetailsArgs({required this.product, this.heroTag});
}

class ProductDetailsScreen extends StatefulWidget {
  static const routeName = '/product-details';

  final ProductModel product;
  final String heroTag;

  ProductDetailsScreen({
    super.key,
    required this.product,
    String? heroTag,
  }) : heroTag = heroTag ?? 'product_details_${product.id}';

  @override
  State<ProductDetailsScreen> createState() => _ProductDetailsScreenState();
}

class _ProductDetailsScreenState extends State<ProductDetailsScreen>
    with SingleTickerProviderStateMixin {
  int _quantity = 1;
  late final AnimationController _cartCtrl;
  late final Animation<double> _cartScale;
  bool _added = false;

  ProductModel get p => widget.product;

  @override
  void initState() {
    super.initState();
    _cartCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 180),
      lowerBound: 0.92,
      upperBound: 1.0,
      value: 1.0,
    );
    _cartScale = _cartCtrl;
  }

  @override
  void dispose() {
    _cartCtrl.dispose();
    super.dispose();
  }

  Future<void> _addToCart() async {
    HapticFeedback.mediumImpact();
    await _cartCtrl.reverse();
    await _cartCtrl.forward();

    if (!mounted) return;
    for (int i = 0; i < _quantity; i++) {
      context.read<CartCubit>().addToCart(p);
    }

    setState(() => _added = true);
    Future.delayed(const Duration(seconds: 2), () {
      if (mounted) setState(() => _added = false);
    });

    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(
            AppStrings.productAddedToCart(p.name),
            textAlign: TextAlign.center,
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          backgroundColor: _kGreen,
          behavior: SnackBarBehavior.floating,
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          margin: const EdgeInsets.fromLTRB(16, 0, 16, 20),
          duration: const Duration(seconds: 2),
        ),
      );
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.light,
        child: Scaffold(
          backgroundColor: _kGreenBg,
          body: Stack(
            children: [
              // ── المحتوى القابل للتمرير ───────────────────────────────────
              CustomScrollView(
                slivers: [
                  _ProductHeroHeader(product: p, heroTag: widget.heroTag),
                  SliverToBoxAdapter(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // ── بيانات المنتج الأساسية ─────────────────────────
                        _ProductInfoSection(product: p),

                        const SizedBox(height: 12),

                        // ── نصيحة المساعد الذكي ────────────────────────────
                        if (p.benefits.isNotEmpty)
                          _AiTipsSection(benefits: p.benefits),

                        const SizedBox(height: 12),

                        // ── الوصف الكامل ───────────────────────────────────
                        _DescriptionSection(description: p.description),

                        const SizedBox(height: 12),

                        // ── طريقة الاستخدام ────────────────────────────────
                        if (p.usageInstructions.isNotEmpty)
                          _UsageSection(instructions: p.usageInstructions),

                        // ── مساحة للزر الثابت في الأسفل ───────────────────
                        const SizedBox(height: 110),
                      ],
                    ),
                  ),
                ],
              ),

              // ── زر "أضف للسلة" الثابت ───────────────────────────────────
              Positioned(
                bottom: 0,
                left: 0,
                right: 0,
                child: _AddToCartBar(
                  product: p,
                  quantity: _quantity,
                  added: _added,
                  scaleAnim: _cartScale,
                  onQuantityChanged: (q) => setState(() => _quantity = q),
                  onAddToCart: _addToCart,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// Hero Image Header
// ══════════════════════════════════════════════════════════════════════════════
class _ProductHeroHeader extends StatelessWidget {
  final ProductModel product;
  final String heroTag;
  const _ProductHeroHeader({required this.product, required this.heroTag});

  @override
  Widget build(BuildContext context) {
    return SliverAppBar(
      expandedHeight: 320,
      pinned: true,
      elevation: 0,
      backgroundColor: _kGreen,
      // زر الرجوع بـ Glassmorphism
      leading: Padding(
        padding: const EdgeInsets.all(8),
        child: _GlassButton(
          icon: Icons.arrow_forward_ios_rounded,
          onTap: () => Navigator.of(context).pop(),
        ),
      ),
      actions: [
        Padding(
          padding: const EdgeInsets.all(8),
          child: _GlassButton(
            icon: Icons.share_outlined,
            onTap: () {},
          ),
        ),
        Padding(
          padding: const EdgeInsetsDirectional.only(end: 8),
          child: BlocBuilder<FavoriteCubit, FavoriteState>(
            buildWhen: (prev, curr) =>
                prev.contains(product.id) != curr.contains(product.id),
            builder: (context, fav) {
              final on = fav.contains(product.id);
              return _GlassButton(
                icon: on
                    ? Icons.favorite_rounded
                    : Icons.favorite_border_rounded,
                onTap: () {
                  HapticFeedback.lightImpact();
                  context.read<FavoriteCubit>().toggle(product.id);
                },
              );
            },
          ),
        ),
      ],
      flexibleSpace: FlexibleSpaceBar(
        collapseMode: CollapseMode.parallax,
        background: Stack(
          fit: StackFit.expand,
          children: [
            // الصورة بـ Hero animation
            Hero(
              tag: heroTag,
              child: AppNetworkImage(
                product.imageUrl,
                fit: BoxFit.cover,
                placeholder: Shimmer.fromColors(
                  baseColor: const Color(0xFFE0E0E0),
                  highlightColor: const Color(0xFFF5F5F5),
                  child: Container(color: Colors.white),
                ),
                error: Container(
                  color: _kGreenBg,
                  alignment: Alignment.center,
                  child: const Icon(
                    Icons.image_not_supported_outlined,
                    size: 64,
                    color: _kSubtext,
                  ),
                ),
              ),
            ),
            // تدرج في الأسفل لتمرير النص فوقه
            Positioned(
              bottom: 0,
              left: 0,
              right: 0,
              child: Container(
                height: 100,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.bottomCenter,
                    end: Alignment.topCenter,
                    colors: [
                      _kGreenBg,
                      _kGreenBg.withValues(alpha: 0),
                    ],
                  ),
                ),
              ),
            ),
            // شارة الخصم
            if (product.hasDiscount)
              Positioned(
                top: 80,
                left: 16,
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 6,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.redAccent,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.redAccent.withValues(alpha: 0.4),
                        blurRadius: 8,
                        offset: const Offset(0, 3),
                      ),
                    ],
                  ),
                  child: Text(
                    'خصم ${product.discountPercentage.toInt()}٪',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
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
// قسم المعلومات الأساسية
// ══════════════════════════════════════════════════════════════════════════════
class _ProductInfoSection extends StatelessWidget {
  final ProductModel product;
  const _ProductInfoSection({required this.product});

  @override
  Widget build(BuildContext context) {
    final p = product;
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: _kSurface,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // اسم المنتج
          Text(
            p.name,
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w900,
              color: _kText,
              height: 1.35,
            ),
          ),

          const SizedBox(height: 12),

          // التقييم والمراجعات والمخزون
          Row(
            children: [
              _StarRating(rating: p.rating),
              const SizedBox(width: 6),
              Flexible(
                child: Text(
                  '${p.rating}  (${p.reviewCount} تقييم)',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                    color: _kText,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Flexible(
                child: Align(
                  alignment: AlignmentDirectional.centerEnd,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: p.isAvailable
                          ? _kGreen.withValues(alpha: 0.1)
                          : Colors.red.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      p.isAvailable
                          ? 'متوفر · ${p.stock} قطعة'
                          : AppStrings.productOutOfStock,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: p.isAvailable ? _kGreen : Colors.redAccent,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 16),
          const Divider(height: 1),
          const SizedBox(height: 16),

          // السعر
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Expanded(
                child: FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: AlignmentDirectional.centerStart,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (p.hasDiscount) ...[
                        Text(
                          '${p.price.toStringAsFixed(2)} \u{20C1}',
                          style: TextStyle(
                            fontSize: 14,
                            color: _kSubtext.withValues(alpha: 0.7),
                            decoration: TextDecoration.lineThrough,
                            decorationColor: _kSubtext,
                          ),
                        ),
                        const SizedBox(height: 2),
                      ],
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            p.effectivePrice.toStringAsFixed(2),
                            style: const TextStyle(
                              fontSize: 32,
                              fontWeight: FontWeight.w900,
                              color: _kGreen,
                              height: 1.0,
                            ),
                          ),
                          const Padding(
                            padding: EdgeInsetsDirectional.only(bottom: 2, start: 6),
                            child: Text(
                              '\u{20C1}',
                              style: TextStyle(
                                fontFamily: 'SaudiRiyal',
                                fontSize: 26,
                                fontWeight: FontWeight.w400,
                                color: _kGreen,
                                height: 1,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              // التوفير في الخصم
              if (p.hasDiscount)
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.green.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: _kGreen.withValues(alpha: 0.2),
                    ),
                  ),
                  child: Column(
                    children: [
                      const Text(
                        'توفيرك',
                        style: TextStyle(
                          fontSize: 11,
                          color: _kGreen,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      Text(
                        '${(p.price - p.effectivePrice).toStringAsFixed(2)} \u{20C1}',
                        style: const TextStyle(
                          fontSize: 14,
                          color: _kGreen,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ],
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// نصيحة المساعد الذكي — القسم المميز
// ══════════════════════════════════════════════════════════════════════════════
class _AiTipsSection extends StatefulWidget {
  final List<String> benefits;
  const _AiTipsSection({required this.benefits});

  @override
  State<_AiTipsSection> createState() => _AiTipsSectionState();
}

class _AiTipsSectionState extends State<_AiTipsSection>
    with SingleTickerProviderStateMixin {
  late final AnimationController _shimmerCtrl;
  bool _expanded = true;

  @override
  void initState() {
    super.initState();
    _shimmerCtrl = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat();
  }

  @override
  void dispose() {
    _shimmerCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: const LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [
            Color(0xFF0D47A1),
            Color(0xFF1565C0),
            Color(0xFF1976D2),
          ],
        ),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1565C0).withValues(alpha: 0.35),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 0, sigmaY: 0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // ── الهيدر ───────────────────────────────────────────────────
              GestureDetector(
                onTap: () => setState(() => _expanded = !_expanded),
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
                  child: Row(
                    children: [
                      // أيقونة المساعد المتحركة
                      AnimatedBuilder(
                        animation: _shimmerCtrl,
                        builder: (_, __) {
                          final glow = (0.5 + 0.5 * _shimmerCtrl.value);
                          return Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color:
                                  Colors.white.withValues(alpha: 0.15 * glow),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.white
                                      .withValues(alpha: 0.2 * glow),
                                  blurRadius: 12,
                                  spreadRadius: 2,
                                ),
                              ],
                            ),
                            child: const Icon(
                              Icons.auto_awesome_rounded,
                              color: Colors.white,
                              size: 22,
                            ),
                          );
                        },
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              AppStrings.productAiTips,
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 15,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            Text(
                              'روعة توصي بهذا المنتج لهذه الأسباب',
                              style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.7),
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Icon(
                        _expanded
                            ? Icons.keyboard_arrow_up_rounded
                            : Icons.keyboard_arrow_down_rounded,
                        color: Colors.white.withValues(alpha: 0.8),
                        size: 22,
                      ),
                    ],
                  ),
                ),
              ),

              // ── قائمة الفوائد ─────────────────────────────────────────────
              AnimatedCrossFade(
                firstChild: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                  child: Column(
                    children: [
                      Divider(
                        color: Colors.white.withValues(alpha: 0.15),
                        height: 1,
                      ),
                      const SizedBox(height: 12),
                      ...widget.benefits.asMap().entries.map(
                            (e) => _BenefitRow(
                              index: e.key + 1,
                              text: e.value,
                            ),
                          ),
                    ],
                  ),
                ),
                secondChild: const SizedBox.shrink(),
                crossFadeState: _expanded
                    ? CrossFadeState.showFirst
                    : CrossFadeState.showSecond,
                duration: const Duration(milliseconds: 300),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _BenefitRow extends StatelessWidget {
  final int index;
  final String text;

  const _BenefitRow({required this.index, required this.text});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 24,
            height: 24,
            margin: const EdgeInsetsDirectional.only(end: 10),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              shape: BoxShape.circle,
            ),
            alignment: Alignment.center,
            child: Text(
              '$index',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 11,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.92),
                fontSize: 13.5,
                height: 1.5,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// قسم الوصف الكامل
// ══════════════════════════════════════════════════════════════════════════════
class _DescriptionSection extends StatefulWidget {
  final String description;
  const _DescriptionSection({required this.description});

  @override
  State<_DescriptionSection> createState() => _DescriptionSectionState();
}

class _DescriptionSectionState extends State<_DescriptionSection> {
  bool _expanded = false;
  static const _maxLines = 3;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: _kSurface,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _SectionTitle(
            icon: Icons.description_outlined,
            title: AppStrings.productDescription,
            color: _kGreen,
          ),
          const SizedBox(height: 12),
          AnimatedCrossFade(
            firstChild: Text(
              widget.description,
              style: const TextStyle(
                fontSize: 14,
                color: _kSubtext,
                height: 1.7,
              ),
            ),
            secondChild: Text(
              widget.description,
              maxLines: _maxLines,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 14,
                color: _kSubtext,
                height: 1.7,
              ),
            ),
            crossFadeState: _expanded
                ? CrossFadeState.showFirst
                : CrossFadeState.showSecond,
            duration: const Duration(milliseconds: 300),
          ),
          const SizedBox(height: 8),
          GestureDetector(
            onTap: () => setState(() => _expanded = !_expanded),
            child: Text(
              _expanded ? '${AppStrings.productReadLess} ▲' : '${AppStrings.productReadMore} ▼',
              style: const TextStyle(
                color: _kGreen,
                fontSize: 13,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// قسم طريقة الاستخدام
// ══════════════════════════════════════════════════════════════════════════════
class _UsageSection extends StatelessWidget {
  final String instructions;
  const _UsageSection({required this.instructions});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: _kSurface,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _SectionTitle(
            icon: Icons.info_outline_rounded,
            title: AppStrings.productUsage,
            color: const Color(0xFFE64A19),
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFE64A19).withValues(alpha: 0.06),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: const Color(0xFFE64A19).withValues(alpha: 0.15),
              ),
            ),
            child: Text(
              instructions,
              style: const TextStyle(
                fontSize: 13.5,
                color: _kText,
                height: 1.7,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// شريط "أضف للسلة" الثابت في الأسفل
// ══════════════════════════════════════════════════════════════════════════════
class _AddToCartBar extends StatelessWidget {
  final ProductModel product;
  final int quantity;
  final bool added;
  final Animation<double> scaleAnim;
  final ValueChanged<int> onQuantityChanged;
  final VoidCallback onAddToCart;

  const _AddToCartBar({
    required this.product,
    required this.quantity,
    required this.added,
    required this.scaleAnim,
    required this.onQuantityChanged,
    required this.onAddToCart,
  });

  @override
  Widget build(BuildContext context) {
    final total = (product.effectivePrice * quantity).toStringAsFixed(2);

    return ClipRect(
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
        child: Container(
          padding: EdgeInsets.fromLTRB(
            16,
            12,
            16,
            12 + MediaQuery.of(context).padding.bottom,
          ),
          decoration: BoxDecoration(
            color: _kSurface.withValues(alpha: 0.95),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: 20,
                offset: const Offset(0, -6),
              ),
            ],
          ),
          child: Row(
            children: [
              // ── منتقي الكمية ──────────────────────────────────────────────
              Container(
                decoration: BoxDecoration(
                  color: _kGreenBg,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: _kGreen.withValues(alpha: 0.25),
                  ),
                ),
                child: Row(
                  children: [
                    _QtyButton(
                      icon: Icons.remove_rounded,
                      onTap: quantity > 1
                          ? () => onQuantityChanged(quantity - 1)
                          : null,
                    ),
                    SizedBox(
                      width: 36,
                      child: Text(
                        '$quantity',
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: _kText,
                        ),
                      ),
                    ),
                    _QtyButton(
                      icon: Icons.add_rounded,
                      onTap: quantity < product.stock
                          ? () => onQuantityChanged(quantity + 1)
                          : null,
                    ),
                  ],
                ),
              ),

              const SizedBox(width: 12),

              // ── زر الإضافة ────────────────────────────────────────────────
              Expanded(
                child: ScaleTransition(
                  scale: scaleAnim,
                  child: GestureDetector(
                    onTap: product.isAvailable ? onAddToCart : null,
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 300),
                      height: 54,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: added
                              ? [
                                  const Color(0xFF2E7D32),
                                  const Color(0xFF388E3C),
                                ]
                              : product.isAvailable
                                  ? [
                                      const Color(0xFF1B5E20),
                                      _kGreenLight,
                                    ]
                                  : [
                                      _kSubtext.withValues(alpha: 0.4),
                                      _kSubtext.withValues(alpha: 0.3),
                                    ],
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: product.isAvailable
                            ? [
                                BoxShadow(
                                  color: _kGreen.withValues(alpha: 0.4),
                                  blurRadius: 12,
                                  offset: const Offset(0, 4),
                                )
                              ]
                            : null,
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          AnimatedSwitcher(
                            duration: const Duration(milliseconds: 300),
                            child: Icon(
                              added
                                  ? Icons.check_circle_rounded
                                  : Icons.shopping_cart_rounded,
                              key: ValueKey(added),
                              color: Colors.white,
                              size: 22,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Flexible(
                            child: AnimatedSwitcher(
                              duration: const Duration(milliseconds: 300),
                              child: Text(
                                added
                                    ? 'أُضيف للسلة ✓'
                                    : !product.isAvailable
                                        ? AppStrings.productOutOfStock
                                        : '${AppStrings.productAddToCart} · $total ${AppStrings.currency}',
                                key: ValueKey(added),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 15,
                                  fontWeight: FontWeight.w800,
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
            ],
          ),
        ),
      ),
    );
  }
}

class _QtyButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback? onTap;

  const _QtyButton({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        width: 38,
        height: 44,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          color: onTap != null
              ? _kGreen.withValues(alpha: 0.1)
              : Colors.transparent,
        ),
        child: Icon(
          icon,
          color: onTap != null ? _kGreen : _kSubtext.withValues(alpha: 0.3),
          size: 20,
        ),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// مساعدات مشتركة
// ══════════════════════════════════════════════════════════════════════════════
class _GlassButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;

  const _GlassButton({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
          child: Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: Colors.black.withValues(alpha: 0.25),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.2),
              ),
            ),
            child: Icon(icon, color: Colors.white, size: 18),
          ),
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final IconData icon;
  final String title;
  final Color color;

  const _SectionTitle({
    required this.icon,
    required this.title,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, color: color, size: 18),
        ),
        const SizedBox(width: 10),
        Text(
          title,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w800,
            color: color,
          ),
        ),
      ],
    );
  }
}

class _StarRating extends StatelessWidget {
  final double rating;
  const _StarRating({required this.rating});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(5, (i) {
        final filled = i < rating.floor();
        final half = !filled && i < rating;
        return Icon(
          filled
              ? Icons.star_rounded
              : half
                  ? Icons.star_half_rounded
                  : Icons.star_outline_rounded,
          color: const Color(0xFFFFC107),
          size: 16,
        );
      }),
    );
  }
}

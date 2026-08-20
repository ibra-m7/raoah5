import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../../ai_assistant/presentation/pages/chat_screen.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../auth/presentation/auth_flow.dart';
import '../../../auth/data/services/auth_session.dart';
import '../../../auth/presentation/manager/address_cubit.dart';
import '../../../auth/presentation/widgets/delivery_addresses_sheet.dart';
import '../../data/models/delivery_quote.dart';
import '../../data/models/product_model.dart';
import '../../data/services/delivery_api.dart';
import '../../domain/entities/cart_item.dart';
import '../manager/cart_cubit.dart';
import '../manager/catalog_cubit.dart';
import '../providers/cart_scope.dart';
import '../widgets/main_bottom_nav_bar.dart';
import '../widgets/main_shell_scope.dart';
import '../widgets/product_preview_sheet.dart';

// ── الهوية البصرية (Mint Green) ───────────────────────────────────────────────
const _kPrimary  = AppTheme.primary;
const _kDark     = AppTheme.primaryDark;
const _kBg       = AppTheme.background;
const _kSurface  = AppTheme.surface;
const _kText     = AppTheme.darkText;
const _kSubtext  = AppTheme.mutedText;
const _kBorder   = AppTheme.primaryLight;

/// حد التوصيل المجاني — القيمة الافتراضية إن لم تصل إعدادات المتجر بعد
const _kFreeShippingThreshold = 150.0;

// ══════════════════════════════════════════════════════════════════════════════
class CheckoutScreen extends StatelessWidget {
  static const routeName = '/checkout';
  const CheckoutScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Directionality(
      textDirection: TextDirection.rtl,
      child: _CartView(embeddedInMainShell: false),
    );
  }
}

/// سلة التسوق كتبويب داخل [MainScreen] — بدون شريط تنقل مضاعف أو مسار منفصل.
class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Directionality(
      textDirection: TextDirection.rtl,
      child: _CartView(embeddedInMainShell: true),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// العرض الرئيسي للسلة
// ══════════════════════════════════════════════════════════════════════════════
class _CartView extends StatelessWidget {
  final bool embeddedInMainShell;

  const _CartView({required this.embeddedInMainShell});

  @override
  Widget build(BuildContext context) {
    // فقط عند الانتقال من/إلى السلة الفارغة يُعاد بناء الجذر بالكامل
    return BlocBuilder<CartCubit, CartState>(
      buildWhen: (prev, curr) => prev.isEmpty != curr.isEmpty,
      builder: (context, cart) {
        if (cart.isEmpty) {
          return _EmptyCartScreen(embeddedInMainShell: embeddedInMainShell);
        }
        return _CartContent(embeddedInMainShell: embeddedInMainShell);
      },
    );
  }
}

/// محتوى السلة — كل قسم يستمع لـ [CartCubit] عبر [BlocSelector]
/// لتحديث الإجمالي وشريط التوصيل فوراً دون إعادة بناء الصفحة كاملة.
class _CartContent extends StatelessWidget {
  final bool embeddedInMainShell;

  const _CartContent({required this.embeddedInMainShell});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kBg,
      body: Column(
        children: [
          BlocSelector<CartCubit, CartState, int>(
            selector: (s) => s.count,
            builder: (context, count) => _CartAppBar(
              itemCount: count,
              showLeading: !embeddedInMainShell,
            ),
          ),
          Expanded(
            child: SingleChildScrollView(
              primary: false,
              physics: const BouncingScrollPhysics(),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 12),
                  BlocSelector<CartCubit, CartState, double>(
                    selector: (s) => s.total,
                    builder: (context, total) =>
                        _ShippingProgressBar(total: total),
                  ),
                  const SizedBox(height: 12),
                  BlocSelector<CartCubit, CartState, double>(
                    selector: (s) => s.total,
                    builder: (context, total) =>
                        _LiveDeliveryQuote(subtotal: total),
                  ),
                  const SizedBox(height: 16),
                  BlocSelector<CartCubit, CartState, List<CartItem>>(
                    selector: (s) => s.items,
                    builder: (context, items) => _CartItemsList(items: items),
                  ),
                  const SizedBox(height: 16),
                  BlocSelector<CartCubit, CartState, List<CartItem>>(
                    selector: (s) => s.items,
                    builder: (context, items) =>
                        _UpsellingSection(cartItems: items),
                  ),
                  const SizedBox(height: 120),
                ],
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: embeddedInMainShell
          ? BlocSelector<CartCubit, CartState, ({double total, int count})>(
              selector: (s) => (total: s.total, count: s.count),
              builder: (context, data) => _StickyCheckoutBar(
                total: data.total,
                count: data.count,
                onCheckout: () => openCheckoutSheet(context),
              ),
            )
          : Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                BlocSelector<CartCubit, CartState, ({double total, int count})>(
                  selector: (s) => (total: s.total, count: s.count),
                  builder: (context, data) => _StickyCheckoutBar(
                    total: data.total,
                    count: data.count,
                    onCheckout: () => openCheckoutSheet(context),
                  ),
                ),
                MainBottomNavBar(
                  currentTabIndex: 0,
                  cartScreenActive: true,
                  onTabTap: (i) => Navigator.of(context).pop(i),
                  onAiAssistantTap: () {
                    Navigator.of(context, rootNavigator: true).push<void>(
                      MaterialPageRoute<void>(
                        builder: (_) => const ChatScreen(useHeroMic: true),
                      ),
                    );
                  },
                ),
              ],
            ),
    );
  }
}

/// يفتح ورقة الدفع مع ربط صريح بـ [CartCubit] (مزود السلة في التطبيق).
void openCheckoutSheet(BuildContext context) {
  if (!AuthSession.instance.isLoggedIn) {
    AuthFlow.requireLogin(
      context,
      message: AppStrings.guestCheckoutMessage,
    );
    return;
  }
  final address = context.read<AddressCubit>().state.selected;
  if (address == null) {
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
      content: Text('أضف عنوان توصيل أولاً', textAlign: TextAlign.center),
    ));
    DeliveryAddressesSheet.show(context);
    return;
  }

  Navigator.of(context).pushNamed(AppRouter.invoice);
}

// ══════════════════════════════════════════════════════════════════════════════
// AppBar السلة
// ══════════════════════════════════════════════════════════════════════════════
class _CartAppBar extends StatelessWidget {
  final int itemCount;
  final bool showLeading;

  const _CartAppBar({
    required this.itemCount,
    this.showLeading = true,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      color: _kBg,
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(8, 8, 16, 12),
          child: Row(
            children: [
              if (showLeading)
                IconButton(
                  onPressed: () => Navigator.of(context).pop(),
                  icon: const Icon(Icons.arrow_forward_ios_rounded, size: 20),
                  color: _kText,
                )
              else
                const SizedBox(width: 12),
              const Expanded(
                child: Text(
                  'السلة',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                    color: _kText,
                  ),
                ),
              ),
              IconButton(
                onPressed: () => _confirmClear(context),
                tooltip: 'تفريغ السلة',
                icon: const Icon(Icons.delete_outline_rounded, size: 26),
                color: _kText,
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _confirmClear(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('تفريغ السلة', textAlign: TextAlign.center),
        content: const Text('هل تريد حذف جميع المنتجات من السلة؟',
            textAlign: TextAlign.center),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('إلغاء'),
          ),
          ElevatedButton(
            onPressed: () {
              context.read<CartCubit>().clearCart();
              Navigator.pop(ctx);
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.redAccent,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
            child: const Text('تفريغ'),
          ),
        ],
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// شريط التوصيل المجاني المتحرك
// ══════════════════════════════════════════════════════════════════════════════
class _ShippingProgressBar extends StatelessWidget {
  final double total;
  const _ShippingProgressBar({required this.total});

  @override
  Widget build(BuildContext context) {
    final store = context.watch<CatalogCubit>().state.store;
    final threshold = store.freeShippingThreshold;
    final showThreshold = threshold > 0;
    final isFree = showThreshold && total >= threshold;
    final progress = !showThreshold
        ? 1.0
        : (total / threshold).clamp(0.0, 1.0);
    final remaining = (threshold - total).clamp(0.0, double.infinity);
    final policy = store.delivery.policyHint;
    final firstFree = store.delivery.firstOrderFree;

    String message;
    if (firstFree) {
      message = showThreshold && !isFree
          ? 'أول طلب توصيل مجاني · أضف ${remaining.toStringAsFixed(2)} ${store.currency} للتوصيل المجاني لاحقاً'
          : 'أول طلب في التطبيق — توصيل مجاني';
    } else if (showThreshold) {
      message = isFree
          ? 'مبروك! حصلت على توصيل مجاني 🎉'
          : 'أضف ${remaining.toStringAsFixed(2)} ${store.currency} للتوصيل المجاني';
    } else if (policy.isNotEmpty) {
      message = policy;
    } else {
      message = 'التوصيل يُحسب حسب المسافة من المتجر';
    }

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
      decoration: BoxDecoration(
        color: _kSurface,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text('🚚', style: TextStyle(fontSize: 18)),
              const SizedBox(width: 8),
              Expanded(
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 400),
                  child: Text(
                    message,
                    key: ValueKey(message),
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: isFree || firstFree ? _kDark : _kText,
                    ),
                  ),
                ),
              ),
            ],
          ),

          if (showThreshold) ...[
            const SizedBox(height: 10),
            ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: Stack(
              children: [
                // الخلفية
                Container(
                  height: 8,
                  color: const Color(0xFFE8F8F0),
                ),
                // التقدم
                AnimatedFractionallySizedBox(
                  duration: const Duration(milliseconds: 600),
                  curve: Curves.easeOutCubic,
                  alignment: AlignmentDirectional.centerEnd,
                  widthFactor: progress,
                  child: Container(
                    height: 8,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: isFree
                            ? [_kDark, _kPrimary]
                            : [_kDark, _kPrimary],
                        begin: AlignmentDirectional.centerEnd,
                        end: AlignmentDirectional.centerStart,
                      ),
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 6),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                '0 \u{20C1}',
                style: TextStyle(fontSize: 10, color: _kSubtext),
              ),
              Text(
                '${threshold.toInt()} ${store.currency}',
                style: const TextStyle(
                  fontSize: 10,
                  color: _kDark,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          ],
        ],
      ),
    );
  }
}

class _LiveDeliveryQuote extends StatefulWidget {
  final double subtotal;
  const _LiveDeliveryQuote({required this.subtotal});

  @override
  State<_LiveDeliveryQuote> createState() => _LiveDeliveryQuoteState();
}

class _LiveDeliveryQuoteState extends State<_LiveDeliveryQuote> {
  DeliveryQuote? _quote;
  String? _error;
  String _key = '';

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _refresh();
  }

  @override
  void didUpdateWidget(covariant _LiveDeliveryQuote oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.subtotal != widget.subtotal) {
      _refresh();
    }
  }

  Future<void> _refresh() async {
    if (!AuthSession.instance.isLoggedIn) return;
    final address = context.read<AddressCubit>().state.selected;
    final key = '${address?.id}|${widget.subtotal.toStringAsFixed(2)}';
    if (key == _key && _quote != null) return;
    _key = key;
    try {
      final quote = await DeliveryApi.instance.quote(
        addressId: address?.id,
        subtotal: widget.subtotal,
      );
      if (!mounted) return;
      setState(() {
        _quote = quote;
        _error = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    context.watch<AddressCubit>();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _refresh();
    });
    final quote = _quote;
    if (quote == null && _error == null) return const SizedBox.shrink();
    final feeText = quote == null
        ? ''
        : (quote.isFree ? 'مجاني' : '${quote.fee.toStringAsFixed(2)} \u{20C1}');

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      decoration: BoxDecoration(
        color: _kSurface,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          const Icon(Icons.local_shipping_outlined, color: _kDark, size: 22),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'رسوم التوصيل لعنوانك',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: _kDark),
                ),
                if (quote?.label.isNotEmpty == true)
                  Text(
                    quote!.label,
                    style: const TextStyle(fontSize: 12, color: _kSubtext, fontWeight: FontWeight.w600),
                  ),
                if (_error != null)
                  Text(
                    _error!,
                    style: const TextStyle(fontSize: 12, color: Color(0xFFC62828), fontWeight: FontWeight.w700),
                  ),
              ],
            ),
          ),
          if (feeText.isNotEmpty)
            Text(
              feeText,
              style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: _kDark),
            ),
        ],
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// قائمة منتجات السلة
// ══════════════════════════════════════════════════════════════════════════════
class _CartItemsList extends StatelessWidget {
  final List<CartItem> items;
  const _CartItemsList({required this.items});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: _kSurface,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          children: [
            for (var i = 0; i < items.length; i++)
              _CartItemRow(item: items[i], showDivider: i < items.length - 1),
          ],
        ),
      ),
    );
  }
}

// ── صف منتج السلة ────────────────────────────────────────────────────────────
class _CartItemRow extends StatelessWidget {
  final CartItem item;
  final bool showDivider;
  const _CartItemRow({required this.item, this.showDivider = true});

  @override
  Widget build(BuildContext context) {
    final cubit = context.cart;
    final p = item.product;

    return Container(
      margin: const EdgeInsets.only(bottom: 4),
      padding: const EdgeInsets.fromLTRB(4, 12, 4, 12),
      decoration: BoxDecoration(
        color: _kSurface,
        border: showDivider
            ? const Border(
                bottom: BorderSide(color: Color(0xFFF3D6DC), width: 1),
              )
            : null,
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: AppNetworkImage(
                  p.imageUrl,
                  width: 72,
                  height: 64,
                  fit: BoxFit.cover,
                  error: Container(
                    width: 72,
                    height: 64,
                    color: _kBg,
                    child: const Icon(Icons.image_not_supported_outlined,
                        color: _kSubtext, size: 28),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              _QuantityControl(
                quantity: item.quantity,
                onDecrement: () =>
                    cubit.updateQuantity(p.id, item.quantity - 1),
                onIncrement: () =>
                    cubit.updateQuantity(p.id, item.quantity + 1),
              ),
            ],
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    p.name,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                      color: _kText,
                      height: 1.35,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${p.effectivePrice.toStringAsFixed(1)} \u{20C1}',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: _kSubtext,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 4),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextButton.icon(
                onPressed: () {
                  HapticFeedback.lightImpact();
                  cubit.removeFromCart(p.id);
                },
                style: TextButton.styleFrom(
                  foregroundColor: const Color(0xFFE53935),
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                icon: const Icon(Icons.delete_outline_rounded, size: 18),
                label: const Text(
                  'حذف',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                ),
              ),
              TextButton.icon(
                onPressed: () {
                  final model = p is ProductModel
                      ? p
                      : ProductModel(
                          id: p.id,
                          name: p.name,
                          description: p.description,
                          price: p.price,
                          discountPrice: p.discountPrice,
                          imageUrl: p.imageUrl,
                          imageUrls: p.imageUrls,
                          categoryId: p.categoryId,
                          stock: p.stock,
                          quantityLabel: p.quantityLabel,
                          soldCount: p.soldCount,
                          rating: p.rating,
                          reviewCount: p.reviewCount,
                          benefits: p.benefits,
                          keywords: p.keywords,
                          usageInstructions: p.usageInstructions,
                        );
                  showProductPreview(context, model);
                },
                style: TextButton.styleFrom(
                  foregroundColor: _kText,
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                icon: const Icon(Icons.edit_outlined, size: 18),
                label: const Text(
                  'تعديل',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

// ── أزرار التحكم في الكمية ────────────────────────────────────────────────────
class _QuantityControl extends StatelessWidget {
  final int quantity;
  final VoidCallback onDecrement;
  final VoidCallback onIncrement;

  const _QuantityControl({
    required this.quantity,
    required this.onDecrement,
    required this.onIncrement,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: _kBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: _kBorder),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          // زر الإنقاص
          _QtyButton(
            icon: Icons.remove_rounded,
            onTap: onDecrement,
            isDecrement: true,
          ),
          // العدد
          AnimatedSwitcher(
            duration: const Duration(milliseconds: 200),
            child: SizedBox(
              width: 28,
              child: Text(
                '$quantity',
                key: ValueKey(quantity),
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w800,
                  color: _kText,
                ),
              ),
            ),
          ),
          // زر الإضافة
          _QtyButton(
            icon: Icons.add_rounded,
            onTap: onIncrement,
            isDecrement: false,
          ),
        ],
      ),
    );
  }
}

class _QtyButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;
  final bool isDecrement;

  const _QtyButton({
    required this.icon,
    required this.onTap,
    required this.isDecrement,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        HapticFeedback.lightImpact();
        onTap();
      },
      child: Container(
        width: 30,
        height: 30,
        decoration: BoxDecoration(
          color: isDecrement ? Colors.transparent : _kDark,
          borderRadius: BorderRadius.circular(10),
        ),
        child: Icon(
          icon,
          size: 16,
          color: isDecrement ? _kSubtext : Colors.white,
        ),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// قسم Upselling — منتجات تكمل سلتك
// ══════════════════════════════════════════════════════════════════════════════
class _UpsellingSection extends StatelessWidget {
  final List<CartItem> cartItems;
  const _UpsellingSection({required this.cartItems});

  List<ProductModel> _suggestions(BuildContext context) {
    final catalog = context.read<CatalogCubit>().state;
    final cartProductIds = cartItems.map((i) => i.product.id).toSet();
    final categoriesInCart =
        cartItems.map((i) => i.product.categoryId).toSet();
    return catalog.suggestions(
      excludeIds: cartProductIds,
      excludeCategoryIds: categoriesInCart,
    );
  }

  @override
  Widget build(BuildContext context) {
    final suggestions = _suggestions(context);
    if (suggestions.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
          child: Row(
            children: [
              const Text('✨', style: TextStyle(fontSize: 18)),
              const SizedBox(width: 8),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'منتجات تكمل سلتك!',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: _kText,
                      ),
                    ),
                    Text(
                      'من فئات مختلفة عن سلتك — قد تعجبك',
                      style: TextStyle(
                        fontSize: 12,
                        color: _kSubtext,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        SizedBox(
          height: 170,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
            itemCount: suggestions.length,
            itemBuilder: (ctx, i) =>
                _UpsellingCard(product: suggestions[i]),
          ),
        ),
      ],
    );
  }
}

// ── كارت الاقتراح ─────────────────────────────────────────────────────────────
class _UpsellingCard extends StatelessWidget {
  final ProductModel product;
  const _UpsellingCard({required this.product});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 130,
      margin: const EdgeInsets.only(left: 12),
      decoration: BoxDecoration(
        color: _kSurface,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // الصورة + زر +
          Stack(
            children: [
              ClipRRect(
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(18)),
                child: AppNetworkImage(
                  product.imageUrl,
                  width: 130,
                  height: 95,
                  fit: BoxFit.cover,
                  error: Container(
                    width: 130,
                    height: 95,
                    color: _kBg,
                    child: const Icon(Icons.image_not_supported_outlined,
                        color: _kSubtext, size: 28),
                  ),
                ),
              ),
              // زر الإضافة
              PositionedDirectional(
                bottom: 7,
                end: 7,
                child: GestureDetector(
                  onTap: () {
                    context.read<CartCubit>().addToCart(product);
                    ScaffoldMessenger.of(context)
                      ..hideCurrentSnackBar()
                      ..showSnackBar(SnackBar(
                        content: Text(
                          AppStrings.productAddedToCart(product.name),
                          textAlign: TextAlign.center,
                        ),
                        backgroundColor: _kDark,
                        behavior: SnackBarBehavior.floating,
                        duration: const Duration(seconds: 2),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                        margin:
                            const EdgeInsets.fromLTRB(16, 0, 16, 20),
                      ));
                  },
                  child: Container(
                    width: 28,
                    height: 28,
                    decoration: BoxDecoration(
                      color: _kDark,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: _kDark.withValues(alpha: 0.4),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: const Icon(Icons.add_rounded,
                        color: Colors.white, size: 18),
                  ),
                ),
              ),
            ],
          ),

          // السعر + الاسم
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 7, 8, 7),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: AlignmentDirectional.centerStart,
                  child: Text(
                    '${product.effectivePrice.toStringAsFixed(0)} \u{20C1}',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w900,
                      color: _kDark,
                    ),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  product.name,
                  style: const TextStyle(
                    fontSize: 10.5,
                    fontWeight: FontWeight.w600,
                    color: _kText,
                    height: 1.3,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// شريط الدفع الثابت في الأسفل
// ══════════════════════════════════════════════════════════════════════════════
class _StickyCheckoutBar extends StatelessWidget {
  final double total;
  final int count;
  final VoidCallback onCheckout;

  const _StickyCheckoutBar({
    required this.total,
    required this.count,
    required this.onCheckout,
  });

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 10),
        child: Material(
          color: AppTheme.primaryDark,
          borderRadius: BorderRadius.circular(22),
          elevation: 8,
          shadowColor: AppTheme.primaryDark.withValues(alpha: 0.35),
          child: InkWell(
            onTap: onCheckout,
            borderRadius: BorderRadius.circular(22),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
              child: Row(
                children: [
                  Text(
                    '${total.toStringAsFixed(1)} \u{20C1}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    width: 26,
                    height: 26,
                    alignment: Alignment.center,
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                    ),
                    child: Text(
                      '$count',
                      style: const TextStyle(
                        color: AppTheme.primaryDark,
                        fontWeight: FontWeight.w900,
                        fontSize: 12,
                      ),
                    ),
                  ),
                  const Spacer(),
                  const Icon(
                    Icons.arrow_back_ios_new_rounded,
                    color: Colors.white,
                    size: 16,
                  ),
                  const SizedBox(width: 6),
                  const Text(
                    'عرض الفاتورة',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
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
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// ══════════════════════════════════════════════════════════════════════════════
// شاشة السلة الفارغة
// ══════════════════════════════════════════════════════════════════════════════
class _EmptyCartScreen extends StatelessWidget {
  final bool embeddedInMainShell;

  const _EmptyCartScreen({required this.embeddedInMainShell});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kBg,
      appBar: AppBar(
        backgroundColor: _kBg,
        elevation: 0,
        scrolledUnderElevation: 0,
        automaticallyImplyLeading: false,
        leading: embeddedInMainShell
            ? null
            : IconButton(
                icon: const Icon(Icons.arrow_forward_ios_rounded,
                    size: 20, color: _kText),
                onPressed: () => Navigator.of(context).pop(),
              ),
        title: const Text(
          'السلة',
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.w900,
            color: _kText,
          ),
        ),
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                color: _kSurface,
                shape: BoxShape.circle,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.06),
                    blurRadius: 20,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: const Icon(
                Icons.shopping_bag_outlined,
                size: 56,
                color: _kBorder,
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              AppStrings.cartEmpty,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: _kText,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              AppStrings.cartEmptyHint,
              style: TextStyle(
                fontSize: 14,
                color: _kSubtext,
                fontWeight: FontWeight.w500,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),
            ElevatedButton.icon(
              onPressed: () {
                if (embeddedInMainShell) {
                  MainShellScope.read(context).selectTab(0);
                } else {
                  Navigator.of(context).pop(0);
                }
              },
              icon: const Icon(Icons.storefront_rounded, size: 20),
              label: const Text(AppStrings.cartStartShopping),
              style: ElevatedButton.styleFrom(
                backgroundColor: _kDark,
                foregroundColor: Colors.white,
                minimumSize: const Size(200, 52),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                ),
                elevation: 0,
                textStyle: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: embeddedInMainShell
          ? null
          : MainBottomNavBar(
              currentTabIndex: 0,
              cartScreenActive: true,
              onTabTap: (i) => Navigator.of(context).pop(i),
              onAiAssistantTap: () {
                Navigator.of(context, rootNavigator: true).push<void>(
                  MaterialPageRoute<void>(
                    builder: (_) => const ChatScreen(useHeroMic: true),
                  ),
                );
              },
            ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// حوار النجاح
// ══════════════════════════════════════════════════════════════════════════════
class OrderSuccessDialog extends StatefulWidget {
  const OrderSuccessDialog({super.key});

  @override
  State<OrderSuccessDialog> createState() => _OrderSuccessDialogState();
}

class _OrderSuccessDialogState extends State<OrderSuccessDialog>
    with SingleTickerProviderStateMixin {
  late AnimationController _ctrl;
  late Animation<double> _circleSc;
  late Animation<double> _checkFd;
  late Animation<double> _textFd;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );
    _circleSc = CurvedAnimation(
      parent: _ctrl,
      curve: const Interval(0.0, 0.5, curve: Curves.elasticOut),
    );
    _checkFd = CurvedAnimation(
      parent: _ctrl,
      curve: const Interval(0.4, 0.75, curve: Curves.easeOut),
    );
    _textFd = CurvedAnimation(
      parent: _ctrl,
      curve: const Interval(0.65, 1.0, curve: Curves.easeOut),
    );
    _ctrl.forward();
    Future.delayed(const Duration(seconds: 3), () {
      if (mounted) Navigator.of(context).pop();
    });
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(28),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.12),
              blurRadius: 30,
              offset: const Offset(0, 10),
            ),
          ],
        ),
        padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ScaleTransition(
              scale: _circleSc,
              child: FadeTransition(
                opacity: _checkFd,
                child: Container(
                  width: 110,
                  height: 110,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: const LinearGradient(
                      colors: [Color(0xFF27AE60), Color(0xFF2ECC71)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: _kDark.withValues(alpha: 0.35),
                        blurRadius: 28,
                        spreadRadius: 4,
                      ),
                    ],
                  ),
                  child: const Icon(Icons.check_rounded,
                      color: Colors.white, size: 60),
                ),
              ),
            ),
            const SizedBox(height: 28),
            FadeTransition(
              opacity: _textFd,
              child: Column(
                children: [
                  const Text(
                    'تم الطلب بنجاح! 🎉',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      color: _kText,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 10),
                  Text(
                    'سيتم تأكيد طلبك وتوصيله في أقرب وقت ممكن.',
                    style: TextStyle(
                      fontSize: 14,
                      color: _kSubtext,
                      height: 1.5,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      _StepIcon(
                          icon: Icons.check_circle_rounded,
                          color: _kDark,
                          label: 'مؤكد'),
                      _StepArrow(),
                      _StepIcon(
                          icon: Icons.local_shipping_rounded,
                          color: Colors.orange,
                          label: 'جاري التحضير'),
                      _StepArrow(),
                      _StepIcon(
                          icon: Icons.home_rounded,
                          color: Colors.blueAccent,
                          label: 'التوصيل'),
                    ],
                  ),
                  const SizedBox(height: 24),
                  // زر تتبع الطلب
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.of(context).pop();
                        Navigator.of(context).pushNamed(AppRouter.orders);
                      },
                      icon: const Icon(Icons.track_changes_rounded, size: 18),
                      label: const Text('تتبع الطلب'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _kDark,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        elevation: 0,
                        textStyle: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StepIcon extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String label;
  const _StepIcon(
      {required this.icon, required this.color, required this.label});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, color: color, size: 28),
        const SizedBox(height: 4),
        Text(label,
            style: TextStyle(fontSize: 10, color: _kSubtext)),
      ],
    );
  }
}

class _StepArrow extends StatelessWidget {
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        child: Icon(Icons.arrow_forward_ios_rounded,
            size: 14, color: Colors.grey[400]),
      );
}


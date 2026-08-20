import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../auth/data/services/auth_session.dart';
import '../../../auth/presentation/auth_flow.dart';
import '../../../auth/presentation/manager/address_cubit.dart';
import '../../../auth/presentation/widgets/delivery_addresses_sheet.dart';
import '../../data/models/coupon_quote.dart';
import '../../data/models/delivery_quote.dart';
import '../../data/models/home_feed.dart';
import '../../data/services/coupons_api.dart';
import '../../data/services/delivery_api.dart';
import '../../domain/entities/cart_item.dart';
import '../manager/cart_cubit.dart';
import '../manager/catalog_cubit.dart';
import '../manager/orders_cubit.dart';
import '../widgets/main_shell_scope.dart';
import 'checkout_screen.dart';

class InvoiceScreen extends StatefulWidget {
  static const routeName = '/invoice';

  const InvoiceScreen({super.key});

  @override
  State<InvoiceScreen> createState() => _InvoiceScreenState();
}

class _InvoiceScreenState extends State<InvoiceScreen> {
  final _couponCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  bool _paying = false;
  bool _fulfillNow = true;
  DateTime? _scheduledAt;
  String _method = 'cash';
  CouponQuote? _quote;
  DeliveryQuote? _delivery;
  String? _deliveryError;
  String _deliveryKey = '';
  bool _applyingCoupon = false;
  String? _couponError;
  String _quotedCartKey = '';

  @override
  void initState() {
    super.initState();
    final methods =
        context.read<CatalogCubit>().state.store.paymentMethods;
    if (methods.isNotEmpty) {
      _method = methods.first.id;
    }
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _refreshDelivery();
    });
  }

  @override
  void dispose() {
    _couponCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickSchedule() async {
    final date = await showDatePicker(
      context: context,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 30)),
      initialDate: _scheduledAt ?? DateTime.now().add(const Duration(hours: 2)),
    );
    if (!mounted || date == null) return;
    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(
        _scheduledAt ?? DateTime.now().add(const Duration(hours: 2)),
      ),
    );
    if (!mounted) return;
    setState(() {
      _fulfillNow = false;
      _scheduledAt = DateTime(
        date.year,
        date.month,
        date.day,
        time?.hour ?? 12,
        time?.minute ?? 0,
      );
    });
  }

  Future<void> _submit() async {
    if (!AuthSession.instance.isLoggedIn) {
      await AuthFlow.requireLogin(
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
      await DeliveryAddressesSheet.show(context);
      return;
    }
    if (!_fulfillNow && _scheduledAt == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('اختر وقت تنفيذ الطلب', textAlign: TextAlign.center),
      ));
      return;
    }

    setState(() => _paying = true);
    final cartState = context.read<CartCubit>().state;
    try {
      await context.read<OrdersCubit>().placeOrder(
            items: List.from(cartState.items),
            paymentMethod: _method,
            addressId: address.id,
            notes: _notesCtrl.text.trim(),
            couponCode: _quote?.code,
            fulfillmentType: _fulfillNow ? 'now' : 'scheduled',
            scheduledAt: _fulfillNow ? null : _scheduledAt,
          );
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _paying = false);
      if (e.statusCode == 401) {
        await AuthFlow.requireLogin(
          context,
          message: AppStrings.guestCheckoutMessage,
        );
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message, textAlign: TextAlign.center)),
      );
      return;
    } catch (e) {
      if (!mounted) return;
      setState(() => _paying = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString(), textAlign: TextAlign.center)),
      );
      return;
    }

    if (!mounted) return;
    setState(() => _paying = false);
    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const OrderSuccessDialog(),
    );
    if (!mounted) return;
    context.read<CartCubit>().clearCart();
    Navigator.of(context).popUntil((r) => r.isFirst);
  }

  String _cartKey(List<CartItem> items) =>
      items.map((i) => '${i.product.id}:${i.quantity}').join(',');

  Future<void> _applyCoupon(List<CartItem> items) async {
    final code = _couponCtrl.text.trim();
    if (code.isEmpty) {
      setState(() => _couponError = 'أدخل كود الخصم أولاً');
      return;
    }
    setState(() {
      _applyingCoupon = true;
      _couponError = null;
    });
    try {
      final quote = await CouponsApi.instance.preview(
        code: code,
        items: items,
        addressId: context.read<AddressCubit>().state.selected?.id,
      );
      if (!mounted) return;
      setState(() {
        _quote = quote;
        _quotedCartKey = _cartKey(items);
        _applyingCoupon = false;
        _couponError = null;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _quote = null;
        _applyingCoupon = false;
        _couponError = e.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _quote = null;
        _applyingCoupon = false;
        _couponError = 'تعذّر التحقق من الكوبون.';
      });
    }
  }

  Future<void> _refreshDelivery() async {
    final cart = context.read<CartCubit>().state;
    final address = context.read<AddressCubit>().state.selected;
    final key = '${address?.id}|${_cartKey(cart.items)}';
    if (!AuthSession.instance.isLoggedIn) {
      if (_delivery != null || _deliveryError != null || _deliveryKey != key) {
        setState(() {
          _delivery = null;
          _deliveryError = null;
          _deliveryKey = key;
        });
      } else {
        _deliveryKey = key;
      }
      return;
    }
    _deliveryKey = key;
    try {
      final quote = await DeliveryApi.instance.quote(
        addressId: address?.id,
        subtotal: cart.total,
      );
      if (!mounted) return;
      final current =
          '${context.read<AddressCubit>().state.selected?.id}|${_cartKey(context.read<CartCubit>().state.items)}';
      if (current != key) return;
      setState(() {
        _delivery = quote;
        _deliveryError = null;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _deliveryError = e.message;
      });
    } catch (_) {
      if (!mounted) return;
    }
  }

  void _clearCoupon() {
    setState(() {
      _quote = null;
      _couponError = null;
      _quotedCartKey = '';
      _couponCtrl.clear();
    });
  }

  IconData _iconFor(String id) {
    return switch (id) {
      'mada' => Icons.credit_card_rounded,
      'apple_pay' => Icons.phone_iphone_rounded,
      'stc_pay' => Icons.account_balance_wallet_rounded,
      'card' => Icons.credit_score_rounded,
      'bank_transfer' => Icons.account_balance_rounded,
      _ => Icons.payments_rounded,
    };
  }

  @override
  Widget build(BuildContext context) {
    final store = context.watch<CatalogCubit>().state.store;
    final address = context.watch<AddressCubit>().state.selected;

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppTheme.background,
        appBar: AppBar(
          backgroundColor: AppTheme.background,
          elevation: 0,
          leading: IconButton(
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.arrow_forward_ios_rounded, size: 20),
          ),
          title: const Text(
            'الطلب',
            style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20),
          ),
          centerTitle: true,
        ),
        body: BlocBuilder<CartCubit, CartState>(
          builder: (context, cart) {
            final subtotal = cart.total;
            final quoteValid =
                _quote != null && _quotedCartKey == _cartKey(cart.items);
            final quote = quoteValid ? _quote : null;
            final currentDeliveryKey = '${address?.id}|${_cartKey(cart.items)}';
            if (currentDeliveryKey != _deliveryKey) {
              WidgetsBinding.instance.addPostFrameCallback((_) {
                if (mounted) _refreshDelivery();
              });
            }
            final loggedIn = AuthSession.instance.isLoggedIn;
            final quotedShipping = quote?.shippingFee ?? _delivery?.fee;
            final shipping = quotedShipping ??
                (!loggedIn && store.delivery.firstOrderFree
                    ? 0.0
                    : (store.freeShippingThreshold > 0 &&
                            subtotal >= store.freeShippingThreshold
                        ? 0.0
                        : store.shippingFee));
            final free = quote?.hasFreeShipping ??
                _delivery?.isFree ??
                shipping <= 0;
            final deliveryLabel = quote?.deliveryLabel ??
                _delivery?.label ??
                (!loggedIn && store.delivery.firstOrderFree
                    ? 'أول طلب مجاني'
                    : null);
            final discount = quote?.discountAmount ?? 0.0;
            final grand = quote?.total ?? (subtotal - discount + shipping);
            final methods = store.paymentMethods;
            if (methods.isNotEmpty &&
                !methods.any((m) => m.id == _method)) {
              WidgetsBinding.instance.addPostFrameCallback((_) {
                if (mounted) setState(() => _method = methods.first.id);
              });
            }
            return Column(
              children: [
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                    children: [
                      _InvoiceCard(
                        icon: Icons.location_on_rounded,
                        title: 'التوصيل إلى',
                        onTap: () => DeliveryAddressesSheet.show(context),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                address?.label ?? 'اختر عنواناً',
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                  fontSize: 15,
                                  color: AppTheme.darkText,
                                ),
                              ),
                            ),
                            const Icon(
                              Icons.chevron_left_rounded,
                              color: AppTheme.mutedText,
                            ),
                          ],
                        ),
                      ),
                      _InvoiceCard(
                        icon: Icons.bolt_rounded,
                        title: 'وقت تنفيذ الطلب',
                        child: Container(
                          padding: const EdgeInsets.all(4),
                          decoration: BoxDecoration(
                            color: AppTheme.primarySurface,
                            borderRadius: BorderRadius.circular(18),
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: _TimeChip(
                                  label: 'الآن',
                                  selected: _fulfillNow,
                                  onTap: () => setState(() {
                                    _fulfillNow = true;
                                    _scheduledAt = null;
                                  }),
                                ),
                              ),
                              Expanded(
                                child: _TimeChip(
                                  label: _scheduledAt == null
                                      ? 'وقت آخر'
                                      : '${_scheduledAt!.day}/${_scheduledAt!.month}  ${_scheduledAt!.hour.toString().padLeft(2, '0')}:${_scheduledAt!.minute.toString().padLeft(2, '0')}',
                                  selected: !_fulfillNow,
                                  onTap: _pickSchedule,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      _InvoiceCard(
                        icon: Icons.local_offer_outlined,
                        title: 'الخصم - الكوبون',
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            TextField(
                              controller: _couponCtrl,
                              textAlign: TextAlign.right,
                              decoration: InputDecoration(
                                hintText: 'أدخل كود الخصم',
                                filled: true,
                                fillColor: AppTheme.background,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(14),
                                  borderSide: const BorderSide(
                                    color: AppTheme.primaryLight,
                                  ),
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(14),
                                  borderSide: const BorderSide(
                                    color: AppTheme.primaryLight,
                                  ),
                                ),
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 14,
                                  vertical: 12,
                                ),
                              ),
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  child: OutlinedButton(
                                    onPressed: _applyingCoupon
                                        ? null
                                        : () => _applyCoupon(cart.items),
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: AppTheme.primaryDark,
                                      side: const BorderSide(
                                        color: AppTheme.primaryDark,
                                      ),
                                      minimumSize: const Size(0, 44),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(14),
                                      ),
                                    ),
                                    child: _applyingCoupon
                                        ? const SizedBox(
                                            width: 18,
                                            height: 18,
                                            child: CircularProgressIndicator(
                                              strokeWidth: 2,
                                            ),
                                          )
                                        : Text(
                                            quote == null
                                                ? 'تفعيل الخصم'
                                                : 'تحديث ${quote.code}',
                                            style: const TextStyle(
                                              fontWeight: FontWeight.w800,
                                            ),
                                          ),
                                  ),
                                ),
                                if (quote != null) ...[
                                  const SizedBox(width: 8),
                                  TextButton(
                                    onPressed: _clearCoupon,
                                    child: const Text('إزالة'),
                                  ),
                                ],
                              ],
                            ),
                            if (_couponError != null) ...[
                              const SizedBox(height: 8),
                              Text(
                                _couponError!,
                                style: const TextStyle(
                                  color: Color(0xFFC62828),
                                  fontWeight: FontWeight.w700,
                                  fontSize: 12.5,
                                ),
                              ),
                            ],
                            if (quote != null) ...[
                              const SizedBox(height: 8),
                              Text(
                                quote.message,
                                style: const TextStyle(
                                  color: AppTheme.primaryDark,
                                  fontWeight: FontWeight.w700,
                                  fontSize: 13,
                                ),
                              ),
                            ],
                            if (_quote != null && quote == null) ...[
                              const SizedBox(height: 8),
                              const Text(
                                'تعدّلت السلة — أعد تفعيل الكوبون',
                                style: TextStyle(
                                  color: Color(0xFFC77800),
                                  fontWeight: FontWeight.w700,
                                  fontSize: 12.5,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      _InvoiceCard(
                        icon: Icons.shopping_cart_outlined,
                        title: 'ملخص السلة',
                        child: Column(
                          children: [
                            const _TableHead(),
                            const Divider(height: 16),
                            ...cart.items.map(
                              (item) => _CartSummaryRow(item: item),
                            ),
                          ],
                        ),
                      ),
                      _InvoiceCard(
                        icon: Icons.receipt_long_rounded,
                        title: 'ملخص الدفع',
                        child: Column(
                          children: [
                            _PayRow(
                              label: 'إجمالي السلة',
                              value:
                                  '${subtotal.toStringAsFixed(1)} ${store.currency}',
                            ),
                            const SizedBox(height: 8),
                            _PayRow(
                              label: 'التوصيل',
                              value: free
                                  ? 'مجاني'
                                  : '${shipping.toStringAsFixed(1)} ${store.currency}',
                            ),
                            if (deliveryLabel != null &&
                                deliveryLabel.trim().isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Align(
                                alignment: Alignment.centerRight,
                                child: Text(
                                  deliveryLabel,
                                  style: const TextStyle(
                                    color: AppTheme.mutedText,
                                    fontWeight: FontWeight.w600,
                                    fontSize: 12,
                                  ),
                                ),
                              ),
                            ],
                            if (_deliveryError != null) ...[
                              const SizedBox(height: 8),
                              Text(
                                _deliveryError!,
                                style: const TextStyle(
                                  color: Color(0xFFC62828),
                                  fontWeight: FontWeight.w700,
                                  fontSize: 12.5,
                                ),
                              ),
                            ],
                            if (discount > 0) ...[
                              const SizedBox(height: 8),
                              _PayRow(
                                label: quote?.code == null
                                    ? 'الخصم'
                                    : 'الخصم (${quote!.code})',
                                value:
                                    '- ${discount.toStringAsFixed(1)} ${store.currency}',
                              ),
                            ],
                            const Divider(height: 18),
                            _PayRow(
                              label: 'الإجمالي',
                              value:
                                  '${grand.toStringAsFixed(1)} ${store.currency}',
                              emphasize: true,
                            ),
                          ],
                        ),
                      ),
                      _InvoiceCard(
                        icon: Icons.account_balance_wallet_outlined,
                        title: 'طريقة الدفع',
                        child: Column(
                          children: [
                            for (final method in store.paymentMethods)
                              _PaymentTile(
                                method: method,
                                selected: _method == method.id,
                                icon: _iconFor(method.id),
                                onTap: () =>
                                    setState(() => _method = method.id),
                              ),
                            if (_method == 'bank_transfer' &&
                                store.bankIban.isNotEmpty)
                              Padding(
                                padding: const EdgeInsets.only(top: 8),
                                child: Text(
                                  '${store.bankName}\n${store.bankIban}',
                                  textDirection: TextDirection.ltr,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                    height: 1.5,
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                      _InvoiceCard(
                        icon: Icons.edit_note_rounded,
                        title: 'الملاحظات',
                        child: TextField(
                          controller: _notesCtrl,
                          maxLines: 4,
                          decoration: InputDecoration(
                            hintText: 'أضف ملاحظاتك هنا',
                            filled: true,
                            fillColor: AppTheme.background,
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(14),
                              borderSide: const BorderSide(
                                color: AppTheme.primaryLight,
                              ),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(14),
                              borderSide: const BorderSide(
                                color: AppTheme.primaryLight,
                              ),
                            ),
                            contentPadding: const EdgeInsets.all(14),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                SafeArea(
                  top: false,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                    child: Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: _paying
                                ? null
                                : () {
                                    Navigator.of(context).pop();
                                    try {
                                      MainShellScope.read(context).selectTab(0);
                                    } catch (_) {}
                                  },
                            style: OutlinedButton.styleFrom(
                              foregroundColor: AppTheme.primaryDark,
                              side: const BorderSide(
                                color: AppTheme.primaryDark,
                                width: 1.4,
                              ),
                              minimumSize: const Size(0, 52),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(18),
                              ),
                              textStyle: const TextStyle(
                                fontWeight: FontWeight.w800,
                                fontSize: 15,
                              ),
                            ),
                            child: const Text('إضافة المزيد'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: (_paying || _deliveryError != null)
                                ? null
                                : _submit,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppTheme.primaryDark,
                              foregroundColor: Colors.white,
                              minimumSize: const Size(0, 52),
                              elevation: 0,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(18),
                              ),
                              textStyle: const TextStyle(
                                fontWeight: FontWeight.w800,
                                fontSize: 15,
                              ),
                            ),
                            child: _paying
                                ? const SizedBox(
                                    width: 22,
                                    height: 22,
                                    child: CircularProgressIndicator(
                                      color: Colors.white,
                                      strokeWidth: 2.4,
                                    ),
                                  )
                                : const Text('تنفيذ الطلب'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _InvoiceCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final Widget child;
  final VoidCallback? onTap;

  const _InvoiceCard({
    required this.icon,
    required this.title,
    required this.child,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final content = Padding(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: AppTheme.primarySurface,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: AppTheme.primaryDark, size: 20),
              ),
              const SizedBox(width: 10),
              Text(
                title,
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 16,
                  color: AppTheme.darkText,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE6EEE8)),
      ),
      child: onTap == null
          ? content
          : InkWell(
              onTap: onTap,
              borderRadius: BorderRadius.circular(18),
              child: content,
            ),
    );
  }
}

class _TimeChip extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onTap;

  const _TimeChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: selected ? AppTheme.primaryDark : Colors.transparent,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontWeight: FontWeight.w800,
              color: selected ? Colors.white : AppTheme.darkText,
            ),
          ),
        ),
      ),
    );
  }
}

class _TableHead extends StatelessWidget {
  const _TableHead();

  @override
  Widget build(BuildContext context) {
    const style = TextStyle(
      fontWeight: FontWeight.w800,
      fontSize: 12,
      color: AppTheme.mutedText,
    );
    return const Row(
      children: [
        Expanded(flex: 3, child: Text('المنتج', style: style)),
        Expanded(child: Text('السعر', textAlign: TextAlign.center, style: style)),
        Expanded(child: Text('الكمية', textAlign: TextAlign.center, style: style)),
        Expanded(
          child: Text('الإجمالي', textAlign: TextAlign.center, style: style),
        ),
      ],
    );
  }
}

class _CartSummaryRow extends StatelessWidget {
  final CartItem item;
  const _CartSummaryRow({required this.item});

  @override
  Widget build(BuildContext context) {
    const style = TextStyle(
      fontWeight: FontWeight.w600,
      fontSize: 12.5,
      color: AppTheme.darkText,
    );
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Expanded(
            flex: 3,
            child: Text(
              item.product.name,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: style,
            ),
          ),
          Expanded(
            child: Text(
              item.product.effectivePrice.toStringAsFixed(1),
              textAlign: TextAlign.center,
              style: style,
            ),
          ),
          Expanded(
            child: Text(
              '${item.quantity}',
              textAlign: TextAlign.center,
              style: style,
            ),
          ),
          Expanded(
            child: Text(
              item.totalPrice.toStringAsFixed(1),
              textAlign: TextAlign.center,
              style: style,
            ),
          ),
        ],
      ),
    );
  }
}

class _PayRow extends StatelessWidget {
  final String label;
  final String value;
  final bool emphasize;

  const _PayRow({
    required this.label,
    required this.value,
    this.emphasize = false,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          label,
          style: TextStyle(
            fontWeight: emphasize ? FontWeight.w900 : FontWeight.w600,
            color: AppTheme.darkText,
          ),
        ),
        const Spacer(),
        Text(
          value,
          style: TextStyle(
            fontWeight: FontWeight.w900,
            color: emphasize ? AppTheme.primaryDark : AppTheme.darkText,
            fontSize: emphasize ? 16 : 14,
          ),
        ),
      ],
    );
  }
}

class _PaymentTile extends StatelessWidget {
  final PaymentOption method;
  final bool selected;
  final IconData icon;
  final VoidCallback onTap;

  const _PaymentTile({
    required this.method,
    required this.selected,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Material(
        color: selected ? AppTheme.primarySurface : AppTheme.background,
        borderRadius: BorderRadius.circular(14),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(14),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: selected ? AppTheme.primaryDark : AppTheme.primaryLight,
                width: selected ? 1.5 : 1,
              ),
            ),
            child: Row(
              children: [
                Icon(icon, color: AppTheme.primaryDark),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        method.label,
                        style: const TextStyle(
                          fontWeight: FontWeight.w800,
                          color: AppTheme.darkText,
                        ),
                      ),
                      if (method.hint.isNotEmpty)
                        Text(
                          method.hint,
                          style: const TextStyle(
                            fontSize: 11.5,
                            color: AppTheme.mutedText,
                            height: 1.3,
                          ),
                        ),
                    ],
                  ),
                ),
                Icon(
                  selected
                      ? Icons.radio_button_checked_rounded
                      : Icons.radio_button_off_rounded,
                  color: selected ? AppTheme.primaryDark : AppTheme.mutedText,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

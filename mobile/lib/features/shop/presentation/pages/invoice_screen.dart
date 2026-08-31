import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../auth/data/services/auth_session.dart';
import '../../../auth/presentation/auth_flow.dart';
import '../../../auth/presentation/manager/address_cubit.dart';
import '../../../auth/presentation/widgets/delivery_addresses_sheet.dart';
import '../../data/models/coupon_quote.dart';
import '../../data/models/delivery_quote.dart';
import '../../data/models/delivery_slots.dart';
import '../../data/models/home_feed.dart';
import '../../data/services/coupons_api.dart';
import '../../data/services/delivery_api.dart';
import '../../domain/entities/cart_item.dart';
import '../manager/cart_cubit.dart';
import '../manager/catalog_cubit.dart';
import '../manager/orders_cubit.dart';
import '../widgets/checkout_action_bar.dart';
import '../widgets/checkout_sheet.dart';
import '../widgets/cart_item_groups.dart';
import '../widgets/cart_summary_group.dart';
import '../widgets/coupon_badge_icon.dart';
import '../widgets/delivery_slots_sheet.dart';
import '../widgets/notes_sheet.dart';
import '../widgets/payment_method_logo.dart';
import '../widgets/payment_sheets.dart';
import 'checkout_screen.dart';

class InvoiceScreen extends StatefulWidget {
  static const routeName = '/invoice';

  const InvoiceScreen({super.key});

  @override
  State<InvoiceScreen> createState() => _InvoiceScreenState();
}

class _InvoiceScreenState extends State<InvoiceScreen> {
  final _notesCtrl = TextEditingController();
  final _couponCtrl = TextEditingController();
  bool _paying = false;
  bool _fulfillNow = true;
  DateTime? _scheduledAt;
  DeliverySlotSelection? _slotSelection;
  String? _method;
  bool _paymentChosen = false;
  String? _stcPhone;
  final List<CouponQuote> _quotes = [];
  DeliveryQuote? _delivery;
  String? _deliveryError;
  String _deliveryKey = '';
  bool _applyingCoupon = false;
  String? _couponError;
  String _quotedCartKey = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      FocusManager.instance.primaryFocus?.unfocus();
      _refreshDelivery();
    });
  }

  @override
  void dispose() {
    _notesCtrl.dispose();
    _couponCtrl.dispose();
    super.dispose();
  }

  String _scheduleLabel() {
    if (_fulfillNow) return 'الآن';
    final slot = _slotSelection?.slot;
    final day = _slotSelection?.day;
    if (slot != null && day != null) {
      return '${day.label} · ${slot.timeRange} ${slot.periodLetter}';
    }
    if (_scheduledAt != null) {
      final d = _scheduledAt!;
      return '${d.day}/${d.month}  ${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}';
    }
    return 'وقت آخر';
  }

  Future<void> _pickSchedule() async {
    final result = await DeliverySlotsSheet.show(
      context,
      initial: _slotSelection ??
          (_fulfillNow
              ? const DeliverySlotSelection.now()
              : null),
    );
    if (!mounted || result == null) return;
    setState(() {
      _slotSelection = result;
      _fulfillNow = result.fulfillNow;
      _scheduledAt = result.scheduledAt;
    });
  }

  Future<void> _openNotesSheet() async {
    FocusManager.instance.primaryFocus?.unfocus();
    final notes = await NotesSheet.show(
      context,
      initialNotes: _notesCtrl.text,
    );
    if (!mounted || notes == null) return;
    setState(() => _notesCtrl.text = notes);
  }

  Future<void> _selectPayment(PaymentOption method) async {
    final kind = paymentKindFor(method.id);
    if (kind == PaymentKind.stcPay) {
      final phone = await StcPaySheet.show(
        context,
        initialPhone: _stcPhone,
      );
      if (!mounted || phone == null) return;
      setState(() {
        _method = method.id;
        _paymentChosen = true;
        _stcPhone = phone;
      });
      return;
    }
    if (kind == PaymentKind.card) {
      final ok = await CardPaymentSheet.show(
        context,
        title: method.label,
      );
      if (!mounted || ok != true) return;
      setState(() {
        _method = method.id;
        _paymentChosen = true;
      });
      return;
    }
    setState(() {
      _method = method.id;
      _paymentChosen = true;
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
    if (!_paymentChosen || _method == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('اختر طريقة دفع أولاً', textAlign: TextAlign.center),
      ));
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
    var notes = _notesCtrl.text.trim();
    if (_stcPhone != null &&
        paymentKindFor(_method!) == PaymentKind.stcPay) {
      final stcLine = 'STC Pay: $_stcPhone';
      notes = notes.isEmpty ? stcLine : '$notes\n$stcLine';
    }
    try {
      await context.read<OrdersCubit>().placeOrder(
            items: List.from(cartState.items),
            paymentMethod: _method!,
            addressId: address.id,
            notes: notes,
            couponCodes: _quotes.map((q) => q.code).toList(),
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
    final next = await showDialog<String>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const OrderSuccessDialog(),
    );
    if (!mounted) return;
    context.read<CartCubit>().clearCart();
    unawaited(context.read<OrdersCubit>().load());
    final nav = Navigator.of(context);
    nav.popUntil((r) => r.isFirst);
    if (next == 'orders') {
      nav.pushNamed(AppRouter.orders);
    }
  }

  String _cartKey(List<CartItem> items) =>
      items.map((i) => '${i.product.id}:${i.quantity}').join(',');

  Future<void> _applyCoupon(List<CartItem> items) async {
    FocusManager.instance.primaryFocus?.unfocus();
    final value = _couponCtrl.text.trim().toUpperCase();
    if (value.isEmpty) {
      setState(() => _couponError = 'أدخل كود الخصم أولاً');
      return;
    }
    if (_quotes.any((q) => q.code.toUpperCase() == value)) {
      setState(() => _couponError = 'هذا الكوبون مضاف مسبقاً');
      return;
    }
    setState(() {
      _applyingCoupon = true;
      _couponError = null;
    });
    try {
      final quote = await CouponsApi.instance.preview(
        code: value,
        items: items,
        addressId: context.read<AddressCubit>().state.selected?.id,
      );
      if (!mounted) return;
      setState(() {
        _quotes.removeWhere((q) => q.code.toUpperCase() == quote.code.toUpperCase());
        _quotes.add(quote);
        _quotedCartKey = _cartKey(items);
        _applyingCoupon = false;
        _couponError = null;
        _couponCtrl.clear();
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _applyingCoupon = false;
        _couponError = e.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
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

  void _removeCoupon(String code) {
    setState(() {
      _quotes.removeWhere((q) => q.code.toUpperCase() == code.toUpperCase());
      _couponError = null;
      if (_quotes.isEmpty) _quotedCartKey = '';
    });
  }

  @override
  Widget build(BuildContext context) {
    final store = context.watch<CatalogCubit>().state.store;
    final address = context.watch<AddressCubit>().state.selected;

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        resizeToAvoidBottomInset: false,
        backgroundColor: AppTheme.background,
        appBar: AppBar(
          backgroundColor: AppTheme.background,
          elevation: 0,
          toolbarHeight: 48,
          leadingWidth: 56,
          leading: Padding(
            padding: const EdgeInsetsDirectional.only(start: 12),
            child: Align(
              alignment: AlignmentDirectional.centerStart,
              child: _InvoiceBackButton(
                onTap: () => Navigator.of(context).pop(),
              ),
            ),
          ),
          title: const Text(
            'مراجعة الطلب',
            style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16.5),
          ),
          centerTitle: true,
        ),
        body: BlocBuilder<CartCubit, CartState>(
          builder: (context, cart) {
            final subtotal = cart.total;
            final quoteValid =
                _quotes.isNotEmpty && _quotedCartKey == _cartKey(cart.items);
            final quotes = quoteValid ? List<CouponQuote>.from(_quotes) : const <CouponQuote>[];
            final currentDeliveryKey = '${address?.id}|${_cartKey(cart.items)}';
            if (currentDeliveryKey != _deliveryKey) {
              WidgetsBinding.instance.addPostFrameCallback((_) {
                if (mounted) _refreshDelivery();
              });
            }
            final loggedIn = AuthSession.instance.isLoggedIn;
            final quotedShipping = quotes.isNotEmpty
                ? quotes.last.shippingFee
                : _delivery?.fee;
            final shipping = quotedShipping ??
                (!loggedIn && store.delivery.firstOrderFree
                    ? 0.0
                    : (store.freeShippingThreshold > 0 &&
                            subtotal >= store.freeShippingThreshold
                        ? 0.0
                        : store.shippingFee));
            final free = quotes.any((q) => q.hasFreeShipping || q.freeShipping) ||
                (_delivery?.isFree ?? shipping <= 0);
            final deliveryLabel = store.delivery.hideDeliverySubtitle
                ? null
                : (quotes.isNotEmpty
                        ? quotes.last.deliveryLabel
                        : null) ??
                    _delivery?.label ??
                    (!loggedIn && store.delivery.firstOrderFree
                        ? 'أول طلب مجاني'
                        : null);
            final deliveryNote = store.delivery.hideDeliverySubtitle
                ? null
                : () {
                    final fromQuote = quotes.isNotEmpty
                        ? quotes.last.deliveryNote
                        : null;
                    final fromDelivery = _delivery?.note;
                    final raw = (fromQuote ?? fromDelivery)?.trim();
                    if (raw != null && raw.isNotEmpty) return raw;
                    if (store.delivery.notesEnabled &&
                        store.delivery.generalNote.trim().isNotEmpty) {
                      return store.delivery.generalNote.trim();
                    }
                    return null;
                  }();
            final discount = quotes.fold<double>(
              0,
              (sum, q) => sum + q.discountAmount,
            ).clamp(0, subtotal).toDouble();
            final productDiscount = _cartProductDiscount(cart.items);
            final totalDiscount = productDiscount + discount;
            final listSubtotal = _cartListSubtotal(cart.items);
            final effectiveShipping = free ? 0.0 : shipping;
            final grand = subtotal - discount + effectiveShipping;
            final methods = store.paymentMethods;
            return Stack(
              children: [
                ListView(
                  padding: const EdgeInsets.fromLTRB(12, 2, 12, 96),
                  children: [
                      _InvoicePlainCard(
                        onTap: () => DeliveryAddressesSheet.show(context),
                        child: Row(
                          children: [
                            const Icon(
                              Icons.location_on_outlined,
                              size: 18,
                              color: AppTheme.primaryDark,
                            ),
                            const SizedBox(width: 8),
                            const Text(
                              'التوصيل إلى',
                              style: TextStyle(
                                fontWeight: FontWeight.w600,
                                fontSize: 14.5,
                                color: AppTheme.darkText,
                              ),
                            ),
                            const SizedBox(width: 6),
                            Expanded(
                              child: Text(
                                address?.label ?? 'اختر عنواناً',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w900,
                                  fontSize: 14.5,
                                  color: AppTheme.primaryDark,
                                ),
                              ),
                            ),
                            const Icon(
                              Icons.chevron_left_rounded,
                              size: 26,
                              color: AppTheme.mutedText,
                              textDirection: TextDirection.ltr,
                            ),
                          ],
                        ),
                      ),
                      _InvoiceCard(
                        icon: Icons.schedule_rounded,
                        title: 'فترة التوصيل',
                        child: Row(
                          children: [
                            Expanded(
                              child: _DeliveryOptionCard(
                                title: 'توصيل فوري',
                                subtitle: 'أقرب وقت',
                                icon: Icons.bolt_rounded,
                                selected: _fulfillNow,
                                onTap: () => setState(() {
                                  _fulfillNow = true;
                                  _scheduledAt = null;
                                  _slotSelection =
                                      const DeliverySlotSelection.now();
                                }),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: _DeliveryOptionCard(
                                title: 'جدولة الطلب',
                                subtitle: !_fulfillNow &&
                                        _scheduleLabel() != 'الآن'
                                    ? _scheduleLabel()
                                    : 'حدد الوقت',
                                icon: Icons.calendar_today_outlined,
                                selected: !_fulfillNow,
                                onTap: _pickSchedule,
                              ),
                            ),
                          ],
                        ),
                      ),
                      _InvoiceCard(
                        icon: Icons.edit_note_outlined,
                        title: 'الملاحظات',
                        child: _NotesEntryButton(
                          notes: _notesCtrl.text,
                          onTap: _openNotesSheet,
                        ),
                      ),
                      _InvoiceCard(
                        labelLeading: const CouponBadgeIcon(size: 17),
                        title: 'الخصومات والقسائم',
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            CouponSectionInput(
                              controller: _couponCtrl,
                              loading: _applyingCoupon,
                              onApply: () => _applyCoupon(cart.items),
                            ),
                            if (quotes.isNotEmpty) ...[
                              const SizedBox(height: 10),
                              Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                children: [
                                  for (final q in quotes)
                                    _CouponStickerChip(
                                      code: q.code,
                                      onCancel: () => _removeCoupon(q.code),
                                    ),
                                ],
                              ),
                            ],
                            if (_couponError != null) ...[
                              const SizedBox(height: 8),
                              Text(
                                _couponError!,
                                style: const TextStyle(
                                  color: Color(0xFFC62828),
                                  fontWeight: FontWeight.w500,
                                  fontSize: 11.5,
                                ),
                              ),
                            ],
                            if (_quotes.isNotEmpty && quotes.isEmpty) ...[
                              const SizedBox(height: 8),
                              const Text(
                                'تعدّلت السلة — أعد تفعيل الكوبونات',
                                style: TextStyle(
                                  color: Color(0xFFC77800),
                                  fontWeight: FontWeight.w500,
                                  fontSize: 11.5,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      _InvoiceCard(
                        icon: Icons.shopping_cart_outlined,
                        title: 'ملخص السلة (${paidCartProductCount(cart.items)} منتج)',
                        child: Column(
                          children: [
                            const _TableHead(),
                            const Divider(height: 20),
                            ListView.builder(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              padding: EdgeInsets.zero,
                              itemCount: groupedCartItems(cart.items).length,
                              itemBuilder: (_, i) {
                                final group = groupedCartItems(cart.items)[i];
                                return CartSummaryGroup(
                                  item: group.paid,
                                  gift: group.gift,
                                  tableLayout: true,
                                );
                              },
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
                                  '${(productDiscount > 0 ? listSubtotal : subtotal).toStringAsFixed(1)} ${store.currency}',
                              bold: true,
                            ),
                            if (productDiscount > 0) ...[
                              const SizedBox(height: 8),
                              _PayRow(
                                label: 'خصم المنتجات',
                                value:
                                    '- ${productDiscount.toStringAsFixed(1)} ${store.currency}',
                              ),
                            ],
                            const SizedBox(height: 10),
                            _PayRow(
                              label: 'التوصيل',
                              value: free
                                  ? 'مجاني'
                                  : '${shipping.toStringAsFixed(1)} ${store.currency}',
                              bold: true,
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
                                    fontWeight: FontWeight.w500,
                                    fontSize: 11,
                                  ),
                                ),
                              ),
                            ],
                            if (deliveryNote != null &&
                                deliveryNote.trim().isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Align(
                                alignment: Alignment.centerRight,
                                child: Text(
                                  deliveryNote,
                                  textAlign: TextAlign.right,
                                  style: const TextStyle(
                                    color: AppTheme.mutedText,
                                    fontWeight: FontWeight.w500,
                                    fontSize: 11,
                                    height: 1.35,
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
                                  fontWeight: FontWeight.w500,
                                  fontSize: 11.5,
                                ),
                              ),
                            ],
                            if (discount > 0) ...[
                              const SizedBox(height: 8),
                              for (final q in quotes)
                                if (q.discountAmount > 0) ...[
                                  _PayRow(
                                    label: 'خصم القسيمة (${q.code})',
                                    value:
                                        '- ${q.discountAmount.toStringAsFixed(1)} ${store.currency}',
                                  ),
                                  const SizedBox(height: 6),
                                ],
                            ],
                            if (totalDiscount > 0) ...[
                              const SizedBox(height: 8),
                              _PayRow(
                                label: 'إجمالي الخصم',
                                value:
                                    '- ${totalDiscount.toStringAsFixed(1)} ${store.currency}',
                                bold: true,
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
                        fillColor: AppTheme.background,
                        child: Column(
                          children: [
                            GridView.builder(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: store.paymentMethods.length,
                              gridDelegate:
                                  const SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 3,
                                mainAxisSpacing: 8,
                                crossAxisSpacing: 8,
                                childAspectRatio: 1.32,
                              ),
                              itemBuilder: (context, index) {
                                final method = store.paymentMethods[index];
                                final selected =
                                    _paymentChosen && _method == method.id;
                                final subtitle = method.id == 'stc_pay' &&
                                        selected &&
                                        (_stcPhone?.isNotEmpty ?? false)
                                    ? 'الجوال: $_stcPhone'
                                    : '';
                                return _PaymentTile(
                                  method: method,
                                  selected: selected,
                                  subtitle: subtitle,
                                  onTap: () => _selectPayment(method),
                                );
                              },
                            ),
                            if (_paymentChosen &&
                                _method == 'bank_transfer' &&
                                store.bankIban.isNotEmpty)
                              Padding(
                                padding: const EdgeInsets.only(top: 8),
                                child: Text(
                                  '${store.bankName}\n${store.bankIban}',
                                  textDirection: TextDirection.ltr,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w500,
                                    fontSize: 11.5,
                                    height: 1.5,
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ],
                  ),
                Positioned(
                  left: 0,
                  right: 0,
                  bottom: 0,
                  child: CheckoutActionBar(
                    label:
                        _paymentChosen ? 'تنفيذ الطلب' : 'اختر طريقة دفع',
                    total: grand,
                    originalTotal:
                        totalDiscount > 0 ? grand + totalDiscount : null,
                    discount: totalDiscount,
                    glass: !_paymentChosen,
                    loading: _paying,
                    enabled: _paymentChosen &&
                        _deliveryError == null &&
                        !_paying,
                    onTap: _submit,
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

double _cartListSubtotal(List<CartItem> items) => items.fold(
      0.0,
      (sum, item) => sum + item.product.price * item.quantity,
    );

double _cartProductDiscount(List<CartItem> items) => items.fold(
      0.0,
      (sum, item) {
        if (!item.product.hasDiscount) return sum;
        return sum +
            (item.product.price - item.product.effectivePrice) * item.quantity;
      },
    );

class _InvoiceBackButton extends StatelessWidget {
  final VoidCallback onTap;

  const _InvoiceBackButton({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: Container(
          width: 30,
          height: 30,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(color: const Color(0x14000000), width: 0.5),
          ),
          child: const Icon(
            Icons.chevron_right_rounded,
            size: 22,
            color: AppTheme.darkText,
            textDirection: TextDirection.ltr,
          ),
        ),
      ),
    );
  }
}

class _InvoicePlainCard extends StatelessWidget {
  final Widget child;
  final VoidCallback? onTap;

  const _InvoicePlainCard({
    required this.child,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final content = Padding(
      padding: const EdgeInsets.fromLTRB(10, 10, 8, 10),
      child: child,
    );

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE6EEE8)),
      ),
      child: onTap == null
          ? content
          : InkWell(
              onTap: onTap,
              borderRadius: BorderRadius.circular(14),
              child: content,
            ),
    );
  }
}

class _InvoiceCard extends StatelessWidget {
  final IconData? icon;
  final Widget? labelLeading;
  final String? title;
  final Widget child;
  final VoidCallback? onTap;
  final Color fillColor;

  const _InvoiceCard({
    this.icon,
    this.labelLeading,
    this.title,
    required this.child,
    this.onTap,
    this.fillColor = Colors.white,
  });

  InputDecoration _fieldDecoration({required Widget label}) => InputDecoration(
        label: label,
        floatingLabelBehavior: FloatingLabelBehavior.always,
        floatingLabelAlignment: FloatingLabelAlignment.start,
        alignLabelWithHint: true,
        filled: true,
        fillColor: fillColor,
        isDense: true,
        contentPadding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
        labelStyle: const TextStyle(
          fontWeight: FontWeight.w900,
          fontSize: 15.5,
          color: AppTheme.darkText,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFE4EDE6)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFE4EDE6)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(
            color: AppTheme.primaryLight,
            width: 1.2,
          ),
        ),
      );

  Widget _buildLabel() {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (labelLeading != null) ...[
          labelLeading!,
          const SizedBox(width: 5),
        ] else if (icon != null) ...[
          Icon(icon, color: AppTheme.primaryDark, size: 17),
          const SizedBox(width: 5),
        ],
        if (title != null && title!.isNotEmpty)
          Text(
            title!,
            style: const TextStyle(
              fontWeight: FontWeight.w900,
              fontSize: 15.5,
              color: AppTheme.darkText,
            ),
          ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final hasLabel = labelLeading != null ||
        icon != null ||
        (title != null && title!.isNotEmpty);

    final content = InputDecorator(
      decoration: hasLabel
          ? _fieldDecoration(label: _buildLabel())
          : const InputDecoration(
              border: InputBorder.none,
              contentPadding: EdgeInsets.zero,
              isCollapsed: true,
            ),
      child: hasLabel
          ? Padding(
              padding: const EdgeInsets.only(top: 6),
              child: child,
            )
          : child,
    );

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Colors.transparent,
        child: onTap == null
            ? content
            : InkWell(
                onTap: onTap,
                borderRadius: BorderRadius.circular(12),
                child: content,
              ),
      ),
    );
  }
}

class _CouponStickerChip extends StatelessWidget {
  final String code;
  final VoidCallback onCancel;

  const _CouponStickerChip({
    required this.code,
    required this.onCancel,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: const Color(0xFFFFF8E7),
      borderRadius: BorderRadius.circular(8),
      elevation: 0,
      child: Container(
        padding: const EdgeInsetsDirectional.only(
          start: 8,
          end: 2,
          top: 5,
          bottom: 5,
        ),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: const Color(0xFFE0C98A),
            width: 1,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const CouponBadgeIcon(size: 16, color: Color(0xFFC9A227)),
            const SizedBox(width: 6),
            Text(
              code,
              style: const TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w800,
                color: AppTheme.darkText,
                height: 1.1,
              ),
            ),
            const SizedBox(width: 2),
            InkWell(
              onTap: onCancel,
              borderRadius: BorderRadius.circular(14),
              child: const Padding(
                padding: EdgeInsets.all(4),
                child: Icon(
                  Icons.close_rounded,
                  size: 16,
                  color: AppTheme.mutedText,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DeliveryOptionCard extends StatelessWidget {
  final String title;
  final String subtitle;
  final IconData icon;
  final String? badge;
  final bool selected;
  final VoidCallback onTap;

  const _DeliveryOptionCard({
    required this.title,
    required this.subtitle,
    required this.icon,
    this.badge,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          padding: const EdgeInsets.fromLTRB(8, 7, 8, 7),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: selected
                  ? AppTheme.primaryDark
                  : const Color(0xFFE4EDE6),
              width: selected ? 1.3 : 1,
            ),
            color: selected
                ? AppTheme.primarySurface.withValues(alpha: 0.35)
                : Colors.white,
          ),
          child: Row(
            children: [
              Icon(icon, size: 16, color: AppTheme.primaryDark),
              const SizedBox(width: 6),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontWeight:
                            selected ? FontWeight.w900 : FontWeight.w700,
                        fontSize: 11.5,
                        height: 1.15,
                        color: AppTheme.darkText,
                      ),
                    ),
                    Text(
                      subtitle,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontWeight: FontWeight.w500,
                        fontSize: 9.5,
                        height: 1.2,
                        color: selected
                            ? AppTheme.primaryDark
                            : AppTheme.mutedText,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 4),
              Icon(
                selected
                    ? Icons.radio_button_checked_rounded
                    : Icons.radio_button_off_rounded,
                size: 15,
                color: selected ? AppTheme.primaryDark : AppTheme.mutedText,
              ),
            ],
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
      fontSize: 11,
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

class _PayRow extends StatelessWidget {
  final String label;
  final String value;
  final bool emphasize;
  final bool bold;

  const _PayRow({
    required this.label,
    required this.value,
    this.emphasize = false,
    this.bold = false,
  });

  @override
  Widget build(BuildContext context) {
    final labelWeight = emphasize
        ? FontWeight.w800
        : bold
            ? FontWeight.w800
            : FontWeight.w500;
    final valueWeight = emphasize
        ? FontWeight.w800
        : bold
            ? FontWeight.w800
            : FontWeight.w500;

    return Row(
      children: [
        Text(
          label,
          style: TextStyle(
            fontWeight: labelWeight,
            fontSize: emphasize ? 13.5 : bold ? 12.5 : 11.5,
            color: AppTheme.darkText,
          ),
        ),
        const Spacer(),
        Text(
          value,
          style: TextStyle(
            fontWeight: valueWeight,
            color: emphasize ? AppTheme.primaryDark : AppTheme.darkText,
            fontSize: emphasize ? 14.5 : bold ? 12.5 : 11.5,
          ),
        ),
      ],
    );
  }
}

class _PaymentTile extends StatelessWidget {
  final PaymentOption method;
  final bool selected;
  final String subtitle;
  final VoidCallback onTap;

  const _PaymentTile({
    required this.method,
    required this.selected,
    required this.onTap,
    this.subtitle = '',
  });

  @override
  Widget build(BuildContext context) {
    const radius = BorderRadius.all(Radius.circular(10));
    final isCash = method.id == 'cash';
    final logoWidth = isCash ? 54.0 : 46.0;
    final logoHeight = isCash ? 30.0 : 24.0;

    return AnimatedContainer(
      duration: const Duration(milliseconds: 180),
      decoration: BoxDecoration(
        color: selected ? AppTheme.primarySurface : Colors.white,
        borderRadius: radius,
        border: Border.all(
          color: selected ? AppTheme.primaryDark : const Color(0xFFE4EDE6),
          width: selected ? 1.4 : 1,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: radius,
        child: InkWell(
          onTap: onTap,
          borderRadius: radius,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(4, 3, 4, 7),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                    child: Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(7),
                        border: Border.all(
                          color: selected
                              ? AppTheme.primaryLight
                              : const Color(0xFFEEF3EF),
                        ),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 4,
                        vertical: 3,
                      ),
                      child: Stack(
                        children: [
                          Center(
                            child: PaymentMethodLogo(
                              method: method,
                              width: logoWidth,
                              height: logoHeight,
                              fit: BoxFit.contain,
                            ),
                          ),
                          if (selected)
                            const PositionedDirectional(
                              top: 0,
                              end: 0,
                              child: Icon(
                                Icons.check_circle_rounded,
                                size: 11,
                                color: AppTheme.primaryDark,
                              ),
                            ),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 7),
                Text(
                  method.label,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                    fontSize: 8.5,
                    height: 1,
                    color: selected ? AppTheme.primaryDark : AppTheme.darkText,
                  ),
                ),
                if (subtitle.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    textAlign: TextAlign.center,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 7,
                      color: AppTheme.mutedText,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
                const SizedBox(height: 5),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _NotesEntryButton extends StatelessWidget {
  final String notes;
  final VoidCallback onTap;

  const _NotesEntryButton({
    required this.notes,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final hasNotes = notes.trim().isNotEmpty;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: CustomPaint(
          painter: _DashedRRectPainter(
            color: hasNotes
                ? AppTheme.primaryDark.withValues(alpha: 0.4)
                : const Color(0xFFC9D5CD),
            radius: 12,
          ),
          child: Container(
            padding: const EdgeInsets.fromLTRB(10, 10, 8, 10),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              color: hasNotes
                  ? AppTheme.primarySurface.withValues(alpha: 0.45)
                  : Colors.white,
            ),
            child: Row(
              children: [
                Expanded(
                  child: hasNotes
                      ? Text(
                          notes,
                          maxLines: 3,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontWeight: FontWeight.w500,
                            fontSize: 12,
                            color: AppTheme.bodyText,
                            height: 1.35,
                          ),
                        )
                      : const Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'أضف ملاحظات للطلب',
                              style: TextStyle(
                                fontWeight: FontWeight.w600,
                                fontSize: 12,
                                color: AppTheme.darkText,
                              ),
                            ),
                            SizedBox(height: 4),
                            Text(
                              'اضغط لكتابة تعليمات التوصيل أو الطلب',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontWeight: FontWeight.w500,
                                fontSize: 11,
                                color: AppTheme.mutedText,
                                height: 1.25,
                              ),
                            ),
                          ],
                        ),
                ),
                const Icon(
                  Icons.chevron_left_rounded,
                  size: 26,
                  color: AppTheme.mutedText,
                  textDirection: TextDirection.ltr,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DashedRRectPainter extends CustomPainter {
  final Color color;
  final double radius;
  final double strokeWidth;
  final double dash;
  final double gap;

  const _DashedRRectPainter({
    required this.color,
    required this.radius,
    this.strokeWidth = 1.2,
    this.dash = 5,
    this.gap = 3.5,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth;
    final rrect = RRect.fromRectAndRadius(
      Offset.zero & size,
      Radius.circular(radius),
    );
    final path = Path()..addRRect(rrect);
    for (final metric in path.computeMetrics()) {
      var distance = 0.0;
      while (distance < metric.length) {
        final next = distance + dash;
        canvas.drawPath(
          metric.extractPath(distance, next.clamp(0, metric.length)),
          paint,
        );
        distance = next + gap;
      }
    }
  }

  @override
  bool shouldRepaint(covariant _DashedRRectPainter oldDelegate) =>
      oldDelegate.color != color ||
      oldDelegate.radius != radius ||
      oldDelegate.strokeWidth != strokeWidth;
}

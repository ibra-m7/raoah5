import 'dart:async';

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/constants/app_strings.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/brand_logo.dart';
import '../../../core/widgets/courier_ui.dart';
import '../../auth/data/courier_auth_api.dart';
import '../data/courier_order.dart';
import '../data/courier_orders_api.dart';

class OrderDetailsScreen extends StatefulWidget {
  final String orderId;

  const OrderDetailsScreen({super.key, required this.orderId});

  @override
  State<OrderDetailsScreen> createState() => _OrderDetailsScreenState();
}

class _OrderDetailsScreenState extends State<OrderDetailsScreen> {
  CourierOrder? _order;
  String? _error;
  bool _loading = true;
  bool _busy = false;
  Timer? _poll;

  @override
  void initState() {
    super.initState();
    _load();
    _poll = Timer.periodic(const Duration(seconds: 8), (_) => _load(silent: true));
  }

  @override
  void dispose() {
    _poll?.cancel();
    super.dispose();
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final order = await CourierOrdersApi.instance.show(widget.orderId);
      if (!mounted) return;
      setState(() {
        _order = order;
        _loading = false;
      });
    } catch (error) {
      if (!mounted || silent) return;
      setState(() {
        _loading = false;
        _error = error is ApiException ? error.message : error.toString();
      });
    }
  }

  Future<void> _run(Future<CourierOrder> Function() action, String success) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      final order = await action();
      try {
        await CourierAuthApi.instance.me();
      } catch (_) {}
      if (!mounted) return;
      setState(() => _order = order);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(success)));
    } catch (error) {
      if (!mounted) return;
      final message = error is ApiException ? error.message : error.toString();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _toast(String text) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
  }

  Future<void> _callCustomer(CourierOrder order) async {
    final digits = order.phoneDigits;
    if (digits.isEmpty) {
      _toast(AppStrings.noCustomerPhone);
      return;
    }
    final uri = Uri(scheme: 'tel', path: digits);
    if (!await canLaunchUrl(uri) ||
        !await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (!mounted) return;
      _toast(AppStrings.cannotOpenDialer);
    }
  }

  Future<void> _openCustomerAddress(CourierOrder order) async {
    final url = (order.mapsUrl ?? '').trim().isNotEmpty
        ? order.mapsUrl!
        : (order.addressLine.isEmpty
            ? ''
            : 'https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(order.addressLine)}');
    if (url.isEmpty) {
      _toast(AppStrings.cannotOpenMap);
      return;
    }
    final uri = Uri.parse(url);
    if (!await canLaunchUrl(uri) ||
        !await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (!mounted) return;
      _toast(AppStrings.cannotOpenMap);
    }
  }

  @override
  Widget build(BuildContext context) {
    final order = _order;
    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(
        centerTitle: true,
        title: Text(
          AppStrings.orderDetails,
          style: GoogleFonts.cairo(
            fontWeight: FontWeight.w800,
            color: AppTheme.primaryDark,
          ),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: TextButton(onPressed: _load, child: Text(_error!)),
                )
              : order == null
                  ? const SizedBox.shrink()
                  : Column(
                      children: [
                        Expanded(
                          child: ListView(
                            padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                            children: [
                              _OrderSheet(order: order),
                              const SizedBox(height: 12),
                              _MetaCard(order: order),
                              const SizedBox(height: 14),
                              Row(
                                children: [
                                  Expanded(
                                    child: CourierActionButton(
                                      label: AppStrings.customerAddress,
                                      icon: Icons.location_on_rounded,
                                      onTap: () => _openCustomerAddress(order),
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: CourierActionButton(
                                      label: AppStrings.callCustomer,
                                      icon: Icons.phone_rounded,
                                      onTap: () => _callCustomer(order),
                                    ),
                                  ),
                                ],
                              ),
                              if (order.canAccept || order.canPickup || order.canDeliver) ...[
                                const SizedBox(height: 12),
                                if (order.canAccept)
                                  OutlinedButton(
                                    onPressed: _busy
                                        ? null
                                        : () => _run(
                                              () => CourierOrdersApi.instance.accept(order.id),
                                              'تم قبول الطلب',
                                            ),
                                    child: const Text(AppStrings.acceptOrder),
                                  ),
                                if (order.canPickup) ...[
                                  if (order.canAccept) const SizedBox(height: 8),
                                  ElevatedButton(
                                    onPressed: _busy
                                        ? null
                                        : () => _run(
                                              () => CourierOrdersApi.instance.pickup(order.id),
                                              'الطلب في الطريق إليك',
                                            ),
                                    child: const Text(AppStrings.pickupOrder),
                                  ),
                                ],
                                if (order.canDeliver) ...[
                                  if (order.canAccept || order.canPickup) const SizedBox(height: 8),
                                  ElevatedButton(
                                    onPressed: _busy
                                        ? null
                                        : () => _run(
                                              () => CourierOrdersApi.instance.deliver(order.id),
                                              'تم تسليم الطلب',
                                            ),
                                    child: const Text(AppStrings.markDelivered),
                                  ),
                                ],
                              ],
                              const SizedBox(height: 8),
                            ],
                          ),
                        ),
                        _StatusBar(order: order),
                      ],
                    ),
    );
  }
}

class _OrderSheet extends StatelessWidget {
  final CourierOrder order;

  const _OrderSheet({required this.order});

  @override
  Widget build(BuildContext context) {
    final shopTotal = order.subtotal > 0 ? order.subtotal : (order.total - order.shippingFee);
    final city = (order.shippingCity ?? '').trim();

    return CourierPanel(
      color: AppTheme.primarySurface,
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
      child: Column(
        children: [
          Row(
            children: [
              const BrandLogoTile(size: 58),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${AppStrings.orderNumber} ${order.orderNumber}',
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.w900,
                        fontSize: 17,
                        color: AppTheme.darkText,
                      ),
                    ),
                    Text(
                      AppStrings.appName,
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.w700,
                        color: AppTheme.bodyText,
                      ),
                    ),
                    if (city.isNotEmpty)
                      Text(
                        city,
                        style: GoogleFonts.cairo(
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                          color: AppTheme.mutedText,
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Divider(color: AppTheme.primary, thickness: 1.2),
          ),
          for (final item in order.items) _ItemRow(item: item),
          const Padding(
            padding: EdgeInsets.only(top: 4, bottom: 12),
            child: Divider(color: AppTheme.primary, thickness: 1.2),
          ),
          _TotalLine(label: AppStrings.shopTotal, amount: shopTotal),
          const SizedBox(height: 8),
          _TotalLine(label: AppStrings.deliveryFee, amount: order.shippingFee),
          const SizedBox(height: 10),
          Row(
            children: [
              Text(
                AppStrings.grandTotal,
                style: GoogleFonts.cairo(
                  fontWeight: FontWeight.w900,
                  fontSize: 16,
                  color: AppTheme.darkText,
                ),
              ),
              const Spacer(),
              MoneyText(order.total, size: 18, color: AppTheme.darkText),
            ],
          ),
        ],
      ),
    );
  }
}

class _ItemRow extends StatelessWidget {
  final CourierOrderItem item;

  const _ItemRow({required this.item});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Container(
            constraints: const BoxConstraints(minWidth: 36),
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              '${item.quantity}',
              textAlign: TextAlign.center,
              style: GoogleFonts.cairo(
                fontWeight: FontWeight.w900,
                color: AppTheme.primaryDark,
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              item.name,
              style: GoogleFonts.cairo(
                fontWeight: FontWeight.w700,
                color: AppTheme.darkText,
              ),
            ),
          ),
          MoneyText(item.lineTotal, size: 14, color: AppTheme.bodyText),
        ],
      ),
    );
  }
}

class _TotalLine extends StatelessWidget {
  final String label;
  final double amount;

  const _TotalLine({required this.label, required this.amount});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          label,
          style: GoogleFonts.cairo(
            fontWeight: FontWeight.w600,
            color: AppTheme.mutedText,
          ),
        ),
        const Spacer(),
        MoneyText(amount, size: 14, weight: FontWeight.w700, color: AppTheme.bodyText),
      ],
    );
  }
}

class _MetaCard extends StatelessWidget {
  final CourierOrder order;

  const _MetaCard({required this.order});

  @override
  Widget build(BuildContext context) {
    return CourierPanel(
      child: Column(
        children: [
          if (order.streetLine.isNotEmpty)
            DetailInfoRow(icon: Icons.place_rounded, text: order.streetLine),
          if (order.detailsLine.isNotEmpty)
            DetailInfoRow(icon: Icons.apartment_rounded, text: order.detailsLine),
          if ((order.notes ?? '').trim().isNotEmpty)
            DetailInfoRow(
              icon: Icons.sticky_note_2_rounded,
              text: order.notes!.trim(),
              textColor: AppTheme.primaryDark,
              bold: true,
            ),
          DetailInfoRow(
            icon: Icons.account_balance_wallet_rounded,
            text: order.paymentMethodLabel.isEmpty
                ? AppStrings.cashOnDelivery
                : order.paymentMethodLabel,
          ),
          if (order.formattedTime.isNotEmpty)
            DetailInfoRow(icon: Icons.schedule_rounded, text: order.formattedTime),
        ],
      ),
    );
  }
}

class _StatusBar extends StatelessWidget {
  final CourierOrder order;

  const _StatusBar({required this.order});

  @override
  Widget build(BuildContext context) {
    final delivered = order.status == 'delivered';
    final onTheWay = order.status == 'on_the_way';
    final preparing = order.status == 'preparing';
    final color = delivered
        ? const Color(0xFF2E7D32)
        : onTheWay
            ? AppTheme.primaryDark
            : preparing
                ? const Color(0xFFE65100)
                : const Color(0xFF1565C0);
    final label = delivered
        ? AppStrings.orderDelivered
        : onTheWay
            ? AppStrings.orderOnTheWay
            : preparing
                ? AppStrings.orderPreparing
                : AppStrings.newOrder;

    return Container(
      width: double.infinity,
      color: color,
      padding: EdgeInsets.fromLTRB(16, 14, 16, 14 + MediaQuery.paddingOf(context).bottom),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            delivered ? Icons.check_circle_rounded : Icons.local_shipping_rounded,
            color: Colors.white,
          ),
          const SizedBox(width: 8),
          Text(
            label,
            style: GoogleFonts.cairo(
              color: Colors.white,
              fontWeight: FontWeight.w800,
              fontSize: 16,
            ),
          ),
        ],
      ),
    );
  }
}

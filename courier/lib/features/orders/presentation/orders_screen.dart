import 'dart:async';

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../core/constants/app_strings.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/brand_logo.dart';
import '../../../core/widgets/courier_ui.dart';
import '../../auth/data/courier_auth_api.dart';
import '../../auth/data/courier_session.dart';
import '../data/courier_order.dart';
import '../data/courier_orders_api.dart';
import 'order_details_screen.dart';

class OrdersScreen extends StatefulWidget {
  final int generation;

  const OrdersScreen({super.key, this.generation = 0});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  int _tab = 0;
  bool _loading = true;
  String? _error;
  List<CourierOrder> _current = const [];
  List<CourierOrder> _previous = const [];
  Timer? _poll;

  @override
  void initState() {
    super.initState();
    _reload();
    _poll = Timer.periodic(const Duration(seconds: 8), (_) => _reload(silent: true));
  }

  @override
  void dispose() {
    _poll?.cancel();
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant OrdersScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.generation != widget.generation) {
      _reload();
    }
  }

  Future<void> _reload({bool silent = false}) async {
    if (!silent) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final current = await CourierOrdersApi.instance.current();
      final previous = await CourierOrdersApi.instance.previous();
      if (!mounted) return;
      setState(() {
        _current = current;
        _previous = previous;
        _loading = false;
        _error = null;
      });
    } catch (error) {
      if (!mounted || silent) return;
      setState(() {
        _loading = false;
        _error = error is ApiException ? error.message : error.toString();
      });
    }
  }

  Future<void> _openDetails(CourierOrder order) async {
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => OrderDetailsScreen(orderId: order.id)),
    );
    await _reload();
  }

  Future<void> _accept(CourierOrder order) async {
    try {
      await CourierOrdersApi.instance.accept(order.id);
      try {
        await CourierAuthApi.instance.me();
      } catch (_) {}
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تم قبول الطلب')),
      );
      await _reload();
    } catch (error) {
      if (!mounted) return;
      final message = error is ApiException ? error.message : error.toString();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final items = _tab == 0 ? _current : _previous;
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
          child: Row(
            children: [
              _TabChip(
                label: AppStrings.currentOrders,
                selected: _tab == 0,
                count: _current.length,
                onTap: () => setState(() => _tab = 0),
              ),
              const SizedBox(width: 8),
              _TabChip(
                label: AppStrings.previousOrders,
                selected: _tab == 1,
                count: _previous.length,
                onTap: () => setState(() => _tab = 1),
              ),
            ],
          ),
        ),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                  ? _Message(
                      text: _error!,
                      action: AppStrings.retry,
                      onAction: _reload,
                    )
                  : items.isEmpty
                      ? _Message(
                          text: _tab == 0
                              ? ((CourierSession.instance.user?.isOnline ?? false)
                                  ? AppStrings.noCurrentOrders
                                  : AppStrings.goOnlineToReceive)
                              : AppStrings.noPreviousOrders,
                        )
                      : RefreshIndicator(
                          onRefresh: _reload,
                          child: ListView.separated(
                            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                            itemCount: items.length,
                            separatorBuilder: (_, index) => const SizedBox(height: 12),
                            itemBuilder: (context, index) {
                              final order = items[index];
                              return _OrderCard(
                                order: order,
                                onDetails: () => _openDetails(order),
                                onAccept: order.canAccept ? () => _accept(order) : null,
                              );
                            },
                          ),
                        ),
        ),
      ],
    );
  }
}

class _TabChip extends StatelessWidget {
  final String label;
  final bool selected;
  final int count;
  final VoidCallback onTap;

  const _TabChip({
    required this.label,
    required this.selected,
    required this.count,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Material(
        color: selected ? AppTheme.primary : Colors.white,
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(18),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Flexible(
                  child: Text(
                    label,
                    textAlign: TextAlign.center,
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.cairo(
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                      color: selected ? Colors.white : AppTheme.mutedText,
                    ),
                  ),
                ),
                const SizedBox(width: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                  decoration: BoxDecoration(
                    color: selected
                        ? Colors.white.withValues(alpha: 0.22)
                        : AppTheme.primarySurface,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '$count',
                    style: GoogleFonts.cairo(
                      fontWeight: FontWeight.w900,
                      fontSize: 12,
                      color: selected ? Colors.white : AppTheme.primaryDark,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  final CourierOrder order;
  final VoidCallback onDetails;
  final VoidCallback? onAccept;

  const _OrderCard({
    required this.order,
    required this.onDetails,
    this.onAccept,
  });

  Color get _statusColor {
    return switch (order.status) {
      'pending' => const Color(0xFF1565C0),
      'preparing' => const Color(0xFF6B8A76),
      'on_the_way' => AppTheme.primaryDark,
      'delivered' => const Color(0xFF2E7D32),
      _ => AppTheme.mutedText,
    };
  }

  @override
  Widget build(BuildContext context) {
    return CourierPanel(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 12),
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const BrandLogoTile(size: 62),
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
                    const SizedBox(height: 2),
                    Text(
                      AppStrings.appName,
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                        color: AppTheme.bodyText,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: const Color(0xFFF3F4F6),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  order.statusLabel,
                  style: GoogleFonts.cairo(
                    fontWeight: FontWeight.w800,
                    fontSize: 11,
                    color: _statusColor,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          if (onAccept != null)
            OutlinedButton(
              onPressed: onAccept,
              child: const Text(AppStrings.acceptOrder),
            )
          else
            OutlinedButton(
              onPressed: onDetails,
              child: const Text(AppStrings.viewDetails),
            ),
        ],
      ),
    );
  }
}

class _Message extends StatelessWidget {
  final String text;
  final String? action;
  final VoidCallback? onAction;

  const _Message({required this.text, this.action, this.onAction});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 88,
              height: 88,
              decoration: BoxDecoration(
                color: AppTheme.primarySurface,
                borderRadius: BorderRadius.circular(28),
              ),
              child: Icon(Icons.inbox_outlined, size: 42, color: AppTheme.primaryDark),
            ),
            const SizedBox(height: 16),
            Text(
              text,
              textAlign: TextAlign.center,
              style: GoogleFonts.cairo(
                fontWeight: FontWeight.w700,
                color: AppTheme.mutedText,
              ),
            ),
            if (action != null && onAction != null) ...[
              const SizedBox(height: 16),
              TextButton(onPressed: onAction, child: Text(action!)),
            ],
          ],
        ),
      ),
    );
  }
}

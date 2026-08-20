import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../auth/data/services/auth_session.dart';
import '../../../auth/presentation/auth_flow.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../domain/entities/cart_item.dart';
import '../../domain/entities/order_entity.dart';
import '../manager/orders_cubit.dart';

const _kBg = Color(0xFFEDF9F2);
const _kSurface = Color(0xFFFFFFFF);
const _kDark = Color(0xFF27AE60);
const _kText = Color(0xFF1B3A2D);
const _kSubtext = Color(0xFF6B8A76);
const _kBorder = Color(0xFFB8F0D0);

const _kTrackSteps = [
  OrderStatus.pending,
  OrderStatus.preparing,
  OrderStatus.onTheWay,
  OrderStatus.delivered,
];

enum _OrdersFilter { all, active, delivered, cancelled }

class OrdersScreen extends StatefulWidget {
  static const routeName = '/orders';
  const OrdersScreen({super.key});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  _OrdersFilter _filter = _OrdersFilter.all;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted || !AuthSession.instance.isLoggedIn) return;
      context.read<OrdersCubit>().load();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: _kBg,
        appBar: AppBar(
          backgroundColor: _kBg,
          foregroundColor: _kText,
          elevation: 0,
          title: const Text(
            'طلباتي',
            style: TextStyle(fontWeight: FontWeight.w900, color: _kText),
          ),
          actions: [
            BlocBuilder<OrdersCubit, OrdersState>(
              builder: (_, state) {
                if (state.orders.isEmpty) return const SizedBox.shrink();
                final count =
                    _applyFilter(state.orders, _filter).length;
                return Padding(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 12, vertical: 12),
                  child: Center(
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: _kDark.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        _ordersCountLabel(count),
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: _kDark,
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          ],
        ),
        body: !AuthSession.instance.isLoggedIn
            ? const _GuestOrders()
            : BlocBuilder<OrdersCubit, OrdersState>(
                builder: (context, state) {
                  if (state.loading && state.orders.isEmpty) {
                    return const Center(
                      child: CircularProgressIndicator(color: _kDark),
                    );
                  }
                  if (state.error != null && state.orders.isEmpty) {
                    return _ErrorOrders(
                      message: state.error!,
                      onRetry: () => context.read<OrdersCubit>().load(),
                    );
                  }
                  if (state.orders.isEmpty) {
                    return const _EmptyOrders();
                  }

                  final sorted = [...state.orders]
                    ..sort((a, b) => b.createdAt.compareTo(a.createdAt));
                  final filtered = _applyFilter(sorted, _filter);
                  final counts = _filterCounts(sorted);
                  final entries = _groupByDate(filtered);

                  return Column(
                    children: [
                      _OrdersFilterBar(
                        filter: _filter,
                        counts: counts,
                        onChanged: (value) => setState(() => _filter = value),
                      ),
                      Expanded(
                        child: filtered.isEmpty
                            ? _EmptyFilter(filter: _filter)
                            : RefreshIndicator(
                                color: _kDark,
                                onRefresh: () =>
                                    context.read<OrdersCubit>().load(),
                                child: ListView.builder(
                                  physics: const AlwaysScrollableScrollPhysics(
                                    parent: BouncingScrollPhysics(),
                                  ),
                                  padding:
                                      const EdgeInsets.fromLTRB(16, 4, 16, 32),
                                  itemCount: entries.length,
                                  itemBuilder: (_, i) {
                                    final entry = entries[i];
                                    if (entry.header != null) {
                                      return _DateHeader(title: entry.header!);
                                    }
                                    return _OrderCard(order: entry.order!);
                                  },
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

  static List<OrderEntity> _applyFilter(
    List<OrderEntity> orders,
    _OrdersFilter filter,
  ) {
    return switch (filter) {
      _OrdersFilter.all => orders,
      _OrdersFilter.active => orders
          .where((o) =>
              o.status == OrderStatus.pending ||
              o.status == OrderStatus.preparing ||
              o.status == OrderStatus.onTheWay)
          .toList(),
      _OrdersFilter.delivered =>
        orders.where((o) => o.status == OrderStatus.delivered).toList(),
      _OrdersFilter.cancelled =>
        orders.where((o) => o.status == OrderStatus.cancelled).toList(),
    };
  }

  static String _ordersCountLabel(int count) {
    if (count == 1) return 'طلب واحد';
    if (count == 2) return 'طلبان';
    if (count >= 3 && count <= 10) return '$count طلبات';
    return '$count طلب';
  }

  static Map<_OrdersFilter, int> _filterCounts(List<OrderEntity> orders) {
    return {
      _OrdersFilter.all: orders.length,
      _OrdersFilter.active: orders
          .where((o) =>
              o.status == OrderStatus.pending ||
              o.status == OrderStatus.preparing ||
              o.status == OrderStatus.onTheWay)
          .length,
      _OrdersFilter.delivered:
          orders.where((o) => o.status == OrderStatus.delivered).length,
      _OrdersFilter.cancelled:
          orders.where((o) => o.status == OrderStatus.cancelled).length,
    };
  }

  static List<_ListEntry> _groupByDate(List<OrderEntity> orders) {
    final entries = <_ListEntry>[];
    String? lastHeader;
    for (final order in orders) {
      final header = _dateGroup(order.createdAt);
      if (header != lastHeader) {
        entries.add(_ListEntry.header(header));
        lastHeader = header;
      }
      entries.add(_ListEntry.order(order));
    }
    return entries;
  }

  static String _dateGroup(DateTime dt) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final date = DateTime(dt.year, dt.month, dt.day);
    final diff = today.difference(date).inDays;
    if (diff == 0) return 'اليوم';
    if (diff == 1) return 'أمس';
    if (diff < 7) return 'هذا الأسبوع';
    if (date.year == today.year && date.month == today.month) {
      return 'هذا الشهر';
    }
    return 'طلبات سابقة';
  }
}

class _ListEntry {
  final String? header;
  final OrderEntity? order;

  const _ListEntry.header(this.header) : order = null;
  const _ListEntry.order(this.order) : header = null;
}

class _OrdersFilterBar extends StatelessWidget {
  final _OrdersFilter filter;
  final Map<_OrdersFilter, int> counts;
  final ValueChanged<_OrdersFilter> onChanged;

  const _OrdersFilterBar({
    required this.filter,
    required this.counts,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    const items = <_OrdersFilter, String>{
      _OrdersFilter.all: 'الكل',
      _OrdersFilter.active: 'جارية',
      _OrdersFilter.delivered: 'مكتملة',
      _OrdersFilter.cancelled: 'ملغاة',
    };

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
      child: Row(
        children: items.entries.map((entry) {
          final selected = filter == entry.key;
          final count = counts[entry.key] ?? 0;
          return Expanded(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 3),
              child: GestureDetector(
                onTap: () => onChanged(entry.key),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  decoration: BoxDecoration(
                    color: selected ? _kDark : _kSurface,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: selected ? _kDark : _kBorder),
                  ),
                  child: Text(
                    '${entry.value} $count',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: selected ? Colors.white : _kText,
                    ),
                  ),
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _DateHeader extends StatelessWidget {
  final String title;
  const _DateHeader({required this.title});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 10, 4, 8),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w800,
          color: _kSubtext,
        ),
      ),
    );
  }
}

class _EmptyFilter extends StatelessWidget {
  final _OrdersFilter filter;
  const _EmptyFilter({required this.filter});

  @override
  Widget build(BuildContext context) {
    final text = switch (filter) {
      _OrdersFilter.active => 'لا توجد طلبات جارية حالياً',
      _OrdersFilter.delivered => 'لا توجد طلبات مكتملة بعد',
      _OrdersFilter.cancelled => 'لا توجد طلبات ملغاة',
      _OrdersFilter.all => 'لا توجد طلبات',
    };
    return Center(
      child: Text(
        text,
        style: const TextStyle(
          color: _kSubtext,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class _ErrorOrders extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorOrders({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: _kSubtext),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: onRetry,
              style: ElevatedButton.styleFrom(
                backgroundColor: _kDark,
                foregroundColor: Colors.white,
              ),
              child: const Text('إعادة المحاولة'),
            ),
          ],
        ),
      ),
    );
  }
}

// ── كارت الطلب ───────────────────────────────────────────────────────────
class _OrderCard extends StatefulWidget {
  final OrderEntity order;
  const _OrderCard({required this.order});

  @override
  State<_OrderCard> createState() => _OrderCardState();
}

class _OrderCardState extends State<_OrderCard>
    with SingleTickerProviderStateMixin {
  bool _expanded = false;
  late final AnimationController _ctrl;
  late final Animation<double> _expandAnim;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 300),
    );
    _expandAnim =
        CurvedAnimation(parent: _ctrl, curve: Curves.easeOutCubic);
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  void _toggle() {
    HapticFeedback.selectionClick();
    setState(() => _expanded = !_expanded);
    _expanded ? _ctrl.forward() : _ctrl.reverse();
  }

  @override
  Widget build(BuildContext context) {
    final order = widget.order;
    final statusColor = _statusColor(order.status);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: _kSurface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: _kBorder.withValues(alpha: 0.7)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          InkWell(
            onTap: _toggle,
            borderRadius: BorderRadius.circular(22),
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      _ProductThumbs(items: order.items),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    order.id,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontSize: 14,
                                      fontWeight: FontWeight.w800,
                                      color: _kText,
                                    ),
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: statusColor.withValues(alpha: 0.12),
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Text(
                                    order.status.label,
                                    style: TextStyle(
                                      fontSize: 10,
                                      fontWeight: FontWeight.w800,
                                      color: statusColor,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${order.itemCount} منتج  ·  ${_formatDate(order.createdAt)}',
                              style: const TextStyle(
                                fontSize: 11,
                                color: _kSubtext,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              _itemsSummary(order),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 12,
                                color: _kText,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  if (order.status != OrderStatus.cancelled) ...[
                    const SizedBox(height: 12),
                    _MiniTrackBar(status: order.status),
                  ],
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Text(
                        '${order.total.toStringAsFixed(2)} \u{20C1}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w900,
                          color: _kDark,
                        ),
                      ),
                      const Spacer(),
                      Text(
                        _expanded ? 'إخفاء التفاصيل' : 'عرض التفاصيل',
                        style: const TextStyle(
                          fontSize: 11,
                          color: _kDark,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(width: 2),
                      AnimatedRotation(
                        turns: _expanded ? 0.5 : 0,
                        duration: const Duration(milliseconds: 280),
                        child: const Icon(Icons.keyboard_arrow_down_rounded,
                            color: _kDark, size: 18),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          SizeTransition(
            sizeFactor: _expandAnim,
            child: Column(
              children: [
                Divider(
                  height: 1,
                  color: _kBorder.withValues(alpha: 0.5),
                  indent: 16,
                  endIndent: 16,
                ),
                _OrderDetails(order: order),
              ],
            ),
          ),
        ],
      ),
    );
  }

  static Color _statusColor(OrderStatus s) => switch (s) {
    OrderStatus.pending   => const Color(0xFFF59E0B),
    OrderStatus.preparing => const Color(0xFF3B82F6),
    OrderStatus.onTheWay  => const Color(0xFF8B5CF6),
    OrderStatus.delivered => const Color(0xFF10B981),
    OrderStatus.cancelled => Colors.redAccent,
  };

  static String _itemsSummary(OrderEntity order) {
    final names = order.items.map((i) => i.product.name).take(3).join('، ');
    final extra = order.items.length > 3 ? ' و${order.items.length - 3} أخرى' : '';
    return '$names$extra';
  }

  static String _formatDate(DateTime dt) {
    const months = [
      'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
      'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر',
    ];
    return '${dt.day} ${months[dt.month - 1]} ${dt.year}  '
        '${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
  }
}

class _ProductThumbs extends StatelessWidget {
  final List<CartItem> items;
  const _ProductThumbs({required this.items});

  @override
  Widget build(BuildContext context) {
    final thumbs = items.take(3).toList();
    if (thumbs.isEmpty) {
      return Container(
        width: 46,
        height: 46,
        decoration: BoxDecoration(
          color: _kBg,
          borderRadius: BorderRadius.circular(14),
        ),
        child: const Icon(Icons.shopping_bag_outlined, color: _kSubtext, size: 20),
      );
    }
    const size = 46.0;
    final extra = items.length - thumbs.length;
    final width = size + (thumbs.length - 1) * 16.0;

    return SizedBox(
      width: width,
      height: size,
      child: Stack(
        children: [
          for (var i = 0; i < thumbs.length; i++)
            Positioned(
              right: i * 16.0,
              child: Container(
                width: size,
                height: size,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.white, width: 2),
                  color: _kBg,
                ),
                clipBehavior: Clip.antiAlias,
                child: _thumbImage(thumbs[i].product.imageUrl),
              ),
            ),
          if (extra > 0)
            Positioned(
              left: 0,
              bottom: 0,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                decoration: BoxDecoration(
                  color: _kDark,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '+$extra',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 9,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _thumbImage(String url) => _productImage(url, 46);
}

Widget _productImage(String url, double size) {
  if (url.isEmpty) {
    return Container(
      width: size,
      height: size,
      color: _kBg,
      alignment: Alignment.center,
      child: const Icon(Icons.shopping_bag_outlined, color: _kSubtext, size: 20),
    );
  }
  return AppNetworkImage(
    url,
    width: size,
    height: size,
    fit: BoxFit.cover,
    error: Container(
      width: size,
      height: size,
      color: _kBg,
      alignment: Alignment.center,
      child: const Icon(Icons.shopping_bag_outlined, color: _kSubtext, size: 20),
    ),
  );
}

// ── شريط تتبع مصغّر ──────────────────────────────────────────────────────
class _MiniTrackBar extends StatelessWidget {
  final OrderStatus status;
  const _MiniTrackBar({required this.status});

  @override
  Widget build(BuildContext context) {
    final current = _kTrackSteps.indexOf(status);

    return Row(
      children: List.generate(_kTrackSteps.length * 2 - 1, (i) {
        if (i.isOdd) {
          final stepIdx = i ~/ 2;
          final filled = stepIdx < current;
          return Expanded(
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 500),
              height: 3,
              decoration: BoxDecoration(
                color: filled ? _kDark : _kBorder,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          );
        }
        final stepIdx = i ~/ 2;
        final isActive = stepIdx <= current;
        return AnimatedContainer(
          duration: const Duration(milliseconds: 400),
          width: 18,
          height: 18,
          decoration: BoxDecoration(
            color: isActive ? _kDark : _kBorder,
            shape: BoxShape.circle,
            border: Border.all(
              color: isActive ? _kDark : _kBorder,
              width: 2,
            ),
          ),
          child: isActive
              ? const Icon(Icons.check_rounded,
                  color: Colors.white, size: 12)
              : null,
        );
      }),
    );
  }
}

// ── تفاصيل الطلب الكاملة ─────────────────────────────────────────────────
class _OrderDetails extends StatelessWidget {
  final OrderEntity order;
  const _OrderDetails({required this.order});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // خط زمني للتتبع
          if (order.status != OrderStatus.cancelled)
            _TrackingTimeline(status: order.status),

          if (order.status != OrderStatus.cancelled)
            const SizedBox(height: 20),

          // قائمة المنتجات
          const Text(
            'المنتجات',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w800,
              color: _kText,
            ),
          ),
          const SizedBox(height: 10),
          ...order.items.map((item) => _ItemRow(cartItem: item)),

          const SizedBox(height: 14),
          Divider(height: 1, color: _kBorder.withValues(alpha: 0.5)),
          const SizedBox(height: 12),

          // الملخص المالي
          _SummaryRow(
            label: 'إجمالي المنتجات',
            value: '${order.subtotal.toStringAsFixed(2)} \u{20C1}',
          ),
          const SizedBox(height: 6),
          _SummaryRow(
            label: 'رسوم التوصيل',
            value: order.hasFreeShipping
                ? 'مجاني 🎉'
                : '${order.shippingFee.toStringAsFixed(2)} \u{20C1}',
            valueColor: order.hasFreeShipping ? _kDark : null,
          ),
          if (order.discountAmount > 0) ...[
            const SizedBox(height: 6),
            _SummaryRow(
              label: order.couponCode == null
                  ? 'الخصم'
                  : 'الخصم (${order.couponCode})',
              value: '- ${order.discountAmount.toStringAsFixed(2)} \u{20C1}',
              valueColor: _kDark,
            ),
          ],
          const SizedBox(height: 6),
          _SummaryRow(
            label: 'طريقة الدفع',
            value: order.paymentMethodLabel,
          ),
          const SizedBox(height: 6),
          _SummaryRow(
            label: 'حالة الدفع',
            value: order.paymentStatusLabel,
          ),
          const SizedBox(height: 6),
          _SummaryRow(
            label: 'الإجمالي الكلي',
            value: '${order.total.toStringAsFixed(2)} \u{20C1}',
            isBold: true,
          ),
          if (order.cancelReason != null &&
              order.status == OrderStatus.cancelled) ...[
            const SizedBox(height: 8),
            _SummaryRow(
              label: 'سبب الإلغاء',
              value: order.cancelReason!,
            ),
          ],
          if (order.canCancel) ...[
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () => _confirmCancel(context, order),
                icon: const Icon(Icons.cancel_outlined, size: 18),
                label: const Text('إلغاء الطلب'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.redAccent,
                  side: const BorderSide(color: Colors.redAccent),
                  minimumSize: const Size(0, 46),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                  textStyle: const TextStyle(fontWeight: FontWeight.w800),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Future<void> _confirmCancel(BuildContext context, OrderEntity order) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('إلغاء الطلب', textAlign: TextAlign.center),
        content: const Text(
          'يمكن إلغاء الطلب قبل خروجه للتوصيل. سيُعاد المخزون والكوبون إن وُجد.',
          textAlign: TextAlign.center,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('تراجع'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.redAccent,
              foregroundColor: Colors.white,
            ),
            child: const Text('تأكيد الإلغاء'),
          ),
        ],
      ),
    );
    if (ok != true || !context.mounted) return;
    try {
      await context.read<OrdersCubit>().cancelOrder(order.id);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تم إلغاء الطلب', textAlign: TextAlign.center)),
      );
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString(), textAlign: TextAlign.center)),
      );
    }
  }
}

// ── خط زمني للتتبع ────────────────────────────────────────────────────────
class _TrackingTimeline extends StatelessWidget {
  final OrderStatus status;
  const _TrackingTimeline({required this.status});

  @override
  Widget build(BuildContext context) {
    final current = _kTrackSteps.indexOf(status);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'تتبع الطلب',
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w800,
            color: _kText,
          ),
        ),
        const SizedBox(height: 14),
        ...List.generate(_kTrackSteps.length, (i) {
          final s = _kTrackSteps[i];
          final isDone = i <= current;
          final isLast = i == _kTrackSteps.length - 1;

          return IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // العمود الأيسر: أيقونة + خط
                SizedBox(
                  width: 36,
                  child: Column(
                    children: [
                      AnimatedContainer(
                        duration: const Duration(milliseconds: 600),
                        curve: Curves.easeOutCubic,
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: isDone ? _kDark : const Color(0xFFE8F8F0),
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: isDone ? _kDark : _kBorder,
                            width: 2,
                          ),
                          boxShadow: isDone
                              ? [
                                  BoxShadow(
                                    color: _kDark.withValues(alpha: 0.3),
                                    blurRadius: 10,
                                    offset: const Offset(0, 3),
                                  ),
                                ]
                              : null,
                        ),
                        child: AnimatedSwitcher(
                          duration: const Duration(milliseconds: 300),
                          child: isDone
                              ? const Icon(Icons.check_rounded,
                                  color: Colors.white, size: 18,
                                  key: ValueKey('done'))
                              : Text(
                                  s.emoji,
                                  key: ValueKey('pending_$i'),
                                  style: const TextStyle(fontSize: 16),
                                ),
                        ),
                      ),
                      if (!isLast)
                        Expanded(
                          child: Center(
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 500),
                              width: 2,
                              color: i < current
                                  ? _kDark
                                  : _kBorder.withValues(alpha: 0.6),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
                const SizedBox(width: 14),
                // المحتوى
                Expanded(
                  child: Padding(
                    padding: EdgeInsets.only(
                      bottom: isLast ? 0 : 18,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          s.label,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: isDone
                                ? FontWeight.w700
                                : FontWeight.w500,
                            color: isDone ? _kText : _kSubtext,
                          ),
                        ),
                        if (isDone && i == current)
                          Padding(
                            padding: const EdgeInsets.only(top: 3),
                            child: Text(
                              'الحالة الحالية',
                              style: TextStyle(
                                fontSize: 10,
                                color: _kDark,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          );
        }),
      ],
    );
  }
}

// ── صف منتج ───────────────────────────────────────────────────────────────
class _ItemRow extends StatelessWidget {
  final CartItem cartItem;
  const _ItemRow({required this.cartItem});

  @override
  Widget build(BuildContext context) {
    final item = cartItem;
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: _productImage(item.product.imageUrl, 48),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.product.name,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: _kText,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  '${item.product.effectivePrice.toStringAsFixed(2)} \u{20C1} × ${item.quantity}',
                  style: const TextStyle(
                    fontSize: 11,
                    color: _kSubtext,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          Text(
            '${(item.product.effectivePrice * item.quantity).toStringAsFixed(2)} \u{20C1}',
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w800,
              color: _kDark,
            ),
          ),
        ],
      ),
    );
  }
}

// ── صف ملخص ───────────────────────────────────────────────────────────────
class _SummaryRow extends StatelessWidget {
  final String label;
  final String value;
  final Color? valueColor;
  final bool isBold;

  const _SummaryRow({
    required this.label,
    required this.value,
    this.valueColor,
    this.isBold = false,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: isBold ? 14 : 12,
            fontWeight: isBold ? FontWeight.w800 : FontWeight.w500,
            color: isBold ? _kText : _kSubtext,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: isBold ? 15 : 12,
            fontWeight: isBold ? FontWeight.w900 : FontWeight.w700,
            color: valueColor ?? (isBold ? _kDark : _kText),
          ),
        ),
      ],
    );
  }
}

// ── زائر بدون تسجيل ───────────────────────────────────────────────────────
class _GuestOrders extends StatelessWidget {
  const _GuestOrders();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
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
                Icons.lock_person_rounded,
                size: 54,
                color: _kBorder,
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              AppStrings.guestLoginRequiredTitle,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: _kText,
              ),
            ),
            const SizedBox(height: 10),
            const Text(
              AppStrings.guestOrdersMessage,
              style: TextStyle(
                fontSize: 14,
                color: _kSubtext,
                fontWeight: FontWeight.w500,
                height: 1.6,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),
            ElevatedButton.icon(
              onPressed: () => AuthFlow.openLogin(context),
              icon: const Icon(Icons.login_rounded, size: 20),
              label: const Text(AppStrings.guestLoginCta),
              style: ElevatedButton.styleFrom(
                backgroundColor: _kDark,
                foregroundColor: Colors.white,
                minimumSize: const Size(180, 52),
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
    );
  }
}

// ── حالة فارغة ────────────────────────────────────────────────────────────
class _EmptyOrders extends StatelessWidget {
  const _EmptyOrders();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
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
                Icons.receipt_long_rounded,
                size: 54,
                color: _kBorder,
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'لا توجد طلبات بعد',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: _kText,
              ),
            ),
            const SizedBox(height: 10),
            const Text(
              'بعد إتمام طلبك الأول\nستظهر هنا مع إمكانية التتبع',
              style: TextStyle(
                fontSize: 14,
                color: _kSubtext,
                fontWeight: FontWeight.w500,
                height: 1.6,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),
            ElevatedButton.icon(
              onPressed: () => Navigator.of(context).pop(),
              icon: const Icon(Icons.storefront_rounded, size: 20),
              label: const Text('تسوق الآن'),
              style: ElevatedButton.styleFrom(
                backgroundColor: _kDark,
                foregroundColor: Colors.white,
                minimumSize: const Size(180, 52),
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
    );
  }
}

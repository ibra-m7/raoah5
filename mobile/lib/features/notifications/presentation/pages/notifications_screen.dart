import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/router/app_router.dart';
import '../../../auth/data/services/auth_session.dart';
import '../../../auth/presentation/auth_flow.dart';
import '../../data/models/app_notification.dart';
import '../manager/notifications_cubit.dart';

const _kBg = Color(0xFFEDF9F2);
const _kSurface = Color(0xFFFFFFFF);
const _kDark = Color(0xFF27AE60);
const _kText = Color(0xFF1B3A2D);
const _kSubtext = Color(0xFF6B8A76);

class NotificationsScreen extends StatelessWidget {
  static const routeName = '/notifications';

  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.dark,
        child: Scaffold(
          backgroundColor: _kBg,
          appBar: AppBar(
            backgroundColor: _kBg,
            elevation: 0,
            foregroundColor: _kText,
            title: const Text(
              'الإشعارات',
              style: TextStyle(fontWeight: FontWeight.w800),
            ),
            actions: [
              if (AuthSession.instance.isLoggedIn)
                TextButton(
                  onPressed: () =>
                      context.read<NotificationsCubit>().markAllRead(),
                  child: const Text('قراءة الكل'),
                ),
            ],
          ),
          body: !AuthSession.instance.isLoggedIn
              ? _GuestView(onLogin: () => AuthFlow.openLogin(context))
              : BlocBuilder<NotificationsCubit, NotificationsState>(
                  builder: (context, state) {
                    if (state.loading && state.items.isEmpty) {
                      return const Center(child: CircularProgressIndicator());
                    }
                    if (state.error != null && state.items.isEmpty) {
                      return Center(
                        child: Padding(
                          padding: const EdgeInsets.all(24),
                          child: Text(
                            state.error!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: _kSubtext),
                          ),
                        ),
                      );
                    }
                    if (state.items.isEmpty) {
                      return const _EmptyView();
                    }
                    return RefreshIndicator(
                      color: _kDark,
                      onRefresh: () =>
                          context.read<NotificationsCubit>().load(),
                      child: ListView.separated(
                        physics: const AlwaysScrollableScrollPhysics(
                          parent: BouncingScrollPhysics(),
                        ),
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                        itemCount: state.items.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 10),
                        itemBuilder: (_, i) => _NotificationTile(
                          item: state.items[i],
                        ),
                      ),
                    );
                  },
                ),
        ),
      ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  final AppNotification item;

  const _NotificationTile({required this.item});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: _kSurface,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () async {
          await context.read<NotificationsCubit>().markRead(item);
          if (!context.mounted) return;
          if (item.isOrder) {
            Navigator.of(context).pushNamed(AppRouter.orders);
          }
        },
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                backgroundColor: const Color(0xFFE8F8ED),
                child: Icon(
                  item.isOrder
                      ? Icons.local_shipping_outlined
                      : Icons.campaign_outlined,
                  color: _kDark,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            item.title,
                            style: TextStyle(
                              fontWeight: item.read
                                  ? FontWeight.w600
                                  : FontWeight.w800,
                              color: _kText,
                              fontSize: 15,
                            ),
                          ),
                        ),
                        if (!item.read)
                          Container(
                            width: 8,
                            height: 8,
                            decoration: const BoxDecoration(
                              color: _kDark,
                              shape: BoxShape.circle,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      item.body,
                      style: const TextStyle(
                        color: _kSubtext,
                        height: 1.45,
                      ),
                    ),
                    if (item.createdAt != null) ...[
                      const SizedBox(height: 6),
                      Text(
                        _when(item.createdAt!),
                        style: const TextStyle(
                          color: _kSubtext,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _when(DateTime time) {
    final local = time.toLocal();
    return '${local.year}/${local.month.toString().padLeft(2, '0')}/${local.day.toString().padLeft(2, '0')}  ${local.hour.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')}';
  }
}

class _EmptyView extends StatelessWidget {
  const _EmptyView();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Padding(
        padding: EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.notifications_none_rounded, size: 64, color: _kDark),
            SizedBox(height: 12),
            Text(
              'لا توجد إشعارات بعد',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 18,
                color: _kText,
              ),
            ),
            SizedBox(height: 6),
            Text(
              'عندما يصلك عرض أو يتغير طلبك سيظهر هنا.',
              textAlign: TextAlign.center,
              style: TextStyle(color: _kSubtext),
            ),
          ],
        ),
      ),
    );
  }
}

class _GuestView extends StatelessWidget {
  final VoidCallback onLogin;

  const _GuestView({required this.onLogin});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.notifications_active_outlined,
                size: 64, color: _kDark),
            const SizedBox(height: 12),
            const Text(
              'سجّل دخولك لاستلام التنبيهات',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 18,
                color: _kText,
              ),
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: onLogin,
              style: FilledButton.styleFrom(backgroundColor: _kDark),
              child: const Text('تسجيل الدخول'),
            ),
          ],
        ),
      ),
    );
  }
}

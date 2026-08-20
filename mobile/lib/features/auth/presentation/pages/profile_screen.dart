import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../shop/domain/entities/order_entity.dart';
import '../../../shop/presentation/manager/orders_cubit.dart';
import '../../../shop/presentation/pages/orders_screen.dart';
import '../manager/address_cubit.dart';
import '../../data/models/auth_user.dart';
import '../../data/services/auth_session.dart';
import '../../data/services/phone_auth_api.dart';
import '../../../notifications/data/services/push_service.dart';
import '../../../notifications/presentation/manager/notifications_cubit.dart';
import '../auth_flow.dart';
import '../widgets/edit_name_dialog.dart';

// ── ثوابت ─────────────────────────────────────────────────────────────────────
const _kGreen      = Color(0xFF2E7D32);
const _kGreenLight = Color(0xFF4CAF50);
const _kGreenBg    = Color(0xFFF1F8F1);
const _kText       = Color(0xFF1A2E1A);
const _kSubtext    = Color(0xFF6B7B6B);
const _kSurface    = Color(0xFFFFFFFF);

// ══════════════════════════════════════════════════════════════════════════════
// ProfileScreen
// ══════════════════════════════════════════════════════════════════════════════
class ProfileScreen extends StatefulWidget {
  static const routeName = '/profile';
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _fadeCtrl;
  late final Animation<double> _fadeAnim;

  // ── إعدادات المستخدم (local state) ──────────────────────────────────────
  bool _arabicLanguage = true;
  bool _togglingNotifications = false;
  final bool _darkMode = false;

  @override
  void initState() {
    super.initState();
    _fadeCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _fadeAnim = CurvedAnimation(parent: _fadeCtrl, curve: Curves.easeOut);
    _fadeCtrl.forward();
  }

  @override
  void dispose() {
    _fadeCtrl.dispose();
    super.dispose();
  }

  Future<void> _editName() async {
    final current = AuthSession.instance.user?.name ?? '';
    final saved = await EditNameDialog.show(context, currentName: current);
    if (saved && mounted) {
      setState(() {});
      _showSnack('تم تحديث اسمك بنجاح ✓', _kGreen);
    }
  }

  Future<void> _toggleNotifications(bool enabled) async {
    setState(() => _togglingNotifications = true);
    try {
      await PushService.instance.setEnabled(enabled);
      if (mounted) {
        try {
          context.read<NotificationsCubit>().load();
        } catch (_) {}
        _showSnack(
          enabled ? 'تم تفعيل الإشعارات' : 'تم إيقاف الإشعارات',
          _kGreen,
        );
      }
    } catch (_) {
      if (mounted) {
        _showSnack('تعذّر حفظ إعداد الإشعارات', Colors.redAccent);
      }
    } finally {
      if (mounted) setState(() => _togglingNotifications = false);
    }
  }

  Future<void> _openMyOrders() {
    return Navigator.of(context, rootNavigator: true).push(
      MaterialPageRoute<void>(
        settings: const RouteSettings(name: OrdersScreen.routeName),
        builder: (_) => const OrdersScreen(),
      ),
    );
  }

  void _showSnack(String msg, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg, textAlign: TextAlign.center,
            style: const TextStyle(fontWeight: FontWeight.w700)),
        backgroundColor: color,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        margin: const EdgeInsets.all(16),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  void _confirmSignOut() {
    showDialog(
      context: context,
      builder: (_) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Text(AppStrings.profileSignOutConfirmTitle,
              style: TextStyle(fontWeight: FontWeight.w800)),
          content: const Text(
            AppStrings.profileSignOutConfirmBody,
            style: TextStyle(height: 1.6),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text(AppStrings.cancel),
            ),
            ElevatedButton.icon(
              onPressed: () async {
                Navigator.pop(context);
                await PushService.instance.unregisterForLogout();
                await PhoneAuthApi.instance.logout();
                if (!mounted) return;
                try {
                  context.read<OrdersCubit>().load();
                } catch (_) {}
                try {
                  context.read<NotificationsCubit>().load();
                } catch (_) {}
                try {
                  context.read<AddressCubit>().load();
                } catch (_) {}
              },
              icon: const Icon(Icons.logout_rounded, size: 18),
              label: const Text('خروج'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.redAccent,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: AuthSession.instance,
      builder: (context, _) {
        if (!AuthSession.instance.isLoggedIn) {
          return _GuestProfileView(
            onLogin: () => AuthFlow.openLogin(context),
          );
        }
        return _buildLoggedIn(context);
      },
    );
  }

  Widget _buildLoggedIn(BuildContext context) {
    final user = AuthSession.instance.user;

    return Directionality(
      textDirection: TextDirection.rtl,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.light,
        child: Scaffold(
          backgroundColor: _kGreenBg,
          body: FadeTransition(
            opacity: _fadeAnim,
            child: CustomScrollView(
              primary: false,
              slivers: [
                // ── هيدر الملف الشخصي ──────────────────────────────────────
                _ProfileHeader(
                  user: user,
                  onEditTap: _editName,
                ),

                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        // ── إحصائيات سريعة ─────────────────────────────────
                        _StatsRow(onOrdersTap: _openMyOrders),

                        const SizedBox(height: 24),

                        _SettingsCard(
                          children: [
                            _TileOption(
                              icon: Icons.receipt_long_rounded,
                              label: AppStrings.profileOrders,
                              color: _kGreen,
                              onTap: _openMyOrders,
                            ),
                            const _Divider(),
                            _TileOption(
                              icon: Icons.favorite_rounded,
                              label: AppStrings.favoritesTitle,
                              color: const Color(0xFFE53935),
                              onTap: () => Navigator.of(context,
                                      rootNavigator: true)
                                  .pushNamed(AppRouter.favorites),
                            ),
                          ],
                        ),
                        const SizedBox(height: 24),

                        // ── إعدادات اللغة والتطبيق ─────────────────────────
                        _SectionHeader(
                          icon: Icons.settings_outlined,
                          title: AppStrings.profileSettings,
                          color: const Color(0xFF0288D1),
                        ),
                        const SizedBox(height: 12),
                        _SettingsCard(
                          children: [
                            _LanguageToggle(
                              isArabic: _arabicLanguage,
                              onChanged: (v) =>
                                  setState(() => _arabicLanguage = v),
                            ),
                            const _Divider(),
                            _SwitchOption(
                              icon: Icons.notifications_outlined,
                              label: AppStrings.profileNotifications,
                              subtitle: 'عروض وتنبيهات الطلبات',
                              color: const Color(0xFF7B1FA2),
                              value: user?.notificationsEnabled ?? true,
                              onChanged: _togglingNotifications
                                  ? (_) {}
                                  : (v) => _toggleNotifications(v),
                            ),
                            const _Divider(),
                            _SwitchOption(
                              icon: Icons.dark_mode_outlined,
                              label: 'الوضع الداكن',
                              subtitle: 'قريباً — تجربة تصفح مريحة ليلاً',
                              color: _kText,
                              value: _darkMode,
                              onChanged: (_) => _showSnack(
                                'الوضع الداكن قادم قريباً!',
                                const Color(0xFF37474F),
                              ),
                            ),
                          ],
                        ),

                        const SizedBox(height: 16),

                        // ── معلومات الحساب ──────────────────────────────────
                        _SectionHeader(
                          icon: Icons.manage_accounts_outlined,
                          title: 'الحساب',
                          color: const Color(0xFFE64A19),
                        ),
                        const SizedBox(height: 12),
                        _SettingsCard(
                          children: [
                            _TileOption(
                              icon: Icons.notifications_active_outlined,
                              label: 'مركز الإشعارات',
                              color: const Color(0xFF7B1FA2),
                              onTap: () => Navigator.of(context)
                                  .pushNamed(AppRouter.notifications),
                            ),
                            const _Divider(),
                            _TileOption(
                              icon: Icons.privacy_tip_outlined,
                              label: AppStrings.privacyPolicy,
                              color: _kGreen,
                              onTap: () {},
                            ),
                            const _Divider(),
                            _TileOption(
                              icon: Icons.description_outlined,
                              label: AppStrings.termsOfUse,
                              color: _kGreen,
                              onTap: () {},
                            ),
                            const _Divider(),
                            _TileOption(
                              icon: Icons.info_outline_rounded,
                              label: AppStrings.profileAbout,
                              color: _kSubtext,
                              trailing: const Text(
                                'v1.0.0',
                                style: TextStyle(
                                  color: _kSubtext,
                                  fontSize: 12,
                                ),
                              ),
                              onTap: () {},
                            ),
                          ],
                        ),

                        const SizedBox(height: 24),

                        // ── زر تسجيل الخروج ─────────────────────────────────
                        _SignOutButton(onTap: _confirmSignOut),

                        const SizedBox(height: 40),
                      ],
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

class _GuestProfileView extends StatelessWidget {
  final VoidCallback onLogin;

  const _GuestProfileView({required this.onLogin});

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.light,
        child: Scaffold(
          backgroundColor: _kGreenBg,
          body: CustomScrollView(
            primary: false,
            slivers: [
              SliverAppBar(
                expandedHeight: 180,
                pinned: true,
                automaticallyImplyLeading: false,
                backgroundColor: _kGreen,
                title: const Text(
                  'حسابي',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                    fontSize: 18,
                  ),
                ),
                flexibleSpace: FlexibleSpaceBar(
                  background: Container(
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [
                          Color(0xFF0A3D1F),
                          Color(0xFF1B5E20),
                          _kGreen,
                        ],
                      ),
                    ),
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 28, 20, 40),
                  child: Container(
                    padding: const EdgeInsets.fromLTRB(22, 28, 22, 24),
                    decoration: BoxDecoration(
                      color: _kSurface,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.05),
                          blurRadius: 18,
                          offset: const Offset(0, 8),
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        Container(
                          width: 88,
                          height: 88,
                          decoration: BoxDecoration(
                            color: _kGreen.withValues(alpha: 0.1),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.person_outline_rounded,
                            size: 44,
                            color: _kGreen,
                          ),
                        ),
                        const SizedBox(height: 18),
                        const Text(
                          AppStrings.guestProfileTitle,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 22,
                            fontWeight: FontWeight.w900,
                            color: _kText,
                          ),
                        ),
                        const SizedBox(height: 10),
                        const Text(
                          AppStrings.guestProfileBody,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 14.5,
                            height: 1.7,
                            color: _kSubtext,
                          ),
                        ),
                        const SizedBox(height: 24),
                        SizedBox(
                          width: double.infinity,
                          height: 52,
                          child: FilledButton.icon(
                            onPressed: onLogin,
                            icon: const Icon(Icons.login_rounded),
                            label: const Text(
                              AppStrings.guestLoginCta,
                              style: TextStyle(
                                fontWeight: FontWeight.w800,
                                fontSize: 16,
                              ),
                            ),
                            style: FilledButton.styleFrom(
                              backgroundColor: _kGreen,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'يمكنك تصفح المنتجات وإضافتها للسلة بدون حساب.',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 12.5,
                            color: _kSubtext,
                          ),
                        ),
                      ],
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

// ══════════════════════════════════════════════════════════════════════════════
// هيدر الملف الشخصي
// ══════════════════════════════════════════════════════════════════════════════
class _ProfileHeader extends StatelessWidget {
  final AuthUser? user;
  final VoidCallback onEditTap;

  const _ProfileHeader({
    required this.user,
    required this.onEditTap,
  });

  String get _initials {
    final name = user?.name ?? '';
    if (name.isEmpty) return 'م';
    final parts = name.trim().split(' ');
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    return name[0].toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    return SliverAppBar(
      expandedHeight: 260,
      pinned: true,
      backgroundColor: _kGreen,
      elevation: 0,
      automaticallyImplyLeading: false,
      title: const Text(
        'ملفي الشخصي',
        style: TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w800,
          fontSize: 18,
        ),
      ),
      actions: [
        IconButton(
          onPressed: onEditTap,
          icon: const Icon(Icons.edit_outlined, color: Colors.white),
          tooltip: 'تعديل الاسم',
        ),
      ],
      flexibleSpace: FlexibleSpaceBar(
        collapseMode: CollapseMode.parallax,
        background: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFF0A3D1F), Color(0xFF1B5E20), _kGreen],
            ),
          ),
          child: Stack(
            children: [
              // دوائر ديكورية
              Positioned(top: -40, left: -40,
                  child: _decorCircle(160, 0.06)),
              Positioned(bottom: 20, right: -30,
                  child: _decorCircle(120, 0.08)),
              // المحتوى — توسيط عمودي وأفقي داخل الهيدر
              SafeArea(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 50, 20, 16),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      // الصورة الرمزية + التحقق (متمركزة)
                      Stack(
                        clipBehavior: Clip.none,
                        alignment: Alignment.center,
                        children: [
                          Container(
                            width: 90,
                            height: 90,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: const LinearGradient(
                                colors: [Color(0xFF81C784), _kGreenLight],
                              ),
                              border: Border.all(
                                color: Colors.white.withValues(alpha: 0.5),
                                width: 3,
                              ),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.25),
                                  blurRadius: 16,
                                  offset: const Offset(0, 6),
                                ),
                              ],
                            ),
                            alignment: Alignment.center,
                            child: Text(
                              _initials,
                              style: const TextStyle(
                                fontSize: 32,
                                fontWeight: FontWeight.w900,
                                color: Colors.white,
                              ),
                            ),
                          ),
                          // أيقونة التحقق — زاوية سفلية باتجاه «النهاية» (مناسب لـ RTL)
                          PositionedDirectional(
                            bottom: -2,
                            end: -2,
                            child: Container(
                              width: 26,
                              height: 26,
                              decoration: BoxDecoration(
                                color: const Color(0xFF4CAF50),
                                shape: BoxShape.circle,
                                border: Border.all(
                                    color: Colors.white, width: 2),
                              ),
                              child: const Icon(
                                Icons.verified_rounded,
                                color: Colors.white,
                                size: 14,
                              ),
                            ),
                          ),
                        ],
                      ),

                      const SizedBox(height: 12),

                      // الاسم — قابل للتعديل
                      GestureDetector(
                        onTap: onEditTap,
                        child: Text(
                          user?.name ?? AppStrings.profileGuestName,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 22,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ),

                      const SizedBox(height: 4),

                      SizedBox(
                          width: double.infinity,
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.phone_iphone_rounded,
                                color: Colors.white.withValues(alpha: 0.7),
                                size: 13,
                              ),
                              const SizedBox(width: 5),
                              Flexible(
                                child: Text(
                                  user?.phone ?? AppStrings.profileGuestEmail,
                                  textDirection: TextDirection.ltr,
                                  style: TextStyle(
                                    color:
                                        Colors.white.withValues(alpha: 0.75),
                                    fontSize: 13,
                                  ),
                                  textAlign: TextAlign.center,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _decorCircle(double size, double opacity) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.white.withValues(alpha: opacity),
        ),
      );
}

// ══════════════════════════════════════════════════════════════════════════════
// إحصائيات سريعة
// ══════════════════════════════════════════════════════════════════════════════
class _StatsRow extends StatelessWidget {
  final VoidCallback onOrdersTap;
  const _StatsRow({required this.onOrdersTap});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<OrdersCubit, OrdersState>(
      builder: (context, state) {
        final orders = state.orders;
        final ordersCount = orders.length;
        final delivered =
            orders.where((o) => o.status == OrderStatus.delivered).length;
        final totalSpent =
            orders.fold(0.0, (s, o) => s + o.total).toStringAsFixed(0);

        return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
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
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(
            child: _StatItem(
              label: AppStrings.profileOrders,
              value: '$ordersCount',
              icon: Icons.shopping_bag_outlined,
              color: _kGreen,
              onTap: onOrdersTap,
            ),
          ),
          _VerticalDivider(),
          Expanded(
            child: _StatItem(
              label: 'مُسلَّمة',
              value: '$delivered',
              icon: Icons.check_circle_outline_rounded,
              color: const Color(0xFF0288D1),
            ),
          ),
          _VerticalDivider(),
          Expanded(
            child: _StatItem(
              label: 'إجمالي المشتريات',
              value: '$totalSpent \u{20C1}',
              icon: Icons.wallet_outlined,
              color: const Color(0xFF7B1FA2),
            ),
          ),
        ],
      ),
    );
      },
    );
  }
}

class _StatItem extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color color;
  final VoidCallback? onTap;

  const _StatItem({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final content = Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: color, size: 22),
        ),
        const SizedBox(height: 6),
        Text(
          value,
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w900,
            color: color,
          ),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: const TextStyle(fontSize: 11, color: _kSubtext),
          textAlign: TextAlign.center,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
        ),
      ],
    );
    if (onTap == null) return content;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: content,
    );
  }
}

class _VerticalDivider extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      width: 1,
      height: 60,
      color: _kGreenBg,
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// تبديل اللغة
// ══════════════════════════════════════════════════════════════════════════════
class _LanguageToggle extends StatelessWidget {
  final bool isArabic;
  final ValueChanged<bool> onChanged;

  const _LanguageToggle({required this.isArabic, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFF0288D1).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.language_rounded,
              color: Color(0xFF0288D1),
              size: 22,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'لغة التطبيق',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 14,
                    color: _kText,
                  ),
                ),
                Text(
                  isArabic ? 'العربية' : 'English',
                  style: const TextStyle(fontSize: 12, color: _kSubtext),
                ),
              ],
            ),
          ),
          // مبدّل اللغة
          Container(
            height: 36,
            decoration: BoxDecoration(
              color: _kGreenBg,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: _kGreen.withValues(alpha: 0.25),
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                _LangChip(
                  label: 'ع',
                  isSelected: isArabic,
                  onTap: () => onChanged(true),
                ),
                _LangChip(
                  label: 'EN',
                  isSelected: !isArabic,
                  onTap: () => onChanged(false),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _LangChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  const _LangChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        width: 44,
        height: 36,
        decoration: BoxDecoration(
          color: isSelected ? _kGreen : Colors.transparent,
          borderRadius: BorderRadius.circular(20),
        ),
        alignment: Alignment.center,
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w800,
            color: isSelected ? Colors.white : _kSubtext,
          ),
        ),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// مكونات مساعدة مشتركة
// ══════════════════════════════════════════════════════════════════════════════
class _SectionHeader extends StatelessWidget {
  final IconData icon;
  final String title;
  final Color color;

  const _SectionHeader({
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

class _SettingsCard extends StatelessWidget {
  final List<Widget> children;
  const _SettingsCard({required this.children});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: _kSurface,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(18),
        child: Column(children: children),
      ),
    );
  }
}

class _SwitchOption extends StatelessWidget {
  final IconData icon;
  final String label;
  final String subtitle;
  final Color color;
  final bool value;
  final ValueChanged<bool> onChanged;

  const _SwitchOption({
    required this.icon,
    required this.label,
    required this.subtitle,
    required this.color,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 14,
                    color: _kText,
                  ),
                ),
                Text(
                  subtitle,
                  style:
                      const TextStyle(fontSize: 11, color: _kSubtext),
                ),
              ],
            ),
          ),
          Switch(
            value: value,
            onChanged: onChanged,
            activeThumbColor: _kGreen,
            thumbColor: WidgetStateProperty.all(Colors.white),
          ),
        ],
      ),
    );
  }
}

class _TileOption extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;
  final Widget? trailing;

  const _TileOption({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                label,
                style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 14,
                  color: _kText,
                ),
              ),
            ),
            trailing ??
                Icon(
                  Icons.arrow_back_ios_new_rounded,
                  size: 14,
                  color: _kSubtext.withValues(alpha: 0.5),
                ),
          ],
        ),
      ),
    );
  }
}

class _Divider extends StatelessWidget {
  const _Divider();

  @override
  Widget build(BuildContext context) => Container(
        height: 1,
        margin: const EdgeInsets.symmetric(horizontal: 16),
        color: _kGreenBg,
      );
}

// ══════════════════════════════════════════════════════════════════════════════
// زر تسجيل الخروج
// ══════════════════════════════════════════════════════════════════════════════
class _SignOutButton extends StatefulWidget {
  final VoidCallback onTap;
  const _SignOutButton({required this.onTap});

  @override
  State<_SignOutButton> createState() => _SignOutButtonState();
}

class _SignOutButtonState extends State<_SignOutButton>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;
  late final Animation<double> _scale;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 120),
      lowerBound: 0.95,
      upperBound: 1.0,
      value: 1.0,
    );
    _scale = _ctrl;
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ScaleTransition(
      scale: _scale,
      child: GestureDetector(
        onTapDown: (_) => _ctrl.reverse(),
        onTapUp: (_) {
          _ctrl.forward();
          widget.onTap();
        },
        onTapCancel: () => _ctrl.forward(),
        child: Container(
          width: double.infinity,
          height: 56,
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFFB71C1C), Colors.redAccent],
            ),
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: Colors.redAccent.withValues(alpha: 0.35),
                blurRadius: 14,
                offset: const Offset(0, 5),
              ),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.logout_rounded, color: Colors.white, size: 22),
              const SizedBox(width: 10),
              const Text(
                AppStrings.profileSignOut,
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
    );
  }
}

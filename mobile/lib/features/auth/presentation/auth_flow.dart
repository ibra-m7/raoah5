import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/constants/app_strings.dart';
import '../../../core/router/app_router.dart';
import '../../../core/theme/app_theme.dart';
import '../../shop/presentation/manager/orders_cubit.dart';
import '../data/models/auth_user.dart';
import '../data/services/auth_session.dart';
import 'manager/address_cubit.dart';

/// تنقل تسجيل الدخول ووضع الضيف: الرجوع يفتح الرئيسية بدون صفحة خطأ.
abstract class AuthFlow {
  AuthFlow._();

  static Future<void> goHome(BuildContext context, {int tab = 0}) {
    return Navigator.of(context).pushNamedAndRemoveUntil(
      AppRouter.main,
      (_) => false,
      arguments: tab,
    );
  }

  /// من شاشة دخول بدون رئيسية تحتها → الرئيسية. إن وُجدت رئيسية → رجوع عادي.
  static void leaveAuth(BuildContext context) {
    final nav = Navigator.of(context);
    if (nav.canPop()) {
      nav.pop();
      return;
    }
    goHome(context);
  }

  static Future<void> openLogin(BuildContext context) {
    return Navigator.of(context).pushNamed(AppRouter.phoneLogin);
  }

  static Future<bool> requireLogin(
    BuildContext context, {
    String message = AppStrings.guestLoginRequiredBody,
  }) async {
    if (AuthSession.instance.isLoggedIn) return true;
    final shouldLogin = await showModalBottomSheet<bool>(
      context: context,
      showDragHandle: false,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _GuestLoginSheet(message: message),
    );
    if (shouldLogin == true && context.mounted) {
      await openLogin(context);
    }
    return AuthSession.instance.isLoggedIn;
  }

  static Future<void> afterLogin(BuildContext context, AuthUser user) async {
    final session = AuthSession.instance;
    session.offerCompleteName = user.needsName;
    session.welcomeAfterLogin = true;

    try {
      context.read<OrdersCubit>().load();
    } catch (_) {}
    try {
      context.read<AddressCubit>().load();
    } catch (_) {}

    if (!context.mounted) return;
    await goHome(context);
  }

  static void returnToMain(BuildContext context) {
    final nav = Navigator.of(context);
    var hasMain = false;
    nav.popUntil((route) {
      if (route.settings.name == AppRouter.main) {
        hasMain = true;
        return true;
      }
      return route.isFirst;
    });
    if (!hasMain && context.mounted) {
      goHome(context);
    }
  }
}

class _GuestLoginSheet extends StatelessWidget {
  final String message;

  const _GuestLoginSheet({required this.message});

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
        child: Material(
          color: Colors.white,
          borderRadius: BorderRadius.circular(28),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 14, 24, 22),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 44,
                  height: 5,
                  decoration: BoxDecoration(
                    color: const Color(0xFFE0E8E0),
                    borderRadius: BorderRadius.circular(20),
                  ),
                ),
                const SizedBox(height: 20),
                Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withValues(alpha: 0.12),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.lock_person_rounded,
                    color: AppTheme.primaryDark,
                    size: 32,
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  AppStrings.guestLoginRequiredTitle,
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                    color: AppTheme.darkText,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 14.5,
                    height: 1.65,
                    color: AppTheme.mutedText,
                  ),
                ),
                const SizedBox(height: 22),
                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: FilledButton(
                    onPressed: () => Navigator.pop(context, true),
                    style: FilledButton.styleFrom(
                      backgroundColor: AppTheme.primaryDark,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    child: const Text(
                      AppStrings.guestLoginCta,
                      style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                TextButton(
                  onPressed: () => Navigator.pop(context, false),
                  child: const Text(
                    AppStrings.guestBrowse,
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      color: AppTheme.mutedText,
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

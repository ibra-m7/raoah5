import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/circle_back_button.dart';
import '../../../notifications/data/services/notifications_api.dart';
import '../../../notifications/data/services/push_service.dart';
import '../../../notifications/presentation/manager/notifications_cubit.dart';
import '../../data/services/auth_session.dart';
import '../../data/services/phone_auth_api.dart';
import '../../../shop/presentation/manager/orders_cubit.dart';
import '../manager/address_cubit.dart';

class SettingsScreen extends StatefulWidget {
  static const routeName = '/settings';

  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  Future<void> _toggleNotifications(bool enabled) async {
    final user = AuthSession.instance.user;
    if (user == null) return;

    final previous = user.notificationsEnabled;
    await AuthSession.instance.updateUser(
      user.copyWith(notificationsEnabled: enabled),
    );

    try {
      await NotificationsApi.instance.setEnabled(enabled);
      unawaited(PushService.instance.applyPreference(enabled));
      if (!mounted) return;
      try {
        context.read<NotificationsCubit>().load(silent: true);
      } catch (_) {}
    } catch (_) {
      await AuthSession.instance.updateUser(
        user.copyWith(notificationsEnabled: previous),
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text(
              AppStrings.profileNotificationsSaveFailed,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12),
            ),
            backgroundColor: Colors.redAccent,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
            margin: const EdgeInsets.all(16),
          ),
        );
      }
    }
  }

  void _confirmDeleteAccount() {
    showDialog<void>(
      context: context,
      builder: (dialogContext) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
          ),
          title: const Text(
            AppStrings.profileDeleteAccountConfirmTitle,
            style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
          ),
          content: const Text(
            AppStrings.profileDeleteAccountConfirmBody,
            style: TextStyle(fontSize: 12.5, height: 1.55),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text(
                AppStrings.cancel,
                style: TextStyle(fontSize: 12.5),
              ),
            ),
            TextButton(
              onPressed: () async {
                Navigator.pop(dialogContext);
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
              child: const Text(
                AppStrings.profileSignOut,
                style: TextStyle(
                  color: Colors.redAccent,
                  fontSize: 12.5,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.dark,
        child: Scaffold(
          backgroundColor: AppTheme.background,
          appBar: AppBar(
            backgroundColor: AppTheme.background,
            elevation: 0,
            scrolledUnderElevation: 0,
            centerTitle: true,
            automaticallyImplyLeading: false,
            leadingWidth: 56,
            leading: CircleBackButton.appBarLeading(),
            title: const Text(
              AppStrings.profileSettings,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: AppTheme.darkText,
              ),
            ),
          ),
          body: ListenableBuilder(
            listenable: AuthSession.instance,
            builder: (context, _) {
              final notificationsEnabled =
                  AuthSession.instance.user?.notificationsEnabled ?? true;

              return ListView(
                padding: const EdgeInsets.fromLTRB(18, 6, 18, 24),
                children: [
                  _SettingsSectionLabel(AppStrings.settingsSectionGeneral),
                  const SizedBox(height: 8),
                  _SettingsCard(
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 34,
                          height: 34,
                          decoration: BoxDecoration(
                            color: AppTheme.primarySurface,
                            borderRadius: BorderRadius.circular(11),
                          ),
                          child: Icon(
                            Icons.notifications_outlined,
                            size: 18,
                            color: AppTheme.primaryDark.withValues(alpha: 0.9),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                AppStrings.profileNotifications,
                                style: TextStyle(
                                  fontSize: 13.5,
                                  fontWeight: FontWeight.w800,
                                  color: AppTheme.darkText,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                AppStrings.profileNotificationsDesc,
                                style: TextStyle(
                                  fontSize: 11,
                                  height: 1.45,
                                  color: AppTheme.mutedText.withValues(
                                    alpha: 0.95,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        Switch(
                          value: notificationsEnabled,
                          onChanged: _toggleNotifications,
                          thumbColor: WidgetStateProperty.resolveWith(
                            (states) => states.contains(WidgetState.selected)
                                ? Colors.white
                                : const Color(0xFFF5F5F5),
                          ),
                          trackColor: WidgetStateProperty.resolveWith(
                            (states) {
                              if (states.contains(WidgetState.selected)) {
                                return AppTheme.primary;
                              }
                              return const Color(0xFFD0D8D3);
                            },
                          ),
                          trackOutlineColor: WidgetStateProperty.all(
                            Colors.transparent,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),
                  _SettingsSectionLabel(AppStrings.settingsSectionAccount),
                  const SizedBox(height: 8),
                  _SettingsCard(
                    child: InkWell(
                      onTap: _confirmDeleteAccount,
                      borderRadius: BorderRadius.circular(16),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 2),
                        child: Row(
                          children: [
                            Container(
                              width: 34,
                              height: 34,
                              decoration: BoxDecoration(
                                color: const Color(0xFFFFEBEE),
                                borderRadius: BorderRadius.circular(11),
                              ),
                              child: const Icon(
                                Icons.logout_rounded,
                                size: 18,
                                color: Color(0xFFE57373),
                              ),
                            ),
                            const SizedBox(width: 12),
                            const Expanded(
                              child: Text(
                                AppStrings.profileDeleteAccount,
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFFE57373),
                                ),
                              ),
                            ),
                            Icon(
                              Icons.chevron_right_rounded,
                              size: 18,
                              color: AppTheme.mutedText.withValues(alpha: 0.6),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

class _SettingsSectionLabel extends StatelessWidget {
  final String text;

  const _SettingsSectionLabel(this.text);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsetsDirectional.only(start: 4),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 11.5,
          fontWeight: FontWeight.w800,
          letterSpacing: 0.2,
          color: AppTheme.mutedText.withValues(alpha: 0.95),
        ),
      ),
    );
  }
}

class _SettingsCard extends StatelessWidget {
  final Widget child;

  const _SettingsCard({required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppTheme.primaryLight.withValues(alpha: 0.75)),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primaryDark.withValues(alpha: 0.04),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: child,
    );
  }
}

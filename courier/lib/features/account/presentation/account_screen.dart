import 'dart:async';

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../core/constants/app_strings.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/courier_ui.dart';
import '../../auth/data/courier_auth_api.dart';
import '../../auth/data/courier_session.dart';
import '../../auth/data/courier_user.dart';
import '../../auth/presentation/login_screen.dart';

class AccountScreen extends StatefulWidget {
  final int generation;

  const AccountScreen({super.key, this.generation = 0});

  @override
  State<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends State<AccountScreen> {
  bool _loading = false;
  List<CourierLedgerEntry> _entries = const [];
  Timer? _poll;

  @override
  void initState() {
    super.initState();
    _refresh();
    _poll = Timer.periodic(const Duration(seconds: 12), (_) => _refresh(silent: true));
  }

  @override
  void dispose() {
    _poll?.cancel();
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant AccountScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.generation != widget.generation) {
      _refresh();
    }
  }

  Future<void> _refresh({bool silent = false}) async {
    if (!silent) setState(() => _loading = true);
    try {
      final account = await CourierAuthApi.instance.account();
      if (!mounted) return;
      setState(() {
        _entries = account.entries;
        _loading = false;
      });
    } catch (_) {
      if (!silent) {
        try {
          await CourierAuthApi.instance.me();
        } catch (_) {}
      }
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _logout() async {
    await CourierAuthApi.instance.logout();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: CourierSession.instance,
      builder: (context, _) {
        final user = CourierSession.instance.user;
        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
            children: [
              if (_loading) const LinearProgressIndicator(minHeight: 2),
              const SizedBox(height: 8),
              CourierPanel(
                color: AppTheme.primarySurface,
                child: Column(
                  children: [
                    CircleAvatar(
                      radius: 40,
                      backgroundColor: Colors.white,
                      child: Icon(
                        Icons.delivery_dining_rounded,
                        size: 40,
                        color: AppTheme.primaryDark,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      user?.name ?? '—',
                      textAlign: TextAlign.center,
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.w800,
                        fontSize: 20,
                        color: AppTheme.darkText,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      user?.phoneDisplay ?? '',
                      textAlign: TextAlign.center,
                      textDirection: TextDirection.ltr,
                      style: GoogleFonts.cairo(
                        color: AppTheme.primaryDark,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              CourierPanel(
                color: AppTheme.primaryDark,
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        children: [
                          Text(
                            AppStrings.deliveredBox,
                            style: GoogleFonts.cairo(
                              color: Colors.white.withValues(alpha: 0.85),
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            '${user?.deliveredCount ?? 0}',
                            style: GoogleFonts.cairo(
                              color: Colors.white,
                              fontWeight: FontWeight.w900,
                              fontSize: 28,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Container(width: 1, height: 64, color: Colors.white24),
                    Expanded(
                      child: Column(
                        children: [
                          Text(
                            AppStrings.collectedBox,
                            style: GoogleFonts.cairo(
                              color: Colors.white.withValues(alpha: 0.85),
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 6),
                          MoneyText(
                            user?.codCollected ?? 0,
                            size: 20,
                            color: Colors.white,
                          ),
                          const SizedBox(height: 4),
                          Text(
                            AppStrings.cashOnDelivery,
                            textAlign: TextAlign.center,
                            style: GoogleFonts.cairo(
                              color: Colors.white.withValues(alpha: 0.85),
                              fontWeight: FontWeight.w700,
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: CourierPanel(
                      child: Column(
                        children: [
                          Text(AppStrings.owes, style: GoogleFonts.cairo(color: AppTheme.mutedText, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 4),
                          MoneyText(user?.owes ?? 0, size: 18, color: const Color(0xFFC62828)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: CourierPanel(
                      child: Column(
                        children: [
                          Text(AppStrings.owed, style: GoogleFonts.cairo(color: AppTheme.mutedText, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 4),
                          MoneyText(user?.owed ?? 0, size: 18, color: AppTheme.primaryDark),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              CourierPanel(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      AppStrings.accountStatement,
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.w800,
                        fontSize: 16,
                        color: AppTheme.darkText,
                      ),
                    ),
                    const SizedBox(height: 10),
                    if (_entries.isEmpty)
                      Text(
                        AppStrings.noStatement,
                        style: GoogleFonts.cairo(color: AppTheme.mutedText, fontWeight: FontWeight.w600),
                      )
                    else
                      for (final entry in _entries) ...[
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      entry.typeLabel,
                                      style: GoogleFonts.cairo(
                                        fontWeight: FontWeight.w800,
                                        color: AppTheme.darkText,
                                      ),
                                    ),
                                    if ((entry.orderNumber ?? '').isNotEmpty)
                                      Text(
                                        entry.orderNumber!,
                                        style: GoogleFonts.cairo(
                                          color: AppTheme.mutedText,
                                          fontSize: 12,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                  ],
                                ),
                              ),
                              MoneyText(
                                entry.amount,
                                size: 14,
                                color: entry.direction == 'debit'
                                    ? const Color(0xFFC62828)
                                    : AppTheme.primaryDark,
                              ),
                            ],
                          ),
                        ),
                        const Divider(),
                      ],
                  ],
                ),
              ),
              const SizedBox(height: 14),
              CourierPanel(
                padding: EdgeInsets.zero,
                child: ListTile(
                  leading: Icon(Icons.logout_rounded, color: Colors.redAccent.shade200),
                  title: Text(
                    AppStrings.logout,
                    style: GoogleFonts.cairo(fontWeight: FontWeight.w800),
                  ),
                  onTap: _logout,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

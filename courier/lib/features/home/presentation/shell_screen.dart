import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../core/constants/app_strings.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/brand_logo.dart';
import '../../account/presentation/account_screen.dart';
import '../../auth/data/courier_auth_api.dart';
import '../../auth/data/courier_session.dart';
import '../../orders/presentation/orders_screen.dart';

class ShellScreen extends StatefulWidget {
  const ShellScreen({super.key});

  @override
  State<ShellScreen> createState() => _ShellScreenState();
}

class _ShellScreenState extends State<ShellScreen> {
  int _index = 0;
  int _ordersGeneration = 0;
  int _accountGeneration = 0;
  bool _toggling = false;

  @override
  void initState() {
    super.initState();
    CourierAuthApi.instance.me().then((_) {}, onError: (_) {});
  }

  Future<void> _toggleOnline(bool value) async {
    if (_toggling) return;
    setState(() => _toggling = true);
    try {
      await CourierAuthApi.instance.setOnline(value);
    } catch (error) {
      if (!mounted) return;
      final message = error is ApiException ? error.message : error.toString();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    } finally {
      if (mounted) {
        setState(() {
          _toggling = false;
          _ordersGeneration++;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: CourierSession.instance,
      builder: (context, _) {
        final online = CourierSession.instance.user?.isOnline ?? false;
        return Scaffold(
          backgroundColor: AppTheme.background,
          body: Column(
            children: [
              SafeArea(
                bottom: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
                  child: Row(
                    children: [
                      const BrandLogoMark(size: 72),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          AppStrings.courierApp,
                          style: GoogleFonts.cairo(
                            fontWeight: FontWeight.w800,
                            fontSize: 16,
                            color: AppTheme.darkText,
                          ),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsetsDirectional.only(start: 10, end: 4),
                        decoration: BoxDecoration(
                          color: online ? AppTheme.primarySurface : Colors.white,
                          borderRadius: BorderRadius.circular(22),
                          border: Border.all(
                            color: online ? AppTheme.primary : AppTheme.primaryLight,
                          ),
                        ),
                        child: Row(
                          children: [
                            Text(
                              online ? AppStrings.available : AppStrings.stopped,
                              style: GoogleFonts.cairo(
                                fontWeight: FontWeight.w800,
                                fontSize: 13,
                                color: online ? AppTheme.primaryDark : AppTheme.mutedText,
                              ),
                            ),
                            Switch.adaptive(
                              value: online,
                              onChanged: _toggling ? null : _toggleOnline,
                              activeThumbColor: Colors.white,
                              activeTrackColor: AppTheme.primaryDark,
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Expanded(
                child: IndexedStack(
                  index: _index,
                  children: [
                    OrdersScreen(generation: _ordersGeneration),
                    AccountScreen(generation: _accountGeneration),
                  ],
                ),
              ),
            ],
          ),
          bottomNavigationBar: NavigationBar(
            selectedIndex: _index,
            onDestinationSelected: (value) {
              setState(() {
                _index = value;
                if (value == 0) {
                  _ordersGeneration++;
                } else {
                  _accountGeneration++;
                }
              });
            },
            backgroundColor: Colors.white,
            indicatorColor: AppTheme.primary,
            destinations: const [
              NavigationDestination(
                icon: Icon(Icons.receipt_long_outlined),
                selectedIcon: Icon(Icons.receipt_long_rounded),
                label: AppStrings.orders,
              ),
              NavigationDestination(
                icon: Icon(Icons.person_outline_rounded),
                selectedIcon: Icon(Icons.person_rounded),
                label: AppStrings.myAccount,
              ),
            ],
          ),
        );
      },
    );
  }
}

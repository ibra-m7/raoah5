import 'dart:async';

import 'package:flutter/material.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/brand_logo.dart';
import '../../../auth/data/services/auth_session.dart';
import '../../../auth/data/services/phone_auth_api.dart';

class SplashScreen extends StatefulWidget {
  static const routeName = '/splash';

  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _intro;

  @override
  void initState() {
    super.initState();
    _intro = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2600),
    );
    _startSequence();
  }

  Future<void> _startSequence() async {
    await Future.delayed(const Duration(milliseconds: 180));
    if (!mounted) return;
    await _intro.forward();
    await Future.delayed(const Duration(milliseconds: 700));
    if (mounted) await _navigateNext();
  }

  Future<void> _navigateNext() async {
    final session = AuthSession.instance;
    var route = AppRouter.onboarding;

    if (session.isLoggedIn) {
      try {
        await PhoneAuthApi.instance.me().timeout(const Duration(seconds: 6));
        route = AppRouter.main;
      } on ApiException catch (e) {
        if (e.statusCode == 401) {
          await session.clear();
          route = session.onboardingSeen ? AppRouter.main : AppRouter.onboarding;
        } else {
          route = AppRouter.main;
        }
      } catch (_) {
        route = AppRouter.main;
      }
    } else if (session.onboardingSeen) {
      route = AppRouter.main;
    }

    if (!mounted) return;
    Navigator.of(context).pushReplacementNamed(route);
  }

  @override
  void dispose() {
    _intro.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Color(0xFFFFFFFF),
              Color(0xFFF0FAF3),
              Color(0xFFE8F8EC),
            ],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              const Spacer(flex: 3),
              BrandLogoIntro(animation: _intro),
              const Spacer(flex: 2),
              AnimatedBuilder(
                animation: _intro,
                builder: (_, __) => Opacity(
                  opacity: Interval(0.75, 1.0).transform(_intro.value),
                  child: const _PulsingDots(),
                ),
              ),
              const SizedBox(height: 48),
            ],
          ),
        ),
      ),
    );
  }
}

class _PulsingDots extends StatefulWidget {
  const _PulsingDots();

  @override
  State<_PulsingDots> createState() => _PulsingDotsState();
}

class _PulsingDotsState extends State<_PulsingDots>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat(reverse: true);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(3, (i) {
        return AnimatedBuilder(
          animation: _controller,
          builder: (_, __) {
            final delay = i * 0.2;
            final t = ((_controller.value - delay).clamp(0.0, 1.0));
            final scale = 0.55 + 0.45 * t;
            return Container(
              margin: const EdgeInsets.symmetric(horizontal: 5),
              width: 8 * scale,
              height: 8 * scale,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppTheme.primaryDark.withValues(alpha: 0.35 + 0.5 * t),
              ),
            );
          },
        );
      }),
    );
  }
}

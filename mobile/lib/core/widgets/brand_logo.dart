import 'package:flutter/material.dart';

import '../constants/app_strings.dart';
import '../theme/app_theme.dart';

/// شعار روعة الخمسة الكامل من [assets/images/logo.png].
class BrandLogoMark extends StatelessWidget {
  /// عرض الشعار. الارتفاع يُحسب من أبعاد الملف (1536×1024).
  final double size;

  static const assetPath = 'assets/images/logo.png';
  static const double _aspect = 1536 / 1024;

  const BrandLogoMark({
    super.key,
    this.size = 160,
  });

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      assetPath,
      width: size,
      height: size / _aspect,
      fit: BoxFit.contain,
      filterQuality: FilterQuality.high,
      errorBuilder: (_, __, ___) => Icon(
        Icons.shopping_basket_rounded,
        color: AppTheme.primary,
        size: size * 0.45,
      ),
    );
  }
}

/// أنيميشن الافتتاح: الشعار الكامل ثم شعار التطبيق النصي.
class BrandLogoIntro extends StatelessWidget {
  final Animation<double> animation;
  final double markSize;

  const BrandLogoIntro({
    super.key,
    required this.animation,
    this.markSize = 300,
  });

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: animation,
      builder: (context, _) {
        final t = animation.value;
        final mark = Curves.elasticOut.transform(
          Interval(0.0, 0.42, curve: Curves.linear).transform(t),
        );
        final tagline = Curves.easeOutCubic.transform(
          Interval(0.55, 1.0, curve: Curves.linear).transform(t),
        );

        return Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Opacity(
              opacity: mark.clamp(0.0, 1.0),
              child: Transform.scale(
                scale: mark.clamp(0.0, 1.15),
                child: Hero(
                  tag: 'app_logo',
                  child: BrandLogoMark(size: markSize),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Opacity(
              opacity: tagline,
              child: Transform.translate(
                offset: Offset(0, (1 - tagline) * 10),
                child: Text(
                  AppStrings.appTagline,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.mutedText,
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

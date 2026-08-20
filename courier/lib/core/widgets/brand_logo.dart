import 'package:flutter/material.dart';

import '../constants/app_strings.dart';
import '../theme/app_theme.dart';

class BrandLogoMark extends StatelessWidget {
  final double size;
  static const assetPath = 'assets/images/logo.png';
  static const double _aspect = 1536 / 1024;

  const BrandLogoMark({super.key, this.size = 160});

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      assetPath,
      width: size,
      height: size / _aspect,
      fit: BoxFit.contain,
      filterQuality: FilterQuality.high,
      errorBuilder: (context, error, stackTrace) => Icon(
        Icons.delivery_dining_rounded,
        color: AppTheme.primary,
        size: size * 0.45,
      ),
    );
  }
}

class BrandLogoTile extends StatelessWidget {
  final double size;

  const BrandLogoTile({super.key, this.size = 58});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      padding: const EdgeInsets.all(6),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppTheme.primaryLight),
      ),
      child: BrandLogoMark(size: size - 4),
    );
  }
}

class BrandLockup extends StatelessWidget {
  final double markSize;

  const BrandLockup({super.key, this.markSize = 72});

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        BrandLogoMark(size: markSize),
        const SizedBox(height: 4),
        Text(
          AppStrings.courierApp,
          style: Theme.of(context).textTheme.labelMedium?.copyWith(
                color: AppTheme.mutedText,
                fontWeight: FontWeight.w700,
              ),
        ),
      ],
    );
  }
}

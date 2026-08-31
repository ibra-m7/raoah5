import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// زر رجوع دائري — سهم لليمين (>) ثابت في RTL.
class CircleBackButton extends StatelessWidget {
  final VoidCallback? onTap;
  final double size;

  const CircleBackButton({
    super.key,
    this.onTap,
    this.size = 38,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      shape: const CircleBorder(),
      elevation: 0,
      shadowColor: Colors.transparent,
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap ?? () => Navigator.of(context).maybePop(),
        child: Container(
          width: size,
          height: size,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(
              color: AppTheme.cardShadow,
              width: 0.5,
            ),
          ),
          child: Icon(
            Icons.chevron_right_rounded,
            size: size * 0.58,
            color: AppTheme.darkText,
            textDirection: TextDirection.ltr,
          ),
        ),
      ),
    );
  }
}

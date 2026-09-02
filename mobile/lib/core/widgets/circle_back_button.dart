import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// زر رجوع دائري — سهم لليمين (>) ثابت في RTL.
/// نفس مقاسات زر الرجوع في طلباتي / مراجعة الطلب: 30×30 وسهم 22.
class CircleBackButton extends StatelessWidget {
  static const double defaultSize = 30;
  static const double defaultIconSize = 22;
  static const Color defaultBorderColor = Color(0x14000000);

  final VoidCallback? onTap;
  final double size;
  final double? iconSize;
  final Color? backgroundColor;
  final Color? iconColor;
  final Color? borderColor;

  const CircleBackButton({
    super.key,
    this.onTap,
    this.size = defaultSize,
    this.iconSize,
    this.backgroundColor,
    this.iconColor,
    this.borderColor,
  });

  /// زر رجوع داخل AppBar — محاذاة موحّدة مع طلباتي.
  static Widget appBarLeading({VoidCallback? onTap}) {
    return Padding(
      padding: const EdgeInsetsDirectional.only(start: 12),
      child: Align(
        alignment: AlignmentDirectional.centerStart,
        child: CircleBackButton(onTap: onTap),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final bg = backgroundColor ?? Colors.white;
    final icon = iconColor ?? AppTheme.darkText;
    final border = borderColor ?? defaultBorderColor;
    final resolvedIconSize = iconSize ?? defaultIconSize;

    return Material(
      color: bg,
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
              color: border,
              width: 0.5,
            ),
          ),
          child: Icon(
            Icons.chevron_right_rounded,
            size: resolvedIconSize,
            color: icon,
            textDirection: TextDirection.ltr,
          ),
        ),
      ),
    );
  }
}

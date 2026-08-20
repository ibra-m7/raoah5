import 'package:flutter/material.dart';

import '../../../../core/theme/app_theme.dart';

/// سطر إثبات الشراء بأسلوب المتاجر: أيقونة اتجاه + «اشتراه +1K عميل» ثم «أمس».
class SoldProofLine extends StatelessWidget {
  final int soldCount;
  final double fontSize;

  const SoldProofLine({
    super.key,
    required this.soldCount,
    this.fontSize = 10,
  });

  static const _vivid = Color(0xFF1B9A4B);
  static const _muted = Color(0xFF8AAE96);

  String get _countPart {
    if (soldCount >= 1000) {
      final k = soldCount / 1000;
      final text = k == k.roundToDouble()
          ? k.toInt().toString()
          : k.toStringAsFixed(1).replaceAll(RegExp(r'\.0$'), '');
      return '+${text}K';
    }
    return '+$soldCount';
  }

  @override
  Widget build(BuildContext context) {
    if (soldCount <= 0) return const SizedBox.shrink();

    final vivid = TextStyle(
      fontSize: fontSize,
      fontWeight: FontWeight.w800,
      color: _vivid,
      height: 1.15,
    );

    return Row(
      children: [
        Transform.flip(
          flipX: true,
          child: Icon(
            Icons.trending_up_rounded,
            size: fontSize + 3,
            color: _vivid,
          ),
        ),
        const SizedBox(width: 3),
        Expanded(
          child: Text.rich(
            TextSpan(
              children: [
                TextSpan(text: 'اشتراه $_countPart عميل', style: vivid),
                TextSpan(
                  text: ' أمس',
                  style: vivid.copyWith(
                    color: _muted,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.start,
          ),
        ),
      ],
    );
  }
}

/// اسم منتج يتكيّف مع الطول: سطران كحد أقصى، وخط أصغر قليلاً للأسماء الطويلة.
class ProductNameText extends StatelessWidget {
  final String name;
  final int maxLines;
  final double baseSize;
  final FontWeight fontWeight;
  final Color color;
  final TextAlign textAlign;

  const ProductNameText(
    this.name, {
    super.key,
    this.maxLines = 2,
    this.baseSize = 13,
    this.fontWeight = FontWeight.w800,
    this.color = AppTheme.darkText,
    this.textAlign = TextAlign.start,
  });

  double get _size {
    final length = name.trim().length;
    if (length > 42) return baseSize * 0.82;
    if (length > 28) return baseSize * 0.90;
    return baseSize;
  }

  @override
  Widget build(BuildContext context) {
    return Text(
      name,
      maxLines: maxLines,
      overflow: TextOverflow.ellipsis,
      textAlign: textAlign,
      style: TextStyle(
        fontSize: _size,
        fontWeight: fontWeight,
        color: color,
        height: 1.22,
      ),
    );
  }
}

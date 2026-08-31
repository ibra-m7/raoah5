import 'package:flutter/material.dart';

import 'app_theme.dart';

/// ألوان خلفية أقسام الصفحة الرئيسية (تدرج يمين-أعلى → يسار-أسفل).
abstract final class HomeSectionGradient {
  static const Color defaultStart = AppTheme.primarySurface;
  static const Color defaultEnd = Color(0xFFF7FCF9);

  static List<Color> colors(String? backgroundColorHex) {
    final parsed = parseHex(backgroundColorHex);
    if (parsed == null) {
      return const [defaultStart, defaultEnd];
    }

    return [
      parsed,
      Color.lerp(parsed, Colors.white, 0.58) ?? defaultEnd,
    ];
  }

  static Color? parseHex(String? raw) {
    final value = (raw ?? '').trim();
    if (value.isEmpty) return null;

    final hex = value.startsWith('#') ? value.substring(1) : value;
    if (hex.length != 6) return null;

    final parsed = int.tryParse(hex, radix: 16);
    if (parsed == null) return null;

    return Color(0xFF000000 | parsed);
  }

  static Color? _tryParseHex(String? raw) => parseHex(raw);
}

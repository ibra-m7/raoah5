import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// أنماط النصوص المركزية للتطبيق - خط Cairo من Google Fonts
///
/// التسلسل الهرمي للأحجام:
/// ┌─────────────────────────────────────────────────┐
/// │  display*    → بانرات وشاشات التعريف (36-57 px) │
/// │  headline*   → عناوين الصفحات والأقسام (24-32 px)│
/// │  title*      → عناوين البطاقات والعناصر (14-22 px)│
/// │  body*       → نص المحتوى والأوصاف (12-16 px)   │
/// │  label*      → الأزرار والتسميات (11-14 px)      │
/// └─────────────────────────────────────────────────┘
abstract final class AppTextStyles {
  // ── الألوان الأساسية (Mint Green Palette) ────────────────────────
  static const Color _darkText  = Color(0xFF1B3A2D);
  static const Color _bodyText  = Color(0xFF2D4A38);
  static const Color _mutedText = Color(0xFF6B8A76);

  // ── Display: بانرات وشاشات التعريف ───────────────────────────────

  static TextStyle get displayLarge => GoogleFonts.cairo(
        fontSize: 52,
        fontWeight: FontWeight.w900,
        color: _darkText,
        height: 1.2,
        letterSpacing: -0.5,
      );

  static TextStyle get displayMedium => GoogleFonts.cairo(
        fontSize: 42,
        fontWeight: FontWeight.w800,
        color: _darkText,
        height: 1.25,
      );

  static TextStyle get displaySmall => GoogleFonts.cairo(
        fontSize: 34,
        fontWeight: FontWeight.w700,
        color: _darkText,
        height: 1.3,
      );

  // ── Headline: عناوين الصفحات والأقسام ────────────────────────────

  static TextStyle get headlineLarge => GoogleFonts.cairo(
        fontSize: 30,
        fontWeight: FontWeight.w800,
        color: _darkText,
        height: 1.3,
      );

  static TextStyle get headlineMedium => GoogleFonts.cairo(
        fontSize: 26,
        fontWeight: FontWeight.w700,
        color: _darkText,
        height: 1.35,
      );

  static TextStyle get headlineSmall => GoogleFonts.cairo(
        fontSize: 22,
        fontWeight: FontWeight.w700,
        color: _darkText,
        height: 1.4,
      );

  // ── Title: عناوين البطاقات والعناصر ──────────────────────────────

  static TextStyle get titleLarge => GoogleFonts.cairo(
        fontSize: 20,
        fontWeight: FontWeight.w700,
        color: _darkText,
        height: 1.4,
      );

  static TextStyle get titleMedium => GoogleFonts.cairo(
        fontSize: 16,
        fontWeight: FontWeight.w600,
        color: _darkText,
        height: 1.45,
      );

  static TextStyle get titleSmall => GoogleFonts.cairo(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        color: _bodyText,
        height: 1.5,
      );

  // ── Body: نص المحتوى والأوصاف ────────────────────────────────────

  static TextStyle get bodyLarge => GoogleFonts.cairo(
        fontSize: 16,
        fontWeight: FontWeight.w400,
        color: _bodyText,
        height: 1.6,
      );

  static TextStyle get bodyMedium => GoogleFonts.cairo(
        fontSize: 14,
        fontWeight: FontWeight.w400,
        color: _bodyText,
        height: 1.6,
      );

  static TextStyle get bodySmall => GoogleFonts.cairo(
        fontSize: 12,
        fontWeight: FontWeight.w400,
        color: _mutedText,
        height: 1.55,
      );

  // ── Label: الأزرار والتسميات والملاحظات ──────────────────────────

  static TextStyle get labelLarge => GoogleFonts.cairo(
        fontSize: 14,
        fontWeight: FontWeight.w700,
        color: _darkText,
        height: 1.4,
        letterSpacing: 0.1,
      );

  static TextStyle get labelMedium => GoogleFonts.cairo(
        fontSize: 12,
        fontWeight: FontWeight.w600,
        color: _bodyText,
        height: 1.4,
      );

  static TextStyle get labelSmall => GoogleFonts.cairo(
        fontSize: 11,
        fontWeight: FontWeight.w500,
        color: _mutedText,
        height: 1.4,
      );

  // ── أنماط مساعدة متخصصة ──────────────────────────────────────────

  /// سعر المنتج - كبير وبارز
  static TextStyle get price => GoogleFonts.cairo(
        fontSize: 18,
        fontWeight: FontWeight.w800,
        color: const Color(0xFF27AE60),
        height: 1.2,
      );

  /// سعر قديم مشطوب
  static TextStyle get priceStrikethrough => GoogleFonts.cairo(
        fontSize: 13,
        fontWeight: FontWeight.w400,
        color: _mutedText,
        decoration: TextDecoration.lineThrough,
        height: 1.2,
      );

  /// وصف المنتج - مريح للقراءة
  static TextStyle get productDescription => GoogleFonts.cairo(
        fontSize: 14,
        fontWeight: FontWeight.w400,
        color: _bodyText,
        height: 1.7,
      );

  /// اسم المنتج في البطاقة
  static TextStyle get productTitle => GoogleFonts.cairo(
        fontSize: 15,
        fontWeight: FontWeight.w700,
        color: _darkText,
        height: 1.4,
      );

  /// نص الفئات والتصنيفات
  static TextStyle get category => GoogleFonts.cairo(
        fontSize: 13,
        fontWeight: FontWeight.w600,
        color: const Color(0xFF27AE60),
        height: 1.3,
      );

  /// نص رسائل الدردشة المُرسَلة
  static TextStyle get chatMessageSent => GoogleFonts.cairo(
        fontSize: 14,
        fontWeight: FontWeight.w400,
        color: Colors.white,
        height: 1.6,
      );

  /// نص رسائل الدردشة المُستقبَلة
  static TextStyle get chatMessageReceived => GoogleFonts.cairo(
        fontSize: 14,
        fontWeight: FontWeight.w400,
        color: const Color(0xFF1B3A2D),
        height: 1.6,
      );

  /// تسمية حقول الإدخال
  static TextStyle get inputLabel => GoogleFonts.cairo(
        fontSize: 14,
        fontWeight: FontWeight.w500,
        color: _mutedText,
        height: 1.4,
      );

  /// نص placeholder في حقول الإدخال
  static TextStyle get inputHint => GoogleFonts.cairo(
        fontSize: 14,
        fontWeight: FontWeight.w400,
        color: _mutedText.withAlpha(180),
        height: 1.4,
      );

  /// عنوان القسم في الشاشات
  static TextStyle get sectionTitle => GoogleFonts.cairo(
        fontSize: 18,
        fontWeight: FontWeight.w800,
        color: _darkText,
        height: 1.3,
      );

  /// نص الزر الأساسي
  static TextStyle get buttonPrimary => GoogleFonts.cairo(
        fontSize: 16,
        fontWeight: FontWeight.w700,
        color: Colors.white,
        letterSpacing: 0.2,
      );

  /// نص الزر الثانوي
  static TextStyle get buttonSecondary => GoogleFonts.cairo(
        fontSize: 15,
        fontWeight: FontWeight.w600,
        color: const Color(0xFF27AE60),
        letterSpacing: 0.2,
      );

  /// نص الـ AppBar
  static TextStyle get appBarTitle => GoogleFonts.cairo(
        fontSize: 20,
        fontWeight: FontWeight.w800,
        color: Colors.white,
      );
}

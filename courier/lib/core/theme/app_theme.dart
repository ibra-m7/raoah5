import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'app_text_styles.dart';

/// الهوية البصرية الكاملة للتطبيق — روعة الخمسة
///
/// مستوحى من تطبيق "كيو" مع لون العلامة الأخضر الفاتح #88D498
/// الخلفية: منت فاتح مريح للعين
/// الحواف: دائرية للغاية (pill-shaped) — 30.0 للبحث والأزرار، 25.0 للبطاقات
abstract final class AppTheme {
  // ── الألوان الأساسية (علامة روعة الخمسة) ──────────────────────────────────
  static const Color primary        = Color(0xFF88D498);
  static const Color primaryDark    = Color(0xFF5FAF72);
  static const Color primaryLight   = Color(0xFFC8ECD3);
  static const Color primarySurface = Color(0xFFE8F8EC);

  /// خلفية الـ Scaffold
  static const Color background     = Color(0xFFF0FAF3);
  static const Color surface        = Color(0xFFFFFFFF); // سطح البطاقات أبيض نقي

  static const Color darkText       = Color(0xFF1B3A2D); // نص داكن مائل للأخضر
  static const Color bodyText       = Color(0xFF2D4A38); // نص المحتوى
  static const Color mutedText      = Color(0xFF6B8A76); // نص خافت
  static const Color cardShadow     = Color(0x14000000); // ظل البطاقات (8% أسود)

  // ── نصف قطر الحواف (Extra Rounded — pill-shaped) ─────────────────────────
  static const double radiusPill = 50.0; // شريط البحث — بيضاوي تماماً
  static const double radiusXL   = 25.0; // بطاقات وأزرار رئيسية
  static const double radiusLg   = 20.0; // حوارات وـ BottomSheet
  static const double radiusMd   = 16.0; // حقول الإدخال والـ SnackBar
  static const double radiusSm   = 30.0; // Chips — pill-shaped

  // ═════════════════════════════════════════════════════════════════════════
  static ThemeData buildTheme() {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: primary,
      primary: primary,
      onPrimary: Colors.white,
      secondary: primaryDark,
      onSecondary: Colors.white,
      surface: surface,
      onSurface: darkText,
      surfaceContainerHighest: primarySurface,
      brightness: Brightness.light,
    );

    TextStyle? withSar(TextStyle? style) {
      if (style == null) return null;
      final fallbacks = [...?style.fontFamilyFallback];
      if (!fallbacks.contains('SaudiRiyal')) {
        fallbacks.add('SaudiRiyal');
      }
      return style.copyWith(fontFamilyFallback: fallbacks);
    }

    final textTheme = TextTheme(
      displayLarge: withSar(AppTextStyles.displayLarge),
      displayMedium: withSar(AppTextStyles.displayMedium),
      displaySmall: withSar(AppTextStyles.displaySmall),
      headlineLarge: withSar(AppTextStyles.headlineLarge),
      headlineMedium: withSar(AppTextStyles.headlineMedium),
      headlineSmall: withSar(AppTextStyles.headlineSmall),
      titleLarge: withSar(AppTextStyles.titleLarge),
      titleMedium: withSar(AppTextStyles.titleMedium),
      titleSmall: withSar(AppTextStyles.titleSmall),
      bodyLarge: withSar(AppTextStyles.bodyLarge),
      bodyMedium: withSar(AppTextStyles.bodyMedium),
      bodySmall: withSar(AppTextStyles.bodySmall),
      labelLarge: withSar(AppTextStyles.labelLarge),
      labelMedium: withSar(AppTextStyles.labelMedium),
      labelSmall: withSar(AppTextStyles.labelSmall),
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: background,
      textTheme: textTheme,

      // ── AppBar — فاتح مع نص داكن (مستوحى من الصورة المرجعية) ──────────
      appBarTheme: AppBarTheme(
        backgroundColor: background,
        foregroundColor: darkText,
        surfaceTintColor: Colors.transparent,
        shadowColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        titleTextStyle: GoogleFonts.cairo(
          fontSize: 22,
          fontWeight: FontWeight.w900,
          color: darkText,
        ),
        toolbarTextStyle: GoogleFonts.cairo(
          fontSize: 14,
          fontWeight: FontWeight.w500,
          color: bodyText,
        ),
        iconTheme: const IconThemeData(color: darkText),
      ),

      // ── ElevatedButton ─────────────────────────────────────────────────
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primary,
          foregroundColor: Colors.white,
          elevation: 0,
          shadowColor: Colors.transparent,
          minimumSize: const Size(double.infinity, 54),
          textStyle: GoogleFonts.cairo(
            fontSize: 16,
            fontWeight: FontWeight.w700,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusXL),
          ),
        ),
      ),

      // ── TextButton ──────────────────────────────────────────────────────
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: primaryDark,
          textStyle: GoogleFonts.cairo(
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusXL),
          ),
        ),
      ),

      // ── OutlinedButton ──────────────────────────────────────────────────
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: primaryDark,
          side: const BorderSide(color: primary, width: 1.5),
          minimumSize: const Size(double.infinity, 54),
          textStyle: GoogleFonts.cairo(
            fontSize: 15,
            fontWeight: FontWeight.w700,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusXL),
          ),
        ),
      ),

      // ── Card — بيضاء نقية بظل ناعم بلا حدود (مثل الصورة المرجعية) ────
      cardTheme: CardThemeData(
        color: surface,
        surfaceTintColor: Colors.transparent,
        elevation: 4,
        shadowColor: cardShadow,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusXL),
        ),
        margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 0),
      ),

      // ── Input Fields — pill-shaped كما في الصورة المرجعية ──────────────
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusPill),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusPill),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusPill),
          borderSide: const BorderSide(color: primary, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusPill),
          borderSide: const BorderSide(color: Colors.redAccent),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusPill),
          borderSide: const BorderSide(color: Colors.redAccent, width: 1.8),
        ),
        labelStyle: GoogleFonts.cairo(
          fontSize: 14,
          fontWeight: FontWeight.w500,
          color: mutedText,
        ),
        hintStyle: GoogleFonts.cairo(
          fontSize: 14,
          fontWeight: FontWeight.w400,
          color: mutedText,
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 20,
          vertical: 16,
        ),
      ),

      // ── SnackBar ────────────────────────────────────────────────────────
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: darkText,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMd),
        ),
        contentTextStyle: GoogleFonts.cairo(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: Colors.white,
        ),
      ),

      // ── Dialog ──────────────────────────────────────────────────────────
      dialogTheme: DialogThemeData(
        backgroundColor: surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLg),
        ),
        titleTextStyle: GoogleFonts.cairo(
          fontSize: 18,
          fontWeight: FontWeight.w800,
          color: darkText,
        ),
        contentTextStyle: GoogleFonts.cairo(
          fontSize: 14,
          fontWeight: FontWeight.w400,
          color: mutedText,
        ),
      ),

      // ── BottomSheet ─────────────────────────────────────────────────────
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(
            top: Radius.circular(radiusLg),
          ),
        ),
        showDragHandle: true,
      ),

      // ── Chip — pill-shaped (مثل تصنيفات الصورة المرجعية) ────────────────
      chipTheme: ChipThemeData(
        backgroundColor: primarySurface,
        selectedColor: primary,
        checkmarkColor: Colors.white,
        labelStyle: GoogleFonts.cairo(
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: primaryDark,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusSm),
          side: const BorderSide(color: primaryLight),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      ),

      // ── Bottom Navigation Bar — بيضاء مرتفعة قليلاً مع أيقونة وسطى بارزة
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        backgroundColor: surface,
        selectedItemColor: primaryDark,
        unselectedItemColor: mutedText,
        type: BottomNavigationBarType.fixed,
        selectedLabelStyle: GoogleFonts.cairo(
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
        unselectedLabelStyle: GoogleFonts.cairo(
          fontSize: 12,
          fontWeight: FontWeight.w500,
        ),
        elevation: 8,
      ),

      // ── FloatingActionButton ────────────────────────────────────────────
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: primary,
        foregroundColor: Colors.white,
        elevation: 4,
        shape: CircleBorder(),
      ),

      // ── Divider ─────────────────────────────────────────────────────────
      dividerTheme: const DividerThemeData(
        color: primaryLight,
        thickness: 1,
        space: 1,
      ),

      // ── ListTile ────────────────────────────────────────────────────────
      listTileTheme: ListTileThemeData(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusSm),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      ),
    );
  }
}

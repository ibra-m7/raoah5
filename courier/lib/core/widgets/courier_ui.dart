import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../constants/app_strings.dart';
import '../theme/app_theme.dart';

class MoneyText extends StatelessWidget {
  final double amount;
  final double size;
  final FontWeight weight;
  final Color? color;

  const MoneyText(
    this.amount, {
    super.key,
    this.size = 15,
    this.weight = FontWeight.w800,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Text(
      '${amount.toStringAsFixed(2)} ${AppStrings.currency}',
      style: GoogleFonts.cairo(
        fontSize: size,
        fontWeight: weight,
        color: color ?? AppTheme.darkText,
      ).copyWith(fontFamilyFallback: const ['SaudiRiyal']),
    );
  }
}

class CourierPanel extends StatelessWidget {
  final Widget child;
  final Color? color;
  final EdgeInsetsGeometry padding;

  const CourierPanel({
    super.key,
    required this.child,
    this.color,
    this.padding = const EdgeInsets.all(16),
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: padding,
      decoration: BoxDecoration(
        color: color ?? Colors.white,
        borderRadius: BorderRadius.circular(22),
        boxShadow: const [
          BoxShadow(color: AppTheme.cardShadow, blurRadius: 14, offset: Offset(0, 4)),
        ],
      ),
      child: child,
    );
  }
}

class DetailInfoRow extends StatelessWidget {
  final IconData icon;
  final String text;
  final Color? textColor;
  final bool bold;

  const DetailInfoRow({
    super.key,
    required this.icon,
    required this.text,
    this.textColor,
    this.bold = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: AppTheme.primarySurface,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: AppTheme.primaryDark, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                text,
                style: GoogleFonts.cairo(
                  fontSize: 14,
                  height: 1.45,
                  fontWeight: bold ? FontWeight.w800 : FontWeight.w600,
                  color: textColor ?? AppTheme.darkText,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class CourierActionButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final VoidCallback? onTap;

  const CourierActionButton({
    super.key,
    required this.label,
    required this.icon,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppTheme.primarySurface,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, color: AppTheme.primaryDark),
              const SizedBox(width: 8),
              Text(
                label,
                style: GoogleFonts.cairo(
                  fontWeight: FontWeight.w800,
                  fontSize: 15,
                  color: AppTheme.primaryDark,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

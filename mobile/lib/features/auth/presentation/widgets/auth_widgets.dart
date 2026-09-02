import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_theme.dart';

// ── خلفية فاخرة متناسقة مع هوية التطبيق ─────────────────────────────────────
class AuthGradientBackground extends StatelessWidget {
  const AuthGradientBackground({super.key});

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        const ColoredBox(color: AppTheme.background),
        DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                AppTheme.primarySurface.withValues(alpha: 0.95),
                AppTheme.background,
                AppTheme.background,
              ],
              stops: const [0.0, 0.42, 1.0],
            ),
          ),
        ),
        Positioned(
          top: -120,
          right: -90,
          child: _glowBlob(260, AppTheme.primary.withValues(alpha: 0.16)),
        ),
        Positioned(
          top: 80,
          left: -70,
          child: _glowBlob(180, AppTheme.primaryLight.withValues(alpha: 0.55)),
        ),
        Positioned(
          bottom: -60,
          right: -40,
          child: _glowBlob(200, AppTheme.primary.withValues(alpha: 0.1)),
        ),
        const Positioned.fill(child: CustomPaint(painter: _AuthTopWavePainter())),
      ],
    );
  }

  Widget _glowBlob(double size, Color color) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(shape: BoxShape.circle, color: color),
      );
}

class _AuthTopWavePainter extends CustomPainter {
  const _AuthTopWavePainter();

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..shader = LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          AppTheme.primary.withValues(alpha: 0.22),
          AppTheme.primaryDark.withValues(alpha: 0.08),
        ],
      ).createShader(Rect.fromLTWH(0, 0, size.width, size.height * 0.34));

    final path = Path()
      ..moveTo(0, 0)
      ..lineTo(size.width, 0)
      ..lineTo(size.width, size.height * 0.18)
      ..quadraticBezierTo(
        size.width * 0.72,
        size.height * 0.30,
        size.width * 0.5,
        size.height * 0.22,
      )
      ..quadraticBezierTo(
        size.width * 0.18,
        size.height * 0.12,
        0,
        size.height * 0.20,
      )
      ..close();

    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

// ── رأس شاشة المصادقة ───────────────────────────────────────────────────────
class AuthScreenHeader extends StatelessWidget {
  final String title;
  final String? subtitle;
  final double logoSize;

  const AuthScreenHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.logoSize = 128,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          AppStrings.appName,
          style: TextStyle(
            fontSize: 11.5,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.4,
            color: AppTheme.primaryDark.withValues(alpha: 0.85),
          ),
        ),
        const SizedBox(height: 10),
        Text(
          title,
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.w900,
            height: 1.2,
            color: AppTheme.darkText,
          ),
        ),
        if (subtitle != null && subtitle!.trim().isNotEmpty) ...[
          const SizedBox(height: 6),
          Text(
            subtitle!,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 12.5,
              height: 1.45,
              fontWeight: FontWeight.w500,
              color: AppTheme.mutedText,
            ),
          ),
        ],
      ],
    );
  }
}

// ── بطاقة المحتوى ─────────────────────────────────────────────────────────────
class AuthGlassCard extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry padding;

  const AuthGlassCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.fromLTRB(18, 18, 18, 16),
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(26),
        border: Border.all(
          color: AppTheme.primaryLight.withValues(alpha: 0.85),
        ),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primaryDark.withValues(alpha: 0.07),
            blurRadius: 28,
            offset: const Offset(0, 12),
          ),
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      padding: padding,
      child: child,
    );
  }
}

// ── تسمية حقل صغيرة ───────────────────────────────────────────────────────────
class AuthFieldLabel extends StatelessWidget {
  final String text;
  final String? hint;

  const AuthFieldLabel({super.key, required this.text, this.hint});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          text,
          style: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w800,
            color: AppTheme.darkText,
          ),
        ),
        if (hint != null && hint!.isNotEmpty) ...[
          const SizedBox(height: 3),
          Text(
            hint!,
            style: const TextStyle(
              fontSize: 11,
              height: 1.35,
              color: AppTheme.mutedText,
            ),
          ),
        ],
      ],
    );
  }
}

// ── حقل الإدخال ──────────────────────────────────────────────────────────────
class AuthGlassField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final IconData icon;
  final bool obscureText;
  final TextInputType? keyboardType;
  final Widget? suffixIcon;
  final String? Function(String?)? validator;
  final TextInputAction? textInputAction;
  final int? maxLength;
  final List<TextInputFormatter>? inputFormatters;
  final TextAlign textAlign;
  final TextDirection? textDirection;
  final bool autofocus;
  final ValueChanged<String>? onChanged;
  final String? hintText;
  final bool showLabel;

  const AuthGlassField({
    super.key,
    required this.controller,
    required this.label,
    required this.icon,
    this.obscureText = false,
    this.keyboardType,
    this.suffixIcon,
    this.validator,
    this.textInputAction,
    this.maxLength,
    this.inputFormatters,
    this.textAlign = TextAlign.start,
    this.textDirection,
    this.autofocus = false,
    this.onChanged,
    this.hintText,
    this.showLabel = true,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (showLabel) ...[
          AuthFieldLabel(text: label),
          const SizedBox(height: 8),
        ],
        TextFormField(
          controller: controller,
          obscureText: obscureText,
          keyboardType: keyboardType,
          textInputAction: textInputAction,
          maxLength: maxLength,
          inputFormatters: inputFormatters,
          textAlign: textAlign,
          textDirection: textDirection,
          autofocus: autofocus,
          onChanged: onChanged,
          style: const TextStyle(
            color: AppTheme.darkText,
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
          autovalidateMode: AutovalidateMode.onUserInteraction,
          validator: validator,
          decoration: InputDecoration(
            hintText: hintText,
            hintStyle: TextStyle(
              color: AppTheme.mutedText.withValues(alpha: 0.65),
              fontSize: 13.5,
              fontWeight: FontWeight.w500,
            ),
            prefixIcon: Padding(
              padding: const EdgeInsetsDirectional.only(start: 12, end: 4),
              child: Container(
                width: 34,
                height: 34,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: AppTheme.primarySurface,
                  borderRadius: BorderRadius.circular(11),
                ),
                child: Icon(icon, color: AppTheme.primaryDark, size: 18),
              ),
            ),
            prefixIconConstraints: const BoxConstraints(minWidth: 52),
            suffixIcon: suffixIcon,
            filled: true,
            fillColor: AppTheme.primarySurface.withValues(alpha: 0.45),
            counterText: '',
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 13,
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide(
                color: AppTheme.primaryLight.withValues(alpha: 0.9),
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide(
                color: AppTheme.primaryLight.withValues(alpha: 0.9),
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(color: AppTheme.primary, width: 1.4),
            ),
            errorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide(color: Colors.red.shade300),
            ),
            focusedErrorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide(color: Colors.red.shade400, width: 1.3),
            ),
            errorStyle: TextStyle(
              color: Colors.red.shade700,
              fontSize: 11,
              height: 1.2,
            ),
          ),
        ),
      ],
    );
  }
}

// ── شارات ثقة صغيرة ─────────────────────────────────────────────────────────
class AuthTrustRow extends StatelessWidget {
  const AuthTrustRow({super.key});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: const [
        Expanded(child: _AuthTrustChip(icon: Icons.verified_user_outlined, label: 'آمن')),
        SizedBox(width: 8),
        Expanded(child: _AuthTrustChip(icon: Icons.bolt_rounded, label: 'سريع')),
        SizedBox(width: 8),
        Expanded(child: _AuthTrustChip(icon: Icons.chat_rounded, label: 'واتساب')),
      ],
    );
  }
}

class _AuthTrustChip extends StatelessWidget {
  final IconData icon;
  final String label;

  const _AuthTrustChip({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
      decoration: BoxDecoration(
        color: AppTheme.surface.withValues(alpha: 0.72),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppTheme.primaryLight.withValues(alpha: 0.75)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 13, color: AppTheme.primaryDark),
          const SizedBox(width: 4),
          Flexible(
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w700,
                color: AppTheme.bodyText,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── زر الدخول الرئيسي ────────────────────────────────────────────────────────
class AuthPrimaryButton extends StatelessWidget {
  final String label;
  final bool isLoading;
  final VoidCallback onPressed;

  const AuthPrimaryButton({
    super.key,
    required this.label,
    required this.isLoading,
    required this.onPressed,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 48,
      child: DecoratedBox(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(AppTheme.radiusPill),
          gradient: const LinearGradient(
            colors: [AppTheme.primary, AppTheme.primaryDark],
          ),
          boxShadow: [
            BoxShadow(
              color: AppTheme.primaryDark.withValues(alpha: 0.22),
              blurRadius: 14,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: ElevatedButton(
          onPressed: isLoading ? null : onPressed,
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.transparent,
            shadowColor: Colors.transparent,
            foregroundColor: Colors.white,
            disabledBackgroundColor: Colors.transparent,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppTheme.radiusPill),
            ),
          ),
          child: isLoading
              ? const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2.2,
                    color: Colors.white,
                  ),
                )
              : Text(
                  label,
                  style: const TextStyle(
                    fontSize: 14.5,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.2,
                  ),
                ),
        ),
      ),
    );
  }
}

// ── فاصل "أو" ────────────────────────────────────────────────────────────────
class AuthOrDivider extends StatelessWidget {
  const AuthOrDivider({super.key});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Divider(
            color: AppTheme.primaryLight.withValues(alpha: 0.9),
            thickness: 1,
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10),
          child: Text(
            AppStrings.orDivider,
            style: TextStyle(
              color: AppTheme.mutedText.withValues(alpha: 0.85),
              fontSize: 11.5,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        Expanded(
          child: Divider(
            color: AppTheme.primaryLight.withValues(alpha: 0.9),
            thickness: 1,
          ),
        ),
      ],
    );
  }
}

// ── زر Google ─────────────────────────────────────────────────────────────────
class AuthGoogleButton extends StatelessWidget {
  final bool isLoading;
  final VoidCallback onPressed;

  const AuthGoogleButton({
    super.key,
    required this.isLoading,
    required this.onPressed,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 48,
      child: OutlinedButton(
        onPressed: isLoading ? null : onPressed,
        style: OutlinedButton.styleFrom(
          foregroundColor: AppTheme.darkText,
          side: BorderSide(color: AppTheme.primaryLight.withValues(alpha: 0.95)),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppTheme.radiusPill),
          ),
          backgroundColor: AppTheme.surface,
        ),
        child: isLoading
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2.2,
                  color: AppTheme.primaryDark,
                ),
              )
            : Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  _GoogleColorIcon(),
                  const SizedBox(width: 8),
                  const Text(
                    AppStrings.loginWithGoogle,
                    style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700),
                  ),
                ],
              ),
      ),
    );
  }
}

class _GoogleColorIcon extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return const SizedBox(width: 18, height: 18, child: _GooglePainterWidget());
  }
}

class _GooglePainterWidget extends StatelessWidget {
  const _GooglePainterWidget();

  @override
  Widget build(BuildContext context) {
    return CustomPaint(painter: _GoogleLogoPainter());
  }
}

class _GoogleLogoPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final cx = size.width / 2;
    final cy = size.height / 2;
    final r = size.width / 2;

    final colors = [
      const Color(0xFF4285F4),
      const Color(0xFF34A853),
      const Color(0xFFFBBC05),
      const Color(0xFFEA4335),
    ];
    final sweeps = [90.0, 90.0, 90.0, 90.0];
    final starts = [-45.0, 45.0, 135.0, 225.0];

    for (int i = 0; i < 4; i++) {
      final paint = Paint()
        ..color = colors[i]
        ..style = PaintingStyle.stroke
        ..strokeWidth = size.width * 0.22;
      canvas.drawArc(
        Rect.fromCircle(center: Offset(cx, cy), radius: r * 0.72),
        starts[i] * 3.14159 / 180,
        sweeps[i] * 3.14159 / 180,
        false,
        paint,
      );
    }

    final barPaint = Paint()
      ..color = AppTheme.surface
      ..strokeWidth = size.height * 0.22;
    canvas.drawLine(Offset(cx, cy), Offset(cx + r * 0.85, cy), barPaint);
  }

  @override
  bool shouldRepaint(_) => false;
}

// ── Overlay تحميل ─────────────────────────────────────────────────────────────
class AuthLoadingOverlay extends StatelessWidget {
  final String? message;

  const AuthLoadingOverlay({super.key, this.message});

  @override
  Widget build(BuildContext context) {
    return AbsorbPointer(
      absorbing: true,
      child: Container(
        color: AppTheme.darkText.withValues(alpha: 0.18),
        alignment: Alignment.center,
        child: Container(
          width: 132,
          padding: const EdgeInsets.symmetric(vertical: 22, horizontal: 18),
          decoration: BoxDecoration(
            color: AppTheme.surface,
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: AppTheme.primaryLight),
            boxShadow: [
              BoxShadow(
                color: AppTheme.primaryDark.withValues(alpha: 0.12),
                blurRadius: 24,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(
                width: 30,
                height: 30,
                child: CircularProgressIndicator(
                  strokeWidth: 2.6,
                  color: AppTheme.primaryDark,
                ),
              ),
              if (message != null) ...[
                const SizedBox(height: 12),
                Text(
                  message!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: AppTheme.bodyText,
                    fontSize: 11.5,
                    fontWeight: FontWeight.w600,
                    height: 1.35,
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

// ── أزرار علوية ───────────────────────────────────────────────────────────────
class AuthBackButton extends StatelessWidget {
  final VoidCallback onPressed;

  const AuthBackButton({super.key, required this.onPressed});

  @override
  Widget build(BuildContext context) {
    return _AuthIconButton(
      icon: Icons.arrow_forward_ios_rounded,
      onPressed: onPressed,
    );
  }
}

class AuthCloseButton extends StatelessWidget {
  final VoidCallback onPressed;
  final String? tooltip;

  const AuthCloseButton({
    super.key,
    required this.onPressed,
    this.tooltip,
  });

  @override
  Widget build(BuildContext context) {
    return _AuthIconButton(
      icon: Icons.close_rounded,
      onPressed: onPressed,
      tooltip: tooltip,
    );
  }
}

class _AuthIconButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onPressed;
  final String? tooltip;

  const _AuthIconButton({
    required this.icon,
    required this.onPressed,
    this.tooltip,
  });

  @override
  Widget build(BuildContext context) {
    return IconButton(
      onPressed: onPressed,
      tooltip: tooltip,
      style: IconButton.styleFrom(
        minimumSize: const Size(40, 40),
        backgroundColor: AppTheme.surface.withValues(alpha: 0.88),
        foregroundColor: AppTheme.darkText,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: BorderSide(color: AppTheme.primaryLight.withValues(alpha: 0.9)),
        ),
      ),
      icon: Icon(icon, size: 18),
    );
  }
}

// ── رابط نصي صغير ───────────────────────────────────────────────────────────
class AuthTextLink extends StatelessWidget {
  final String label;
  final VoidCallback onTap;
  final bool underline;

  const AuthTextLink({
    super.key,
    required this.label,
    required this.onTap,
    this.underline = false,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Text(
        label,
        style: TextStyle(
          fontSize: 12.5,
          fontWeight: FontWeight.w700,
          color: AppTheme.primaryDark,
          decoration: underline ? TextDecoration.underline : null,
          decorationColor: AppTheme.primaryDark.withValues(alpha: 0.45),
        ),
      ),
    );
  }
}

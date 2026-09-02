import 'package:flutter/material.dart';

/// ══════════════════════════════════════════════════════════════════════════
/// الهوية البصرية الفخمة لصفحات الحساب.
/// شريطان منحنيان متقاطعان — نفس أسلوب المرجع — بلوحة أخضر زمرّدي.
/// ══════════════════════════════════════════════════════════════════════════

const kLuxeDeepA = Color(0xFF14603C);
const kLuxeDeepB = Color(0xFF2C8B55);
const kLuxeDeepC = Color(0xFF1A7348);

const kLuxeVividA = Color(0xFF35A863);
const kLuxeVividB = Color(0xFF8ADCA6);
const kLuxeVividC = Color(0xFF5BC47E);

const kLuxeBodyBg = Color(0xFFF6F8F7);
const kLuxeRowTint = Color(0xFFEDF6F0);
const kLuxeHairline = Color(0xFFE9EFEB);
const kLuxeRowLabel = Color(0xFF4F6B5B);
const kLuxeFooterText = Color(0xFF8C9E93);
const kLuxeGold = Color(0xFFD9B45B);

/// ارتفاع شريط الموجة في هيدر الحساب.
const kLuxeProfileWaveHeight = 76.0;

/// فراغ علوي للأفاتار داخل الهيدر.
const kLuxeHeaderTopPad = 6.0;

/// مساحة محجوزة لكتلة الاسم فوق الموجة.
const kLuxeHeaderNameBlock = 36.0;

/// فراغ بين الأفاتار وكتلة الاسم.
const kLuxeHeaderAvatarNameGap = 14.0;

/// ── شريطان منحنيان متقاطعان مع توهج وعمق ─────────────────────────────────
class LuxeWavePainter extends CustomPainter {
  const LuxeWavePainter();

  static Path _backRibbon(double w, double h) {
    return Path()
      ..moveTo(0, 0)
      ..cubicTo(w * 0.25, h * 0.1, w * 0.4, h * 0.7, w, h * 0.4)
      ..lineTo(w, h * 0.8)
      ..cubicTo(w * 0.4, h * 0.9, w * 0.25, h * 0.5, 0, h * 0.4)
      ..close();
  }

  static Path _frontRibbon(double w, double h) {
    return Path()
      ..moveTo(0, h * 0.1)
      ..cubicTo(w * 0.4, h * 0.9, w * 0.7, h * 0.9, w, h * 0.1)
      ..lineTo(w, h * 0.6)
      ..cubicTo(w * 0.7, h * 1.0, w * 0.4, h * 1.0, 0, h * 0.5)
      ..close();
  }

  static Path _backTopEdge(double w, double h) {
    return Path()
      ..moveTo(0, 0)
      ..cubicTo(w * 0.25, h * 0.1, w * 0.4, h * 0.7, w, h * 0.4);
  }

  static Path _frontTopEdge(double w, double h) {
    return Path()
      ..moveTo(0, h * 0.1)
      ..cubicTo(w * 0.4, h * 0.9, w * 0.7, h * 0.9, w, h * 0.1);
  }

  @override
  void paint(Canvas canvas, Size size) {
    final w = size.width;
    final h = size.height;
    final rect = Rect.fromLTWH(0, 0, w, h);

    // خلفية بيضاء تتلاشى نحو جسم الصفحة أسفل الشريط.
    canvas.drawRect(
      rect,
      Paint()
        ..shader = const LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Colors.white, Colors.white, kLuxeBodyBg],
          stops: [0.0, 0.72, 1.0],
        ).createShader(rect),
    );

    final back = _backRibbon(w, h);
    final front = _frontRibbon(w, h);
    final union = Path.combine(PathOperation.union, back, front);

    // توهج/ظل ناعم أسفل الشريط — إحساس 3D.
    canvas.drawPath(
      union.shift(const Offset(0, 10)),
      Paint()
        ..color = kLuxeDeepA.withValues(alpha: 0.20)
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 22),
    );
    canvas.drawPath(
      union.shift(const Offset(0, 5)),
      Paint()
        ..color = kLuxeVividA.withValues(alpha: 0.10)
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 14),
    );

    // الشريط الخلفي (غامق — يسار).
    canvas.drawPath(
      back,
      Paint()
        ..shader = const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [kLuxeDeepB, kLuxeDeepA, kLuxeDeepC],
          stops: [0.0, 0.55, 1.0],
        ).createShader(rect),
    );

    // الشريط الأمامي (لامع — يمين).
    canvas.drawPath(
      front,
      Paint()
        ..shader = const LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [kLuxeVividB, kLuxeVividC, kLuxeVividA],
          stops: [0.0, 0.45, 1.0],
        ).createShader(rect),
    );

    // لمعة حريرية على الشريط الأمامي.
    canvas.save();
    canvas.clipPath(front);
    canvas.drawRect(
      rect,
      Paint()
        ..shader = LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            Colors.white.withValues(alpha: 0.28),
            Colors.white.withValues(alpha: 0.06),
            Colors.white.withValues(alpha: 0),
          ],
          stops: const [0.0, 0.35, 0.75],
        ).createShader(rect),
    );
    canvas.restore();

    // لمعة خفيفة على الشريط الخلفي.
    canvas.save();
    canvas.clipPath(back);
    canvas.drawRect(
      rect,
      Paint()
        ..shader = LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.centerRight,
          colors: [
            Colors.white.withValues(alpha: 0.14),
            Colors.white.withValues(alpha: 0),
          ],
        ).createShader(rect),
    );
    canvas.restore();

    // خط علوي لامع على حافة كل شريط — يعزّز العمق.
    final edgePaint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.4
      ..shader = LinearGradient(
        begin: Alignment.centerLeft,
        end: Alignment.centerRight,
        colors: [
          Colors.white.withValues(alpha: 0.55),
          Colors.white.withValues(alpha: 0.18),
          Colors.white.withValues(alpha: 0.42),
        ],
      ).createShader(rect);

    canvas.drawPath(_backTopEdge(w, h), edgePaint);
    canvas.drawPath(_frontTopEdge(w, h), edgePaint..strokeWidth = 1.2);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// خط ذهبي رقيق متلاشي الأطراف.
class LuxeGoldRule extends StatelessWidget {
  final double width;

  const LuxeGoldRule({super.key, this.width = 38});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: 1.6,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(2),
        gradient: const LinearGradient(
          colors: [Color(0x00D9B45B), kLuxeGold, Color(0x00D9B45B)],
        ),
      ),
    );
  }
}

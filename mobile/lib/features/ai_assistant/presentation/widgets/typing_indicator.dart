import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

const _kMintBrand = Color(0xFF88D498);
const _kAssistBubbleBg = Color(0x1A88D498);

/// مؤشر "المساعد يكتب..." بثلاث نقاط تتحرك بنمط متسلسل
///
/// يُستخدم في:
///   - داخل فقاعة المساعد عند isLoading = true
///   - في _ThinkingIndicator داخل قائمة الرسائل
class TypingIndicator extends StatefulWidget {
  final Color dotColor;
  final double dotSize;
  final Duration speed;

  const TypingIndicator({
    super.key,
    this.dotColor = const Color(0xFF88D498),
    this.dotSize = 9.0,
    this.speed = const Duration(milliseconds: 1100),
  });

  @override
  State<TypingIndicator> createState() => _TypingIndicatorState();
}

class _TypingIndicatorState extends State<TypingIndicator>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  // كل نقطة لها 3 مراحل: صعود، نزول، راحة
  late List<Animation<double>> _bounceAnims;
  late List<Animation<Color?>> _colorAnims;

  static const _dotCount = 3;
  static const _stagger  = 0.18; // تأخر كل نقطة عن التالية

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(vsync: this, duration: widget.speed)
      ..repeat();

    _bounceAnims = List.generate(_dotCount, (i) {
      final start = i * _stagger;
      final end   = start + 0.38;

      return TweenSequence<double>([
        TweenSequenceItem(
          tween: Tween(begin: 0.0, end: -10.0)
              .chain(CurveTween(curve: Curves.easeOut)),
          weight: 40,
        ),
        TweenSequenceItem(
          tween: Tween(begin: -10.0, end: 0.0)
              .chain(CurveTween(curve: Curves.bounceOut)),
          weight: 60,
        ),
      ]).animate(
        CurvedAnimation(
          parent: _controller,
          curve: Interval(
            (start).clamp(0.0, 1.0),
            (end).clamp(0.0, 1.0),
            curve: Curves.linear,
          ),
        ),
      );
    });

    _colorAnims = List.generate(_dotCount, (i) {
      final start = i * _stagger;
      final end   = start + 0.38;
      return ColorTween(
        begin: widget.dotColor.withValues(alpha: 0.35),
        end: widget.dotColor,
      ).animate(
        CurvedAnimation(
          parent: _controller,
          curve: Interval(
            start.clamp(0.0, 1.0),
            end.clamp(0.0, 1.0),
            curve: Curves.easeInOut,
          ),
        ),
      );
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        return Row(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: List.generate(_dotCount, (i) {
            return Padding(
              padding: EdgeInsets.symmetric(horizontal: widget.dotSize * 0.22),
              child: Transform.translate(
                offset: Offset(0, _bounceAnims[i].value),
                child: _Dot(
                  size: widget.dotSize,
                  color: _colorAnims[i].value ?? widget.dotColor,
                ),
              ),
            );
          }),
        );
      },
    );
  }
}

// ── نقطة واحدة ────────────────────────────────────────────────────────────────
class _Dot extends StatelessWidget {
  final double size;
  final Color color;
  const _Dot({required this.size, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: color,
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: color.withValues(alpha: 0.4),
            blurRadius: size * 0.7,
          ),
        ],
      ),
    );
  }
}

/// غلاف جاهز يعرض "روعة يفكر..." داخل فقاعة المساعد
class AssistantTypingBubble extends StatelessWidget {
  const AssistantTypingBubble({super.key});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(left: 12, right: 52, top: 4, bottom: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        mainAxisSize: MainAxisSize.min,
        children: [
          // أفاتار
          Container(
            width: 32,
            height: 32,
            margin: const EdgeInsets.only(left: 8, bottom: 2),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF6BC489), _kMintBrand],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                'ر',
                style: GoogleFonts.cairo(
                  color: Colors.white,
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
          // فقاعة
          Container(
            padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 18),
            decoration: BoxDecoration(
              color: _kAssistBubbleBg,
              borderRadius: BorderRadius.circular(15),
              border: Border.all(
                color: _kMintBrand.withValues(alpha: 0.22),
              ),
              boxShadow: [
                BoxShadow(
                  color: _kMintBrand.withValues(alpha: 0.08),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: const TypingIndicator(),
          ),
        ],
      ),
    );
  }
}

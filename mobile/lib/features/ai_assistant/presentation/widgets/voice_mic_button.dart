import 'dart:math' as math;
import 'package:flutter/material.dart';
import '../cubit/ai_controller_cubit.dart';

class VoiceMicButton extends StatefulWidget {
  final AiProcessingStatus status;
  final VoidCallback onTap;
  final VoidCallback? onLongPressStart;
  final VoidCallback? onLongPressEnd;

  const VoiceMicButton({
    super.key,
    required this.status,
    required this.onTap,
    this.onLongPressStart,
    this.onLongPressEnd,
  });

  @override
  State<VoiceMicButton> createState() => _VoiceMicButtonState();
}

class _VoiceMicButtonState extends State<VoiceMicButton>
    with TickerProviderStateMixin {

  // نبض القلب — ينبض مرتين ثم يستريح (Heartbeat pattern)
  late AnimationController _heartbeatController;
  late Animation<double> _heartbeatAnim;

  // موجات السونار المتمددة
  late AnimationController _sonarController;

  // لمعان الحافة الدائرية
  late AnimationController _glowController;
  late Animation<double> _glowAnim;

  // انتقال ناعم بين الحالات
  late AnimationController _colorController;
  late Animation<Color?> _buttonColorAnim;

  @override
  void initState() {
    super.initState();

    // نبض القلب: ضربة سريعة → ضربة خفيفة → راحة
    _heartbeatController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1400),
    );
    _heartbeatAnim = TweenSequence<double>([
      TweenSequenceItem(tween: Tween(begin: 1.0, end: 1.12)
          .chain(CurveTween(curve: Curves.easeOut)), weight: 8),
      TweenSequenceItem(tween: Tween(begin: 1.12, end: 1.0)
          .chain(CurveTween(curve: Curves.easeIn)), weight: 8),
      TweenSequenceItem(tween: Tween(begin: 1.0, end: 1.07)
          .chain(CurveTween(curve: Curves.easeOut)), weight: 6),
      TweenSequenceItem(tween: Tween(begin: 1.07, end: 1.0)
          .chain(CurveTween(curve: Curves.easeIn)), weight: 6),
      TweenSequenceItem(tween: ConstantTween(1.0), weight: 72), // فترة الراحة
    ]).animate(_heartbeatController);

    // موجات سونار
    _sonarController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2200),
    );

    // لمعان الحافة
    _glowController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    );
    _glowAnim = Tween<double>(begin: 0.3, end: 1.0).animate(
      CurvedAnimation(parent: _glowController, curve: Curves.easeInOut),
    );

    // انتقال لون
    _colorController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 350),
    );
    _buttonColorAnim = ColorTween(
      begin: const Color(0xFF88D498),
      end: const Color(0xFFFF4757),
    ).animate(CurvedAnimation(parent: _colorController, curve: Curves.easeOut));
  }

  @override
  void didUpdateWidget(VoiceMicButton old) {
    super.didUpdateWidget(old);
    _syncAnimations();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _syncAnimations();
  }

  void _syncAnimations() {
    if (widget.status == AiProcessingStatus.listening) {
      _heartbeatController.repeat();
      _sonarController.repeat();
      _glowController.repeat(reverse: true);
      _colorController.forward();
    } else {
      _heartbeatController.stop();
      _heartbeatController.reset();
      _sonarController.stop();
      _sonarController.reset();
      _glowController.stop();
      _glowController.reset();
      _colorController.reverse();
    }
  }

  @override
  void dispose() {
    _heartbeatController.dispose();
    _sonarController.dispose();
    _glowController.dispose();
    _colorController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isListening = widget.status == AiProcessingStatus.listening;
    final isThinking  = widget.status == AiProcessingStatus.thinking;
    final isSpeaking  = widget.status == AiProcessingStatus.speaking;

    return GestureDetector(
      onTap: widget.onTap,
      onLongPressStart: (_) => widget.onLongPressStart?.call(),
      onLongPressEnd:   (_) => widget.onLongPressEnd?.call(),
      child: SizedBox(
        width: 96,
        height: 96,
        child: Stack(
          alignment: Alignment.center,
          children: [

            // ── طبقة 1: موجات السونار ───────────────────────────────────────
            if (isListening) ...[
              _SonarRing(
                controller: _sonarController,
                delay: 0.0,
                maxRadius: 48,
                color: const Color(0xFFFF4757),
              ),
              _SonarRing(
                controller: _sonarController,
                delay: 0.35,
                maxRadius: 44,
                color: const Color(0xFFFF6B81),
              ),
              _SonarRing(
                controller: _sonarController,
                delay: 0.65,
                maxRadius: 40,
                color: const Color(0xFFFFAEB8),
              ),
            ],

            // ── طبقة 2: حلقة لمعان متنفسة ──────────────────────────────────
            AnimatedBuilder(
              animation: isListening ? _glowAnim : const AlwaysStoppedAnimation(0.0),
              builder: (context, child) {
                final glow = isListening ? _glowAnim.value : 0.0;
                return Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: (isListening
                                ? const Color(0xFFFF4757)
                                : const Color(0xFF88D498))
                            .withValues(alpha: 0.2 + glow * 0.35),
                        blurRadius: 12 + glow * 26,
                        spreadRadius: glow * 5,
                      ),
                    ],
                  ),
                );
              },
            ),

            // ── طبقة 3: الزر الرئيسي + نبض القلب ──────────────────────────
            AnimatedBuilder(
              animation: _heartbeatAnim,
              builder: (_, child) {
                final scale = isListening ? _heartbeatAnim.value : 1.0;
                return Transform.scale(
                  scale: scale,
                  child: child,
                );
              },
              child: AnimatedBuilder(
                animation: _buttonColorAnim,
                builder: (context, child) {
                  final baseColor = _buttonColorAnim.value ??
                      const Color(0xFF88D498);
                  final endColor = isListening
                      ? const Color(0xFFFF2D46)
                      : isThinking || isSpeaking
                          ? const Color(0xFF6BC489)
                          : const Color(0xFF6BC489);

                  return Container(
                    width: 64,
                    height: 64,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [baseColor, endColor],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: baseColor.withValues(alpha: 0.5),
                          blurRadius: 16,
                          offset: const Offset(0, 5),
                        ),
                      ],
                    ),
                    child: Center(child: _buildIcon(isListening, isThinking, isSpeaking)),
                  );
                },
              ),
            ),

          ],
        ),
      ),
    );
  }

  Widget _buildIcon(bool listening, bool thinking, bool speaking) {
    if (thinking) {
      return const SizedBox(
        width: 26,
        height: 26,
        child: CircularProgressIndicator(
          color: Colors.white,
          strokeWidth: 2.5,
        ),
      );
    }
    if (speaking) {
      return const _SpeakingBars();
    }
    if (listening) {
      return const Icon(Icons.stop_rounded, color: Colors.white, size: 30);
    }
    return const Icon(Icons.mic_rounded, color: Colors.white, size: 30);
  }
}

// ── موجة السونار المتمددة ─────────────────────────────────────────────────────
class _SonarRing extends StatelessWidget {
  final AnimationController controller;
  final double delay;
  final double maxRadius;
  final Color color;

  const _SonarRing({
    required this.controller,
    required this.delay,
    required this.maxRadius,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: controller,
      builder: (context, child) {
        final raw = (controller.value - delay) % 1.0;
        final t   = raw < 0 ? raw + 1.0 : raw;
        // منحنى يبدأ ببطء ثم يتسارع
        final eased = Curves.easeInCubic.transform(t);
        final radius  = maxRadius * eased * 2;
        final opacity = math.max(0.0, 0.55 * (1.0 - eased));

        return Container(
          width: radius,
          height: radius,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: color.withValues(alpha: opacity),
          ),
        );
      },
    );
  }
}

// ── أشرطة الصوت (أثناء النطق) ────────────────────────────────────────────────
class _SpeakingBars extends StatefulWidget {
  const _SpeakingBars();

  @override
  State<_SpeakingBars> createState() => _SpeakingBarsState();
}

class _SpeakingBarsState extends State<_SpeakingBars>
    with SingleTickerProviderStateMixin {
  late AnimationController _ctrl;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 700),
    )..repeat(reverse: true);
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _ctrl,
      builder: (context, child) {
        return Row(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            _bar(0.3 + 0.7 * Curves.easeInOut.transform((_ctrl.value + 0.0) % 1)),
            const SizedBox(width: 3),
            _bar(0.3 + 0.7 * Curves.easeInOut.transform((_ctrl.value + 0.3) % 1)),
            const SizedBox(width: 3),
            _bar(0.3 + 0.7 * Curves.easeInOut.transform((_ctrl.value + 0.6) % 1)),
          ],
        );
      },
    );
  }

  Widget _bar(double heightFactor) => Container(
        width: 4,
        height: 8 + 16 * heightFactor,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(2),
        ),
      );
}

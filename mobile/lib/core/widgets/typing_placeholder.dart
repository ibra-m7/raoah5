import 'dart:async';

import 'package:flutter/material.dart';

/// Typewriter Placeholder — يكتب التلميح حرفاً بحرف ثم ينتقل للاقتراح التالي.
class TypingPlaceholder extends StatefulWidget {
  final List<String> phrases;
  final TextStyle? style;
  final Duration charDelay;
  final Duration hold;
  final Duration deleteDelay;

  const TypingPlaceholder({
    super.key,
    required this.phrases,
    this.style,
    this.charDelay = const Duration(milliseconds: 70),
    this.hold = const Duration(milliseconds: 1600),
    this.deleteDelay = const Duration(milliseconds: 36),
  });

  @override
  State<TypingPlaceholder> createState() => _TypingPlaceholderState();
}

class _TypingPlaceholderState extends State<TypingPlaceholder>
    with SingleTickerProviderStateMixin {
  late final AnimationController _caret;
  int _phraseIndex = 0;
  int _charCount = 0;
  bool _deleting = false;
  Timer? _timer;

  List<String> get _phrases =>
      widget.phrases.where((p) => p.trim().isNotEmpty).toList();

  String get _current {
    if (_phrases.isEmpty) return '';
    final phrase = _phrases[_phraseIndex % _phrases.length];
    final end = _charCount.clamp(0, phrase.length);
    return phrase.substring(0, end);
  }

  @override
  void initState() {
    super.initState();
    _caret = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 520),
    )..repeat(reverse: true);
    _scheduleNext(widget.charDelay);
  }

  @override
  void didUpdateWidget(covariant TypingPlaceholder oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.phrases.join('|') != widget.phrases.join('|')) {
      _phraseIndex = 0;
      _charCount = 0;
      _deleting = false;
    }
  }

  void _scheduleNext(Duration delay) {
    _timer?.cancel();
    _timer = Timer(delay, _tick);
  }

  void _tick() {
    if (!mounted) return;

    final phrases = _phrases;
    if (phrases.isEmpty) return;
    final phrase = phrases[_phraseIndex % phrases.length];

    setState(() {
      if (!_deleting) {
        if (_charCount < phrase.length) {
          _charCount += 1;
        } else {
          _deleting = true;
        }
      } else if (_charCount > 0) {
        _charCount -= 1;
      } else {
        _deleting = false;
        _phraseIndex = (_phraseIndex + 1) % phrases.length;
      }
    });

    final justFinishedTyping = _deleting && _charCount == phrase.length;
    _scheduleNext(
      justFinishedTyping
          ? widget.hold
          : (_deleting ? widget.deleteDelay : widget.charDelay),
    );
  }

  @override
  void dispose() {
    _timer?.cancel();
    _caret.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Flexible(
          child: Text(
            _current,
            maxLines: 1,
            overflow: TextOverflow.clip,
            style: widget.style,
          ),
        ),
        FadeTransition(
          opacity: _caret,
          child: Container(
            width: 1.6,
            height: (widget.style?.fontSize ?? 14) + 4,
            margin: const EdgeInsetsDirectional.only(start: 2),
            color: widget.style?.color ?? const Color(0xFF6B8A76),
          ),
        ),
      ],
    );
  }
}

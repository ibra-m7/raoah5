import 'package:flutter/material.dart';

class MarqueeText extends StatefulWidget {
  final String text;
  final TextStyle? style;
  final Duration pause;
  final double pixelsPerSecond;

  const MarqueeText({
    super.key,
    required this.text,
    this.style,
    this.pause = const Duration(milliseconds: 900),
    this.pixelsPerSecond = 38,
  });

  @override
  State<MarqueeText> createState() => _MarqueeTextState();
}

class _MarqueeTextState extends State<MarqueeText> {
  final _controller = ScrollController();
  bool _running = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loop());
  }

  @override
  void didUpdateWidget(covariant MarqueeText oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.text != widget.text) {
      _controller.jumpTo(0);
      _loop();
    }
  }

  Future<void> _loop() async {
    if (_running) return;
    _running = true;
    while (mounted) {
      await Future<void>.delayed(widget.pause);
      if (!mounted || !_controller.hasClients) break;
      final max = _controller.position.maxScrollExtent;
      if (max <= 4) {
        break;
      }
      final ms = ((max / widget.pixelsPerSecond) * 1000).round().clamp(2800, 14000);
      await _controller.animateTo(
        max,
        duration: Duration(milliseconds: ms),
        curve: Curves.linear,
      );
      if (!mounted) break;
      await Future<void>.delayed(const Duration(milliseconds: 700));
      if (!mounted || !_controller.hasClients) break;
      _controller.jumpTo(0);
    }
    _running = false;
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: SingleChildScrollView(
        controller: _controller,
        scrollDirection: Axis.horizontal,
        physics: const NeverScrollableScrollPhysics(),
        child: Text(
          widget.text,
          maxLines: 1,
          softWrap: false,
          style: widget.style,
        ),
      ),
    );
  }
}

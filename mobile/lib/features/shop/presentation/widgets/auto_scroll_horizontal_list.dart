import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';

/// قائمة أفقية تتحرك تلقائياً مع إمكانية إيقافها باللمس.
class AutoScrollHorizontalList extends StatefulWidget {
  final double height;
  final double itemWidth;
  final double gap;
  final EdgeInsetsGeometry padding;
  final int itemCount;
  final Widget Function(BuildContext context, int index) itemBuilder;

  const AutoScrollHorizontalList({
    super.key,
    required this.height,
    required this.itemWidth,
    required this.gap,
    required this.padding,
    required this.itemCount,
    required this.itemBuilder,
  });

  @override
  State<AutoScrollHorizontalList> createState() =>
      _AutoScrollHorizontalListState();
}

class _AutoScrollHorizontalListState extends State<AutoScrollHorizontalList>
    with SingleTickerProviderStateMixin {
  late final ScrollController _scroll;
  late final Ticker _ticker;
  Duration _lastElapsed = Duration.zero;
  Timer? _resumeTimer;
  double _loopWidth = 0;
  bool _userHolding = false;

  static const _pixelsPerSecond = 34.0;
  static const _resumeAfter = Duration(seconds: 2);

  /// التكرار اللانهائي يحتاج عنصرين على الأقل؛ وإلا يظهر العنصر الواحد مكرراً بالخطأ.
  bool get _canLoop => widget.itemCount >= 2;

  @override
  void initState() {
    super.initState();
    _scroll = ScrollController();
    _ticker = createTicker(_onTick);
  }

  @override
  void didUpdateWidget(covariant AutoScrollHorizontalList oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!_canLoop) {
      _resumeTimer?.cancel();
      if (_ticker.isActive) _ticker.stop();
      _loopWidth = 0;
      if (_scroll.hasClients && _scroll.offset != 0) {
        _scroll.jumpTo(0);
      }
    }
  }

  @override
  void dispose() {
    _resumeTimer?.cancel();
    _ticker.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _onTick(Duration elapsed) {
    if (!_canLoop || !_scroll.hasClients || _loopWidth <= 0 || _userHolding) {
      _lastElapsed = elapsed;
      return;
    }

    final dt = (elapsed - _lastElapsed).inMicroseconds / 1e6;
    _lastElapsed = elapsed;
    if (dt <= 0 || dt > 0.08) return;

    var next = _scroll.offset + _pixelsPerSecond * dt;
    if (next >= _loopWidth) {
      next -= _loopWidth;
    }
    final max = _scroll.position.maxScrollExtent;
    if (next > max) next = 0;
    if (next < 0) next = 0;
    _scroll.jumpTo(next);
  }

  void _ensureRunning(double loopWidth) {
    if (!_canLoop || loopWidth <= 0) return;
    _loopWidth = loopWidth;
    if (!_ticker.isActive) {
      _lastElapsed = Duration.zero;
      _ticker.start();
    }
  }

  void _pauseForUser() {
    if (!_canLoop || _userHolding) return;
    _userHolding = true;
    _resumeTimer?.cancel();
  }

  void _scheduleResume() {
    if (!_canLoop) return;
    _resumeTimer?.cancel();
    _resumeTimer = Timer(_resumeAfter, () {
      if (!mounted) return;
      if (_scroll.hasClients &&
          _loopWidth > 0 &&
          _scroll.offset >= _loopWidth) {
        _scroll.jumpTo(_scroll.offset % _loopWidth);
      }
      _userHolding = false;
      _lastElapsed = Duration.zero;
      if (!_ticker.isActive) _ticker.start();
    });
  }

  @override
  Widget build(BuildContext context) {
    if (widget.itemCount == 0) {
      return const SizedBox.shrink();
    }

    final loopWidth = (widget.itemWidth + widget.gap) * widget.itemCount;
    final displayCount = _canLoop ? widget.itemCount * 2 : widget.itemCount;

    return SizedBox(
      height: widget.height,
      child: NotificationListener<ScrollNotification>(
        onNotification: (notification) {
          if (!_canLoop) return false;
          if (notification is ScrollStartNotification &&
              notification.dragDetails != null) {
            _pauseForUser();
          } else if (notification is ScrollEndNotification) {
            _scheduleResume();
          }
          return false;
        },
        child: LayoutBuilder(
          builder: (context, constraints) {
            if (_canLoop) {
              WidgetsBinding.instance.addPostFrameCallback((_) {
                if (mounted) _ensureRunning(loopWidth);
              });
            }

            return ListView.separated(
              controller: _scroll,
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              padding: widget.padding,
              itemCount: displayCount,
              separatorBuilder: (_, _) => SizedBox(width: widget.gap),
              itemBuilder: (context, index) {
                final realIndex = index % widget.itemCount;
                return SizedBox(
                  width: widget.itemWidth,
                  height: widget.height,
                  child: widget.itemBuilder(context, realIndex),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

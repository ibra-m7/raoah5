import 'package:flutter/material.dart';
import '../../../../core/router/app_router.dart';
import '../../../auth/data/services/auth_session.dart';

// ── بيانات الشرائح ────────────────────────────────────────────────────────────
class _OnboardingData {
  final String title;
  final String subtitle;
  final String description;
  final IconData icon;
  final List<Color> gradientColors;
  final Color accentColor;

  const _OnboardingData({
    required this.title,
    required this.subtitle,
    required this.description,
    required this.icon,
    required this.gradientColors,
    required this.accentColor,
  });
}

const _pages = [
  _OnboardingData(
    title: 'التسوق الذكي',
    subtitle: 'Smart Shopping',
    description:
        'تصفَّح آلاف المنتجات المختارة بعناية في فئات المنظفات والإلكترونيات والمواد الغذائية. '
        'اكتشف أفضل العروض والخصومات الحصرية بضغطة واحدة.',
    icon: Icons.shopping_cart_rounded,
    gradientColors: [Color(0xFF1B5E20), Color(0xFF388E3C), Color(0xFF66BB6A)],
    accentColor: Color(0xFF4CAF50),
  ),
  _OnboardingData(
    title: 'المساعد الصوتي',
    subtitle: 'Voice Assistant',
    description:
        'تحدَّث مع "روعة" — مساعدتك الذكية المدعومة بالذكاء الاصطناعي. '
        'اسألها عن أي منتج، واحصل على توصيات شخصية، أو أضف للسلة بصوتك!',
    icon: Icons.mic_rounded,
    gradientColors: [Color(0xFF0D47A1), Color(0xFF1565C0), Color(0xFF42A5F5)],
    accentColor: Color(0xFF2196F3),
  ),
  _OnboardingData(
    title: 'توصيل سريع',
    subtitle: 'Fast Delivery',
    description:
        'استلم طلبك في أسرع وقت ممكن مع خدمة التوصيل السريع. '
        'تتبَّع طلبك لحظة بلحظة وادفع بأمان عبر وسائل دفع متعددة.',
    icon: Icons.local_shipping_rounded,
    gradientColors: [Color(0xFF4A148C), Color(0xFF6A1B9A), Color(0xFFAB47BC)],
    accentColor: Color(0xFF9C27B0),
  ),
];

// ── الشاشة الرئيسية ───────────────────────────────────────────────────────────
class OnboardingScreen extends StatefulWidget {
  static const routeName = '/onboarding';

  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen>
    with SingleTickerProviderStateMixin {
  final PageController _pageController = PageController();
  late final AnimationController _buttonController;
  late final Animation<double> _buttonScale;

  int _currentPage = 0;

  @override
  void initState() {
    super.initState();
    _buttonController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 200),
      lowerBound: 0.95,
      upperBound: 1.0,
      value: 1.0,
    );
    _buttonScale = _buttonController;
  }

  @override
  void dispose() {
    _pageController.dispose();
    _buttonController.dispose();
    super.dispose();
  }

  void _nextPage() {
    if (_currentPage < _pages.length - 1) {
      _pageController.nextPage(
        duration: const Duration(milliseconds: 500),
        curve: Curves.easeInOutCubic,
      );
    } else {
      _launchApp();
    }
  }

  Future<void> _launchApp() async {
    await AuthSession.instance.markOnboardingSeen();
    AuthSession.instance.offerLoginOnHome = true;
    if (!mounted) return;
    Navigator.of(context).pushNamedAndRemoveUntil(
      AppRouter.main,
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // ── صفحات الـ PageView ─────────────────────────────────────────
          PageView.builder(
            controller: _pageController,
            itemCount: _pages.length,
            onPageChanged: (i) => setState(() => _currentPage = i),
            itemBuilder: (_, i) => _OnboardingPage(data: _pages[i]),
          ),

          // ── التخطي ─────────────────────────────────────────────────────
          SafeArea(
            child: Align(
              alignment: Alignment.topLeft,
              child: AnimatedOpacity(
                opacity: _currentPage < _pages.length - 1 ? 1.0 : 0.0,
                duration: const Duration(milliseconds: 300),
                child: TextButton(
                  onPressed: _currentPage < _pages.length - 1
                      ? _launchApp
                      : null,
                  child: const Text(
                    'تخطي',
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 15,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ),
            ),
          ),

          // ── الجزء السفلي: المؤشرات + الأزرار ─────────────────────────
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: _BottomBar(
              currentPage: _currentPage,
              totalPages: _pages.length,
              accentColor: _pages[_currentPage].accentColor,
              onNext: _nextPage,
              isLast: _currentPage == _pages.length - 1,
              buttonScale: _buttonScale,
              buttonController: _buttonController,
            ),
          ),
        ],
      ),
    );
  }
}

// ── صفحة الـ Onboarding الواحدة ───────────────────────────────────────────────
class _OnboardingPage extends StatefulWidget {
  final _OnboardingData data;

  const _OnboardingPage({required this.data});

  @override
  State<_OnboardingPage> createState() => _OnboardingPageState();
}

class _OnboardingPageState extends State<_OnboardingPage>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _iconScale;
  late final Animation<double> _iconOpacity;
  late final Animation<double> _contentOpacity;
  late final Animation<Offset> _contentSlide;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 700),
    );

    _iconScale = Tween<double>(begin: 0.4, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.elasticOut),
    );
    _iconOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.0, 0.4, curve: Curves.easeIn),
      ),
    );
    _contentOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.3, 1.0, curve: Curves.easeIn),
      ),
    );
    _contentSlide =
        Tween<Offset>(begin: const Offset(0, 0.3), end: Offset.zero).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.3, 1.0, curve: Curves.easeOutCubic),
      ),
    );

    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final data = widget.data;
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: data.gradientColors,
          stops: const [0.0, 0.5, 1.0],
        ),
      ),
      child: SafeArea(
        child: Column(
          children: [
            const SizedBox(height: 70),

            // ── صورة توضيحية (أيقونة داخل دوائر) ──────────────────────
            AnimatedBuilder(
              animation: _controller,
              builder: (_, __) => Opacity(
                opacity: _iconOpacity.value,
                child: Transform.scale(
                  scale: _iconScale.value,
                  child: _IllustrationWidget(data: data),
                ),
              ),
            ),

            const SizedBox(height: 50),

            // ── النص ─────────────────────────────────────────────────────
            AnimatedBuilder(
              animation: _controller,
              builder: (_, __) => SlideTransition(
                position: _contentSlide,
                child: Opacity(
                  opacity: _contentOpacity.value,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 32),
                    child: Column(
                      children: [
                        Text(
                          data.title,
                          style: const TextStyle(
                            fontSize: 30,
                            fontWeight: FontWeight.w900,
                            color: Colors.white,
                            letterSpacing: 0.5,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 6),
                        Text(
                          data.subtitle,
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w400,
                            color: Colors.white.withValues(alpha: 0.7),
                            letterSpacing: 2.0,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 20),
                        Text(
                          data.description,
                          style: TextStyle(
                            fontSize: 15,
                            height: 1.75,
                            color: Colors.white.withValues(alpha: 0.88),
                            fontWeight: FontWeight.w400,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),

            // مسافة للـ BottomBar
            const SizedBox(height: 140),
          ],
        ),
      ),
    );
  }
}

// ── الصورة التوضيحية ──────────────────────────────────────────────────────────
class _IllustrationWidget extends StatelessWidget {
  final _OnboardingData data;

  const _IllustrationWidget({required this.data});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 220,
      height: 220,
      child: Stack(
        alignment: Alignment.center,
        children: [
          // دائرة خارجية شفافة
          Container(
            width: 220,
            height: 220,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.08),
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.15),
                width: 1.5,
              ),
            ),
          ),
          // دائرة وسطى
          Container(
            width: 170,
            height: 170,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.12),
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.2),
                width: 1.5,
              ),
            ),
          ),
          // دائرة داخلية مع الأيقونة
          Container(
            width: 118,
            height: 118,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.22),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.15),
                  blurRadius: 20,
                  spreadRadius: 2,
                ),
              ],
            ),
            child: Icon(
              data.icon,
              size: 56,
              color: Colors.white,
            ),
          ),

          // نقاط ديكورية
          ..._decorativeDots(),
        ],
      ),
    );
  }

  List<Widget> _decorativeDots() {
    const positions = [
      Offset(10, 30),
      Offset(190, 40),
      Offset(15, 170),
      Offset(185, 175),
      Offset(105, 8),
    ];
    return positions
        .map(
          (p) => Positioned(
            left: p.dx,
            top: p.dy,
            child: Container(
              width: 8,
              height: 8,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.4),
              ),
            ),
          ),
        )
        .toList();
  }
}

// ── الشريط السفلي ─────────────────────────────────────────────────────────────
class _BottomBar extends StatelessWidget {
  final int currentPage;
  final int totalPages;
  final Color accentColor;
  final VoidCallback onNext;
  final bool isLast;
  final Animation<double> buttonScale;
  final AnimationController buttonController;

  const _BottomBar({
    required this.currentPage,
    required this.totalPages,
    required this.accentColor,
    required this.onNext,
    required this.isLast,
    required this.buttonScale,
    required this.buttonController,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(28, 20, 28, 40),
      decoration: BoxDecoration(
        color: Colors.black.withValues(alpha: 0.18),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // ── مؤشرات النقاط ───────────────────────────────────────────
          Row(
            children: List.generate(totalPages, (i) {
              final isActive = i == currentPage;
              return AnimatedContainer(
                duration: const Duration(milliseconds: 350),
                curve: Curves.easeInOutCubic,
                margin: const EdgeInsets.symmetric(horizontal: 4),
                width: isActive ? 28 : 10,
                height: 10,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(5),
                  color: isActive
                      ? Colors.white
                      : Colors.white.withValues(alpha: 0.35),
                ),
              );
            }),
          ),

          // ── زر التالي / ابدأ الآن ────────────────────────────────────
          GestureDetector(
            onTapDown: (_) => buttonController.reverse(),
            onTapUp: (_) {
              buttonController.forward();
              onNext();
            },
            onTapCancel: () => buttonController.forward(),
            child: ScaleTransition(
              scale: buttonScale,
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 350),
                height: 54,
                padding: const EdgeInsets.symmetric(horizontal: 28),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(27),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.2),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    AnimatedSwitcher(
                      duration: const Duration(milliseconds: 300),
                      transitionBuilder: (child, animation) =>
                          FadeTransition(opacity: animation, child: child),
                      child: Text(
                        isLast ? 'ابدأ الآن' : 'التالي',
                        key: ValueKey(isLast),
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: accentColor,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    AnimatedSwitcher(
                      duration: const Duration(milliseconds: 300),
                      child: Icon(
                        isLast
                            ? Icons.rocket_launch_rounded
                            : Icons.arrow_back_rounded,
                        key: ValueKey(isLast),
                        color: accentColor,
                        size: 20,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

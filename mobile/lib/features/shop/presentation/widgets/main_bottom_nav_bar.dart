import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_theme.dart';
import '../manager/cart_cubit.dart';

const _kSurface = Color(0xFFFFFFFF);
const _kSubtext = Color(0xFF6B8A76);

/// شريط التنقل السفلي المشترك بين [MainScreen] ومسار [CheckoutScreen].
///
/// فهارس التبويب: 0 رئيسية، 1 أقسام، 2 سلة، 3 حسابي. زر المساعد (وسط) يفتح الشات عبر [onAiAssistantTap].
/// البصر (RTL): من اليمين للشمال — رئيسية، أقسام، زر المساعد (وسط)، سلة، حسابي.
///
/// عند [cartScreenActive] (مسار السلة المنبثق): تُبرز أيقونة السلة وتُعطّل إعادة فتحها.
class MainBottomNavBar extends StatelessWidget {
  final int currentTabIndex;

  /// true عند عرض الشريط فوق [CheckoutScreen] المفتوح كمسار (وليس كتبويب).
  final bool cartScreenActive;

  final ValueChanged<int> onTabTap;

  /// فتح شاشة المحادثة فوق الـ shell (يُستخدَم مع `Navigator` الجذر لإخفاء الشريط السفلي).
  final VoidCallback? onAiAssistantTap;

  /// عند `true`: يلفّ زر المساعد الوسطي بـ [Hero] tag `ai_button`.
  final bool wrapAiCenterHero;

  const MainBottomNavBar({
    super.key,
    required this.currentTabIndex,
    this.cartScreenActive = false,
    required this.onTabTap,
    this.onAiAssistantTap,
    this.wrapAiCenterHero = true,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: _kSurface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.09),
            blurRadius: 28,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 64,
          child: Stack(
            clipBehavior: Clip.none,
            alignment: Alignment.center,
            children: [
              Positioned.fill(
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    // ── RTL: أول عنصر = أقصى اليمين — الرئيسية ──────────────
                    Expanded(
                      child: _SimpleNavItem(
                        icon: (!cartScreenActive && currentTabIndex == 0)
                            ? Icons.storefront_rounded
                            : Icons.storefront_outlined,
                        label: AppStrings.navHome,
                        isActive: !cartScreenActive && currentTabIndex == 0,
                        onTap: () => onTabTap(0),
                      ),
                    ),
                    Expanded(
                      child: _SimpleNavItem(
                        icon: (!cartScreenActive && currentTabIndex == 1)
                            ? Icons.category_rounded
                            : Icons.category_outlined,
                        label: AppStrings.navCategories,
                        isActive: !cartScreenActive && currentTabIndex == 1,
                        onTap: () => onTabTap(1),
                      ),
                    ),
                    const SizedBox(width: 72),
                    // ── بعد الفراغ الوسطي: السلة ثم حسابي (باتجاه اليسار) ──
                    Expanded(
                      child: BlocBuilder<CartCubit, CartState>(
                        builder: (ctx, state) => _CartNavItem(
                          count: state.count,
                          isActive:
                              cartScreenActive || currentTabIndex == 2,
                          onTap: (cartScreenActive || currentTabIndex == 2)
                              ? null
                              : () => onTabTap(2),
                        ),
                      ),
                    ),
                    Expanded(
                      child: _SimpleNavItem(
                        icon: (!cartScreenActive && currentTabIndex == 3)
                            ? Icons.person_rounded
                            : Icons.person_outline_rounded,
                        label: AppStrings.navProfile,
                        isActive: !cartScreenActive && currentTabIndex == 3,
                        onTap: () => onTabTap(3),
                      ),
                    ),
                  ],
                ),
              ),
              Positioned(
                top: -22,
                child: _buildAiCenterButton(context),
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// زر المساعد الذكي — مع [Hero] عند [wrapAiCenterHero] لانتقال إلى شاشة الشات.
  Widget _buildAiCenterButton(BuildContext context) {
    final button = _AiCenterButton(
      isActive: false,
      onTap: () => onAiAssistantTap?.call(),
    );
    if (!wrapAiCenterHero || cartScreenActive) return button;
    return Hero(
      tag: 'ai_button',
      child: Material(
        type: MaterialType.transparency,
        child: button,
      ),
    );
  }
}

class _SimpleNavItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool isActive;
  final VoidCallback onTap;

  const _SimpleNavItem({
    required this.icon,
    required this.label,
    required this.isActive,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 220),
              curve: Curves.easeOutCubic,
              height: 34,
              width: isActive ? 52 : 36,
              decoration: BoxDecoration(
                color: isActive
                    ? AppTheme.primaryDark.withValues(alpha: 0.1)
                    : Colors.transparent,
                borderRadius: BorderRadius.circular(17),
              ),
              child: Center(
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 180),
                  child: Icon(
                    icon,
                    key: ValueKey(isActive),
                    size: 22,
                    color: isActive ? AppTheme.primaryDark : _kSubtext,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 2),
            AnimatedDefaultTextStyle(
              duration: const Duration(milliseconds: 200),
              style: TextStyle(
                fontFamily: 'Cairo',
                fontSize: 10,
                fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
                color: isActive ? AppTheme.primaryDark : _kSubtext,
              ),
              child: Text(label),
            ),
          ],
        ),
      ),
    );
  }
}

class _CartNavItem extends StatelessWidget {
  final int count;
  final bool isActive;
  final VoidCallback? onTap;

  const _CartNavItem({
    required this.count,
    required this.isActive,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          SizedBox(
            height: 34,
            width: 36,
            child: Stack(
              alignment: Alignment.center,
              clipBehavior: Clip.none,
              children: [
                Icon(
                  isActive
                      ? Icons.shopping_bag_rounded
                      : Icons.shopping_bag_outlined,
                  size: 22,
                  color: isActive ? AppTheme.primaryDark : _kSubtext,
                ),
                if (count > 0)
                  PositionedDirectional(
                    top: 0,
                    end: 0,
                    child: Container(
                      width: 16,
                      height: 16,
                      decoration: BoxDecoration(
                        color: isActive ? AppTheme.primaryDark : AppTheme.primary,
                        shape: BoxShape.circle,
                        border: Border.all(color: _kSurface, width: 1.5),
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        '$count',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 9,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 2),
          Text(
            'السلة',
            style: TextStyle(
              fontFamily: 'Cairo',
              fontSize: 10,
              fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
              color: isActive ? AppTheme.primaryDark : _kSubtext,
            ),
          ),
        ],
      ),
    );
  }
}

class _AiCenterButton extends StatefulWidget {
  final bool isActive;
  final VoidCallback onTap;

  const _AiCenterButton({required this.isActive, required this.onTap});

  @override
  State<_AiCenterButton> createState() => _AiCenterButtonState();
}

class _AiCenterButtonState extends State<_AiCenterButton>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulseCtrl;
  late final Animation<double> _pulseAnim;

  @override
  void initState() {
    super.initState();
    _pulseCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    )..repeat(reverse: true);
    _pulseAnim = Tween<double>(begin: 0.94, end: 1.06).animate(
      CurvedAnimation(parent: _pulseCtrl, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _pulseCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: widget.onTap,
      child: ScaleTransition(
        scale: _pulseAnim,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 250),
          width: 60,
          height: 60,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: LinearGradient(
              colors: [AppTheme.primaryDark, AppTheme.primary],
              begin: Alignment.topRight,
              end: Alignment.bottomLeft,
            ),
            border: widget.isActive
                ? Border.all(color: Colors.white, width: 3)
                : Border.all(color: Colors.white, width: 2.5),
            boxShadow: [
              BoxShadow(
                color: AppTheme.primaryDark.withValues(alpha: 0.45),
                blurRadius: 20,
                spreadRadius: 1,
                offset: const Offset(0, 5),
              ),
            ],
          ),
          child: const Icon(
            Icons.auto_awesome_rounded,
            color: Colors.white,
            size: 26,
          ),
        ),
      ),
    );
  }
}

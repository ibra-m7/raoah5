import 'package:flutter/material.dart';
import '../../features/ai_assistant/presentation/pages/chat_screen.dart';
import '../../features/auth/presentation/pages/add_address_screen.dart';
import '../../features/auth/presentation/pages/complete_location_screen.dart';
import '../../features/auth/presentation/pages/complete_name_screen.dart';
import '../../features/auth/presentation/pages/login_screen.dart';
import '../../features/auth/presentation/pages/otp_verify_screen.dart';
import '../../features/auth/presentation/pages/phone_login_screen.dart';
import '../../features/auth/presentation/pages/profile_screen.dart';
import '../../features/auth/presentation/pages/register_screen.dart';
import '../../features/notifications/presentation/pages/notifications_screen.dart';
import '../../features/onboarding/presentation/pages/onboarding_screen.dart';
import '../../features/onboarding/presentation/pages/splash_screen.dart';
import '../../features/shop/data/models/product_model.dart';
import '../../features/shop/presentation/pages/checkout_screen.dart';
import '../../features/shop/presentation/pages/category_browse_screen.dart';
import '../../features/shop/presentation/pages/custom_dynamic_page_screen.dart';
import '../../features/shop/presentation/pages/invoice_screen.dart';
import '../../features/shop/presentation/pages/favorites_screen.dart';
import '../../features/shop/presentation/pages/groceries_section_screen.dart';
import '../../features/shop/presentation/pages/home_screen.dart';
import '../../features/shop/presentation/pages/orders_screen.dart';
import '../../features/shop/presentation/pages/product_details_screen.dart';
import '../../features/shop/presentation/pages/search_screen.dart';

// ══════════════════════════════════════════════════════════════════════════════
// AppRouter — المرجع الوحيد لجميع مسارات التطبيق
// ══════════════════════════════════════════════════════════════════════════════

/// خريطة التوجيه الكاملة للتطبيق.
///
/// الاستخدام في [MaterialApp]:
/// ```dart
/// onGenerateRoute: AppRouter.onGenerateRoute,
/// initialRoute: AppRouter.initial,
/// ```
///
/// تمرير arguments:
/// ```dart
/// Navigator.pushNamed(context, AppRouter.productDetails,
///   arguments: product);  // ProductModel
/// ```
abstract class AppRouter {
  AppRouter._();

  static final navigatorKey = GlobalKey<NavigatorState>();

  // ── مسارات التطبيق ────────────────────────────────────────────────────────
  static const initial        = SplashScreen.routeName;        // '/splash'
  static const splash         = SplashScreen.routeName;
  static const onboarding     = OnboardingScreen.routeName;    // '/onboarding'
  static const login          = LoginScreen.routeName;         // '/login'
  static const phoneLogin     = PhoneLoginScreen.routeName;    // '/phone-login'
  static const otpVerify      = OtpVerifyScreen.routeName;     // '/otp-verify'
  static const completeName   = CompleteNameScreen.routeName;  // '/complete-name'
  static const completeLocation = CompleteLocationScreen.routeName; // '/complete-location'
  static const addAddress     = AddAddressScreen.routeName;        // '/add-address'
  static const register       = RegisterScreen.routeName;      // '/register'
  static const main           = MainScreen.routeName;          // '/main'
  static const profile        = ProfileScreen.routeName;       // '/profile'
  static const productDetails = ProductDetailsScreen.routeName;// '/product-details'
  static const checkout       = CheckoutScreen.routeName;      // '/checkout'
  static const invoice        = InvoiceScreen.routeName;       // '/invoice'
  static const favorites      = FavoritesScreen.routeName;       // '/favorites'
  static const chat           = ChatScreen.routeName;          // '/chat'
  static const orders         = OrdersScreen.routeName;        // '/orders'
  static const notifications  = NotificationsScreen.routeName; // '/notifications'
  static const search         = SearchScreen.routeName;        // '/search'
  static const dynamicPage    = CustomDynamicPageScreen.routeName; // '/dynamic-page'
  /// عرض كل فئات المقاضي (من زر «عرض الكل» في صفحة الأقسام)
  static const groceriesSection = GroceriesSectionScreen.routeName;
  /// منتجات قسم فرعي من المقاضي (اضغط دائرة في الشبكة)
  static const groceriesSubcategoryProducts =
      GroceriesSubcategoryProductsScreen.routeName;
  static const categorySubcategoriesBrowse =
      CategorySubcategoriesBrowseScreen.routeName;
  static const categoryBrowse = CategoryBrowseScreen.routeName;

  // ── onGenerateRoute ───────────────────────────────────────────────────────
  static Route<dynamic> onGenerateRoute(RouteSettings settings) {
    switch (settings.name) {
      // ── Onboarding ──────────────────────────────────────────────────────
      case splash:
        return _fade(const SplashScreen(), settings);

      case onboarding:
        return _fade(const OnboardingScreen(), settings);

      // ── Auth ─────────────────────────────────────────────────────────────
      case login:
        return _slide(
          const LoginScreen(),
          settings,
          direction: _SlideDir.up,
        );

      case phoneLogin:
        return _slide(
          const PhoneLoginScreen(),
          settings,
          direction: _SlideDir.up,
        );

      case otpVerify:
        final args = settings.arguments is OtpVerifyArgs
            ? settings.arguments as OtpVerifyArgs
            : null;
        if (args == null) {
          return _slide(
            const PhoneLoginScreen(),
            settings,
            direction: _SlideDir.up,
          );
        }
        return _slide(
          OtpVerifyScreen(args: args),
          settings,
          direction: _SlideDir.up,
        );

      case completeName:
        return _slide(
          const CompleteNameScreen(),
          settings,
          direction: _SlideDir.up,
        );

      case completeLocation:
        return _slide(
          const CompleteLocationScreen(),
          settings,
          direction: _SlideDir.up,
        );

      case addAddress:
        return _slide(
          const AddAddressScreen(),
          settings,
          direction: _SlideDir.up,
        );

      case register:
        return _slide(
          const RegisterScreen(),
          settings,
          direction: _SlideDir.right,
        );

      // ── Main Shell ────────────────────────────────────────────────────────
      case main:
        final raw = settings.arguments is int ? settings.arguments as int : 0;
        final idx = raw.clamp(0, 3);
        return _fade(MainScreen(initialIndex: idx), settings);

      // ── Profile (standalone — خارج الـ BottomNav) ─────────────────────────
      case profile:
        return _slide(
          const ProfileScreen(),
          settings,
          direction: _SlideDir.right,
        );

      // ── Product Details ───────────────────────────────────────────────────
      case productDetails:
        final args = settings.arguments;
        if (args is ProductDetailsArgs) {
          return _slide(
            ProductDetailsScreen(
              product: args.product,
              heroTag: args.heroTag,
            ),
            settings,
            direction: _SlideDir.up,
          );
        }
        if (args is ProductModel) {
          return _slide(
            ProductDetailsScreen(product: args),
            settings,
            direction: _SlideDir.up,
          );
        }
        return _errorRoute(settings, 'تعذّر فتح المنتج — بيانات غير صحيحة');

      // ── Checkout ──────────────────────────────────────────────────────────
      case checkout:
        return _slide<int>(
          const CheckoutScreen(),
          settings,
          direction: _SlideDir.up,
        );

      case invoice:
        return _slide(
          const InvoiceScreen(),
          settings,
          direction: _SlideDir.up,
        );

      case favorites:
        return _slide(
          const FavoritesScreen(),
          settings,
          direction: _SlideDir.right,
        );

      // ── Orders ────────────────────────────────────────────────────────────
      case orders:
        return _slide(
          const OrdersScreen(),
          settings,
          direction: _SlideDir.right,
        );

      case notifications:
        return _slide(
          const NotificationsScreen(),
          settings,
          direction: _SlideDir.right,
        );

      case search:
        return _slide(
          const SearchScreen(),
          settings,
          direction: _SlideDir.up,
        );

      case dynamicPage:
        final args = settings.arguments;
        if (args is DynamicPageArgs) {
          return _slide(
            CustomDynamicPageScreen(
              pageId: args.pageId,
              initial: args.initial,
            ),
            settings,
            direction: _SlideDir.up,
          );
        }
        if (args is String && args.isNotEmpty) {
          return _slide(
            CustomDynamicPageScreen(pageId: args),
            settings,
            direction: _SlideDir.up,
          );
        }
        return _errorRoute(settings, 'تعذّر فتح الصفحة — بيانات غير صحيحة');

      // ── Chat (standalone — عند فتحه خارج الـ BottomNav) ──────────────────
      case chat:
        final useHeroMic = settings.arguments == true;
        return _slide(
          ChatScreen(useHeroMic: useHeroMic),
          settings,
          direction: _SlideDir.right,
        );

      case groceriesSection:
        return _slide(
          const GroceriesSectionScreen(),
          settings,
          direction: _SlideDir.right,
        );

      case groceriesSubcategoryProducts:
        if (settings.arguments is! GroceriesSubcategoryProductsArgs) {
          return _errorRoute(
            settings,
            'تعذّر فتح المنتجات — بيانات القسم غير صحيحة',
          );
        }
        return _slide(
          GroceriesSubcategoryProductsScreen(
            args: settings.arguments as GroceriesSubcategoryProductsArgs,
          ),
          settings,
          direction: _SlideDir.right,
        );

      case categorySubcategoriesBrowse:
        if (settings.arguments is! CategorySubcategoriesBrowseArgs) {
          return _errorRoute(
            settings,
            'تعذّر فتح الأقسام — بيانات غير صحيحة',
          );
        }
        return _slide(
          CategorySubcategoriesBrowseScreen(
            args: settings.arguments as CategorySubcategoriesBrowseArgs,
          ),
          settings,
          direction: _SlideDir.right,
        );

      case categoryBrowse:
        if (settings.arguments is! CategoryBrowseArgs) {
          return _errorRoute(
            settings,
            'تعذّر فتح القسم — بيانات غير صحيحة',
          );
        }
        return _slide(
          CategoryBrowseScreen(
            args: settings.arguments as CategoryBrowseArgs,
          ),
          settings,
          direction: _SlideDir.up,
        );

      case '/':
        return _fade(const MainScreen(), settings);

      // مسارات غير معروفة ترجع للرئيسية بدل صفحة الخطأ.
      default:
        return _fade(const MainScreen(), settings);
    }
  }

  // ── Page Transition Builders ──────────────────────────────────────────────

  /// Fade transition — للشاشات الرئيسية (Splash، Main)
  static PageRouteBuilder<T> _fade<T>(
    Widget page,
    RouteSettings settings, {
    Duration duration = const Duration(milliseconds: 220),
  }) {
    return PageRouteBuilder<T>(
      settings: settings,
      transitionDuration: duration,
      reverseTransitionDuration:
          Duration(milliseconds: (duration.inMilliseconds * 0.7).round()),
      pageBuilder: (_, _, _) => page,
      transitionsBuilder: (_, animation, _, child) => FadeTransition(
        opacity: CurvedAnimation(parent: animation, curve: Curves.easeOut),
        child: child,
      ),
    );
  }

  /// Slide transition — للصفحات التفاعلية
  static PageRouteBuilder<T> _slide<T>(
    Widget page,
    RouteSettings settings, {
    _SlideDir direction = _SlideDir.right,
    Duration duration = const Duration(milliseconds: 240),
  }) {
    return PageRouteBuilder<T>(
      settings: settings,
      transitionDuration: duration,
      reverseTransitionDuration:
          Duration(milliseconds: (duration.inMilliseconds * 0.75).round()),
      pageBuilder: (_, _, _) => page,
      transitionsBuilder: (_, animation, _, child) {
        final begin = switch (direction) {
          _SlideDir.right => const Offset(1.0, 0.0),
          _SlideDir.left  => const Offset(-1.0, 0.0),
          _SlideDir.up    => const Offset(0.0, 0.08),
          _SlideDir.down  => const Offset(0.0, -1.0),
        };
        return SlideTransition(
          position: Tween<Offset>(begin: begin, end: Offset.zero).animate(
            CurvedAnimation(
              parent: animation,
              curve: Curves.easeOutCubic,
              reverseCurve: Curves.easeInCubic,
            ),
          ),
          child: child,
        );
      },
    );
  }

  /// صفحة خطأ احتياطية
  static Route<dynamic> _errorRoute(RouteSettings settings, String message) {
    return _fade(
      _ErrorPage(message: message),
      settings,
    );
  }
}

// ── اتجاهات الـ Slide ─────────────────────────────────────────────────────────
enum _SlideDir { right, left, up, down }

// ── صفحة الخطأ الاحتياطية ────────────────────────────────────────────────────
class _ErrorPage extends StatelessWidget {
  final String message;
  const _ErrorPage({required this.message});

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: const Color(0xFFF1F8F1),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  Icons.error_outline_rounded,
                  size: 72,
                  color: Colors.redAccent.withValues(alpha: 0.6),
                ),
                const SizedBox(height: 20),
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 16,
                    color: Color(0xFF6B7B6B),
                  ),
                ),
                const SizedBox(height: 24),
                ElevatedButton.icon(
                  onPressed: () => Navigator.of(context)
                      .pushReplacementNamed(AppRouter.main),
                  icon: const Icon(Icons.home_rounded),
                  label: const Text('العودة للرئيسية'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2E7D32),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 24,
                      vertical: 14,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

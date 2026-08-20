import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'core/constants/app_strings.dart';
import 'core/di/service_locator.dart';
import 'core/network/app_http_overrides.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/data/services/auth_session.dart';
import 'features/auth/presentation/manager/address_cubit.dart';
import 'features/notifications/data/services/push_service.dart';
import 'features/notifications/presentation/manager/notifications_cubit.dart';
import 'features/shop/presentation/manager/cart_cubit.dart';
import 'features/shop/presentation/manager/catalog_cubit.dart';
import 'features/shop/presentation/manager/favorite_cubit.dart';
import 'features/shop/presentation/manager/orders_cubit.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  installAppHttpOverrides();

  // إخفاء شريط الحالة مؤقتاً حتى تكتمل التهيئة
  SystemChrome.setSystemUIOverlayStyle(SystemUiOverlayStyle.light);

  // إجبار الاتجاه العمودي فقط
  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  // تحميل ملف .env
  await dotenv.load(fileName: '.env');
  await AuthSession.instance.load();

  FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
  await ensureFirebaseApp();

  // تهيئة حاقن التبعيات
  ServiceLocator.init();

  // تهيئة خدمة الصوت مبكراً
  await ServiceLocator.instance.voiceService.initialize();

  final notificationsCubit = NotificationsCubit();
  await PushService.instance.initialize(cubit: notificationsCubit);

  runApp(RaoahAlkhamsa(notificationsCubit: notificationsCubit));
}

// ══════════════════════════════════════════════════════════════════════════════
class RaoahAlkhamsa extends StatelessWidget {
  final NotificationsCubit notificationsCubit;

  const RaoahAlkhamsa({super.key, required this.notificationsCubit});

  @override
  Widget build(BuildContext context) {
    return MultiBlocProvider(
      providers: [
        BlocProvider<CartCubit>(
          create: (_) {
            final cubit = ServiceLocator.instance.createCartCubit();
            cubit.restorePersisted();
            return cubit;
          },
        ),
        BlocProvider<CatalogCubit>(
          create: (_) => CatalogCubit()..load(),
        ),
        BlocProvider<FavoriteCubit>(
          create: (_) {
            final cubit = FavoriteCubit();
            cubit.restorePersisted();
            return cubit;
          },
        ),
        BlocProvider<OrdersCubit>(
          create: (_) => OrdersCubit()..load(),
        ),
        BlocProvider<AddressCubit>(
          create: (_) => AddressCubit()..load(),
        ),
        BlocProvider<NotificationsCubit>.value(
          value: notificationsCubit..load(),
        ),
      ],
      child: MaterialApp(
        title: AppStrings.appName,
        debugShowCheckedModeBanner: false,
        navigatorKey: AppRouter.navigatorKey,

        // ── Localization — RTL + Arabic Material widgets ───────────────────
        locale: const Locale('ar', 'SA'),
        supportedLocales: const [
          Locale('ar', 'SA'),
        ],
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,   // ترجمة Material widgets (تاريخ، وقت، إلخ)
          GlobalWidgetsLocalizations.delegate,    // اتجاه RTL للـ widgets
          GlobalCupertinoLocalizations.delegate,  // ترجمة Cupertino widgets
        ],

        // ── Theme ─────────────────────────────────────────────────────────
        theme: AppTheme.buildTheme(),

        // ── Router ────────────────────────────────────────────────────────
        initialRoute: AppRouter.initial,
        onGenerateRoute: AppRouter.onGenerateRoute,

        // ── إجبار RTL على مستوى الـ builder ──────────────────────────────
        builder: (context, child) => Directionality(
          textDirection: TextDirection.rtl,
          child: MediaQuery(
            // منع تكبير الخط من إعدادات الجهاز ليُحافظ على الـ layout
            data: MediaQuery.of(context).copyWith(
              textScaler: TextScaler.linear(
                MediaQuery.of(context).textScaler.scale(1.0).clamp(0.85, 1.15),
              ),
            ),
            child: child!,
          ),
        ),
      ),
    );
  }

}

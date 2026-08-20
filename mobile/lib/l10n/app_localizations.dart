import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[Locale('ar')];

  /// اسم التطبيق
  ///
  /// In ar, this message translates to:
  /// **'روعة الخمسة'**
  String get appName;

  /// الشعار الفرعي للتطبيق
  ///
  /// In ar, this message translates to:
  /// **'تسوق بذكاء — اشترِ بثقة'**
  String get appTagline;

  /// شعار مختصر
  ///
  /// In ar, this message translates to:
  /// **'تسوق بذكاء'**
  String get appTaglineShort;

  /// نص أثناء التحميل
  ///
  /// In ar, this message translates to:
  /// **'جاري التحميل...'**
  String get loading;

  /// زر إعادة المحاولة
  ///
  /// In ar, this message translates to:
  /// **'إعادة المحاولة'**
  String get retry;

  /// زر الإلغاء
  ///
  /// In ar, this message translates to:
  /// **'إلغاء'**
  String get cancel;

  /// زر التأكيد
  ///
  /// In ar, this message translates to:
  /// **'تأكيد'**
  String get confirm;

  /// زر الحفظ
  ///
  /// In ar, this message translates to:
  /// **'حفظ'**
  String get save;

  /// زر التعديل
  ///
  /// In ar, this message translates to:
  /// **'تعديل'**
  String get edit;

  /// زر الحذف
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get delete;

  /// زر الإغلاق
  ///
  /// In ar, this message translates to:
  /// **'إغلاق'**
  String get close;

  /// كلمة نعم
  ///
  /// In ar, this message translates to:
  /// **'نعم'**
  String get yes;

  /// كلمة لا
  ///
  /// In ar, this message translates to:
  /// **'لا'**
  String get no;

  /// زر الرجوع
  ///
  /// In ar, this message translates to:
  /// **'رجوع'**
  String get back;

  /// زر التالي
  ///
  /// In ar, this message translates to:
  /// **'التالي'**
  String get next;

  /// زر التخطي
  ///
  /// In ar, this message translates to:
  /// **'تخطي'**
  String get skip;

  /// زر ابدأ الآن
  ///
  /// In ar, this message translates to:
  /// **'ابدأ الآن'**
  String get getStarted;

  /// رابط عرض الكل
  ///
  /// In ar, this message translates to:
  /// **'عرض الكل'**
  String get viewAll;

  /// رمز العملة
  ///
  /// In ar, this message translates to:
  /// **'⃁'**
  String get currency;

  /// تاب الرئيسية
  ///
  /// In ar, this message translates to:
  /// **'الرئيسية'**
  String get navHome;

  /// تاب الأقسام
  ///
  /// In ar, this message translates to:
  /// **'الأقسام'**
  String get navCategories;

  /// تاب المساعد
  ///
  /// In ar, this message translates to:
  /// **'المساعد'**
  String get navAssistant;

  /// تاب الملف الشخصي
  ///
  /// In ar, this message translates to:
  /// **'حسابي'**
  String get navProfile;

  /// عنوان شاشة Splash
  ///
  /// In ar, this message translates to:
  /// **'روعة الخمسة'**
  String get splashTitle;

  /// وصف شاشة Splash
  ///
  /// In ar, this message translates to:
  /// **'متجرك الذكي المفضّل'**
  String get splashSubtitle;

  /// عنوان الشريحة الأولى
  ///
  /// In ar, this message translates to:
  /// **'تسوّق بذكاء'**
  String get onboardingTitle1;

  /// وصف الشريحة الأولى
  ///
  /// In ar, this message translates to:
  /// **'اكتشف آلاف المنتجات بأسعار لا تُقاوَم، مع عروض يومية حصرية'**
  String get onboardingDesc1;

  /// عنوان الشريحة الثانية
  ///
  /// In ar, this message translates to:
  /// **'مساعدك الذكي'**
  String get onboardingTitle2;

  /// وصف الشريحة الثانية
  ///
  /// In ar, this message translates to:
  /// **'اسأل مساعدنا الذكي عن أي منتج وسيقدم لك توصيات مخصصة'**
  String get onboardingDesc2;

  /// عنوان الشريحة الثالثة
  ///
  /// In ar, this message translates to:
  /// **'توصيل سريع'**
  String get onboardingTitle3;

  /// وصف الشريحة الثالثة
  ///
  /// In ar, this message translates to:
  /// **'استلم طلبك في أسرع وقت ممكن مع خدمة التوصيل السريع'**
  String get onboardingDesc3;

  /// عنوان شاشة تسجيل الدخول
  ///
  /// In ar, this message translates to:
  /// **'مرحباً بك'**
  String get loginTitle;

  /// وصف شاشة تسجيل الدخول
  ///
  /// In ar, this message translates to:
  /// **'سجّل دخولك للمتابعة'**
  String get loginSubtitle;

  /// زر تسجيل الدخول
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الدخول'**
  String get loginButton;

  /// زر الدخول بحساب Google
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الدخول عبر Google'**
  String get loginWithGoogle;

  /// نص 'ليس لديك حساب'
  ///
  /// In ar, this message translates to:
  /// **'ليس لديك حساب؟'**
  String get loginNoAccount;

  /// رابط إنشاء حساب
  ///
  /// In ar, this message translates to:
  /// **'أنشئ حساباً'**
  String get loginCreateAccount;

  /// رابط نسيان كلمة المرور
  ///
  /// In ar, this message translates to:
  /// **'نسيت كلمة المرور؟'**
  String get loginForgotPassword;

  /// رسالة إرسال رابط إعادة كلمة المرور
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال رابط إعادة التعيين على بريدك الإلكتروني.'**
  String get loginPasswordResetSent;

  /// عنوان شاشة التسجيل
  ///
  /// In ar, this message translates to:
  /// **'إنشاء حساب'**
  String get registerTitle;

  /// وصف شاشة التسجيل
  ///
  /// In ar, this message translates to:
  /// **'انضم إلى عائلة روعة الخمسة'**
  String get registerSubtitle;

  /// زر إنشاء الحساب
  ///
  /// In ar, this message translates to:
  /// **'إنشاء الحساب'**
  String get registerButton;

  /// نص 'لديك حساب'
  ///
  /// In ar, this message translates to:
  /// **'لديك حساب بالفعل؟'**
  String get registerHaveAccount;

  /// رابط تسجيل الدخول
  ///
  /// In ar, this message translates to:
  /// **'سجّل دخولك'**
  String get registerSignIn;

  /// حقل البريد الإلكتروني
  ///
  /// In ar, this message translates to:
  /// **'البريد الإلكتروني'**
  String get fieldEmail;

  /// حقل كلمة المرور
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور'**
  String get fieldPassword;

  /// حقل تأكيد كلمة المرور
  ///
  /// In ar, this message translates to:
  /// **'تأكيد كلمة المرور'**
  String get fieldConfirmPassword;

  /// حقل الاسم الكامل
  ///
  /// In ar, this message translates to:
  /// **'الاسم الكامل'**
  String get fieldFullName;

  /// خطأ حقل فارغ
  ///
  /// In ar, this message translates to:
  /// **'هذا الحقل مطلوب'**
  String get fieldRequired;

  /// خطأ صيغة البريد
  ///
  /// In ar, this message translates to:
  /// **'صيغة البريد الإلكتروني غير صحيحة'**
  String get fieldEmailInvalid;

  /// خطأ كلمة المرور القصيرة
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور قصيرة جداً'**
  String get fieldPasswordShort;

  /// خطأ عدم تطابق كلمة المرور
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور غير متطابقة'**
  String get fieldPasswordMismatch;

  /// قوة كلمة المرور: ضعيفة جداً
  ///
  /// In ar, this message translates to:
  /// **'ضعيفة جداً'**
  String get passwordStrengthWeak;

  /// قوة كلمة المرور: ضعيفة
  ///
  /// In ar, this message translates to:
  /// **'ضعيفة'**
  String get passwordStrengthFair;

  /// قوة كلمة المرور: متوسطة
  ///
  /// In ar, this message translates to:
  /// **'متوسطة'**
  String get passwordStrengthGood;

  /// قوة كلمة المرور: قوية
  ///
  /// In ar, this message translates to:
  /// **'قوية'**
  String get passwordStrengthStrong;

  /// بداية نص الموافقة على الشروط
  ///
  /// In ar, this message translates to:
  /// **'أوافق على '**
  String get termsAgree;

  /// شروط الاستخدام
  ///
  /// In ar, this message translates to:
  /// **'شروط الاستخدام'**
  String get termsOfUse;

  /// سياسة الخصوصية
  ///
  /// In ar, this message translates to:
  /// **'سياسة الخصوصية'**
  String get privacyPolicy;

  /// خطأ الموافقة على الشروط
  ///
  /// In ar, this message translates to:
  /// **'يجب الموافقة على الشروط والأحكام للمتابعة.'**
  String get termsRequired;

  /// فاصل 'أو'
  ///
  /// In ar, this message translates to:
  /// **'أو'**
  String get orDivider;

  /// تلميح حقل البحث
  ///
  /// In ar, this message translates to:
  /// **'ابحث عن منتج...'**
  String get homeSearchHint;

  /// تلميح شريط البحث في الصفحة الرئيسية
  ///
  /// In ar, this message translates to:
  /// **'ابحث في روعة الخمسة'**
  String get homeSearchInApp;

  /// تسمية عنوان التوصيل في الرأس
  ///
  /// In ar, this message translates to:
  /// **'المنزل'**
  String get homeLocationHome;

  /// عنوان شريط الأقسام السريعة
  ///
  /// In ar, this message translates to:
  /// **'استكشف الأقسام'**
  String get homeExploreSections;

  /// عنوان قسم الأكثر طلباً
  ///
  /// In ar, this message translates to:
  /// **'الأكثر طلباً'**
  String get homeSectionMostRequested;

  /// عنوان قسم المنتجات الغذائية/الطازجة
  ///
  /// In ar, this message translates to:
  /// **'خضروات وفواكه'**
  String get homeSectionFreshGroceries;

  /// عنوان قسم الأقسام
  ///
  /// In ar, this message translates to:
  /// **'الأقسام'**
  String get homeSectionCategories;

  /// عنوان قسم المنتجات
  ///
  /// In ar, this message translates to:
  /// **'المنتجات المميزة'**
  String get homeSectionFeatured;

  /// عنوان قسم الأسعار الترويجي
  ///
  /// In ar, this message translates to:
  /// **'أسعار ما تلاقيها'**
  String get homeSectionPricesTitle;

  /// سطر تحت عنوان قسم الأسعار الترويجي
  ///
  /// In ar, this message translates to:
  /// **'إلا في روعة الخمسة! 😉'**
  String get homeSectionPricesSubtitle;

  /// عنوان نتائج البحث
  ///
  /// In ar, this message translates to:
  /// **'نتائج البحث'**
  String get homeSearchResults;

  /// فلتر 'الكل' في الأقسام
  ///
  /// In ar, this message translates to:
  /// **'الكل'**
  String get homeCategoryAll;

  /// رسالة لا توجد نتائج
  ///
  /// In ar, this message translates to:
  /// **'لا نتائج لـ \"{query}\"'**
  String homeNoResults(String query);

  /// رسالة قسم فارغ
  ///
  /// In ar, this message translates to:
  /// **'لا توجد منتجات في هذا القسم'**
  String get homeNoProductsInCategory;

  /// زر عرض جميع المنتجات
  ///
  /// In ar, this message translates to:
  /// **'عرض الكل'**
  String get homeShowAll;

  /// شارة العرض الخاص في الـ carousel
  ///
  /// In ar, this message translates to:
  /// **'عرض خاص'**
  String get promoSpecialOffer;

  /// زر تسوق الآن في العرض
  ///
  /// In ar, this message translates to:
  /// **'تسوّق الآن'**
  String get promoShopNow;

  /// عنوان العرض الأول
  ///
  /// In ar, this message translates to:
  /// **'خصم 20٪ على المنظفات'**
  String get promoTitle1;

  /// وصف العرض الأول
  ///
  /// In ar, this message translates to:
  /// **'عرض لفترة محدودة — لا تفوّته!'**
  String get promoSubtitle1;

  /// عنوان العرض الثاني
  ///
  /// In ar, this message translates to:
  /// **'أحدث الإلكترونيات'**
  String get promoTitle2;

  /// وصف العرض الثاني
  ///
  /// In ar, this message translates to:
  /// **'سماعات وشواحن بأسعار لا تُصدَّق'**
  String get promoSubtitle2;

  /// عنوان العرض الثالث
  ///
  /// In ar, this message translates to:
  /// **'عسل سدر أصيل'**
  String get promoTitle3;

  /// وصف العرض الثالث
  ///
  /// In ar, this message translates to:
  /// **'من مناحل جبال السروات مباشرةً'**
  String get promoSubtitle3;

  /// عنوان العرض الرابع
  ///
  /// In ar, this message translates to:
  /// **'اشترِ 2 واحصل على 1'**
  String get promoTitle4;

  /// وصف العرض الرابع
  ///
  /// In ar, this message translates to:
  /// **'عروض حصرية على المواد الغذائية'**
  String get promoSubtitle4;

  /// زر إضافة للسلة
  ///
  /// In ar, this message translates to:
  /// **'أضف للسلة'**
  String get productAddToCart;

  /// رسالة إضافة المنتج للسلة
  ///
  /// In ar, this message translates to:
  /// **'أُضيف \"{name}\" للسلة ✓'**
  String productAddedToCart(String name);

  /// حالة نفاد المخزون
  ///
  /// In ar, this message translates to:
  /// **'نفد المخزون'**
  String get productOutOfStock;

  /// شارة الخصم
  ///
  /// In ar, this message translates to:
  /// **'-{percent}٪'**
  String productDiscount(int percent);

  /// قسم نصائح المساعد الذكي في التفاصيل
  ///
  /// In ar, this message translates to:
  /// **'نصائح المساعد الذكي'**
  String get productAiTips;

  /// قسم وصف المنتج
  ///
  /// In ar, this message translates to:
  /// **'وصف المنتج'**
  String get productDescription;

  /// قسم طريقة الاستخدام
  ///
  /// In ar, this message translates to:
  /// **'طريقة الاستخدام'**
  String get productUsage;

  /// رابط 'اقرأ المزيد'
  ///
  /// In ar, this message translates to:
  /// **'اقرأ المزيد'**
  String get productReadMore;

  /// رابط 'اقرأ أقل'
  ///
  /// In ar, this message translates to:
  /// **'اقرأ أقل'**
  String get productReadLess;

  /// تسمية الكمية
  ///
  /// In ar, this message translates to:
  /// **'الكمية'**
  String get productQuantity;

  /// عنوان شاشة السلة
  ///
  /// In ar, this message translates to:
  /// **'سلة المشتريات'**
  String get cartTitle;

  /// رسالة السلة الفارغة
  ///
  /// In ar, this message translates to:
  /// **'سلتك فارغة'**
  String get cartEmpty;

  /// تلميح السلة الفارغة
  ///
  /// In ar, this message translates to:
  /// **'أضف منتجاتك المفضلة وابدأ التسوق'**
  String get cartEmptyHint;

  /// زر بدء التسوق من السلة
  ///
  /// In ar, this message translates to:
  /// **'ابدأ التسوق'**
  String get cartStartShopping;

  /// تسمية المجموع الكلي
  ///
  /// In ar, this message translates to:
  /// **'المجموع الكلي'**
  String get cartTotal;

  /// تسمية المجموع الجزئي
  ///
  /// In ar, this message translates to:
  /// **'المجموع الجزئي'**
  String get cartSubtotal;

  /// عدد المنتجات في السلة
  ///
  /// In ar, this message translates to:
  /// **'{count} منتج'**
  String cartItemCount(int count);

  /// زر إتمام الشراء
  ///
  /// In ar, this message translates to:
  /// **'إتمام الشراء'**
  String get checkoutButton;

  /// رسالة نجاح الطلب
  ///
  /// In ar, this message translates to:
  /// **'تم تأكيد طلبك بنجاح!'**
  String get checkoutSuccess;

  /// عنوان شاشة المساعد الذكي
  ///
  /// In ar, this message translates to:
  /// **'المساعد الذكي'**
  String get aiAssistantTitle;

  /// تلميح الـ FAB
  ///
  /// In ar, this message translates to:
  /// **'اسأل المساعد الذكي'**
  String get aiAssistantHint;

  /// تلميح حقل الرسالة في المحادثة
  ///
  /// In ar, this message translates to:
  /// **'اكتب رسالتك...'**
  String get aiTypingHint;

  /// زر إرسال الرسالة
  ///
  /// In ar, this message translates to:
  /// **'إرسال'**
  String get aiSendButton;

  /// حالة تفكير المساعد
  ///
  /// In ar, this message translates to:
  /// **'المساعد يفكر...'**
  String get aiThinking;

  /// رسالة ترحيب المساعد الذكي
  ///
  /// In ar, this message translates to:
  /// **'مرحباً! أنا مساعدك الذكي في روعة الخمسة. كيف يمكنني مساعدتك اليوم؟'**
  String get aiWelcome;

  /// خطأ عام في المساعد
  ///
  /// In ar, this message translates to:
  /// **'حدث خطأ. يرجى المحاولة مرة أخرى.'**
  String get aiErrorGeneral;

  /// تلميح زر الميكروفون - بدء
  ///
  /// In ar, this message translates to:
  /// **'ابدأ الاستماع'**
  String get micStartListening;

  /// تلميح زر الميكروفون - إيقاف
  ///
  /// In ar, this message translates to:
  /// **'أوقف الاستماع'**
  String get micStopListening;

  /// عنوان شاشة الملف الشخصي
  ///
  /// In ar, this message translates to:
  /// **'الملف الشخصي'**
  String get profileTitle;

  /// قسم الطلبات
  ///
  /// In ar, this message translates to:
  /// **'طلباتي'**
  String get profileOrders;

  /// قسم الإعدادات
  ///
  /// In ar, this message translates to:
  /// **'الإعدادات'**
  String get profileSettings;

  /// إعداد اللغة
  ///
  /// In ar, this message translates to:
  /// **'اللغة'**
  String get profileLanguage;

  /// إعداد الإشعارات
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات'**
  String get profileNotifications;

  /// إعداد الوضع الليلي
  ///
  /// In ar, this message translates to:
  /// **'الوضع الليلي'**
  String get profileDarkMode;

  /// خيار تغيير كلمة المرور
  ///
  /// In ar, this message translates to:
  /// **'تغيير كلمة المرور'**
  String get profileChangePassword;

  /// خيار الخصوصية
  ///
  /// In ar, this message translates to:
  /// **'الخصوصية والأمان'**
  String get profilePrivacy;

  /// خيار الشروط والأحكام
  ///
  /// In ar, this message translates to:
  /// **'الشروط والأحكام'**
  String get profileTerms;

  /// خيار عن التطبيق
  ///
  /// In ar, this message translates to:
  /// **'عن التطبيق'**
  String get profileAbout;

  /// زر تسجيل الخروج
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الخروج'**
  String get profileSignOut;

  /// عنوان نافذة تأكيد الخروج
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الخروج'**
  String get profileSignOutConfirmTitle;

  /// نص تأكيد الخروج
  ///
  /// In ar, this message translates to:
  /// **'هل أنت متأكد من رغبتك في تسجيل الخروج؟'**
  String get profileSignOutConfirmBody;

  /// اسم المستخدم الضيف
  ///
  /// In ar, this message translates to:
  /// **'مستخدم'**
  String get profileGuestName;

  /// بريد المستخدم الضيف
  ///
  /// In ar, this message translates to:
  /// **'لم يتم تسجيل الدخول'**
  String get profileGuestEmail;

  /// تلميح تعديل الاسم
  ///
  /// In ar, this message translates to:
  /// **'تعديل الاسم'**
  String get profileEditName;

  /// اسم قسم المنظفات
  ///
  /// In ar, this message translates to:
  /// **'منظفات'**
  String get catCleaning;

  /// اسم قسم الإلكترونيات
  ///
  /// In ar, this message translates to:
  /// **'إلكترونيات'**
  String get catElectronics;

  /// اسم قسم المواد الغذائية
  ///
  /// In ar, this message translates to:
  /// **'مواد غذائية'**
  String get catFood;

  /// اسم قسم الإكسسوارات
  ///
  /// In ar, this message translates to:
  /// **'إكسسوارات'**
  String get catAccessories;

  /// اسم قسم الأدوات المنزلية
  ///
  /// In ar, this message translates to:
  /// **'أدوات منزلية'**
  String get catHomeTools;

  /// خطأ غير معروف
  ///
  /// In ar, this message translates to:
  /// **'حدث خطأ غير متوقع. حاول مجدداً.'**
  String get errorUnknown;

  /// خطأ الشبكة
  ///
  /// In ar, this message translates to:
  /// **'تحقق من اتصالك بالإنترنت.'**
  String get errorNetwork;

  /// خطأ 404
  ///
  /// In ar, this message translates to:
  /// **'الصفحة غير موجودة'**
  String get errorPageNotFound;

  /// زر العودة للرئيسية
  ///
  /// In ar, this message translates to:
  /// **'العودة للرئيسية'**
  String get errorReturnHome;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ar'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return AppLocalizationsAr();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}

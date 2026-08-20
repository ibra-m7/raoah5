// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class AppLocalizationsAr extends AppLocalizations {
  AppLocalizationsAr([String locale = 'ar']) : super(locale);

  @override
  String get appName => 'روعة الخمسة';

  @override
  String get appTagline => 'تسوق بذكاء — اشترِ بثقة';

  @override
  String get appTaglineShort => 'تسوق بذكاء';

  @override
  String get loading => 'جاري التحميل...';

  @override
  String get retry => 'إعادة المحاولة';

  @override
  String get cancel => 'إلغاء';

  @override
  String get confirm => 'تأكيد';

  @override
  String get save => 'حفظ';

  @override
  String get edit => 'تعديل';

  @override
  String get delete => 'حذف';

  @override
  String get close => 'إغلاق';

  @override
  String get yes => 'نعم';

  @override
  String get no => 'لا';

  @override
  String get back => 'رجوع';

  @override
  String get next => 'التالي';

  @override
  String get skip => 'تخطي';

  @override
  String get getStarted => 'ابدأ الآن';

  @override
  String get viewAll => 'عرض الكل';

  @override
  String get currency => '⃁';

  @override
  String get navHome => 'الرئيسية';

  @override
  String get navCategories => 'الأقسام';

  @override
  String get navAssistant => 'المساعد';

  @override
  String get navProfile => 'حسابي';

  @override
  String get splashTitle => 'روعة الخمسة';

  @override
  String get splashSubtitle => 'متجرك الذكي المفضّل';

  @override
  String get onboardingTitle1 => 'تسوّق بذكاء';

  @override
  String get onboardingDesc1 =>
      'اكتشف آلاف المنتجات بأسعار لا تُقاوَم، مع عروض يومية حصرية';

  @override
  String get onboardingTitle2 => 'مساعدك الذكي';

  @override
  String get onboardingDesc2 =>
      'اسأل مساعدنا الذكي عن أي منتج وسيقدم لك توصيات مخصصة';

  @override
  String get onboardingTitle3 => 'توصيل سريع';

  @override
  String get onboardingDesc3 =>
      'استلم طلبك في أسرع وقت ممكن مع خدمة التوصيل السريع';

  @override
  String get loginTitle => 'مرحباً بك';

  @override
  String get loginSubtitle => 'سجّل دخولك للمتابعة';

  @override
  String get loginButton => 'تسجيل الدخول';

  @override
  String get loginWithGoogle => 'تسجيل الدخول عبر Google';

  @override
  String get loginNoAccount => 'ليس لديك حساب؟';

  @override
  String get loginCreateAccount => 'أنشئ حساباً';

  @override
  String get loginForgotPassword => 'نسيت كلمة المرور؟';

  @override
  String get loginPasswordResetSent =>
      'تم إرسال رابط إعادة التعيين على بريدك الإلكتروني.';

  @override
  String get registerTitle => 'إنشاء حساب';

  @override
  String get registerSubtitle => 'انضم إلى عائلة روعة الخمسة';

  @override
  String get registerButton => 'إنشاء الحساب';

  @override
  String get registerHaveAccount => 'لديك حساب بالفعل؟';

  @override
  String get registerSignIn => 'سجّل دخولك';

  @override
  String get fieldEmail => 'البريد الإلكتروني';

  @override
  String get fieldPassword => 'كلمة المرور';

  @override
  String get fieldConfirmPassword => 'تأكيد كلمة المرور';

  @override
  String get fieldFullName => 'الاسم الكامل';

  @override
  String get fieldRequired => 'هذا الحقل مطلوب';

  @override
  String get fieldEmailInvalid => 'صيغة البريد الإلكتروني غير صحيحة';

  @override
  String get fieldPasswordShort => 'كلمة المرور قصيرة جداً';

  @override
  String get fieldPasswordMismatch => 'كلمة المرور غير متطابقة';

  @override
  String get passwordStrengthWeak => 'ضعيفة جداً';

  @override
  String get passwordStrengthFair => 'ضعيفة';

  @override
  String get passwordStrengthGood => 'متوسطة';

  @override
  String get passwordStrengthStrong => 'قوية';

  @override
  String get termsAgree => 'أوافق على ';

  @override
  String get termsOfUse => 'شروط الاستخدام';

  @override
  String get privacyPolicy => 'سياسة الخصوصية';

  @override
  String get termsRequired => 'يجب الموافقة على الشروط والأحكام للمتابعة.';

  @override
  String get orDivider => 'أو';

  @override
  String get homeSearchHint => 'ابحث عن منتج...';

  @override
  String get homeSearchInApp => 'ابحث في روعة الخمسة';

  @override
  String get homeLocationHome => 'المنزل';

  @override
  String get homeExploreSections => 'استكشف الأقسام';

  @override
  String get homeSectionMostRequested => 'الأكثر طلباً';

  @override
  String get homeSectionFreshGroceries => 'خضروات وفواكه';

  @override
  String get homeSectionCategories => 'الأقسام';

  @override
  String get homeSectionFeatured => 'المنتجات المميزة';

  @override
  String get homeSectionPricesTitle => 'أسعار ما تلاقيها';

  @override
  String get homeSectionPricesSubtitle => 'إلا في روعة الخمسة! 😉';

  @override
  String get homeSearchResults => 'نتائج البحث';

  @override
  String get homeCategoryAll => 'الكل';

  @override
  String homeNoResults(String query) {
    return 'لا نتائج لـ \"$query\"';
  }

  @override
  String get homeNoProductsInCategory => 'لا توجد منتجات في هذا القسم';

  @override
  String get homeShowAll => 'عرض الكل';

  @override
  String get promoSpecialOffer => 'عرض خاص';

  @override
  String get promoShopNow => 'تسوّق الآن';

  @override
  String get promoTitle1 => 'خصم 20٪ على المنظفات';

  @override
  String get promoSubtitle1 => 'عرض لفترة محدودة — لا تفوّته!';

  @override
  String get promoTitle2 => 'أحدث الإلكترونيات';

  @override
  String get promoSubtitle2 => 'سماعات وشواحن بأسعار لا تُصدَّق';

  @override
  String get promoTitle3 => 'عسل سدر أصيل';

  @override
  String get promoSubtitle3 => 'من مناحل جبال السروات مباشرةً';

  @override
  String get promoTitle4 => 'اشترِ 2 واحصل على 1';

  @override
  String get promoSubtitle4 => 'عروض حصرية على المواد الغذائية';

  @override
  String get productAddToCart => 'أضف للسلة';

  @override
  String productAddedToCart(String name) {
    return 'أُضيف \"$name\" للسلة ✓';
  }

  @override
  String get productOutOfStock => 'نفد المخزون';

  @override
  String productDiscount(int percent) {
    return '-$percent٪';
  }

  @override
  String get productAiTips => 'نصائح المساعد الذكي';

  @override
  String get productDescription => 'وصف المنتج';

  @override
  String get productUsage => 'طريقة الاستخدام';

  @override
  String get productReadMore => 'اقرأ المزيد';

  @override
  String get productReadLess => 'اقرأ أقل';

  @override
  String get productQuantity => 'الكمية';

  @override
  String get cartTitle => 'سلة المشتريات';

  @override
  String get cartEmpty => 'سلتك فارغة';

  @override
  String get cartEmptyHint => 'أضف منتجاتك المفضلة وابدأ التسوق';

  @override
  String get cartStartShopping => 'ابدأ التسوق';

  @override
  String get cartTotal => 'المجموع الكلي';

  @override
  String get cartSubtotal => 'المجموع الجزئي';

  @override
  String cartItemCount(int count) {
    return '$count منتج';
  }

  @override
  String get checkoutButton => 'إتمام الشراء';

  @override
  String get checkoutSuccess => 'تم تأكيد طلبك بنجاح!';

  @override
  String get aiAssistantTitle => 'المساعد الذكي';

  @override
  String get aiAssistantHint => 'اسأل المساعد الذكي';

  @override
  String get aiTypingHint => 'اكتب رسالتك...';

  @override
  String get aiSendButton => 'إرسال';

  @override
  String get aiThinking => 'المساعد يفكر...';

  @override
  String get aiWelcome =>
      'مرحباً! أنا مساعدك الذكي في روعة الخمسة. كيف يمكنني مساعدتك اليوم؟';

  @override
  String get aiErrorGeneral => 'حدث خطأ. يرجى المحاولة مرة أخرى.';

  @override
  String get micStartListening => 'ابدأ الاستماع';

  @override
  String get micStopListening => 'أوقف الاستماع';

  @override
  String get profileTitle => 'الملف الشخصي';

  @override
  String get profileOrders => 'طلباتي';

  @override
  String get profileSettings => 'الإعدادات';

  @override
  String get profileLanguage => 'اللغة';

  @override
  String get profileNotifications => 'الإشعارات';

  @override
  String get profileDarkMode => 'الوضع الليلي';

  @override
  String get profileChangePassword => 'تغيير كلمة المرور';

  @override
  String get profilePrivacy => 'الخصوصية والأمان';

  @override
  String get profileTerms => 'الشروط والأحكام';

  @override
  String get profileAbout => 'عن التطبيق';

  @override
  String get profileSignOut => 'تسجيل الخروج';

  @override
  String get profileSignOutConfirmTitle => 'تسجيل الخروج';

  @override
  String get profileSignOutConfirmBody =>
      'هل أنت متأكد من رغبتك في تسجيل الخروج؟';

  @override
  String get profileGuestName => 'مستخدم';

  @override
  String get profileGuestEmail => 'لم يتم تسجيل الدخول';

  @override
  String get profileEditName => 'تعديل الاسم';

  @override
  String get catCleaning => 'منظفات';

  @override
  String get catElectronics => 'إلكترونيات';

  @override
  String get catFood => 'مواد غذائية';

  @override
  String get catAccessories => 'إكسسوارات';

  @override
  String get catHomeTools => 'أدوات منزلية';

  @override
  String get errorUnknown => 'حدث خطأ غير متوقع. حاول مجدداً.';

  @override
  String get errorNetwork => 'تحقق من اتصالك بالإنترنت.';

  @override
  String get errorPageNotFound => 'الصفحة غير موجودة';

  @override
  String get errorReturnHome => 'العودة للرئيسية';
}

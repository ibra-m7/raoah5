// ══════════════════════════════════════════════════════════════════════════════
// AppStrings — نصوص التطبيق المركزية
//
// هذا الملف يعكس lib/l10n/app_ar.arb تماماً.
// استخدم AppStrings.key مباشرة في أي مكان دون الحاجة لـ BuildContext.
// عند الحاجة لدعم لغات إضافية لاحقاً: استبدل القيم الثابتة بـ
//   AppLocalizations.of(context).key
// ══════════════════════════════════════════════════════════════════════════════

abstract class AppStrings {
  AppStrings._();

  // ── عام ────────────────────────────────────────────────────────────────────
  static const appName            = 'روعة الخمسة';
  static const appTagline         = 'تسوق بذكاء — اشترِ بثقة';
  static const appTaglineShort    = 'تسوق بذكاء';
  static const loading            = 'جاري التحميل...';
  static const retry              = 'إعادة المحاولة';
  static const cancel             = 'إلغاء';
  static const confirm            = 'تأكيد';
  static const save               = 'حفظ';
  static const edit               = 'تعديل';
  static const delete             = 'حذف';
  static const close              = 'إغلاق';
  static const yes                = 'نعم';
  static const no                 = 'لا';
  static const back               = 'رجوع';
  static const next               = 'التالي';
  static const skip               = 'تخطي';
  static const getStarted         = 'ابدأ الآن';
  static const viewAll            = 'عرض الكل';
  static const currency           = '\u{20C1}';
  static const orDivider          = 'أو';

  // ── Bottom Navigation ──────────────────────────────────────────────────────
  static const navHome            = 'الرئيسية';
  static const navCategories      = 'الأقسام';
  static const navAssistant       = 'المساعد';
  static const navProfile         = 'حسابي';

  /// عنوان قسم المقاضي في صفحة الأقسام (مرجع كيو)
  static const categoriesGroceriesSection = 'المقاضي';
  static const categoriesBeveragesTreatsSection = 'المشروبات والمفرحات';
  static const categoriesHomeCareSection = 'العناية بالمنزل';

  // ── Splash ─────────────────────────────────────────────────────────────────
  static const splashTitle        = 'روعة الخمسة';
  static const splashSubtitle     = 'متجرك الذكي المفضّل';

  // ── Onboarding ─────────────────────────────────────────────────────────────
  static const onboardingTitle1   = 'تسوّق بذكاء';
  static const onboardingDesc1    = 'اكتشف آلاف المنتجات بأسعار لا تُقاوَم، مع عروض يومية حصرية';
  static const onboardingTitle2   = 'مساعدك الذكي';
  static const onboardingDesc2    = 'اسأل مساعدنا الذكي عن أي منتج وسيقدم لك توصيات مخصصة';
  static const onboardingTitle3   = 'توصيل سريع';
  static const onboardingDesc3    = 'استلم طلبك في أسرع وقت ممكن مع خدمة التوصيل السريع';

  // ── Login ──────────────────────────────────────────────────────────────────
  static const loginTitle             = 'مرحباً بك';
  static const loginSubtitle          = 'سجّل دخولك للمتابعة';
  static const loginButton            = 'تسجيل الدخول';
  static const loginWithGoogle        = 'تسجيل الدخول عبر Google';
  static const loginNoAccount         = 'ليس لديك حساب؟';
  static const loginCreateAccount     = 'أنشئ حساباً';
  static const loginForgotPassword    = 'نسيت كلمة المرور؟';
  static const loginPasswordResetSent =
      'تم إرسال رابط إعادة التعيين على بريدك الإلكتروني.';

  // ── Phone OTP ──────────────────────────────────────────────────────────────
  static const phoneLoginTitle        = 'أدخل رقم هاتفك';
  static const phoneLoginSubtitle     =
      'سنرسل رمز تحقق عبر واتساب لتأكيد رقمك';
  static const phoneLoginHint         = 'مثال: 5xxxxxxxx أو 05xxxxxxxx';
  static const phoneLoginButton       = 'إرسال رمز التحقق';
  static const phoneLoginWhatsappNote =
      'سيصلك الرمز في واتساب على رقمك السعودي';
  static const guestBrowse = 'تصفح التطبيق';
  static const guestLoginCta = 'تسجيل الدخول';
  static const guestLoginRequiredTitle = 'سجّل دخولك أولاً';
  static const guestLoginRequiredBody =
      'يمكنك تصفح المنتجات بحرية. لإتمام الطلب أو عرض حسابك سجّل دخولك برقم جوالك.';
  static const guestCheckoutMessage =
      'لإتمام الطلب سجّل دخولك برقم جوالك';
  static const guestOrdersMessage =
      'لعرض طلباتك وتتبعها سجّل دخولك أولاً';
  static const guestAddressMessage =
      'لحفظ عنوان التوصيل سجّل دخولك أولاً';
  static const guestProfileTitle = 'حسابك ينتظرك';
  static const guestProfileBody =
      'سجّل دخولك برقم جوالك لعرض طلباتك وعناوينك وإدارة حسابك.';
  static const guestWelcomeSnack = 'أهلاً بك في روعة الخمسة';
  static const completeNameSkip = 'لاحقاً';
  static const completeLocationSkip = 'تخطي، سأضيف العنوان لاحقاً';
  static const companyWhatsapp        = '';
  static const fieldPhone             = 'رقم الجوال';
  static const fieldPhoneInvalid      =
      'أدخل رقماً سعودياً صالحاً يبدأ بـ 5 ويتكون من 9 أرقام';
  static const otpTitle               = 'رمز التحقق';
  static const otpSubtitle            = 'أدخل الرمز المرسل إلى';
  static const otpVerifyButton        = 'تأكيد الرمز';
  static const otpResend              = 'إعادة إرسال الرمز';
  static const otpResendIn            = 'يمكنك إعادة الإرسال بعد';
  static const otpResent              = 'تم إرسال رمز جديد عبر واتساب.';
  static const otpWhatsappHint        =
      'افتح واتساب وأدخل رمز التحقق المرسل إلى جوالك السعودي.';

  // ── إكمال الملف الشخصي ─────────────────────────────────────────────────────
  static const completeNameTitle      = 'اسمك الكامل';
  static const completeNameSubtitle   =
      'اكتب اسمك الثنائي ليظهر على طلباتك وحسابك';
  static const completeNameButton     = 'حفظ الاسم والمتابعة';
  static const fieldFullNameInvalid   = 'أدخل الاسم الثنائي على الأقل';
  static const fieldFullNameReal      = 'أدخل اسمك الحقيقي';
  static const completeLocationTitle  = 'حدد موقعك';
  static const completeLocationSubtitle =
      'نستخدم موقعك لتوصيل طلباتك إلى العنوان الصحيح';
  static const completeLocationButton = 'حفظ الموقع والدخول';
  static const locationDetect         = 'تحديد موقعي الحالي';
  static const locationRetry          = 'إعادة تحديد الموقع';
  static const locationEmpty          = 'اضغط لتحديد موقعك من الـ GPS';
  static const locationCaptured       = 'تم تحديد الموقع';
  static const locationDetailsHint    = 'تفاصيل إضافية (اختياري)';
  static const locationRequired       = 'حدد موقعك أولاً قبل المتابعة';
  static const locationFailed         = 'تعذّر تحديد الموقع. حاول مرة أخرى.';
  static const locationServiceDisabled =
      'خدمة الموقع مغلقة. فعّلها من إعدادات الجهاز ثم أعد المحاولة.';
  static const locationPermissionDenied =
      'نحتاج إذن الموقع لتحديد عنوان التوصيل.';
  static const locationPermissionDeniedForever =
      'إذن الموقع مرفوض. فعّله من إعدادات التطبيق.';

  // ── Register ───────────────────────────────────────────────────────────────
  static const registerTitle      = 'إنشاء حساب';
  static const registerSubtitle   = 'انضم إلى عائلة روعة الخمسة';
  static const registerButton     = 'إنشاء الحساب';
  static const registerHaveAccount= 'لديك حساب بالفعل؟';
  static const registerSignIn     = 'سجّل دخولك';

  // ── Form Fields ────────────────────────────────────────────────────────────
  static const fieldEmail             = 'البريد الإلكتروني';
  static const fieldPassword          = 'كلمة المرور';
  static const fieldConfirmPassword   = 'تأكيد كلمة المرور';
  static const fieldFullName          = 'الاسم الكامل';
  static const fieldRequired          = 'هذا الحقل مطلوب';
  static const fieldEmailInvalid      = 'صيغة البريد الإلكتروني غير صحيحة';
  static const fieldPasswordShort     = 'كلمة المرور قصيرة جداً';
  static const fieldPasswordMismatch  = 'كلمة المرور غير متطابقة';
  static const passwordStrengthWeak   = 'ضعيفة جداً';
  static const passwordStrengthFair   = 'ضعيفة';
  static const passwordStrengthGood   = 'متوسطة';
  static const passwordStrengthStrong = 'قوية';
  static const termsAgree             = 'أوافق على ';
  static const termsOfUse            = 'شروط الاستخدام';
  static const privacyPolicy         = 'سياسة الخصوصية';
  static const termsRequired          =
      'يجب الموافقة على الشروط والأحكام للمتابعة.';

  // ── Home ───────────────────────────────────────────────────────────────────
  static const homeSearchHint         = 'ابحث عن منتج...';
  /// تلميح شريط البحث العلوي (مرجع كيو)
  static const homeSearchInApp        = 'ابحث في روعة الخمسة';
  /// اقتراحات الكتابة المتحركة في حقل البحث
  static const homeSearchHints        = <String>[
    'ابحث عن عروض اليوم...',
    'ابحث في روعة الخمسة',
    'في روعة تحصل كل شي روعة... أنت ابحث فقط',
    'ابحث عن كل شي روعة',
  ];
  /// عنوان موقع التوصيل الافتراضي في الرأس
  static const homeLocationHome       = 'المنزل';
  static const deliveryTo             = 'التوصيل إلى';
  static const deliveryChooseAddress  = 'قم باختيار عنوان التوصيل';
  static const deliveryAddNew         = 'إضافة عنوان جديد';
  static const deliveryAddTitle       = 'اضافة عنوان';
  static const deliverySearch         = 'البحث';
  static const deliveryMyLocation     = 'موقعي';
  static const deliveryNameHint       = 'اسم العنوان';
  static const deliveryDescHint       = 'وصف العنوان';
  static const deliverySave           = 'حفظ';
  static const deliveryUseThis        = 'استخدام هذا العنوان';
  static const deliveryCurrentDetails = 'تفاصيل الموقع';
  static const deliveryDeleteConfirm  = 'حذف هذا العنوان؟';
  static const deliveryDeleteBody     = 'لن يظهر في قائمة التوصيل بعد الحذف.';
  static const deliveryNameRequired   = 'أدخل اسم العنوان';
  static const deliveryDescRequired   = 'أدخل وصف العنوان';
  static const deliveryPinRequired    = 'حدد الموقع على الخريطة أولاً';
  static const deliveryEmpty          = 'لا توجد عناوين محفوظة بعد';
  static const editNameTitle          = 'تعديل الاسم';
  static const editNameSubtitle       =
      'سيظهر اسمك بشكل أنيق على طلباتك وحسابك';
  static const editNameSave           = 'حفظ الاسم';
  /// عنوان شريط الأقسام السريعة
  static const homeExploreSections    = 'استكشف الأقسام';
  /// قسم مواد غذائية / طازج (بيانات من فئة cat_food في Mock)
  static const homeSectionFreshGroceries = 'خضروات وفواكه';
  /// سطر وصفي أسفل عنوان قسم الطازج (مرجع كيو)
  static const homeSectionFreshGroceriesSubtitle =
      'الأقل سعر والأفضل جودة';
  static const homeSectionCategories  = 'الأقسام';
  static const homeSectionMostRequested = 'الأكثر طلباً';
  static const homeSectionFeatured    = 'المنتجات المميزة';
  /// قسم الأسعار الترويجي (محاذي لهيكل «الأكثر طلباً»)
  static const homeSectionPricesTitle =
      'أسعار ما تلاقيها';
  static const homeSectionPricesSubtitle = 'إلا في روعة الخمسة! 😉';
  static const homeSearchResults      = 'نتائج البحث';
  static const homeCategoryAll        = 'الكل';
  static const homeNoProductsInCategory = 'لا توجد منتجات في هذا القسم';
  static const homeShowAll            = 'عرض الكل';

  static String homeNoResults(String query) => 'لا نتائج لـ "$query"';

  // ── Promotions ─────────────────────────────────────────────────────────────
  static const promoSpecialOffer  = 'عرض خاص';
  static const promoShopNow       = 'تسوّق الآن';
  static const promoTitle1        = 'خصم 20٪ على المنظفات';
  static const promoSubtitle1     = 'عرض لفترة محدودة — لا تفوّته!';
  static const promoTitle2        = 'أحدث الإلكترونيات';
  static const promoSubtitle2     = 'سماعات وشواحن بأسعار لا تُصدَّق';
  static const promoTitle3        = 'عسل سدر أصيل';
  static const promoSubtitle3     = 'من مناحل جبال السروات مباشرةً';
  static const promoTitle4        = 'اشترِ 2 واحصل على 1';
  static const promoSubtitle4     = 'عروض حصرية على المواد الغذائية';

  // ── Product ────────────────────────────────────────────────────────────────
  static const productAddToCart   = 'أضف للسلة';
  static const productOutOfStock  = 'نفد المخزون';
  static const productAiTips      = 'نصائح المساعد الذكي';
  static const productDescription = 'وصف المنتج';
  static const productUsage       = 'طريقة الاستخدام';
  static const productReadMore    = 'اقرأ المزيد';
  static const productReadLess    = 'اقرأ أقل';
  static const productQuantity    = 'الكمية';

  static String productAddedToCart(String name) => 'أُضيف "$name" للسلة ✓';
  static String productDiscount(int percent)     => '-$percent٪';

  // ── Cart ───────────────────────────────────────────────────────────────────
  static const cartTitle          = 'سلة المشتريات';
  static const cartEmpty          = 'سلتك فارغة';
  static const cartEmptyHint      = 'أضف منتجاتك المفضلة وابدأ التسوق';
  static const cartStartShopping  = 'ابدأ التسوق';
  static const cartTotal          = 'المجموع الكلي';
  static const cartSubtotal       = 'المجموع الجزئي';
  static const checkoutButton     = 'إتمام الشراء';
  static const checkoutSuccess    = 'تم تأكيد طلبك بنجاح!';

  static String cartItemCount(int count) => '$count منتج';

  // ── Favorites ──────────────────────────────────────────────────────────────
  static const favoritesTitle      = 'المفضلة';
  static const favoritesEmpty      = 'لا توجد منتجات مفضلة بعد';
  static const favoritesEmptyHint  =
      'اضغط أيقونة القلب على بطاقة المنتج أو في صفحة التفاصيل لحفظه هنا';

  // ── AI Assistant ───────────────────────────────────────────────────────────
  static const aiAssistantTitle   = 'المساعد الذكي';
  static const aiAssistantHint    = 'اسأل المساعد الذكي';
  static const aiTypingHint       = 'اكتب رسالتك...';
  static const aiSendButton       = 'إرسال';
  static const aiThinking         = 'المساعد يفكر...';
  static const aiWelcome          =
      'مرحباً! أنا مساعدك الذكي في روعة الخمسة. كيف يمكنني مساعدتك اليوم؟';
  static const aiErrorGeneral     = 'حدث خطأ. يرجى المحاولة مرة أخرى.';
  static const micStartListening  = 'ابدأ الاستماع';
  static const micStopListening   = 'أوقف الاستماع';

  // ── Profile ────────────────────────────────────────────────────────────────
  static const profileTitle               = 'الملف الشخصي';
  static const profileOrders              = 'طلباتي';
  static const profileSettings            = 'الإعدادات';
  static const profileLanguage            = 'اللغة';
  static const profileNotifications       = 'الإشعارات';
  static const profileDarkMode            = 'الوضع الليلي';
  static const profileChangePassword      = 'تغيير كلمة المرور';
  static const profilePrivacy             = 'الخصوصية والأمان';
  static const profileTerms               = 'الشروط والأحكام';
  static const profileAbout               = 'عن التطبيق';
  static const profileSignOut             = 'تسجيل الخروج';
  static const profileSignOutConfirmTitle = 'تسجيل الخروج';
  static const profileSignOutConfirmBody  =
      'هل أنت متأكد من رغبتك في تسجيل الخروج؟';
  static const profileGuestName           = 'مستخدم';
  static const profileGuestEmail          = 'لم يتم تسجيل الدخول';
  static const profileEditName            = 'تعديل الاسم';

  // ── Categories ─────────────────────────────────────────────────────────────
  static const catCleaning       = 'منظفات';
  static const catElectronics    = 'إلكترونيات';
  static const catFood           = 'مواد غذائية';
  static const catAccessories    = 'إكسسوارات';
  static const catHomeTools      = 'أدوات منزلية';

  // ── Errors ─────────────────────────────────────────────────────────────────
  static const errorUnknown      = 'حدث خطأ غير متوقع. حاول مجدداً.';
  static const errorNetwork      = 'تحقق من اتصالك بالإنترنت.';
  static const errorPageNotFound = 'الصفحة غير موجودة';
  static const errorReturnHome   = 'العودة للرئيسية';
}

import 'package:firebase_auth/firebase_auth.dart';
import 'package:google_sign_in/google_sign_in.dart';

// ══════════════════════════════════════════════════════════════════════════════
// AuthService — الخدمة الوحيدة للمصادقة في التطبيق
// ══════════════════════════════════════════════════════════════════════════════

class AuthService {
  AuthService._();
  static final AuthService instance = AuthService._();

  final FirebaseAuth _auth = FirebaseAuth.instance;

  // ── Getters ────────────────────────────────────────────────────────────────
  User?          get currentUser      => _auth.currentUser;
  Stream<User?>  get authStateChanges => _auth.authStateChanges();
  bool           get isLoggedIn       => _auth.currentUser != null;

  // ── تسجيل الدخول بالبريد وكلمة المرور ────────────────────────────────────
  Future<UserCredential> signInWithEmail({
    required String email,
    required String password,
  }) async {
    return _auth.signInWithEmailAndPassword(
      email: email.trim(),
      password: password,
    );
  }

  // ── إنشاء حساب جديد (alias يطابق اسم الدالة المطلوب) ─────────────────────
  Future<UserCredential> signUpWithEmail({
    required String email,
    required String password,
    String displayName = '',
  }) async {
    final credential = await _auth.createUserWithEmailAndPassword(
      email: email.trim(),
      password: password,
    );
    if (displayName.trim().isNotEmpty) {
      await credential.user?.updateDisplayName(displayName.trim());
    }
    return credential;
  }

  /// نفس [signUpWithEmail] مع اسم `registerWithEmail` للتوافق مع الكود القديم.
  Future<UserCredential> registerWithEmail({
    required String email,
    required String password,
    required String displayName,
  }) => signUpWithEmail(
        email: email,
        password: password,
        displayName: displayName,
      );

  // ── تسجيل الدخول عبر Google (google_sign_in 7.x) ─────────────────────────
  /// يُعيد [UserCredential] عند النجاح، أو `null` إذا أغلق المستخدم النافذة.
  Future<UserCredential?> signInWithGoogle() async {
    try {
      // google_sign_in 7.x — authenticate() يُعيد GoogleSignInAccount مباشرة
      final googleUser = await GoogleSignIn.instance.authenticate();

      // authentication هي Future — يجب استخدام await
      final googleAuth = await googleUser.authentication;

      // في google_sign_in 7.x على Android/iOS يكفي idToken لـ Firebase
      final credential = GoogleAuthProvider.credential(
        idToken: googleAuth.idToken,
      );
      return _auth.signInWithCredential(credential);
    } on Exception {
      // المستخدم أغلق النافذة أو حدث خطأ في Google
      return null;
    }
  }

  // ── إعادة تعيين كلمة المرور ───────────────────────────────────────────────
  Future<void> sendPasswordResetEmail(String email) async {
    await _auth.sendPasswordResetEmail(email: email.trim());
  }

  // ── تحديث الاسم الظاهر ────────────────────────────────────────────────────
  Future<void> updateDisplayName(String name) async {
    await _auth.currentUser?.updateDisplayName(name.trim());
    await _auth.currentUser?.reload();
  }

  // ── تغيير كلمة المرور ─────────────────────────────────────────────────────
  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    final user = _auth.currentUser;
    if (user == null || user.email == null) {
      throw FirebaseAuthException(
        code: 'no-current-user',
        message: 'لا يوجد مستخدم مسجّل حالياً.',
      );
    }
    // إعادة المصادقة قبل تغيير كلمة المرور
    final cred = EmailAuthProvider.credential(
      email: user.email!,
      password: currentPassword,
    );
    await user.reauthenticateWithCredential(cred);
    await user.updatePassword(newPassword);
  }

  // ── تسجيل الخروج ──────────────────────────────────────────────────────────
  Future<void> signOut() async {
    await _auth.signOut();
    try {
      await GoogleSignIn.instance.signOut();
    } catch (_) {
      // تجاهل — المستخدم لم يكن مسجلاً عبر Google
    }
  }

  // ══════════════════════════════════════════════════════════════════════════
  // ترجمة رسائل FirebaseAuthException إلى العربية
  // ══════════════════════════════════════════════════════════════════════════
  static String arabicError(FirebaseAuthException e) {
    switch (e.code) {
      case 'user-not-found':
        return 'البريد الإلكتروني غير مسجّل.';
      case 'wrong-password':
        return 'كلمة المرور غير صحيحة.';
      case 'invalid-credential':
        return 'البريد أو كلمة المرور غير صحيحة.';
      case 'email-already-in-use':
        return 'البريد الإلكتروني مُستخدم بالفعل.';
      case 'weak-password':
        return 'كلمة المرور ضعيفة — استخدم 6 أحرف على الأقل.';
      case 'invalid-email':
        return 'صيغة البريد الإلكتروني غير صحيحة.';
      case 'network-request-failed':
        return 'تحقق من اتصالك بالإنترنت.';
      case 'too-many-requests':
        return 'محاولات كثيرة — حاول مجدداً بعد قليل.';
      case 'requires-recent-login':
        return 'يجب تسجيل الدخول مجدداً لإجراء هذا الإجراء.';
      case 'wrong-current-password':
      case 'invalid-login-credentials':
        return 'كلمة المرور الحالية غير صحيحة.';
      case 'no-current-user':
        return 'لا يوجد مستخدم مسجّل حالياً.';
      default:
        return 'حدث خطأ: ${e.message ?? e.code}';
    }
  }
}

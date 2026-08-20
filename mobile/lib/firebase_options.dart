// ملف يُنشأ تلقائياً بواسطة: flutterfire configure
// لا تعدّله يدوياً — شغّل الأمر التالي لتوليده:
//
//   flutterfire configure
//
// تأكد من تثبيت FlutterFire CLI أولاً:
//   dart pub global activate flutterfire_cli

import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) return web;
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        throw UnsupportedError(
          'DefaultFirebaseOptions غير مدعوم لهذه المنصة.',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyACI8rDcs0idoqH66_Wl6QeQYPE6T0FNSw',
    appId: '1:496398916684:android:cf5cc1d252350d180ca8e9',
    messagingSenderId: '496398916684',
    projectId: 'raoahalkhamsa',
    storageBucket: 'raoahalkhamsa.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyBWbbxUwCKn_M1qzjGc5dw2iGJdDHLVjNQ',
    appId: '1:496398916684:ios:495692fa11f5bb750ca8e9',
    messagingSenderId: '496398916684',
    projectId: 'raoahalkhamsa',
    storageBucket: 'raoahalkhamsa.firebasestorage.app',
    iosBundleId: 'com.raoah.raoahAlkhamsa',
  );

  static const FirebaseOptions web = FirebaseOptions(
    apiKey: 'AIzaSyACI8rDcs0idoqH66_Wl6QeQYPE6T0FNSw',
    appId: '1:496398916684:web:pending',
    messagingSenderId: '496398916684',
    projectId: 'raoahalkhamsa',
    storageBucket: 'raoahalkhamsa.firebasestorage.app',
    authDomain: 'raoahalkhamsa.firebaseapp.com',
  );
}

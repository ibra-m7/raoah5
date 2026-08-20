import 'package:flutter/foundation.dart';
import 'package:flutter_tts/flutter_tts.dart';
import 'package:speech_to_text/speech_to_text.dart';
import 'package:speech_to_text/speech_recognition_result.dart';

// ── حالة خدمة الصوت ──────────────────────────────────────────────────────────
enum VoiceState {
  idle,        // استعداد
  listening,   // يستمع للمستخدم
  processing,  // يعالج الصوت
  speaking,    // يتحدث (TTS)
  error,       // خطأ
}

/// خدمة الصوت المركزية للمساعد الذكي
///
/// تُدير عمليتين:
///   - [startListening] / [stopListening]: STT — صوت المستخدم ← نص
///   - [speak] / [stop]: TTS — نص Gemini ← صوت مسموع
class VoiceService {
  final SpeechToText _stt = SpeechToText();
  final FlutterTts _tts = FlutterTts();

  VoiceState _state = VoiceState.idle;
  bool _sttInitialized = false;
  bool _ttsInitialized = false;

  // ── Callbacks ─────────────────────────────────────────────────────────────
  /// يُستدعى مع كل كلمة يتعرف عليها (نتيجة مؤقتة)
  ValueChanged<String>? onPartialResult;

  /// يُستدعى عند انتهاء الاستماع بالنتيجة النهائية
  ValueChanged<String>? onFinalResult;

  /// يُستدعى عند تغيّر حالة الخدمة
  ValueChanged<VoiceState>? onStateChanged;

  /// يُستدعى عند حدوث خطأ
  ValueChanged<String>? onError;

  /// يُستدعى عند انتهاء TTS من النطق
  VoidCallback? onSpeakComplete;

  // ── Getters ───────────────────────────────────────────────────────────────
  VoiceState get state => _state;
  bool get isListening => _state == VoiceState.listening;
  bool get isSpeaking => _state == VoiceState.speaking;
  bool get isIdle => _state == VoiceState.idle;

  // ── التهيئة ───────────────────────────────────────────────────────────────

  /// تهيئة كلتا الخدمتين — يجب استدعاؤها مرة واحدة عند بدء التطبيق
  Future<bool> initialize() async {
    final sttReady = await _initStt();
    await _initTts();
    return sttReady;
  }

  Future<bool> _initStt() async {
    try {
      _sttInitialized = await _stt.initialize(
        onError: (error) {
          debugPrint('[STT Error] ${error.errorMsg}');
          _setState(VoiceState.error);
          onError?.call(_mapSttError(error.errorMsg));
        },
        onStatus: (status) {
          debugPrint('[STT Status] $status');
          if (status == 'done' || status == 'notListening') {
            if (_state == VoiceState.listening) {
              _setState(VoiceState.idle);
            }
          }
        },
        debugLogging: false,
      );
      return _sttInitialized;
    } catch (e) {
      debugPrint('[STT Init Error] $e');
      return false;
    }
  }

  Future<void> _initTts() async {
    try {
      // ── إعدادات اللغة العربية ──────────────────────────────────────────
      await _tts.setLanguage('ar-SA');           // عربي سعودي
      await _tts.setSpeechRate(0.5);             // سرعة طبيعية ومريحة
      await _tts.setVolume(1.0);                 // أقصى مستوى صوت
      await _tts.setPitch(1.0);                  // نبرة طبيعية

      // ── اختيار أفضل صوت عربي متاح ────────────────────────────────────
      await _selectBestArabicVoice();

      // ── Callbacks ─────────────────────────────────────────────────────
      _tts.setStartHandler(() => _setState(VoiceState.speaking));
      _tts.setCompletionHandler(() {
        _setState(VoiceState.idle);
        onSpeakComplete?.call();
      });
      _tts.setErrorHandler((message) {
        debugPrint('[TTS Error] $message');
        _setState(VoiceState.error);
        onError?.call('خطأ في النطق: $message');
      });

      _ttsInitialized = true;
    } catch (e) {
      debugPrint('[TTS Init Error] $e');
    }
  }

  Future<void> _selectBestArabicVoice() async {
    try {
      final voices = await _tts.getVoices as List<dynamic>?;
      if (voices == null) return;

      // أولوية الأصوات: سعودي > مصري > عربي عام
      const preferred = ['ar-SA', 'ar-EG', 'ar-XA', 'ar'];

      for (final locale in preferred) {
        final match = voices.firstWhere(
          (v) =>
              v is Map &&
              (v['locale'] as String?)?.startsWith(locale) == true,
          orElse: () => null,
        );
        if (match != null) {
          await _tts.setVoice({
            'name': match['name'] as String,
            'locale': match['locale'] as String,
          });
          debugPrint('[TTS] اخترت الصوت: ${match['name']} (${match['locale']})');
          return;
        }
      }
    } catch (e) {
      debugPrint('[TTS Voice Selection] $e');
    }
  }

  // ── STT: الاستماع ─────────────────────────────────────────────────────────

  /// يبدأ الاستماع لصوت المستخدم بالعربية
  ///
  /// [onResult] اختصار — يُمكن استخدامه بدلاً من [onFinalResult]
  Future<bool> startListening({ValueChanged<String>? onResult}) async {
    if (onResult != null) onFinalResult = onResult;

    if (!_sttInitialized) {
      final ready = await _initStt();
      if (!ready) {
        onError?.call('جهازك لا يدعم التعرف على الكلام');
        return false;
      }
    }

    if (_state == VoiceState.speaking) await stop();
    if (_state == VoiceState.listening) return true;

    try {
      await _stt.listen(
        onResult: _onSttResult,
        localeId: 'ar-SA',
        // ignore: deprecated_member_use
        listenMode: ListenMode.dictation,
        // ignore: deprecated_member_use
        cancelOnError: true,
        // ignore: deprecated_member_use
        partialResults: true,
        listenFor: const Duration(seconds: 30),
        pauseFor: const Duration(seconds: 3),
      );
      _setState(VoiceState.listening);
      return true;
    } catch (e) {
      debugPrint('[STT Listen Error] $e');
      onError?.call('تعذّر بدء الاستماع');
      return false;
    }
  }

  /// يوقف الاستماع ويُعيد النص المُسجَّل حتى الآن
  Future<String> stopListening() async {
    if (!isListening) return '';
    await _stt.stop();
    _setState(VoiceState.idle);
    return _stt.lastRecognizedWords;
  }

  void _onSttResult(SpeechRecognitionResult result) {
    final words = result.recognizedWords.trim();
    if (words.isEmpty) return;

    if (result.finalResult) {
      _setState(VoiceState.processing);
      onFinalResult?.call(words);
    } else {
      onPartialResult?.call(words);
    }
  }

  // ── TTS: النطق ────────────────────────────────────────────────────────────

  /// يُحوّل النص إلى كلام بلهجة عربية طبيعية
  ///
  /// يُقسّم النص الطويل تلقائياً لتجنب حدود TTS
  Future<void> speak(String text) async {
    if (text.trim().isEmpty) return;
    if (!_ttsInitialized) await _initTts();

    // أوقف الاستماع إن كان جارياً
    if (isListening) await _stt.stop();

    // أوقف النطق الحالي إن وُجد
    if (isSpeaking) await _tts.stop();

    final cleanText = _cleanTextForSpeech(text);

    try {
      _setState(VoiceState.speaking);
      await _tts.speak(cleanText);
    } catch (e) {
      debugPrint('[TTS Speak Error] $e');
      _setState(VoiceState.error);
      onError?.call('تعذّر تشغيل الصوت');
    }
  }

  /// يوقف النطق الجاري
  Future<void> stop() async {
    if (isSpeaking) await _tts.stop();
    if (isListening) await _stt.stop();
    _setState(VoiceState.idle);
  }

  // ── ضبط الإعدادات ─────────────────────────────────────────────────────────

  /// تغيير سرعة النطق (0.1 بطيء جداً — 1.0 سريع)
  Future<void> setSpeechRate(double rate) =>
      _tts.setSpeechRate(rate.clamp(0.1, 1.0));

  /// تغيير مستوى الصوت (0.0 — 1.0)
  Future<void> setVolume(double volume) =>
      _tts.setVolume(volume.clamp(0.0, 1.0));

  /// التحقق من توفر التعرف على الكلام في الجهاز
  Future<bool> get isSttAvailable async {
    if (_sttInitialized) return true;
    return _initStt();
  }

  // ── تنظيف النص قبل النطق ──────────────────────────────────────────────────

  /// يُزيل الـ Markdown والإيموجي والرموز التي تُربك TTS
  static String _cleanTextForSpeech(String text) {
    return text
        .replaceAll(RegExp(r'\*{1,2}(.*?)\*{1,2}'), r'$1') // Bold/Italic
        .replaceAll(RegExp(r'#{1,6}\s'), '')                 // Headers
        .replaceAll(RegExp(r'`{1,3}[^`]*`{1,3}'), '')       // Code
        .replaceAll(RegExp(r'\[([^\]]+)\]\([^)]+\)'), r'$1')// Links
        .replaceAll(RegExp(r'[\u{1F300}-\u{1FAFF}]',        // إيموجي
            unicode: true), '')
        .replaceAll(RegExp(r'[-•*]\s'), '')                  // Bullets
        .replaceAll(RegExp(r'\n{2,}'), '. ')                 // فواصل
        .replaceAll('\n', '، ')
        .replaceAll(RegExp(r'\s{2,}'), ' ')
        .trim();
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  void _setState(VoiceState newState) {
    if (_state == newState) return;
    _state = newState;
    onStateChanged?.call(_state);
  }

  String _mapSttError(String errorMsg) {
    switch (errorMsg) {
      case 'error_permission':
        return 'لم يُمنح إذن الميكروفون';
      case 'error_network':
        return 'تعذّر الاتصال بخدمة التعرف على الكلام';
      case 'error_no_match':
        return 'لم أفهم ما قلته، حاول مجدداً';
      case 'error_speech_timeout':
        return 'انتهت مدة الاستماع';
      default:
        return 'خطأ في التعرف على الكلام: $errorMsg';
    }
  }

  /// تحرير الموارد عند إغلاق التطبيق
  Future<void> dispose() async {
    await _stt.stop();
    await _tts.stop();
  }
}

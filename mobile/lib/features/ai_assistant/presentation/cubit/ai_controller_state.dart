part of 'ai_controller_cubit.dart';

// ── حالات الإدخال الصوتي ──────────────────────────────────────────────────────
enum InputMode { text, voice }

// ── حالة معالجة الذكاء الاصطناعي ─────────────────────────────────────────────
enum AiProcessingStatus {
  idle,       // استعداد لاستقبال مدخلات
  listening,  // STT يستمع
  thinking,   // Gemini يعالج
  speaking,   // TTS ينطق
  error,      // خطأ
}

class AiControllerState extends Equatable {
  // ── المحادثة ──────────────────────────────────────────────────────────────
  final List<ChatMessage> messages;
  final AiProcessingStatus status;
  final InputMode inputMode;
  final int? conversationId;
  final String assistantName;

  // ── الصوت ─────────────────────────────────────────────────────────────────
  final String partialSpeechText;

  // ── المنتج الأخير ─────────────────────────────────────────────────────────
  final ProductModel? lastMentionedProduct;

  // ── التنقل ────────────────────────────────────────────────────────────────
  /// true عندما يُصدر المساعد أمر "الانتقال لصفحة الدفع"
  /// يُمسح بعد تنفيذ التنقل
  final bool checkoutRequested;

  // ── الخطأ ─────────────────────────────────────────────────────────────────
  final String? errorMessage;

  const AiControllerState({
    this.messages = const [],
    this.status = AiProcessingStatus.idle,
    this.inputMode = InputMode.voice,
    this.conversationId,
    this.assistantName = 'روعة',
    this.partialSpeechText = '',
    this.lastMentionedProduct,
    this.checkoutRequested = false,
    this.errorMessage,
  });

  // ── Computed ──────────────────────────────────────────────────────────────
  bool get isListening => status == AiProcessingStatus.listening;
  bool get isThinking  => status == AiProcessingStatus.thinking;
  bool get isSpeaking  => status == AiProcessingStatus.speaking;
  bool get isIdle      => status == AiProcessingStatus.idle;

  // ── copyWith ──────────────────────────────────────────────────────────────

  AiControllerState copyWith({
    List<ChatMessage>? messages,
    AiProcessingStatus? status,
    InputMode? inputMode,
    int? conversationId,
    bool clearConversationId = false,
    String? assistantName,
    String? partialSpeechText,
    ProductModel? lastMentionedProduct,
    bool clearLastProduct = false,
    bool? checkoutRequested,
    String? errorMessage,
    bool clearError = false,
  }) {
    return AiControllerState(
      messages: messages ?? this.messages,
      status: status ?? this.status,
      inputMode: inputMode ?? this.inputMode,
      conversationId:
          clearConversationId ? null : (conversationId ?? this.conversationId),
      assistantName: assistantName ?? this.assistantName,
      partialSpeechText: partialSpeechText ?? this.partialSpeechText,
      lastMentionedProduct: clearLastProduct
          ? null
          : (lastMentionedProduct ?? this.lastMentionedProduct),
      checkoutRequested: checkoutRequested ?? this.checkoutRequested,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
    );
  }

  @override
  List<Object?> get props => [
        messages,
        status,
        inputMode,
        conversationId,
        assistantName,
        partialSpeechText,
        lastMentionedProduct,
        checkoutRequested,
        errorMessage,
      ];
}

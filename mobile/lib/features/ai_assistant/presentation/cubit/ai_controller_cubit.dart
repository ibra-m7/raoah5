import 'package:equatable/equatable.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/error/exceptions.dart';
import '../../../../core/network/api_exception.dart';
import '../../data/services/ai_chat_api.dart';
import '../../data/services/voice_service.dart';
import '../../data/models/chat_message_model.dart';
import '../../domain/entities/chat_message.dart';
import '../../../shop/data/models/product_model.dart';
import '../../../shop/presentation/manager/cart_cubit.dart';

part 'ai_controller_state.dart';

/// المتحكم المركزي للمساعد الذكي
///
/// يُنسّق بين:
///   [VoiceService]  ← STT / TTS على الجهاز
///   [AiChatApi]     ← الردود والمنتجات من باك اند المتجر
///   [CartCubit]     ← السلة
class AiControllerCubit extends Cubit<AiControllerState> {
  final AiChatApi _aiChatApi;
  final VoiceService _voiceService;
  final CartCubit _cartCubit;

  AiControllerCubit({
    required AiChatApi aiChatApi,
    required VoiceService voiceService,
    required CartCubit cartCubit,
  })  : _aiChatApi = aiChatApi,
        _voiceService = voiceService,
        _cartCubit = cartCubit,
        super(const AiControllerState()) {
    _setupCallbacks();
  }

  void _setupCallbacks() {
    _voiceService.onPartialResult = (text) {
      if (isClosed) return;
      emit(state.copyWith(partialSpeechText: text));
    };

    _voiceService.onFinalResult = (text) {
      if (isClosed) return;
      emit(state.copyWith(partialSpeechText: ''));
      _handleUserInput(text);
    };

    _voiceService.onStateChanged = (voiceState) {
      if (isClosed) return;
      switch (voiceState) {
        case VoiceState.listening:
          emit(state.copyWith(status: AiProcessingStatus.listening));
        case VoiceState.speaking:
          emit(state.copyWith(status: AiProcessingStatus.speaking));
        case VoiceState.idle:
        case VoiceState.processing:
          if (state.status != AiProcessingStatus.thinking) {
            emit(state.copyWith(status: AiProcessingStatus.idle));
          }
        case VoiceState.error:
          break;
      }
    };

    _voiceService.onError = (message) {
      if (isClosed) return;
      emit(state.copyWith(
        status: AiProcessingStatus.idle,
        errorMessage: message,
      ));
    };

    _voiceService.onSpeakComplete = () {
      if (isClosed) return;
      emit(state.copyWith(status: AiProcessingStatus.idle));
    };
  }

  Future<void> initConversation() async {
    if (state.messages.isNotEmpty) return;
    if (state.isThinking) return;
    emit(state.copyWith(status: AiProcessingStatus.thinking, clearError: true));
    try {
      final config = await _aiChatApi.config();
      final welcomeText = config.enabled
          ? config.welcome
          : 'المساعد الذكي متوقف مؤقتاً. يمكنك تصفح المتجر كالمعتاد.';
      final welcome = ChatMessageModel.fromGeminiResponse(welcomeText);
      emit(state.copyWith(
        messages: [welcome],
        assistantName: config.name,
        status: AiProcessingStatus.idle,
      ));
      if (config.enabled) {
        await _voiceService.speak(welcome.content);
      }
    } catch (e) {
      debugPrint('[AiController] initConversation error: $e');
      final offlineWelcome = ChatMessageModel.fromGeminiResponse(
        'أهلاً بك في روعة الخمسة! كيف يمكنني مساعدتك اليوم؟',
      );
      emit(state.copyWith(
        messages: [offlineWelcome],
        status: AiProcessingStatus.idle,
      ));
      await _voiceService.speak(offlineWelcome.content);
    }
  }

  Future<void> startVoiceInput() async {
    if (state.isListening) return;
    if (state.isSpeaking) await _voiceService.stop();

    emit(state.copyWith(partialSpeechText: '', clearError: true));

    final started = await _voiceService.startListening();
    if (!started) {
      emit(state.copyWith(
        errorMessage: 'تعذّر الوصول للميكروفون. تحقق من الصلاحيات.',
      ));
    }
  }

  Future<void> stopVoiceInput() async {
    if (!state.isListening) return;
    final text = await _voiceService.stopListening();
    if (text.trim().isNotEmpty) {
      _handleUserInput(text);
    } else {
      emit(state.copyWith(status: AiProcessingStatus.idle));
    }
  }

  Future<void> sendTextMessage(String text) async {
    final trimmed = text.trim();
    if (trimmed.isEmpty) return;
    await _handleUserInput(trimmed);
  }

  Future<void> _handleUserInput(String userText) async {
    if (state.isThinking) return;

    if (_isCheckoutIntent(userText)) {
      await _handleCheckoutIntent();
      return;
    }

    if (_isCartIntent(userText) && state.lastMentionedProduct != null) {
      await _handleCartIntent();
      return;
    }

    final userMsg = ChatMessageModel.userMessage(userText);
    final updatedMessages = [...state.messages, userMsg];

    emit(state.copyWith(
      messages: updatedMessages,
      status: AiProcessingStatus.thinking,
      partialSpeechText: '',
      clearError: true,
    ));

    try {
      final result = await _aiChatApi.chat(
        message: userText,
        conversationId: state.conversationId,
      );
      final response = ChatMessageModel.fromGeminiResponse(
        result.reply,
        products: result.products,
      );

      emit(state.copyWith(
        messages: [...updatedMessages, response],
        conversationId: result.conversationId > 0
            ? result.conversationId
            : state.conversationId,
        lastMentionedProduct: result.products.isNotEmpty
            ? result.products.last
            : state.lastMentionedProduct,
        status: AiProcessingStatus.idle,
      ));

      await _voiceService.speak(response.content);
    } catch (e) {
      debugPrint('[AiController] processUserQuery error: $e');
      emit(state.copyWith(
        messages: updatedMessages,
        status: AiProcessingStatus.error,
        errorMessage: _mapError(e),
      ));
    }
  }

  Future<void> addToCart(ProductModel product) async {
    _cartCubit.addToCart(product);
    await _suggestComplement(product);
  }

  void removeFromCart(String productId) {
    _cartCubit.removeFromCart(productId);
  }

  void clearCart() {
    _cartCubit.clearCart();
  }

  Future<void> _handleCartIntent() async {
    final product = state.lastMentionedProduct;
    if (product == null) return;
    _cartCubit.addToCart(product);
    await _suggestComplement(product);
  }

  Future<void> _suggestComplement(ProductModel addedProduct) async {
    if (isClosed || state.isThinking) return;

    emit(state.copyWith(status: AiProcessingStatus.thinking));

    try {
      final result = await _aiChatApi.chat(
        message:
            'أضفت «${addedProduct.name}» للسلة. أكّد الإضافة واقترح منتجات مكملة.',
        conversationId: state.conversationId,
        intent: 'complement',
        productId: addedProduct.id,
      );
      if (isClosed) return;

      final suggestion = ChatMessageModel.fromGeminiResponse(
        result.reply,
        products: result.products,
      );

      emit(state.copyWith(
        messages: [...state.messages, suggestion],
        conversationId: result.conversationId > 0
            ? result.conversationId
            : state.conversationId,
        lastMentionedProduct: result.products.isNotEmpty
            ? result.products.first
            : addedProduct,
        status: AiProcessingStatus.idle,
      ));

      await _voiceService.speak(suggestion.content);
    } catch (_) {
      if (!isClosed) emit(state.copyWith(status: AiProcessingStatus.idle));
    }
  }

  Future<void> _handleCheckoutIntent() async {
    if (_cartCubit.state.isEmpty) {
      final msg = 'السلة فارغة! أضف منتجات أولاً وسأساعدك في إتمام الطلب.';
      final reply = ChatMessageModel.fromGeminiResponse(msg);
      emit(state.copyWith(messages: [...state.messages, reply]));
      await _voiceService.speak(msg);
      return;
    }

    final count = _cartCubit.state.count;
    final total = _cartCubit.state.total;
    final ttsText =
        'لديك $count قطعة بإجمالي ${total.toStringAsFixed(0)} \u{20C1}. سأنقلك لصفحة إتمام الطلب الآن.';
    final reply = ChatMessageModel.fromGeminiResponse(ttsText);

    emit(state.copyWith(
      messages: [...state.messages, reply],
      checkoutRequested: true,
    ));
    await _voiceService.speak(ttsText);
  }

  void clearCheckoutRequest() =>
      emit(state.copyWith(checkoutRequested: false));

  void clearConversation() {
    emit(const AiControllerState());
  }

  void dismissError() => emit(state.copyWith(clearError: true));

  void switchInputMode(InputMode mode) =>
      emit(state.copyWith(inputMode: mode));

  static const _checkoutKeywords = [
    'أريد إنهاء الطلب',
    'إنهاء الطلب',
    'خلاص اشتريت',
    'أريد الدفع',
    'ادفع',
    'تأكيد الطلب',
    'إتمام الطلب',
    'أتمام الشراء',
    'أنهِ الطلب',
    'روعة للدفع',
    'checkout',
    'pay now',
  ];

  static bool _isCheckoutIntent(String text) {
    final lower = text.toLowerCase();
    return _checkoutKeywords.any((kw) => lower.contains(kw));
  }

  static const _addToCartKeywords = [
    'أضف للسلة',
    'أضيفه للسلة',
    'ضعه في السلة',
    'أضف هذا للسلة',
    'أريد شراء هذا',
    'أريد أشتريه',
    'سأشتريه',
    'أشتريه',
    'اشتره',
    'أريده',
    'خذه',
    'احجزه',
    'ضيفه',
    'أضفه',
    'للسلة',
    'add to cart',
  ];

  static bool _isCartIntent(String text) {
    final lower = text.toLowerCase();
    return _addToCartKeywords.any((kw) => lower.contains(kw));
  }

  String _mapError(Object e) {
    if (e is ApiException) return e.message;
    if (e is NetworkException) return e.message;
    if (e is ServerException) return e.message;

    final raw = e.toString();
    debugPrint('[AiController] _mapError raw: $raw');

    if (raw.contains('network') ||
        raw.contains('socket') ||
        raw.contains('SocketException') ||
        raw.contains('Failed host lookup')) {
      return 'لا يوجد اتصال بالإنترنت';
    }
    if (raw.contains('timeout') || raw.contains('TimeoutException')) {
      return 'انتهت مهلة الاتصال، حاول مجدداً';
    }
    return 'تعذّر الرد الآن. حاول مجدداً';
  }

  @override
  Future<void> close() async {
    await _voiceService.stop();
    return super.close();
  }
}

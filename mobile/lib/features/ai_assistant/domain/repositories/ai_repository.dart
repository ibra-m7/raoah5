import 'package:dartz/dartz.dart';
import '../../../../core/error/failures.dart';
import '../entities/chat_message.dart';

abstract class AiRepository {
  /// يرسل رسالة إلى Gemini ويعيد رد المساعد
  Future<Either<Failure, ChatMessage>> sendMessage({
    required String userMessage,
    required List<ChatMessage> conversationHistory,
  });

  /// يعيد تحية افتراحية عند فتح المحادثة
  Future<Either<Failure, ChatMessage>> getWelcomeMessage();

  /// إعادة تعيين جلسة المحادثة
  void resetSession();
}

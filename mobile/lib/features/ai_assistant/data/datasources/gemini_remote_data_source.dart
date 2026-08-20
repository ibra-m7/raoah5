import '../models/chat_message_model.dart';
import '../services/ai_service.dart';

abstract class GeminiRemoteDataSource {
  Future<ChatMessageModel> sendMessage({
    required String userMessage,
    required List<ChatMessageModel> history,
  });

  Future<ChatMessageModel> getWelcomeMessage();

  void resetSession();
}

class GeminiRemoteDataSourceImpl implements GeminiRemoteDataSource {
  final AiService aiService;

  GeminiRemoteDataSourceImpl({required this.aiService});

  @override
  Future<ChatMessageModel> sendMessage({
    required String userMessage,
    required List<ChatMessageModel> history,
  }) async {
    return aiService.processUserQuery(userMessage, history: history);
  }

  @override
  Future<ChatMessageModel> getWelcomeMessage() async {
    return aiService.getWelcomeMessage();
  }

  @override
  void resetSession() => aiService.resetSession();
}

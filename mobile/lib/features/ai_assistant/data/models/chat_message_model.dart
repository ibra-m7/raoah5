import '../../../shop/data/models/product_model.dart';
import '../../domain/entities/chat_message.dart';

class ChatMessageModel extends ChatMessage {
  const ChatMessageModel({
    required super.id,
    required super.content,
    required super.role,
    required super.timestamp,
    super.isLoading,
    super.suggestedProducts,
  });

  factory ChatMessageModel.fromJson(Map<String, dynamic> json) {
    return ChatMessageModel(
      id: json['id'] as String,
      content: json['content'] as String,
      role: json['role'] == 'user' ? MessageRole.user : MessageRole.assistant,
      timestamp: DateTime.parse(json['timestamp'] as String),
    );
  }

  /// تحويل من صيغة Gemini API
  factory ChatMessageModel.fromGeminiResponse(
    String responseText, {
    List<ProductModel> products = const [],
  }) {
    return ChatMessageModel(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      content: responseText,
      role: MessageRole.assistant,
      timestamp: DateTime.now(),
      suggestedProducts: products,
    );
  }

  factory ChatMessageModel.userMessage(String text) {
    return ChatMessageModel(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      content: text,
      role: MessageRole.user,
      timestamp: DateTime.now(),
    );
  }

  factory ChatMessageModel.loadingPlaceholder() {
    return ChatMessageModel(
      id: 'loading',
      content: '',
      role: MessageRole.assistant,
      timestamp: DateTime.now(),
      isLoading: true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'content': content,
      'role': role == MessageRole.user ? 'user' : 'assistant',
      'timestamp': timestamp.toIso8601String(),
    };
  }

  /// تحويل إلى صيغة Gemini API لإرسال سياق المحادثة
  Map<String, dynamic> toGeminiContent() {
    return {
      'role': role == MessageRole.user ? 'user' : 'model',
      'parts': [
        {'text': content}
      ],
    };
  }
}

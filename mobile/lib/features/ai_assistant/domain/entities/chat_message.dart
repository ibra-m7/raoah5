import 'package:equatable/equatable.dart';

import '../../../shop/data/models/product_model.dart';

enum MessageRole { user, assistant }

class ChatMessage extends Equatable {
  final String id;
  final String content;
  final MessageRole role;
  final DateTime timestamp;
  final bool isLoading;
  final List<ProductModel> suggestedProducts;

  const ChatMessage({
    required this.id,
    required this.content,
    required this.role,
    required this.timestamp,
    this.isLoading = false,
    this.suggestedProducts = const [],
  });

  bool get isUser => role == MessageRole.user;
  bool get isAssistant => role == MessageRole.assistant;

  @override
  List<Object> get props =>
      [id, content, role, timestamp, isLoading, suggestedProducts];
}

part of 'ai_assistant_bloc.dart';

abstract class AiAssistantState extends Equatable {
  const AiAssistantState();

  @override
  List<Object> get props => [];
}

class AiAssistantInitial extends AiAssistantState {}

class AiAssistantLoaded extends AiAssistantState {
  final List<ChatMessage> messages;
  final bool isTyping;

  const AiAssistantLoaded({
    required this.messages,
    this.isTyping = false,
  });

  AiAssistantLoaded copyWith({
    List<ChatMessage>? messages,
    bool? isTyping,
  }) {
    return AiAssistantLoaded(
      messages: messages ?? this.messages,
      isTyping: isTyping ?? this.isTyping,
    );
  }

  @override
  List<Object> get props => [messages, isTyping];
}

class AiAssistantError extends AiAssistantState {
  final String message;

  const AiAssistantError({required this.message});

  @override
  List<Object> get props => [message];
}

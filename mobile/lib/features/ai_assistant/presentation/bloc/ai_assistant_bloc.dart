import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../domain/entities/chat_message.dart';
import '../../domain/usecases/send_message.dart';

part 'ai_assistant_event.dart';
part 'ai_assistant_state.dart';

class AiAssistantBloc extends Bloc<AiAssistantEvent, AiAssistantState> {
  final SendMessage sendMessage;

  AiAssistantBloc({required this.sendMessage}) : super(AiAssistantInitial()) {
    on<InitializeAssistantEvent>(_onInitialize);
    on<SendUserMessageEvent>(_onSendMessage);
    on<ClearConversationEvent>(_onClearConversation);
  }

  Future<void> _onInitialize(
    InitializeAssistantEvent event,
    Emitter<AiAssistantState> emit,
  ) async {
    emit(const AiAssistantLoaded(messages: []));
  }

  Future<void> _onSendMessage(
    SendUserMessageEvent event,
    Emitter<AiAssistantState> emit,
  ) async {
    final currentState = state;
    if (currentState is! AiAssistantLoaded) return;

    final userMessage = ChatMessage(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      content: event.message,
      role: MessageRole.user,
      timestamp: DateTime.now(),
    );

    final updatedMessages = [...currentState.messages, userMessage];
    emit(currentState.copyWith(messages: updatedMessages, isTyping: true));

    final result = await sendMessage(
      SendMessageParams(
        userMessage: event.message,
        conversationHistory: currentState.messages,
      ),
    );

    result.fold(
      (failure) => emit(
        AiAssistantLoaded(
          messages: updatedMessages,
          isTyping: false,
        ),
      ),
      (assistantMessage) => emit(
        AiAssistantLoaded(
          messages: [...updatedMessages, assistantMessage],
          isTyping: false,
        ),
      ),
    );
  }

  void _onClearConversation(
    ClearConversationEvent event,
    Emitter<AiAssistantState> emit,
  ) {
    emit(const AiAssistantLoaded(messages: []));
  }
}

part of 'ai_assistant_bloc.dart';

abstract class AiAssistantEvent extends Equatable {
  const AiAssistantEvent();

  @override
  List<Object> get props => [];
}

class InitializeAssistantEvent extends AiAssistantEvent {}

class SendUserMessageEvent extends AiAssistantEvent {
  final String message;

  const SendUserMessageEvent({required this.message});

  @override
  List<Object> get props => [message];
}

class ClearConversationEvent extends AiAssistantEvent {}

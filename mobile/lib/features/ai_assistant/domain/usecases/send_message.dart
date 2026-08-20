import 'package:dartz/dartz.dart';
import 'package:equatable/equatable.dart';
import '../../../../core/error/failures.dart';
import '../../../../core/usecases/usecase.dart';
import '../entities/chat_message.dart';
import '../repositories/ai_repository.dart';

class SendMessage implements UseCase<ChatMessage, SendMessageParams> {
  final AiRepository repository;

  SendMessage(this.repository);

  @override
  Future<Either<Failure, ChatMessage>> call(SendMessageParams params) async {
    return await repository.sendMessage(
      userMessage: params.userMessage,
      conversationHistory: params.conversationHistory,
    );
  }
}

class SendMessageParams extends Equatable {
  final String userMessage;
  final List<ChatMessage> conversationHistory;

  const SendMessageParams({
    required this.userMessage,
    required this.conversationHistory,
  });

  @override
  List<Object> get props => [userMessage, conversationHistory];
}

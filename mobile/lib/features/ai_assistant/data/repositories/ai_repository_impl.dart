import 'package:dartz/dartz.dart';
import '../../../../core/error/exceptions.dart';
import '../../../../core/error/failures.dart';
import '../../../../core/network/network_info.dart';
import '../../domain/entities/chat_message.dart';
import '../../domain/repositories/ai_repository.dart';
import '../datasources/gemini_remote_data_source.dart';
import '../models/chat_message_model.dart';

class AiRepositoryImpl implements AiRepository {
  final GeminiRemoteDataSource remoteDataSource;
  final NetworkInfo networkInfo;

  AiRepositoryImpl({
    required this.remoteDataSource,
    required this.networkInfo,
  });

  @override
  Future<Either<Failure, ChatMessage>> sendMessage({
    required String userMessage,
    required List<ChatMessage> conversationHistory,
  }) async {
    if (!await networkInfo.isConnected) {
      return const Left(NetworkFailure());
    }
    try {
      final historyModels = conversationHistory
          .map(
            (m) => ChatMessageModel(
              id: m.id,
              content: m.content,
              role: m.role,
              timestamp: m.timestamp,
            ),
          )
          .toList();

      final response = await remoteDataSource.sendMessage(
        userMessage: userMessage,
        history: historyModels,
      );
      return Right(response);
    } on GeminiException catch (e) {
      return Left(GeminiFailure(message: e.message));
    } on ServerException catch (e) {
      return Left(ServerFailure(message: e.message));
    }
  }

  @override
  Future<Either<Failure, ChatMessage>> getWelcomeMessage() async {
    try {
      final message = await remoteDataSource.getWelcomeMessage();
      return Right(message);
    } on GeminiException catch (e) {
      return Left(GeminiFailure(message: e.message));
    }
  }

  @override
  void resetSession() => remoteDataSource.resetSession();
}

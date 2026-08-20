import 'package:equatable/equatable.dart';

abstract class Failure extends Equatable {
  final String message;
  const Failure({required this.message});

  @override
  List<Object> get props => [message];
}

class ServerFailure extends Failure {
  const ServerFailure({super.message = 'فشل الاتصال بالخادم'});
}

class CacheFailure extends Failure {
  const CacheFailure({super.message = 'فشل التخزين المحلي'});
}

class NetworkFailure extends Failure {
  const NetworkFailure({super.message = 'لا يوجد اتصال بالإنترنت'});
}

class GeminiFailure extends Failure {
  const GeminiFailure({super.message = 'فشل الاتصال بخدمة الذكاء الاصطناعي'});
}

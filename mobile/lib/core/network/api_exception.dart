class ApiException implements Exception {
  final String message;
  final int statusCode;
  final String? debugCode;

  const ApiException(
    this.message, {
    this.statusCode = 0,
    this.debugCode,
  });

  @override
  String toString() => message;
}

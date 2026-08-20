import 'package:dartz/dartz.dart';
import 'package:equatable/equatable.dart';
import '../../../../core/error/failures.dart';
import '../../../../core/usecases/usecase.dart';
import '../entities/product.dart';
import '../repositories/shop_repository.dart';

class GetProducts implements UseCase<List<Product>, GetProductsParams> {
  final ShopRepository repository;

  GetProducts(this.repository);

  @override
  Future<Either<Failure, List<Product>>> call(
    GetProductsParams params,
  ) async {
    return await repository.getProducts(
      page: params.page,
      pageSize: params.pageSize,
    );
  }
}

class GetProductsParams extends Equatable {
  final int page;
  final int pageSize;

  const GetProductsParams({
    this.page = 1,
    this.pageSize = 20,
  });

  @override
  List<Object> get props => [page, pageSize];
}

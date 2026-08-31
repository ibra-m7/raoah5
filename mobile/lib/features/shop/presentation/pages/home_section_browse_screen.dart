import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../data/models/home_feed.dart';
import '../../data/models/product_model.dart';
import '../manager/catalog_cubit.dart';
import '../widgets/product_browse_sheet_screen.dart';

class HomeSectionBrowseArgs {
  final String sectionKey;
  final HomeSectionModel? initial;

  const HomeSectionBrowseArgs({
    required this.sectionKey,
    this.initial,
  });
}

class HomeSectionBrowseScreen extends StatelessWidget {
  static const routeName = '/home-section-browse';

  final HomeSectionBrowseArgs args;

  const HomeSectionBrowseScreen({
    super.key,
    required this.args,
  });

  static String _headerImageFor(List<ProductModel> products) {
    for (final product in products) {
      final url = product.displayImage.trim();
      if (url.isNotEmpty) return url;
    }
    return '';
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<CatalogCubit, CatalogState>(
      builder: (context, catalog) {
        final section =
            args.initial ?? catalog.sectionByKey(args.sectionKey);
        final products = section?.products ?? const <ProductModel>[];
        final key = section?.key ?? args.sectionKey;

        return ProductBrowseSheetScreen(
          headerImageUrl: _headerImageFor(products),
          products: products,
          heroTagPrefix: 'section_$key',
          loading: catalog.loading && products.isEmpty,
        );
      },
    );
  }
}

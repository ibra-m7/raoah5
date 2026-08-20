import 'package:flutter/material.dart';

class ShopPage extends StatelessWidget {
  static const routeName = '/shop';

  const ShopPage({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Text('صفحة المتجر'),
      ),
    );
  }
}

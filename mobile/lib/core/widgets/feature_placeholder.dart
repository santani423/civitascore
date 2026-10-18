import 'package:flutter/material.dart';

/// TEMPORARY scaffold used only while a feature module is being built in
/// parallel. Every usage must be replaced before release — the integration
/// check greps for `FeaturePlaceholder(`.
class FeaturePlaceholder extends StatelessWidget {
  const FeaturePlaceholder({super.key, required this.title});
  final String title;

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(title)),
    body: Center(child: Text(title)),
  );
}

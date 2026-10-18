import 'package:flutter/services.dart';
import 'package:flutter/widgets.dart';

const _channel = MethodChannel('civitasone/secure_screen');

/// Blocks screenshots/screen recording (Android FLAG_SECURE) while [child]
/// is mounted. Used for CBT exam taking and other sensitive screens.
/// On unsupported platforms (tests, future iOS) it is a no-op.
class SecureScreen extends StatefulWidget {
  const SecureScreen({super.key, required this.child});
  final Widget child;

  @override
  State<SecureScreen> createState() => _SecureScreenState();
}

class _SecureScreenState extends State<SecureScreen> {
  @override
  void initState() {
    super.initState();
    _invoke('enable');
  }

  @override
  void dispose() {
    _invoke('disable');
    super.dispose();
  }

  static Future<void> _invoke(String method) async {
    try {
      await _channel.invokeMethod<void>(method);
    } on MissingPluginException {
      // Not available (widget tests / other platforms).
    } on PlatformException {
      // Ignore: protection is best-effort.
    }
  }

  @override
  Widget build(BuildContext context) => widget.child;
}

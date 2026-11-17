import 'package:flutter/widgets.dart';
import 'package:package_info_plus/package_info_plus.dart';

/// The installed build's version, read from the platform so it always matches
/// what pubspec's `version:` stamped into the binary — never hardcode it.
/// Resolved once per launch; '' where the plugin isn't registered (widget
/// tests) rather than throwing.
///
/// Version name only: without a `+N` in pubspec, iOS reports the version name
/// again as the build number (`v1.2.0`) and Android reports `1`.
final Future<String> _appVersion =
    PackageInfo.fromPlatform().then((i) => 'v${i.version}').onError((_, _) => '');

/// `v1.2.0`, with an optional [prefix] (`QLOUD POS · v1.2.0`).
class AppVersionText extends StatelessWidget {
  const AppVersionText({super.key, required this.style, this.prefix = ''});

  final TextStyle style;
  final String prefix;

  @override
  Widget build(BuildContext context) => FutureBuilder<String>(
        future: _appVersion,
        // An empty Text still takes its line height, so nothing shifts when the
        // version arrives a frame later.
        builder: (_, snap) {
          final v = snap.data ?? '';
          return Text(v.isEmpty ? '' : '$prefix$v', style: style);
        },
      );
}

import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

/// The two rules `project_architecture_skeleton.md` §3 states and nothing
/// enforces: a feature does not reach into another feature, and `shared/` does
/// not reach up into a feature at all.
///
/// Both were broken before this test existed — six imports across `product` and
/// `search` pulling the catalog's cubits, and two shared chrome widgets doing
/// the same — and neither would have shown up in `flutter analyze`, which is
/// happy with any import that resolves. Nothing about the funnel cubit's old
/// home read as wrong at the file you were editing; it only reads as wrong from
/// above, which is where this looks from.
void main() {
  /// Every `.dart` under [dir], with the import paths it names.
  Map<String, List<String>> sourcesUnder(String dir) {
    final out = <String, List<String>>{};
    for (final entity in Directory(dir).listSync(recursive: true)) {
      if (entity is! File || !entity.path.endsWith('.dart')) continue;
      out[entity.path] = RegExp(r"""^import\s+'([^']+)'""", multiLine: true)
          .allMatches(entity.readAsStringSync())
          .map((m) => m.group(1)!)
          .toList(growable: false);
    }
    return out;
  }

  /// What a relative import resolves to, as a path from the package root.
  String resolve(String from, String import) =>
      File('${File(from).parent.path}/$import').uri.normalizePath().path;

  test('no feature imports another feature', () {
    final offences = <String>[];
    sourcesUnder('lib/features').forEach((file, imports) {
      final owner = file.split('/')[2];
      for (final import in imports) {
        if (import.startsWith('package:') || import.startsWith('dart:')) continue;
        final target = resolve(file, import);
        final match = RegExp(r'lib/features/([a-z_]+)/').firstMatch(target);
        if (match != null && match.group(1) != owner) {
          offences.add('$file → ${match.group(1)}  ($import)');
        }
      }
    });
    expect(offences, isEmpty,
        reason: 'shared types and logic belong in lib/shared/ — see §3');
  });

  test('shared reaches up into a feature only from the composition roots', () {
    // The router has to name the screens it routes to, and the service locator
    // has to name the implementations it registers. Both are where the app is
    // wired together, so both are allowed to know what is in it — the same two
    // exceptions the sibling app makes.
    const composition = {
      'lib/shared/utils/router/app_router.dart',
      'lib/shared/utils/service_locator_setup/setup.dart',
    };
    final offences = <String>[];
    sourcesUnder('lib/shared').forEach((file, imports) {
      if (composition.contains(file)) return;
      for (final import in imports) {
        if (import.contains('features/')) offences.add('$file  ($import)');
      }
    });
    expect(offences, isEmpty,
        reason: 'a shared widget that needs a feature\'s cubit means the cubit '
            'is not that feature\'s — move it to lib/shared/logic/');
  });
}

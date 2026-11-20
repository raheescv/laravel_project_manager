import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/constants/global_variables.dart';
import '../../utils/local_storage/local_storage_service.dart';

/// Which sizing a shoe size is read out in — UK, US or EU — persisted per tablet.
///
/// Display only. The catalogue stores, filters and asks the server in its own
/// sizes, which are read as EU; this changes the label on the chip and nothing
/// else. A size the chart has no EU row for (40.5, 10C, M …) shows as stored
/// whatever is chosen.
enum SizeSystem { uk, us, eu }

extension SizeSystemLabel on SizeSystem {
  String get code => name.toUpperCase();
}

class SizeSystemCubit extends Cubit<SizeSystem> {
  SizeSystemCubit() : super(_parse(serviceLocator<LocalStorageService>().sizeSystem));

  static SizeSystem _parse(String? code) =>
      SizeSystem.values.firstWhere((s) => s.name == code, orElse: () => SizeSystem.eu);

  Future<void> set(SizeSystem system) async {
    if (system == state) return;
    emit(system);
    await serviceLocator<LocalStorageService>().setSizeSystem(system.name);
  }

  /// Men's footwear chart, EU → (UK, US). Below EU 38.5 it continues with the
  /// women's chart's UK sizes, and US there is UK + 0.5 like the men's rows,
  /// so the run stays in order.
  static final Map<double, (String, String)> _chart = {
    35: ('2.5', '3'), 35.5: ('3', '3.5'), 36: ('3.5', '4'), 37: ('4', '4.5'),
    37.5: ('4.5', '5'), 38: ('5', '5.5'), 38.5: ('6', '6.5'), 39: ('6.5', '7'),
    40: ('7', '7.5'), 41: ('7.5', '8'), 42: ('8', '8.5'), 43: ('8.5', '9'),
    43.5: ('9', '9.5'), 44: ('9.5', '10'), 44.5: ('10', '10.5'), 45: ('10.5', '11'),
    45.5: ('11', '11.5'), 46: ('11.5', '12'), 46.5: ('12', '12.5'), 47: ('12.5', '13'),
  };

  /// A stored (EU) size as [system] reads it: "42" → "8" in UK, "8.5" in US.
  static String labelFor(String size, SizeSystem system) {
    final raw = size.trim();
    if (system == SizeSystem.eu || !RegExp(r'^\d+(\.\d+)?$').hasMatch(raw)) return raw;
    final row = _chart[double.parse(raw)];
    if (row == null) return raw;
    return system == SizeSystem.uk ? row.$1 : row.$2;
  }
}

extension SizeLabelContext on BuildContext {
  /// The sizing chosen in the bar, or null outside the app shell (a widget
  /// pumped on its own), which reads sizes as stored. Rebuilds the caller.
  SizeSystemCubit? get sizeSystemCubit {
    try {
      return watch<SizeSystemCubit>();
    } on ProviderNotFoundException {
      return null;
    }
  }

  /// [size] in the sizing chosen in the bar; rebuilds the caller when it changes.
  String sizeLabel(String size) =>
      SizeSystemCubit.labelFor(size, sizeSystemCubit?.state ?? SizeSystem.eu);
}

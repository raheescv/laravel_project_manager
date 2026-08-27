import 'dart:ui';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/constants/global_variables.dart';
import '../../utils/components/theme/pearl_theme.dart';
import '../../utils/local_storage/local_storage_service.dart';

/// English or Arabic, persisted per tablet.
///
/// `null` means follow the device, which is the default — a tablet handed to an
/// Arabic-speaking customer should already be in Arabic without a member of
/// staff finding the setting first.
///
/// Switching also re-points [PearlText] at a face that has Arabic glyphs and
/// drops the tracking, because Pearl is built on wide letter-spacing and that
/// pulls apart the joins in Arabic script. Nothing else in the app needs to
/// know which language it is in.
class LocaleCubit extends Cubit<Locale?> {
  LocaleCubit() : super(null) {
    final saved = _storage.locale;
    final resolved = saved == null ? null : Locale(saved);
    PearlText.useArabic(_isArabic(resolved));
    emit(resolved);
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  static bool _isArabic(Locale? locale) =>
      (locale ?? PlatformDispatcher.instance.locale).languageCode == 'ar';

  /// Pass null to follow the device.
  Future<void> set(Locale? locale) async {
    if (locale?.languageCode == state?.languageCode) return;
    PearlText.useArabic(_isArabic(locale));
    emit(locale);
    await _storage.setLocale(locale?.languageCode);
  }
}

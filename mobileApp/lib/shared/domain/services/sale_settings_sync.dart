import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';

import '../../utils/local_storage/local_storage_service.dart';
import '../constants/global_variables.dart';
import '../repository/lookup_repository.dart';

/// The remote half of Settings → Sale Configuration, cached for offline use.
typedef RemoteSaleSettings = ({
  double? defaultQuantity,
  bool? tipEnabled,
  String? defaultProductType,
  RemotePrintConfig? print,
});

/// Pull the web Sale Configuration and cache every part of it locally.
///
/// Lives outside [CartCubit] because two callers need it and only one of them is
/// a screen: the cart syncs it when New Sale opens, and first-run provisioning
/// pulls it up front so a till that goes straight offline is not left with
/// factory defaults for quantity, tipping and the receipt layout.
///
/// Returns the settings so a caller can react to them — the cart clears a tip
/// that has since been switched off.
Future<RemoteSaleSettings> pullAndCacheSaleSettings() async {
  final storage = serviceLocator<LocalStorageService>();
  final settings = await serviceLocator<LookupRepository>().saleSettings();

  final quantity = settings.defaultQuantity;
  if (quantity != null && quantity != storage.defaultQuantity) {
    await storage.setDefaultQuantity(quantity);
  }

  final tip = settings.tipEnabled;
  if (tip != null && tip != storage.tipEnabled) await storage.setTipEnabled(tip);

  // Cached so the catalog can preselect the Product/Service filter.
  final type = settings.defaultProductType;
  if (type != null && type != storage.defaultProductType) {
    await storage.setDefaultProductType(type);
  }

  // Thermal-print options ride along on the same response.
  await serviceLocator<PrintSettingsCubit>().applyRemote(settings.print);

  return settings;
}

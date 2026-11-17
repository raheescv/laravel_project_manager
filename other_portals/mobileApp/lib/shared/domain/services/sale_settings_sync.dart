import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';

import '../../utils/local_storage/local_storage_service.dart';
import '../constants/global_variables.dart';
import '../models/catalog.dart';
import '../models/product_sort.dart';
import '../repository/lookup_repository.dart';

/// The remote half of Settings → Sale Configuration, cached for offline use.
typedef RemoteSaleSettings = ({
  double? defaultQuantity,
  bool? tipEnabled,
  bool? roundOffEnabled,
  String? defaultProductType,
  bool? schoolEnabled,
  String? logoUrl,
  ProductSort? productSort,
  List<PaymentMethod>? paymentButtons,
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

  final roundOff = settings.roundOffEnabled;
  if (roundOff != null && roundOff != storage.roundOffEnabled) await storage.setRoundOffEnabled(roundOff);

  // Cached so the catalog can preselect the Product/Service filter.
  final type = settings.defaultProductType;
  if (type != null && type != storage.defaultProductType) {
    await storage.setDefaultProductType(type);
  }

  // School module: whether the till offers student cards at all.
  final school = settings.schoolEnabled;
  if (school != null && school != storage.schoolEnabled) await storage.setSchoolEnabled(school);

  // Company logo — painted on catalog tiles whose product has no photo.
  final logo = settings.logoUrl;
  if (logo != null && logo != storage.companyLogoUrl) await storage.setCompanyLogoUrl(logo);

  // New Sale catalog order — the cubit caches it for the Settings card, and
  // the catalog reloads on its next open when it no longer matches.
  await serviceLocator<PosSettingsCubit>().applyRemoteProductSort(settings.productSort);

  // Checkout quick buttons (System Configurations → POS Payment Buttons).
  final buttons = settings.paymentButtons;
  if (buttons != null) await storage.setPosPaymentButtons(buttons);

  // Thermal-print options ride along on the same response.
  await serviceLocator<PrintSettingsCubit>().applyRemote(settings.print);

  return settings;
}

import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';
import 'app_localizations_en.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of L
/// returned by `L.of(context)`.
///
/// Applications need to include `L.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: L.localizationsDelegates,
///   supportedLocales: L.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the L.supportedLocales
/// property.
abstract class L {
  L(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static L of(BuildContext context) {
    return Localizations.of<L>(context, L)!;
  }

  static const LocalizationsDelegate<L> delegate = _LDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('ar'),
    Locale('en'),
  ];

  /// No description provided for @appTitle.
  ///
  /// In en, this message translates to:
  /// **'Sizerun'**
  String get appTitle;

  /// No description provided for @search.
  ///
  /// In en, this message translates to:
  /// **'Search'**
  String get search;

  /// No description provided for @searchLong.
  ///
  /// In en, this message translates to:
  /// **'Search by name, code or barcode'**
  String get searchLong;

  /// No description provided for @inStock.
  ///
  /// In en, this message translates to:
  /// **'In stock'**
  String get inStock;

  /// No description provided for @chooseStore.
  ///
  /// In en, this message translates to:
  /// **'Choose a store'**
  String get chooseStore;

  /// No description provided for @storeHint.
  ///
  /// In en, this message translates to:
  /// **'Stock counts on every screen describe the store shown here.'**
  String get storeHint;

  /// No description provided for @scanBarcode.
  ///
  /// In en, this message translates to:
  /// **'Scan a barcode'**
  String get scanBarcode;

  /// No description provided for @appearance.
  ///
  /// In en, this message translates to:
  /// **'Appearance'**
  String get appearance;

  /// No description provided for @more.
  ///
  /// In en, this message translates to:
  /// **'More'**
  String get more;

  /// No description provided for @offline.
  ///
  /// In en, this message translates to:
  /// **'Offline — stock figures may be out of date'**
  String get offline;

  /// No description provided for @tryAgain.
  ///
  /// In en, this message translates to:
  /// **'Try again'**
  String get tryAgain;

  /// No description provided for @close.
  ///
  /// In en, this message translates to:
  /// **'Close'**
  String get close;

  /// No description provided for @cancel.
  ///
  /// In en, this message translates to:
  /// **'Cancel'**
  String get cancel;

  /// No description provided for @loading.
  ///
  /// In en, this message translates to:
  /// **'Loading'**
  String get loading;

  /// No description provided for @loadingEllipsis.
  ///
  /// In en, this message translates to:
  /// **'Loading…'**
  String get loadingEllipsis;

  /// No description provided for @stepSize.
  ///
  /// In en, this message translates to:
  /// **'Size'**
  String get stepSize;

  /// No description provided for @stepBrand.
  ///
  /// In en, this message translates to:
  /// **'Brand'**
  String get stepBrand;

  /// No description provided for @stepResults.
  ///
  /// In en, this message translates to:
  /// **'Results'**
  String get stepResults;

  /// No description provided for @choosing.
  ///
  /// In en, this message translates to:
  /// **'Choosing…'**
  String get choosing;

  /// No description provided for @anySize.
  ///
  /// In en, this message translates to:
  /// **'Any size'**
  String get anySize;

  /// No description provided for @anyBrand.
  ///
  /// In en, this message translates to:
  /// **'Any brand'**
  String get anyBrand;

  /// No description provided for @whichSize.
  ///
  /// In en, this message translates to:
  /// **'What is your size?'**
  String get whichSize;

  /// No description provided for @sizesDidNotLoad.
  ///
  /// In en, this message translates to:
  /// **'Sizes did not load'**
  String get sizesDidNotLoad;

  /// No description provided for @sizeHintInStock.
  ///
  /// In en, this message translates to:
  /// **'Only sizes on the shelf here are shown. Turn off \"In stock\" at the top to see the whole size run.'**
  String get sizeHintInStock;

  /// No description provided for @sizeHintAll.
  ///
  /// In en, this message translates to:
  /// **'Sizes with nothing on the shelf are struck through — ask a colleague and we can check the other stores.'**
  String get sizeHintAll;

  /// No description provided for @available.
  ///
  /// In en, this message translates to:
  /// **'Available'**
  String get available;

  /// No description provided for @stylesPerSize.
  ///
  /// In en, this message translates to:
  /// **'styles per size'**
  String get stylesPerSize;

  /// No description provided for @noSizes.
  ///
  /// In en, this message translates to:
  /// **'No sizes recorded'**
  String get noSizes;

  /// No description provided for @noSizesDetail.
  ///
  /// In en, this message translates to:
  /// **'Nothing in this category carries a size yet. Continue to see everything in it.'**
  String get noSizesDetail;

  /// No description provided for @whichBrand.
  ///
  /// In en, this message translates to:
  /// **'Which brand?'**
  String get whichBrand;

  /// No description provided for @brandsDidNotLoad.
  ///
  /// In en, this message translates to:
  /// **'Brands did not load'**
  String get brandsDidNotLoad;

  /// No description provided for @stylesPerBrand.
  ///
  /// In en, this message translates to:
  /// **'styles per brand'**
  String get stylesPerBrand;

  /// No description provided for @stylesPerBrandInSize.
  ///
  /// In en, this message translates to:
  /// **'styles per brand in size {size}'**
  String stylesPerBrandInSize(Object size);

  /// No description provided for @showEveryBrand.
  ///
  /// In en, this message translates to:
  /// **'Show every brand'**
  String get showEveryBrand;

  /// No description provided for @showAllInSize.
  ///
  /// In en, this message translates to:
  /// **'Show all {count} in size {size}'**
  String showAllInSize(int count, Object size);

  /// No description provided for @noBrandsHere.
  ///
  /// In en, this message translates to:
  /// **'No brands here'**
  String get noBrandsHere;

  /// No description provided for @noBrandsInSize.
  ///
  /// In en, this message translates to:
  /// **'No brands in this size'**
  String get noBrandsInSize;

  /// No description provided for @noBrandsInStock.
  ///
  /// In en, this message translates to:
  /// **'Nothing{inSize} is on the shelf here right now. Turn off \"In stock\" at the top to see what we can order in.'**
  String noBrandsInStock(Object inSize);

  /// No description provided for @noBrandsAtAll.
  ///
  /// In en, this message translates to:
  /// **'Nothing in the catalogue carries a brand{inSize}.'**
  String noBrandsAtAll(Object inSize);

  /// No description provided for @inSizeSuffix.
  ///
  /// In en, this message translates to:
  /// **' in size {size}'**
  String inSizeSuffix(Object size);

  /// No description provided for @chooseAnotherSize.
  ///
  /// In en, this message translates to:
  /// **'Choose another size'**
  String get chooseAnotherSize;

  /// No description provided for @skipAhead.
  ///
  /// In en, this message translates to:
  /// **'Skip ahead'**
  String get skipAhead;

  /// No description provided for @filterAndSort.
  ///
  /// In en, this message translates to:
  /// **'Filter and sort'**
  String get filterAndSort;

  /// No description provided for @filtersCount.
  ///
  /// In en, this message translates to:
  /// **'Filters · {count}'**
  String filtersCount(int count);

  /// No description provided for @productsCount.
  ///
  /// In en, this message translates to:
  /// **'{count} products'**
  String productsCount(int count);

  /// No description provided for @shownCount.
  ///
  /// In en, this message translates to:
  /// **'{count} shown'**
  String shownCount(int count);

  /// No description provided for @sortBy.
  ///
  /// In en, this message translates to:
  /// **'Sort · {field}'**
  String sortBy(Object field);

  /// No description provided for @sortName.
  ///
  /// In en, this message translates to:
  /// **'Name'**
  String get sortName;

  /// No description provided for @sortPrice.
  ///
  /// In en, this message translates to:
  /// **'Price'**
  String get sortPrice;

  /// No description provided for @listDidNotLoad.
  ///
  /// In en, this message translates to:
  /// **'The list did not load'**
  String get listDidNotLoad;

  /// No description provided for @nothingMatches.
  ///
  /// In en, this message translates to:
  /// **'Nothing matches'**
  String get nothingMatches;

  /// No description provided for @nothingMatchesDetail.
  ///
  /// In en, this message translates to:
  /// **'Try clearing the filters, or change the store at the top of the screen.'**
  String get nothingMatchesDetail;

  /// No description provided for @clearFilters.
  ///
  /// In en, this message translates to:
  /// **'Clear filters'**
  String get clearFilters;

  /// No description provided for @loadingMore.
  ///
  /// In en, this message translates to:
  /// **'Loading more'**
  String get loadingMore;

  /// No description provided for @scrollForMore.
  ///
  /// In en, this message translates to:
  /// **'Scroll for more'**
  String get scrollForMore;

  /// No description provided for @refine.
  ///
  /// In en, this message translates to:
  /// **'Refine'**
  String get refine;

  /// No description provided for @department.
  ///
  /// In en, this message translates to:
  /// **'Department'**
  String get department;

  /// No description provided for @colour.
  ///
  /// In en, this message translates to:
  /// **'Colour'**
  String get colour;

  /// No description provided for @price.
  ///
  /// In en, this message translates to:
  /// **'Price'**
  String get price;

  /// No description provided for @under.
  ///
  /// In en, this message translates to:
  /// **'Under {amount}'**
  String under(Object amount);

  /// No description provided for @over.
  ///
  /// In en, this message translates to:
  /// **'{amount}+'**
  String over(Object amount);

  /// No description provided for @band.
  ///
  /// In en, this message translates to:
  /// **'{from} – {to}'**
  String band(Object from, Object to);

  /// No description provided for @has360.
  ///
  /// In en, this message translates to:
  /// **'Has a 360° view'**
  String get has360;

  /// No description provided for @appliedToLoaded.
  ///
  /// In en, this message translates to:
  /// **'Applied to loaded results'**
  String get appliedToLoaded;

  /// No description provided for @showResults.
  ///
  /// In en, this message translates to:
  /// **'Show results'**
  String get showResults;

  /// No description provided for @product.
  ///
  /// In en, this message translates to:
  /// **'Product'**
  String get product;

  /// No description provided for @productDidNotLoad.
  ///
  /// In en, this message translates to:
  /// **'This product did not load'**
  String get productDidNotLoad;

  /// No description provided for @photoDidNotLoad.
  ///
  /// In en, this message translates to:
  /// **'This photo did not load'**
  String get photoDidNotLoad;

  /// No description provided for @tapToZoom.
  ///
  /// In en, this message translates to:
  /// **'Tap to zoom'**
  String get tapToZoom;

  /// No description provided for @pinchToZoom.
  ///
  /// In en, this message translates to:
  /// **'Pinch or double tap to zoom'**
  String get pinchToZoom;

  /// No description provided for @pinchToZoomSwipe.
  ///
  /// In en, this message translates to:
  /// **'Pinch or double tap to zoom · swipe for the next photo'**
  String get pinchToZoomSwipe;

  /// No description provided for @spin360.
  ///
  /// In en, this message translates to:
  /// **'Spin 360° · {frames} frames'**
  String spin360(int frames);

  /// No description provided for @dragToSpin.
  ///
  /// In en, this message translates to:
  /// **'Drag to spin · {frames} frames'**
  String dragToSpin(int frames);

  /// No description provided for @loading360.
  ///
  /// In en, this message translates to:
  /// **'Loading 360°'**
  String get loading360;

  /// No description provided for @framesLoaded.
  ///
  /// In en, this message translates to:
  /// **'{loaded} / {total} frames'**
  String framesLoaded(int loaded, int total);

  /// No description provided for @noPhotos.
  ///
  /// In en, this message translates to:
  /// **'No photos for this product'**
  String get noPhotos;

  /// No description provided for @gallery.
  ///
  /// In en, this message translates to:
  /// **'Gallery'**
  String get gallery;

  /// No description provided for @zoom.
  ///
  /// In en, this message translates to:
  /// **'Zoom'**
  String get zoom;

  /// No description provided for @inStockCount.
  ///
  /// In en, this message translates to:
  /// **'In stock · {count}'**
  String inStockCount(int count);

  /// No description provided for @otherStores.
  ///
  /// In en, this message translates to:
  /// **'Other stores'**
  String get otherStores;

  /// No description provided for @soldOut.
  ///
  /// In en, this message translates to:
  /// **'Sold out'**
  String get soldOut;

  /// No description provided for @onlyLeft.
  ///
  /// In en, this message translates to:
  /// **'Only {count}'**
  String onlyLeft(int count);

  /// No description provided for @sizeRun.
  ///
  /// In en, this message translates to:
  /// **'Size run'**
  String get sizeRun;

  /// No description provided for @details.
  ///
  /// In en, this message translates to:
  /// **'Details'**
  String get details;

  /// No description provided for @availability.
  ///
  /// In en, this message translates to:
  /// **'Availability'**
  String get availability;

  /// No description provided for @notOnShelf.
  ///
  /// In en, this message translates to:
  /// **'Not on the shelf at any store right now.'**
  String get notOnShelf;

  /// No description provided for @storeCount.
  ///
  /// In en, this message translates to:
  /// **'{count} store'**
  String storeCount(int count);

  /// No description provided for @storesCount.
  ///
  /// In en, this message translates to:
  /// **'{count} stores'**
  String storesCount(int count);

  /// No description provided for @youMayAlsoLike.
  ///
  /// In en, this message translates to:
  /// **'You may also like'**
  String get youMayAlsoLike;

  /// No description provided for @reserveInStore.
  ///
  /// In en, this message translates to:
  /// **'Reserve in store'**
  String get reserveInStore;

  /// No description provided for @reserveDetail.
  ///
  /// In en, this message translates to:
  /// **'We will note this on the tablet and tell a colleague. Nothing is ordered and nothing is charged.'**
  String get reserveDetail;

  /// No description provided for @fieldCode.
  ///
  /// In en, this message translates to:
  /// **'Code'**
  String get fieldCode;

  /// No description provided for @fieldSize.
  ///
  /// In en, this message translates to:
  /// **'Size'**
  String get fieldSize;

  /// No description provided for @fieldStore.
  ///
  /// In en, this message translates to:
  /// **'Store'**
  String get fieldStore;

  /// No description provided for @fieldPhone.
  ///
  /// In en, this message translates to:
  /// **'Phone'**
  String get fieldPhone;

  /// No description provided for @onTheShelf.
  ///
  /// In en, this message translates to:
  /// **'On the shelf'**
  String get onTheShelf;

  /// No description provided for @noteAndClose.
  ///
  /// In en, this message translates to:
  /// **'Note it and close'**
  String get noteAndClose;

  /// No description provided for @barcodeNotFound.
  ///
  /// In en, this message translates to:
  /// **'No product carries the barcode {code}.'**
  String barcodeNotFound(Object code);

  /// No description provided for @cameraAccess.
  ///
  /// In en, this message translates to:
  /// **'Camera access'**
  String get cameraAccess;

  /// No description provided for @cameraDetail.
  ///
  /// In en, this message translates to:
  /// **'The scanner reads the barcode on a shoe box and opens that product. The camera is only used while this screen is open.'**
  String get cameraDetail;

  /// No description provided for @allowCamera.
  ///
  /// In en, this message translates to:
  /// **'Allow the camera'**
  String get allowCamera;

  /// No description provided for @cameraBlocked.
  ///
  /// In en, this message translates to:
  /// **'Camera is blocked'**
  String get cameraBlocked;

  /// No description provided for @cameraBlockedDetail.
  ///
  /// In en, this message translates to:
  /// **'Turn the camera on for this app in Settings, then come back.'**
  String get cameraBlockedDetail;

  /// No description provided for @appearanceIntro.
  ///
  /// In en, this message translates to:
  /// **'Set once per tablet. Nothing here leaves the device.'**
  String get appearanceIntro;

  /// No description provided for @mode.
  ///
  /// In en, this message translates to:
  /// **'Mode'**
  String get mode;

  /// No description provided for @followDevice.
  ///
  /// In en, this message translates to:
  /// **'Follow device'**
  String get followDevice;

  /// No description provided for @day.
  ///
  /// In en, this message translates to:
  /// **'Day'**
  String get day;

  /// No description provided for @night.
  ///
  /// In en, this message translates to:
  /// **'Night'**
  String get night;

  /// No description provided for @dayPalette.
  ///
  /// In en, this message translates to:
  /// **'Day palette'**
  String get dayPalette;

  /// No description provided for @nightPalette.
  ///
  /// In en, this message translates to:
  /// **'Night palette'**
  String get nightPalette;

  /// No description provided for @usedInLight.
  ///
  /// In en, this message translates to:
  /// **'used in light mode'**
  String get usedInLight;

  /// No description provided for @usedInDark.
  ///
  /// In en, this message translates to:
  /// **'used in dark mode'**
  String get usedInDark;

  /// No description provided for @useForBoth.
  ///
  /// In en, this message translates to:
  /// **'Use {preset} for both'**
  String useForBoth(Object preset);

  /// No description provided for @language.
  ///
  /// In en, this message translates to:
  /// **'Language'**
  String get language;

  /// No description provided for @languageHint.
  ///
  /// In en, this message translates to:
  /// **'Labels only. Product names come from the catalogue.'**
  String get languageHint;

  /// No description provided for @english.
  ///
  /// In en, this message translates to:
  /// **'English'**
  String get english;

  /// No description provided for @arabic.
  ///
  /// In en, this message translates to:
  /// **'العربية'**
  String get arabic;

  /// No description provided for @cannotReach.
  ///
  /// In en, this message translates to:
  /// **'Cannot reach the store. Check the connection and try again.'**
  String get cannotReach;

  /// No description provided for @openSettings.
  ///
  /// In en, this message translates to:
  /// **'Open settings'**
  String get openSettings;

  /// No description provided for @lookingUp.
  ///
  /// In en, this message translates to:
  /// **'Looking it up'**
  String get lookingUp;

  /// No description provided for @searching.
  ///
  /// In en, this message translates to:
  /// **'Searching'**
  String get searching;

  /// No description provided for @nothingFound.
  ///
  /// In en, this message translates to:
  /// **'Nothing found'**
  String get nothingFound;

  /// No description provided for @searchPrompt.
  ///
  /// In en, this message translates to:
  /// **'Name, code or barcode'**
  String get searchPrompt;

  /// No description provided for @searchEmpty.
  ///
  /// In en, this message translates to:
  /// **'Type a name, a product code, or scan a barcode.'**
  String get searchEmpty;

  /// No description provided for @searchNoMatch.
  ///
  /// In en, this message translates to:
  /// **'No product matches \"{query}\" in this store.'**
  String searchNoMatch(Object query);

  /// No description provided for @reserveIntro.
  ///
  /// In en, this message translates to:
  /// **'We will note this on the tablet and tell a colleague. Nothing is ordered and nothing is charged.'**
  String get reserveIntro;

  /// No description provided for @underAmount.
  ///
  /// In en, this message translates to:
  /// **'Under {amount}'**
  String underAmount(Object amount);

  /// No description provided for @presetPearlBlurb.
  ///
  /// In en, this message translates to:
  /// **'Cool pearl and graphite. No accent hue at all — selection is an ink block.'**
  String get presetPearlBlurb;

  /// No description provided for @presetNoirBlurb.
  ///
  /// In en, this message translates to:
  /// **'Ivory paper, ink type, a brass hairline. Reads like a lookbook.'**
  String get presetNoirBlurb;

  /// No description provided for @presetAuroraBlurb.
  ///
  /// In en, this message translates to:
  /// **'Indigo-to-cyan light behind frosted panels. The friendliest of the three.'**
  String get presetAuroraBlurb;

  /// No description provided for @textSize.
  ///
  /// In en, this message translates to:
  /// **'Text size'**
  String get textSize;

  /// No description provided for @textStandard.
  ///
  /// In en, this message translates to:
  /// **'Standard'**
  String get textStandard;

  /// No description provided for @textLarge.
  ///
  /// In en, this message translates to:
  /// **'Large'**
  String get textLarge;

  /// No description provided for @textLarger.
  ///
  /// In en, this message translates to:
  /// **'Larger'**
  String get textLarger;

  /// No description provided for @textSizeHint.
  ///
  /// In en, this message translates to:
  /// **'For reading at arm’s length across a counter.'**
  String get textSizeHint;

  /// No description provided for @typeface.
  ///
  /// In en, this message translates to:
  /// **'Typeface'**
  String get typeface;

  /// No description provided for @typefaceHint.
  ///
  /// In en, this message translates to:
  /// **'Changes every label in the app.'**
  String get typefaceHint;
}

class _LDelegate extends LocalizationsDelegate<L> {
  const _LDelegate();

  @override
  Future<L> load(Locale locale) {
    return SynchronousFuture<L>(lookupL(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ar', 'en'].contains(locale.languageCode);

  @override
  bool shouldReload(_LDelegate old) => false;
}

L lookupL(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return LAr();
    case 'en':
      return LEn();
  }

  throw FlutterError(
    'L.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}

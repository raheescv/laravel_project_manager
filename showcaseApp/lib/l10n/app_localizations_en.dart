// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class LEn extends L {
  LEn([String locale = 'en']) : super(locale);

  @override
  String get appTitle => 'Sizerun';

  @override
  String get search => 'Search';

  @override
  String get searchLong => 'Search by name, code or barcode';

  @override
  String get inStock => 'In stock';

  @override
  String get chooseStore => 'Choose a store';

  @override
  String get storeHint =>
      'Stock counts on every screen describe the store shown here.';

  @override
  String get appearance => 'Appearance';

  @override
  String get more => 'More';

  @override
  String get offline => 'Offline — stock figures may be out of date';

  @override
  String get tryAgain => 'Try again';

  @override
  String get close => 'Close';

  @override
  String get cancel => 'Cancel';

  @override
  String get loading => 'Loading';

  @override
  String get loadingEllipsis => 'Loading…';

  @override
  String get stepSize => 'Size';

  @override
  String get stepBrand => 'Brand';

  @override
  String get stepResults => 'Results';

  @override
  String get choosing => 'Choosing…';

  @override
  String get anyBrand => 'All brands';

  @override
  String get whichSize => 'What is your size?';

  @override
  String get sizesDidNotLoad => 'Sizes did not load';

  @override
  String get sizeHintAll =>
      'Sizes with nothing on the shelf are struck through — ask a colleague and we can check the other stores.';

  @override
  String get noSizes => 'No sizes recorded';

  @override
  String get noSizesDetail =>
      'Nothing in this category carries a size yet. Continue to see everything in it.';

  @override
  String get whichBrand => 'Which brand?';

  @override
  String get brandsDidNotLoad => 'Brands did not load';

  @override
  String get showEveryBrand => 'Show every brand';

  @override
  String showAllInSize(int count, Object size) {
    return 'Show all $count in size $size';
  }

  @override
  String get noBrandsHere => 'No brands here';

  @override
  String get noBrandsInSize => 'No brands in this size';

  @override
  String noBrandsInStock(Object inSize) {
    return 'Nothing$inSize is on the shelf here right now. Turn off \"In stock\" at the top to see what we can order in.';
  }

  @override
  String noBrandsAtAll(Object inSize) {
    return 'Nothing in the catalogue carries a brand$inSize.';
  }

  @override
  String inSizeSuffix(Object size) {
    return ' in size $size';
  }

  @override
  String get chooseAnotherSize => 'Choose another size';

  @override
  String get skipAhead => 'Skip ahead';

  @override
  String get backToHome => 'Back to home';

  @override
  String get filterAndSort => 'Filter and sort';

  @override
  String filtersCount(int count) {
    return 'Filters · $count';
  }

  @override
  String productsCount(int count) {
    return '$count products';
  }

  @override
  String sortBy(Object field) {
    return 'Sort · $field';
  }

  @override
  String get sortName => 'Name';

  @override
  String get sortPrice => 'Price';

  @override
  String get listDidNotLoad => 'The list did not load';

  @override
  String get nothingMatches => 'Nothing matches';

  @override
  String get nothingMatchesDetail =>
      'Try clearing the filters, or change the store at the top of the screen.';

  @override
  String get clearFilters => 'Clear filters';

  @override
  String get loadingMore => 'Loading more';

  @override
  String get scrollForMore => 'Scroll for more';

  @override
  String get refine => 'Refine';

  @override
  String get department => 'Department';

  @override
  String get colour => 'Colour';

  @override
  String get price => 'Price';

  @override
  String under(Object amount) {
    return 'Under $amount';
  }

  @override
  String over(Object amount) {
    return '$amount+';
  }

  @override
  String band(Object from, Object to) {
    return '$from – $to';
  }

  @override
  String get has360 => 'Has a 360° view';

  @override
  String get showResults => 'Show results';

  @override
  String get product => 'Product';

  @override
  String get productDidNotLoad => 'This product did not load';

  @override
  String get photoDidNotLoad => 'This photo did not load';

  @override
  String get tapToZoom => 'Tap to zoom';

  @override
  String get pinchToZoom => 'Pinch or double tap to zoom';

  @override
  String get pinchToZoomSwipe =>
      'Pinch or double tap to zoom · swipe for the next photo';

  @override
  String spin360(int frames) {
    return 'Spin 360° · $frames frames';
  }

  @override
  String dragToSpin(int frames) {
    return 'Drag to spin · $frames frames';
  }

  @override
  String get loading360 => 'Loading 360°';

  @override
  String framesLoaded(int loaded, int total) {
    return '$loaded / $total frames';
  }

  @override
  String get noPhotos => 'No photos for this product';

  @override
  String get gallery => 'Gallery';

  @override
  String get zoom => 'Zoom';

  @override
  String inStockCount(int count) {
    return 'In stock · $count';
  }

  @override
  String get soldOut => 'Sold out';

  @override
  String onlyLeft(int count) {
    return 'Only $count';
  }

  @override
  String get sizeRun => 'Size run';

  @override
  String get details => 'Details';

  @override
  String get availability => 'Availability';

  @override
  String get notOnShelf => 'Not on the shelf at any store right now.';

  @override
  String sizeNotOnShelf(String size) {
    return 'No store has $size right now.';
  }

  @override
  String storeCount(int count) {
    return '$count store';
  }

  @override
  String storesCount(int count) {
    return '$count stores';
  }

  @override
  String get youMayAlsoLike => 'You may also like';

  @override
  String get fieldCode => 'Code';

  @override
  String get fieldSize => 'Size';

  @override
  String get fieldStore => 'Store';

  @override
  String get fieldPhone => 'Phone';

  @override
  String get onTheShelf => 'On the shelf';

  @override
  String get appearanceIntro =>
      'Set once per tablet. Nothing here leaves the device.';

  @override
  String get mode => 'Mode';

  @override
  String get systemMode => 'System';

  @override
  String get lightMode => 'Light';

  @override
  String get darkMode => 'Dark';

  @override
  String get palette => 'Palette';

  @override
  String get usedInLight => 'used in light mode';

  @override
  String get usedInDark => 'used in dark mode';

  @override
  String useForBoth(Object preset) {
    return 'Use $preset for both';
  }

  @override
  String get language => 'Language';

  @override
  String get languageHint =>
      'Labels only. Product names come from the catalogue.';

  @override
  String get english => 'English';

  @override
  String get arabic => 'العربية';

  @override
  String get cannotReach =>
      'Cannot reach the store. Check the connection and try again.';

  @override
  String get searching => 'Searching';

  @override
  String get nothingFound => 'Nothing found';

  @override
  String get searchPrompt => 'Name, code or barcode';

  @override
  String get searchEmpty => 'Type a name or a product code.';

  @override
  String searchNoMatch(Object query) {
    return 'No product matches \"$query\" in this store.';
  }

  @override
  String underAmount(Object amount) {
    return 'Under $amount';
  }

  @override
  String get presetPearlBlurb =>
      'Cool pearl and graphite. No accent hue at all — selection is an ink block.';

  @override
  String get presetNoirBlurb =>
      'Ivory paper, ink type, a brass hairline. Reads like a lookbook.';

  @override
  String get presetAuroraBlurb =>
      'Indigo-to-cyan light behind frosted panels. The friendliest of the set.';

  @override
  String get presetSizerunBlurb =>
      'Paper white, black type, an electric ultramarine block. The house colours.';

  @override
  String get textSize => 'Text size';

  @override
  String get textStandard => 'Standard';

  @override
  String get textLarge => 'Large';

  @override
  String get textLarger => 'Larger';

  @override
  String get textSizeHint => 'For reading at arm’s length across a counter.';

  @override
  String get sizesPerRow => 'Sizes per row';

  @override
  String get sizesPerRowHint => 'on the size screen';

  @override
  String get productsPerRow => 'Products per row';

  @override
  String get productsPerRowHint => 'on the results and search screens';

  @override
  String get typeface => 'Typeface';

  @override
  String get typefaceHint => 'Changes every label in the app.';

  @override
  String searchNoMatchInStock(Object query) {
    return 'No product matches \"$query\" on the shelf here. Turn off \"In stock\" to search the whole catalogue.';
  }

  @override
  String get allStores => 'All stores';

  @override
  String get textLargest => 'Largest';

  @override
  String get allSizes => 'All sizes';

  @override
  String get anySize => 'All';

  @override
  String get resetTimer => 'Reset after';

  @override
  String get resetTimerHint =>
      'Minutes of no touch before the panel returns to the start and forgets the last customer.';

  @override
  String get minutesUnit => 'minutes';

  @override
  String appVersion(Object version) {
    return 'Version $version';
  }

  @override
  String resetTimerRange(int min, int max) {
    return 'Between $min and $max minutes.';
  }
}

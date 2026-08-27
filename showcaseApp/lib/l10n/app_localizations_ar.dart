// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class LAr extends L {
  LAr([String locale = 'ar']) : super(locale);

  @override
  String get appTitle => 'سايزران';

  @override
  String get search => 'بحث';

  @override
  String get searchLong => 'ابحث بالاسم أو الرمز أو الباركود';

  @override
  String get inStock => 'متوفر بالمخزون';

  @override
  String get chooseStore => 'اختر المتجر';

  @override
  String get storeHint => 'أرقام المخزون في كل الشاشات تخص المتجر المعروض هنا.';

  @override
  String get appearance => 'المظهر';

  @override
  String get more => 'المزيد';

  @override
  String get offline => 'غير متصل — قد تكون أرقام المخزون غير محدّثة';

  @override
  String get tryAgain => 'حاول مرة أخرى';

  @override
  String get close => 'إغلاق';

  @override
  String get cancel => 'إلغاء';

  @override
  String get loading => 'جارٍ التحميل';

  @override
  String get loadingEllipsis => 'جارٍ التحميل…';

  @override
  String get stepSize => 'المقاس';

  @override
  String get stepBrand => 'العلامة التجارية';

  @override
  String get stepResults => 'النتائج';

  @override
  String get choosing => 'جارٍ الاختيار…';

  @override
  String get anyBrand => 'كل العلامات';

  @override
  String get whichSize => 'كم مقاسك؟';

  @override
  String get sizesDidNotLoad => 'تعذّر تحميل المقاسات';

  @override
  String get sizeHintAll =>
      'المقاسات غير المتوفرة مشطوبة — اسأل أحد الموظفين ويمكننا التحقق من الفروع الأخرى.';

  @override
  String get noSizes => 'لا توجد مقاسات مسجّلة';

  @override
  String get noSizesDetail =>
      'لا يوجد أي منتج بمقاس في هذا القسم بعد. تابع لعرض كل ما فيه.';

  @override
  String get whichBrand => 'أي علامة تجارية؟';

  @override
  String get brandsDidNotLoad => 'تعذّر تحميل العلامات التجارية';

  @override
  String get showEveryBrand => 'عرض كل العلامات';

  @override
  String showAllInSize(int count, Object size) {
    return 'عرض كل $count في المقاس $size';
  }

  @override
  String get noBrandsHere => 'لا توجد علامات هنا';

  @override
  String get noBrandsInSize => 'لا توجد علامات بهذا المقاس';

  @override
  String noBrandsInStock(Object inSize) {
    return 'لا يوجد شيء$inSize متوفر هنا حالياً. أوقف \"متوفر بالمخزون\" في الأعلى لعرض ما يمكننا طلبه.';
  }

  @override
  String noBrandsAtAll(Object inSize) {
    return 'لا يوجد في الكتالوج أي منتج بعلامة تجارية$inSize.';
  }

  @override
  String inSizeSuffix(Object size) {
    return ' في المقاس $size';
  }

  @override
  String get chooseAnotherSize => 'اختر مقاساً آخر';

  @override
  String get skipAhead => 'تخطَّ إلى النتائج';

  @override
  String get backToHome => 'العودة إلى البداية';

  @override
  String get filterAndSort => 'التصفية والترتيب';

  @override
  String filtersCount(int count) {
    return 'التصفية · $count';
  }

  @override
  String productsCount(int count) {
    return '$count منتج';
  }

  @override
  String sortBy(Object field) {
    return 'الترتيب · $field';
  }

  @override
  String get sortName => 'الاسم';

  @override
  String get sortPrice => 'السعر';

  @override
  String get listDidNotLoad => 'تعذّر تحميل القائمة';

  @override
  String get nothingMatches => 'لا توجد نتائج مطابقة';

  @override
  String get nothingMatchesDetail =>
      'جرّب مسح عوامل التصفية، أو غيّر المتجر من أعلى الشاشة.';

  @override
  String get clearFilters => 'مسح عوامل التصفية';

  @override
  String get loadingMore => 'جارٍ تحميل المزيد';

  @override
  String get scrollForMore => 'مرّر لعرض المزيد';

  @override
  String get refine => 'تحسين النتائج';

  @override
  String get department => 'القسم';

  @override
  String get colour => 'اللون';

  @override
  String get price => 'السعر';

  @override
  String under(Object amount) {
    return 'أقل من $amount';
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
  String get has360 => 'يحتوي على عرض 360°';

  @override
  String get showResults => 'عرض النتائج';

  @override
  String get product => 'المنتج';

  @override
  String get productDidNotLoad => 'تعذّر تحميل هذا المنتج';

  @override
  String get photoDidNotLoad => 'تعذّر تحميل هذه الصورة';

  @override
  String get tapToZoom => 'اضغط للتكبير';

  @override
  String get pinchToZoom => 'قرّب بإصبعيك أو اضغط مرتين للتكبير';

  @override
  String get pinchToZoomSwipe =>
      'قرّب أو اضغط مرتين للتكبير · اسحب للصورة التالية';

  @override
  String spin360(int frames) {
    return 'عرض 360° · $frames لقطة';
  }

  @override
  String dragToSpin(int frames) {
    return 'اسحب للتدوير · $frames لقطة';
  }

  @override
  String get loading360 => 'جارٍ تحميل عرض 360°';

  @override
  String framesLoaded(int loaded, int total) {
    return '$loaded / $total لقطة';
  }

  @override
  String get noPhotos => 'لا توجد صور لهذا المنتج';

  @override
  String get gallery => 'المعرض';

  @override
  String get zoom => 'تكبير';

  @override
  String inStockCount(int count) {
    return 'متوفر · $count';
  }

  @override
  String get soldOut => 'نفدت الكمية';

  @override
  String onlyLeft(int count) {
    return '$count فقط';
  }

  @override
  String get sizeRun => 'المقاسات';

  @override
  String get details => 'التفاصيل';

  @override
  String get availability => 'التوفر';

  @override
  String get notOnShelf => 'غير متوفر في أي فرع حالياً.';

  @override
  String sizeNotOnShelf(String size) {
    return 'المقاس $size غير متوفر في أي فرع حالياً.';
  }

  @override
  String storeCount(int count) {
    return '$count فرع';
  }

  @override
  String storesCount(int count) {
    return '$count فروع';
  }

  @override
  String get youMayAlsoLike => 'قد يعجبك أيضاً';

  @override
  String get fieldCode => 'الرمز';

  @override
  String get fieldSize => 'المقاس';

  @override
  String get fieldStore => 'الفرع';

  @override
  String get fieldPhone => 'الهاتف';

  @override
  String get onTheShelf => 'المتوفر حالياً';

  @override
  String get appearanceIntro =>
      'يُضبط مرة واحدة لكل جهاز. لا شيء هنا يغادر الجهاز.';

  @override
  String get mode => 'الوضع';

  @override
  String get systemMode => 'النظام';

  @override
  String get lightMode => 'فاتح';

  @override
  String get darkMode => 'داكن';

  @override
  String get palette => 'لوحة الألوان';

  @override
  String get usedInLight => 'تُستخدم في الوضع النهاري';

  @override
  String get usedInDark => 'تُستخدم في الوضع الليلي';

  @override
  String useForBoth(Object preset) {
    return 'استخدم $preset للوضعين';
  }

  @override
  String get language => 'اللغة';

  @override
  String get languageHint => 'الواجهة فقط. أسماء المنتجات تأتي من الكتالوج.';

  @override
  String get english => 'English';

  @override
  String get arabic => 'العربية';

  @override
  String get cannotReach =>
      'تعذّر الوصول إلى المتجر. تحقق من الاتصال وحاول مرة أخرى.';

  @override
  String get searching => 'جارٍ البحث';

  @override
  String get nothingFound => 'لم يتم العثور على شيء';

  @override
  String get searchPrompt => 'الاسم أو الرمز أو الباركود';

  @override
  String get searchEmpty => 'اكتب اسماً أو رمز منتج.';

  @override
  String searchNoMatch(Object query) {
    return 'لا يوجد منتج يطابق \"$query\" في هذا الفرع.';
  }

  @override
  String underAmount(Object amount) {
    return 'أقل من $amount';
  }

  @override
  String get presetPearlBlurb =>
      'لؤلؤي بارد ورمادي جرافيت. بلا أي لون مميّز — الاختيار يُظهر بكتلة حبرية.';

  @override
  String get presetNoirBlurb =>
      'ورق عاجي وحبر أسود وخط نحاسي رفيع. يُقرأ كأنه كتالوج أزياء.';

  @override
  String get presetAuroraBlurb =>
      'ضوء نيلي إلى سماوي خلف ألواح ضبابية. الأكثر ودّاً بينها.';

  @override
  String get presetSizerunBlurb =>
      'أبيض ورقي وحبر أسود وكتلة زرقاء كهربائية. ألوان العلامة.';

  @override
  String get textSize => 'حجم النص';

  @override
  String get textStandard => 'قياسي';

  @override
  String get textLarge => 'كبير';

  @override
  String get textLarger => 'أكبر';

  @override
  String get textSizeHint => 'للقراءة من مسافة الذراع عبر الطاولة.';

  @override
  String get sizesPerRow => 'المقاسات في كل صف';

  @override
  String get sizesPerRowHint => 'في شاشة المقاسات';

  @override
  String get productsPerRow => 'المنتجات في كل صف';

  @override
  String get productsPerRowHint => 'في شاشتي النتائج والبحث';

  @override
  String get typeface => 'الخط';

  @override
  String get typefaceHint => 'يغيّر كل النصوص في التطبيق.';

  @override
  String searchNoMatchInStock(Object query) {
    return 'لا يوجد منتج يطابق \"$query\" متوفراً هنا. أوقف \"متوفر بالمخزون\" للبحث في الكتالوج كاملاً.';
  }

  @override
  String get allStores => 'كل الفروع';

  @override
  String get textLargest => 'الأكبر';

  @override
  String get allSizes => 'كل المقاسات';

  @override
  String get anySize => 'الكل';

  @override
  String get resetTimer => 'إعادة الضبط بعد';

  @override
  String get resetTimerHint =>
      'عدد الدقائق دون لمس قبل أن تعود الشاشة إلى البداية وتنسى العميل السابق.';

  @override
  String get minutesUnit => 'دقيقة';

  @override
  String appVersion(Object version) {
    return 'الإصدار $version';
  }

  @override
  String resetTimerRange(int min, int max) {
    return 'بين $min و $max دقيقة.';
  }
}

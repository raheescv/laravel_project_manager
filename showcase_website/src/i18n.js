import { reactive } from 'vue'

/* ---------------------------------------------------------------------------
   SIZE RUN — copy (EN / AR)
   Every string the interface renders. Add a language by adding a key here.
   ------------------------------------------------------------------------ */

const CURRENCY = import.meta.env.VITE_CURRENCY || 'QAR'
const AR_SYMBOL = { QAR: 'ر.ق', AED: 'د.إ', SAR: 'ر.س', KWD: 'د.ك', BHD: 'د.ب', OMR: 'ر.ع', USD: '$' }
const num = (n) => Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 2 })

const STRINGS = {
  en: {
    title: (d) => `${d.store} — Sneakers for Everyone`,
    skip: 'Skip to products',
    step1: 'Step 1 — your size',
    step2: 'Step 2 — brand',
    step3: 'Step 3 — the pairs',
    sizeTitle: "What's your size?",
    sizeLede: 'Pick it once. From there you only see pairs that are actually on the shelf in your size.',
    allSizes: 'Show every size',
    allSizesHint: 'Not sure of your size? Browse the whole shop instead.',
    euLabel: 'EU sizing',
    adultSizes: 'Adult',
    kidsSizes: 'Kids',
    noSizes: 'No sizes on record yet.',
    brandTitle: 'Pick a brand',
    brandLedeSized: (d) => `Counts below are pairs in stock in size ${d.size}.`,
    brandLedeAll: 'Counts below are pairs in stock across every size.',
    allBrands: 'All brands',
    productsTitle: 'In your size',
    productsTitleAll: 'Everything in the shop',
    sort: 'Sort',
    sortName: 'Name: A to Z',
    sortPriceAsc: 'Price: low to high',
    sortPriceDesc: 'Price: high to low',
    searchPh: 'Search a model or colour…',
    pairs: 'pairs',
    onePair: '1 pair',
    noneHere: 'None in stock',
    countPairs: (d) => `${num(d.n)} ${d.n === 1 ? 'pair' : 'pairs'}`,
    loadMore: 'Show more',
    inStock: 'In stock',
    lowStock: (d) => `Only ${d.n} left`,
    lastOne: 'Last pair',
    soldOut: 'Sold out',
    yourSize: 'Your size',
    allSizesChip: 'All sizes',
    changeSize: 'Change size',
    changeBrand: 'Change brand',
    emptyTitle: 'Nothing in this size yet',
    emptyBody:
      'We have no pairs from this brand in your size right now. Try another brand, or browse every size.',
    emptyCta: 'Show every size',
    errorTitle: 'Something went wrong',
    retry: 'Try again',
    back: 'Back to the shop',
    notFound: "We couldn't find that product.",
    colour: 'Colour',
    chooseSize: 'Choose a size',
    sizeGuide: 'EU sizing',
    addToBag: 'Add to bag',
    pickSizeFirst: 'Pick a size',
    inStores: 'In our shops',
    details: 'Details',
    description: 'Description',
    qty: 'Quantity',
    noteOk: (d) => `${d.n} in stock in size ${d.size}`,
    noteLow: (d) => `Only ${d.n} left in size ${d.size}`,
    noteOut: (d) => `Size ${d.size} is sold out`,
    noteTotal: (d) => `${d.n} in stock across our shops`,
    notePick: 'Pick a size to see stock',
    pieces: 'in stock',
    outHere: 'Not here',
    specBrand: 'Brand',
    specColour: 'Colour',
    specSku: 'SKU',
    specBarcode: 'Barcode',
    specModel: 'Model',
    specRun: 'Size run',
    spin: '360° view',
    spinCount: (d) => `360° · ${d.i} / ${d.n}`,
    play: 'Spin',
    pause: 'Pause',
    yourBag: 'Your bag',
    close: 'Close',
    bagEmptyTitle: 'Your bag is empty',
    bagEmptyBody: "Pick your size and add a pair — it'll show up here.",
    bagEmptyCta: 'Find my size',
    fulfilment: 'How would you like it?',
    pickup: 'Collect in shop',
    delivery: 'Deliver to me',
    total: 'Total',
    checkout: 'Checkout',
    fineprint: 'Demo checkout. No payment is taken.',
    remove: 'Remove',
    size: 'Size',
    added: (d) => `${d.name} added to your bag`,
    removed: 'Removed from your bag',
    doneTitle: 'Order placed',
    doneBody: (d) => `Thanks — a demo order for ${d.total} was recorded. Nothing was charged.`,
    keepShopping: 'Keep shopping',
    shops: 'Shops',
    contact: 'Contact',
    hours: 'Open daily 10:00 – 22:00',
    footTag: 'Live stock from our shops. Demo bag — no online orders.',
    footBase: (d) => `${d.store} · POS & storefront`,
    footSample: 'Live stock',
    bag: 'Bag',
    currency: (n) => `${CURRENCY} ${num(n)}`,
    photos: (d) => `${d.i} / ${d.n}`,
  },
  ar: {
    title: (d) => `${d.store} — أحذية رياضية للجميع`,
    skip: 'تخطَّ إلى المنتجات',
    step1: 'الخطوة ١ — مقاسك',
    step2: 'الخطوة ٢ — العلامة',
    step3: 'الخطوة ٣ — الأزواج',
    sizeTitle: 'ما هو مقاسك؟',
    sizeLede: 'اختره مرة واحدة، وبعدها لن ترى إلا الأزواج المتوفرة فعلًا بمقاسك.',
    allSizes: 'اعرض كل المقاسات',
    allSizesHint: 'غير متأكد من مقاسك؟ تصفح المتجر كاملًا.',
    euLabel: 'المقاسات الأوروبية',
    adultSizes: 'الكبار',
    kidsSizes: 'الأطفال',
    noSizes: 'لا توجد مقاسات مسجلة بعد.',
    brandTitle: 'اختر علامة',
    brandLedeSized: (d) => `الأرقام أدناه هي الأزواج المتوفرة بمقاس ${d.size}.`,
    brandLedeAll: 'الأرقام أدناه هي الأزواج المتوفرة بكل المقاسات.',
    allBrands: 'كل العلامات',
    productsTitle: 'بمقاسك',
    productsTitleAll: 'كل ما في المتجر',
    sort: 'ترتيب',
    sortName: 'الاسم: أ إلى ي',
    sortPriceAsc: 'السعر: من الأقل',
    sortPriceDesc: 'السعر: من الأعلى',
    searchPh: 'ابحث عن موديل أو لون…',
    pairs: 'أزواج',
    onePair: 'زوج واحد',
    noneHere: 'غير متوفر',
    countPairs: (d) => `${num(d.n)} ${d.n === 1 ? 'زوج' : 'أزواج'}`,
    loadMore: 'عرض المزيد',
    inStock: 'متوفر',
    lowStock: (d) => `بقي ${d.n} فقط`,
    lastOne: 'آخر زوج',
    soldOut: 'نفد',
    yourSize: 'مقاسك',
    allSizesChip: 'كل المقاسات',
    changeSize: 'تغيير المقاس',
    changeBrand: 'تغيير العلامة',
    emptyTitle: 'لا يوجد بهذا المقاس بعد',
    emptyBody: 'لا تتوفر لدينا أزواج من هذه العلامة بمقاسك حاليًا. جرّب علامة أخرى أو تصفح كل المقاسات.',
    emptyCta: 'اعرض كل المقاسات',
    errorTitle: 'حدث خطأ ما',
    retry: 'حاول مجددًا',
    back: 'العودة إلى المتجر',
    notFound: 'لم نعثر على هذا المنتج.',
    colour: 'اللون',
    chooseSize: 'اختر المقاس',
    sizeGuide: 'المقاسات الأوروبية',
    addToBag: 'أضف إلى الحقيبة',
    pickSizeFirst: 'اختر مقاسًا',
    inStores: 'في فروعنا',
    details: 'التفاصيل',
    description: 'الوصف',
    qty: 'الكمية',
    noteOk: (d) => `${d.n} متوفرة بمقاس ${d.size}`,
    noteLow: (d) => `بقي ${d.n} فقط بمقاس ${d.size}`,
    noteOut: (d) => `المقاس ${d.size} نفد`,
    noteTotal: (d) => `${d.n} متوفرة في فروعنا`,
    notePick: 'اختر مقاسًا لعرض التوفر',
    pieces: 'متوفرة',
    outHere: 'غير متوفر',
    specBrand: 'العلامة',
    specColour: 'اللون',
    specSku: 'الرمز',
    specBarcode: 'الباركود',
    specModel: 'الموديل',
    specRun: 'المقاسات',
    spin: 'عرض ٣٦٠°',
    spinCount: (d) => `٣٦٠° · ${d.i} / ${d.n}`,
    play: 'تدوير',
    pause: 'إيقاف',
    yourBag: 'حقيبتك',
    close: 'إغلاق',
    bagEmptyTitle: 'حقيبتك فارغة',
    bagEmptyBody: 'اختر مقاسك وأضف زوجًا — سيظهر هنا.',
    bagEmptyCta: 'ابحث عن مقاسي',
    fulfilment: 'كيف تريد الاستلام؟',
    pickup: 'الاستلام من الفرع',
    delivery: 'التوصيل إليّ',
    total: 'الإجمالي',
    checkout: 'إتمام الطلب',
    fineprint: 'طلب تجريبي. لا يتم تحصيل أي مبلغ.',
    remove: 'إزالة',
    size: 'المقاس',
    added: (d) => `تمت إضافة ${d.name} إلى حقيبتك`,
    removed: 'تمت الإزالة من حقيبتك',
    doneTitle: 'تم الطلب',
    doneBody: (d) => `شكرًا لك — تم تسجيل طلب تجريبي بقيمة ${d.total}. لم يتم تحصيل أي مبلغ.`,
    keepShopping: 'متابعة التسوق',
    shops: 'الفروع',
    contact: 'تواصل معنا',
    hours: 'يوميًا ١٠:٠٠ – ٢٢:٠٠',
    footTag: 'مخزون مباشر من فروعنا. حقيبة تجريبية — بدون طلبات إلكترونية.',
    footBase: (d) => `${d.store} · نقاط البيع والمتجر`,
    footSample: 'مخزون مباشر',
    bag: 'الحقيبة',
    currency: (n) => `${num(n)} ${AR_SYMBOL[CURRENCY] || CURRENCY}`,
    photos: (d) => `${d.i} / ${d.n}`,
  },
}

const STORAGE_KEY = 'sr.lang'

/** Reactive language state — templates that call t() re-render on change. */
export const i18n = reactive({ lang: 'en', dir: 'ltr' })

export function t(key, vars) {
  const table = STRINGS[i18n.lang] || STRINGS.en
  const v = table[key] ?? STRINGS.en[key]
  if (v == null) return key
  return typeof v === 'function' ? v(vars || {}) : v
}

/**
 * Localised field: `field(product, 'name')` returns `name_arabic` in Arabic
 * when the record has one, otherwise `name`.
 */
export function field(obj, key) {
  if (!obj) return ''
  if (i18n.lang === 'ar') {
    const ar = obj[`${key}_arabic`] ?? obj[`${key}Ar`]
    if (ar && String(ar).trim()) return ar
  }
  return obj[key] ?? ''
}

export const money = (n) => t('currency', n)

export function setLang(lang) {
  const next = lang === 'ar' ? 'ar' : 'en'
  i18n.lang = next
  i18n.dir = next === 'ar' ? 'rtl' : 'ltr'
  document.documentElement.lang = next
  document.documentElement.dir = i18n.dir
  try {
    localStorage.setItem(STORAGE_KEY, next)
  } catch {
    /* private mode */
  }
}

export function initLang() {
  let saved = null
  try {
    saved = localStorage.getItem(STORAGE_KEY)
  } catch {
    /* private mode */
  }
  setLang(saved === 'ar' ? 'ar' : 'en')
}

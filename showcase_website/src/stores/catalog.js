import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'

import { fetchBrands, fetchProducts, fetchSizes } from '@/api/resources'
import router from '@/router'
import { sortSizes } from '@/utils/catalog'

const STORAGE_KEY = 'sr.catalog'
const PER_PAGE = 24

/** UI sort key → API sort_by / sort_direction. */
export const SORTS = {
  name: ['name', 'asc'],
  priceAsc: ['price', 'asc'],
  priceDesc: ['price', 'desc'],
}

/**
 * The filtration system: size → brand → pairs. Size and brand persist per
 * device (sr.catalog); every value is mirrored into the route query so a
 * catalogue URL can be shared.
 */
export const useCatalogStore = defineStore('catalog', () => {
  // ---- selections -------------------------------------------------------
  const size = ref(null) // null (nothing picked yet) | 'all' | '42'
  const brand = ref(null) // brand id | null (= all brands)
  const brandName = ref('') // cached label so the header chip works before /brands answers
  const q = ref('')
  const sort = ref('name')

  // ---- data -------------------------------------------------------------
  const sizes = ref({ adult: [], young: [] })
  const sizesLoading = ref(false)
  const sizesError = ref(null)

  const brands = ref([])
  const brandsLoading = ref(false)
  const brandsError = ref(null)

  const products = ref([])
  const pagination = ref(null)
  const productsLoading = ref(false)
  const loadingMore = ref(false)
  const productsError = ref(null)

  // ---- derived ----------------------------------------------------------
  /** A concrete size is chosen (not null, not "all"). */
  const sized = computed(() => Boolean(size.value) && size.value !== 'all')
  const brandLabel = computed(
    () => brands.value.find((b) => b.id === brand.value)?.name || brandName.value || '',
  )
  const brandsTotal = computed(() =>
    brands.value.reduce((n, b) => n + (Number(b.product_count) || 0), 0),
  )
  const productCount = computed(() => pagination.value?.total ?? products.value.length)
  /**
   * One ruler for the whole shop: adult and kids sizes merged into a single
   * ascending run (numbers first, letter sizes after). A size string that the
   * backend files under both groups collapses to one tick.
   */
  const allSizes = computed(() => {
    const bySize = new Map()
    for (const s of [...sizes.value.adult, ...sizes.value.young]) {
      const prev = bySize.get(s.size)
      bySize.set(s.size, prev
        ? { size: s.size, stock_total: prev.stock_total + s.stock_total, in_stock: prev.in_stock || s.in_stock }
        : { ...s })
    }
    return sortSizes([...bySize.values()], (s) => s.size)
  })

  // ---- persistence ------------------------------------------------------
  function restore() {
    try {
      const s = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null')
      if (s) {
        size.value = s.size ?? null
        brand.value = s.brand ?? null
        brandName.value = s.brandName || ''
      }
    } catch {
      /* ignore */
    }
  }
  function save() {
    try {
      localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({ size: size.value, brand: brand.value, brandName: brandName.value }),
      )
    } catch {
      /* ignore */
    }
  }
  restore()

  // ---- route mirroring --------------------------------------------------
  function toQuery() {
    const query = {}
    if (size.value) query.size = size.value
    if (brand.value) query.brand = String(brand.value)
    if (q.value) query.q = q.value
    if (sort.value !== 'name') query.sort = sort.value
    return query
  }

  function hasQuery(query) {
    return ['size', 'brand', 'q', 'sort'].some((k) => query?.[k] != null && query[k] !== '')
  }

  /** Apply a route query to the state (idempotent — unchanged refs don't retrigger loads). */
  function applyQuery(query) {
    size.value = query?.size ? String(query.size) : null
    brand.value = query?.brand ? Number(query.brand) || null : null
    q.value = query?.q ? String(query.q) : ''
    sort.value = SORTS[query?.sort] ? query.sort : 'name'
    save()
  }

  /** Push the current state into the URL (replace on the catalogue, push from elsewhere). */
  function sync() {
    const target = { name: 'catalogue', query: toQuery() }
    return router.currentRoute.value.name === 'catalogue'
      ? router.replace(target)
      : router.push(target)
  }

  // ---- actions ----------------------------------------------------------
  function setSize(value) {
    size.value = size.value === value ? null : value
    save()
    return sync()
  }
  function clearSize() {
    size.value = null
    save()
    return sync()
  }
  function setBrand(b) {
    if (!b || brand.value === b.id) {
      brand.value = null
      brandName.value = ''
    } else {
      brand.value = b.id
      brandName.value = b.name || ''
    }
    save()
    return sync()
  }
  function clearBrand() {
    brand.value = null
    brandName.value = ''
    save()
    return sync()
  }
  function setQuery(value) {
    q.value = String(value || '').trim()
    return sync()
  }
  function setSort(value) {
    sort.value = SORTS[value] ? value : 'name'
    return sync()
  }

  // ---- loaders ----------------------------------------------------------
  // Each loader is keyed on the filters it depends on, so the route watcher
  // and ensureLoaded() can both call it without duplicate requests; a
  // sequence number discards stale responses.
  let sizesKey = null
  let sizesSeq = 0
  async function loadSizes(force = false) {
    if (!force && sizesKey === 'all') return
    sizesKey = 'all'
    const seq = ++sizesSeq
    sizesLoading.value = true
    sizesError.value = null
    try {
      const data = await fetchSizes()
      if (seq !== sizesSeq) return
      sizes.value = {
        adult: sortSizes(data.adult, (s) => s.size),
        young: sortSizes(data.young, (s) => s.size),
      }
    } catch (e) {
      if (seq !== sizesSeq) return
      sizesError.value = e.message
      sizesKey = null
    } finally {
      if (seq === sizesSeq) sizesLoading.value = false
    }
  }

  let brandsKey = null
  let brandsSeq = 0
  async function loadBrands(force = false) {
    const key = sized.value ? size.value : ''
    if (!force && key === brandsKey) return
    brandsKey = key
    const seq = ++brandsSeq
    brandsLoading.value = true
    brandsError.value = null
    try {
      const list = await fetchBrands({ size: sized.value ? size.value : null })
      if (seq !== brandsSeq) return
      brands.value = Array.isArray(list) ? list : []
    } catch (e) {
      if (seq !== brandsSeq) return
      brandsError.value = e.message
      brandsKey = null
    } finally {
      if (seq === brandsSeq) brandsLoading.value = false
    }
  }

  function productParams() {
    const [sortBy, sortDirection] = SORTS[sort.value] || SORTS.name
    return {
      size: sized.value ? size.value : null,
      brand_id: brand.value,
      search: q.value || null,
      sort_by: sortBy,
      sort_direction: sortDirection,
      in_stock_only: 1,
      type: 'product',
      per_page: PER_PAGE,
    }
  }

  let productsKey = null
  let productsSeq = 0
  async function loadProducts(page = 1, force = false) {
    const key = JSON.stringify(productParams())
    if (page === 1 && !force && key === productsKey) return
    productsKey = key
    const seq = ++productsSeq
    if (page === 1) {
      productsLoading.value = true
      productsError.value = null
    } else {
      loadingMore.value = true
    }
    try {
      const result = await fetchProducts({ ...productParams(), page })
      if (seq !== productsSeq) return
      pagination.value = result?.pagination || null
      const rows = Array.isArray(result?.data) ? result.data : []
      products.value = page === 1 ? rows : [...products.value, ...rows]
    } catch (e) {
      if (seq !== productsSeq) return
      productsError.value = e.message
      if (page === 1) productsKey = null
    } finally {
      if (seq === productsSeq) {
        productsLoading.value = false
        loadingMore.value = false
      }
    }
  }

  function loadMore() {
    if (pagination.value?.has_more_pages && !productsLoading.value && !loadingMore.value) {
      loadProducts(pagination.value.current_page + 1)
    }
  }

  /** Kick off whatever hasn't been fetched for the current selections. */
  function ensureLoaded() {
    loadSizes()
    loadBrands()
    loadProducts(1)
  }

  // Brand counts depend on the size; the grid depends on everything.
  watch(size, () => loadBrands())
  watch([size, brand, q, sort], () => loadProducts(1))

  return {
    size,
    brand,
    brandName,
    q,
    sort,
    sizes,
    sizesLoading,
    sizesError,
    brands,
    brandsLoading,
    brandsError,
    products,
    pagination,
    productsLoading,
    loadingMore,
    productsError,
    sized,
    brandLabel,
    brandsTotal,
    productCount,
    allSizes,
    toQuery,
    hasQuery,
    applyQuery,
    setSize,
    clearSize,
    setBrand,
    clearBrand,
    setQuery,
    setSort,
    loadSizes,
    loadBrands,
    loadProducts,
    loadMore,
    ensureLoaded,
  }
})

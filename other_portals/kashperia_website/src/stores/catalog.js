import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { fetchBranches, fetchCategories, fetchProducts } from '@/api/resources'
import { slugify } from '@/format'

/**
 * Collections as the header and footer name them, in the reference order. Each
 * resolves to the live main category whose name matches (case-insensitive,
 * "Gift Boxes" also answers to "Gifting" / "Gifts"). Live categories beyond
 * these four still appear in the collection sidebar.
 */
export const HOUSE_COLLECTIONS = [
  { slug: 'men', label: 'Men', nav: 'MEN', aliases: ['men', 'mens', "men's"] },
  { slug: 'women', label: 'Women', nav: 'WOMEN', aliases: ['women', 'womens', "women's", 'ladies'] },
  { slug: 'kids', label: 'Kids', nav: 'KIDS', aliases: ['kids', 'children', 'boys', 'girls'] },
  { slug: 'gift-boxes', label: 'Gift Boxes', nav: 'GIFTING', aliases: ['gift boxes', 'gift box', 'gifting', 'gifts'] },
]

/** Storefront listing defaults: one card per size/colour family, online-visible only. */
export const LISTING = { type: 'product', online_only: 1, group_by_family: 1, in_stock_only: 0 }

const PAGE_SIZE = 100
const MAX_PAGES = 5

export const useCatalogStore = defineStore('catalog', () => {
  const categories = ref([])
  const categoriesLoaded = ref(false)
  const shops = ref([])
  /** Products per collection key, fetched once per visit: { [key]: { items, loading, error } } */
  const collections = ref({})
  let categoriesPromise = null

  function loadCategories() {
    if (!categoriesPromise) {
      categoriesPromise = fetchCategories({ type: 'product' })
        .then((list) => {
          categories.value = Array.isArray(list) ? list : []
        })
        .catch(() => {
          categories.value = []
          categoriesPromise = null
        })
        .finally(() => {
          categoriesLoaded.value = true
        })
    }
    return categoriesPromise
  }

  async function loadShops() {
    if (shops.value.length) return
    try {
      const list = await fetchBranches()
      shops.value = Array.isArray(list) ? list : []
    } catch {
      /* pickup choice simply stays empty */
    }
  }

  /** The live category behind a collection slug, or null when the tenant has none. */
  function categoryFor(slug) {
    const house = HOUSE_COLLECTIONS.find((c) => c.slug === slug)
    const names = house ? house.aliases : [slug.replace(/-/g, ' ')]
    return (
      categories.value.find((c) => names.includes(String(c.name).trim().toLowerCase())) ||
      categories.value.find((c) => slugify(c.name) === slug) ||
      null
    )
  }

  /** Sidebar / menu collections: the four house collections that exist live, then any other category. */
  const collectionLinks = computed(() => {
    const used = new Set()
    const links = []
    for (const house of HOUSE_COLLECTIONS) {
      const category = categoryFor(house.slug)
      if (category) used.add(category.id)
      if (category || !categoriesLoaded.value || !categories.value.length) {
        links.push({ slug: house.slug, label: house.label, nav: house.nav, category })
      }
    }
    for (const category of categories.value) {
      if (!used.has(category.id)) {
        links.push({ slug: slugify(category.name), label: category.name, nav: category.name.toUpperCase(), category })
      }
    }
    return links
  })

  /**
   * Every family in a collection (or the whole shop for key "all"), paged through
   * up to MAX_PAGES × 100 rows. Sub-category, price and sort are applied in the
   * view, so switching them never waits on the network.
   */
  async function loadCollection(key, params = {}) {
    const existing = collections.value[key]
    if (existing && (existing.loading || existing.items.length)) return existing
    const entry = { items: [], loading: true, error: null }
    collections.value = { ...collections.value, [key]: entry }
    try {
      for (let page = 1; page <= MAX_PAGES; page++) {
        const res = await fetchProducts({ ...LISTING, ...params, per_page: PAGE_SIZE, page, sort_by: 'priority', sort_direction: 'desc' })
        entry.items.push(...(Array.isArray(res?.data) ? res.data : []))
        if (!res?.pagination?.has_more_pages) break
      }
    } catch (e) {
      entry.error = e.message
    } finally {
      entry.loading = false
      collections.value = { ...collections.value, [key]: { ...entry } }
    }
    return collections.value[key]
  }

  return { categories, categoriesLoaded, shops, collections, collectionLinks, loadCategories, loadShops, categoryFor, loadCollection }
})

<script setup>
import { computed, onBeforeUnmount, ref, watch, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import ProductCard from '@/components/ProductCard.vue'
import { slugify } from '@/format'
import { HOUSE_COLLECTIONS, useCatalogStore } from '@/stores/catalog'
import { priceOf } from '@/utils/catalog'

/**
 * A collection (main category) on the live catalogue: every family in it, refined
 * by sub-category, price and sort in the browser — the reference's "Refine your
 * selection" sidebar, with its mobile chips, filter sheet and sort toggle.
 * `curated="bestsellers"` lists the whole House by merchandising priority.
 */
const props = defineProps({ curated: { type: String, default: null } })

const route = useRoute()
const router = useRouter()
const catalog = useCatalogStore()

const slug = computed(() => (props.curated ? null : String(route.params.slug || '')))
const house = computed(() => HOUSE_COLLECTIONS.find((c) => c.slug === slug.value))
const category = computed(() => (slug.value ? catalog.categoryFor(slug.value) : null))
const title = computed(() => (props.curated ? 'Bestsellers' : category.value?.name || house.value?.label || slug.value.replace(/-/g, ' ')))
const key = computed(() => (props.curated ? 'all' : category.value ? `c${category.value.id}` : null))

const entry = computed(() => (key.value ? catalog.collections[key.value] : null))
const loading = computed(() => !catalog.categoriesLoaded || (key.value && (!entry.value || entry.value.loading)))
const items = computed(() => entry.value?.items || [])

watchEffect(() => {
  if (!key.value) return
  catalog.loadCollection(key.value, category.value ? { main_category_id: category.value.id } : {})
})

watchEffect(() => {
  document.title = `${title.value} — KASHPERIA`
})

// ---- refine (mirrored to the query: ?category=shawls&min=&max=&sort=az) ------------
const subCategories = computed(() => {
  const seen = new Map()
  for (const p of items.value) {
    const s = p.sub_category
    if (s?.name && !seen.has(s.id)) seen.set(s.id, { id: s.id, name: s.name, slug: slugify(s.name) })
  }
  return [...seen.values()].sort((a, b) => a.name.localeCompare(b.name))
})
const selectedSub = computed(() => String(route.query.category || ''))
const minPrice = computed(() => String(route.query.min || ''))
const maxPrice = computed(() => String(route.query.max || ''))
const sort = computed(() => String(route.query.sort || ''))
const SORTS = ['', 'az', 'za', 'priceAsc', 'priceDesc']
const SORT_LABEL = { '': '↕ SORT', az: 'A–Z ↑', za: 'Z–A ↓', priceAsc: 'PRICE ↑', priceDesc: 'PRICE ↓' }

function refine(patch) {
  const query = { ...route.query, ...patch }
  for (const k of Object.keys(query)) if (query[k] === '' || query[k] == null) delete query[k]
  router.replace({ query })
}
function chooseCategory(subSlug) {
  refine({ category: subSlug })
}
function clearFilters() {
  router.replace({ query: {} })
}
function cycleSort() {
  refine({ sort: SORTS[(SORTS.indexOf(sort.value) + 1) % SORTS.length] })
}

let priceTimer = null
function setPrice(which, value) {
  clearTimeout(priceTimer)
  priceTimer = setTimeout(() => refine({ [which]: value }), 300)
}

const visible = computed(() => {
  const min = Number(minPrice.value) || 0
  const max = Number(maxPrice.value) || 0
  let list = items.value.filter((p) => {
    if (selectedSub.value && slugify(p.sub_category?.name) !== selectedSub.value) return false
    const price = priceOf(p).now
    if (min && price < min) return false
    if (max && price > max) return false
    return true
  })
  const byName = (a, b) => String(a.name).localeCompare(String(b.name))
  const byPrice = (a, b) => priceOf(a).now - priceOf(b).now
  if (sort.value === 'az') list = [...list].sort(byName)
  if (sort.value === 'za') list = [...list].sort((a, b) => byName(b, a))
  if (sort.value === 'priceAsc') list = [...list].sort(byPrice)
  if (sort.value === 'priceDesc') list = [...list].sort((a, b) => byPrice(b, a))
  return list
})

const activeFilters = computed(() => {
  const chips = []
  const sub = subCategories.value.find((s) => s.slug === selectedSub.value)
  if (sub) chips.push({ label: sub.name, clear: { category: '' } })
  if (minPrice.value) chips.push({ label: `From QAR ${minPrice.value}`, clear: { min: '' } })
  if (maxPrice.value) chips.push({ label: `Up to QAR ${maxPrice.value}`, clear: { max: '' } })
  return chips
})

const lead = computed(() =>
  props.curated
    ? 'Our most sought-after creations.'
    : `Discover the complete ${title.value.toLowerCase()} collection. Refine your selection by collection, category and price.`,
)

// ---- mobile filter sheet ---------------------------------------------------------
const filtersOpen = ref(false)
watch(filtersOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})
watch(() => route.fullPath, () => {
  if (route.name !== 'collection' || route.params.slug !== slug.value) filtersOpen.value = false
})
onBeforeUnmount(() => {
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="page collection-page" :class="{ 'k-curated': curated }">
    <span class="eyebrow">{{ curated ? 'THE HOUSE OF KASHPERIA' : 'THE KASHPERIA BOUTIQUE' }}</span>
    <h1>{{ title }}</h1>
    <p class="pageLead">{{ lead }}</p>

    <div v-if="catalog.categoriesLoaded && !curated && !category" class="k-curated-empty">
      <h2 class="serif">A selection in preparation</h2>
      <p>The {{ title.toLowerCase() }} collection will be presented here once it is confirmed in the catalogue.</p>
      <RouterLink to="/collection/women" custom v-slot="{ navigate }">
        <button type="button" class="k-side-clear" @click="navigate">EXPLORE COLLECTIONS →</button>
      </RouterLink>
    </div>

    <div v-else class="k-catalog-layout">
      <div id="kCatalogSidebar">
        <aside class="k-catalog-sidebar" aria-label="Collection filters">
          <h2 class="k-side-title">REFINE YOUR SELECTION</h2>
          <div class="k-side-block">
            <div class="k-side-label">COLLECTION</div>
            <RouterLink v-for="link in catalog.collectionLinks" :key="link.slug" :to="`/collection/${link.slug}`" custom v-slot="{ navigate }">
              <button type="button" class="k-side-link" :class="{ selected: link.slug === slug }" @click="navigate">{{ link.label }}</button>
            </RouterLink>
          </div>
          <div v-if="subCategories.length" class="k-side-block">
            <div class="k-side-label">CATEGORY</div>
            <button type="button" class="k-side-link" :class="{ selected: !selectedSub }" @click="chooseCategory('')">All {{ title }}</button>
            <button v-for="s in subCategories" :key="s.id" type="button" class="k-side-link" :class="{ selected: selectedSub === s.slug }" @click="chooseCategory(s.slug)">{{ s.name }}</button>
          </div>
          <div class="k-side-block">
            <div class="k-side-label">DISCOVER</div>
            <RouterLink to="/curated/bestsellers" custom v-slot="{ navigate }"><button type="button" class="k-side-link" :class="{ selected: curated }" @click="navigate">Bestsellers</button></RouterLink>
            <RouterLink to="/page/limited-editions" custom v-slot="{ navigate }"><button type="button" class="k-side-link" @click="navigate">Limited Editions</button></RouterLink>
          </div>
          <div class="k-side-block">
            <div class="k-side-label">PRICE RANGE · QAR</div>
            <div class="k-price-pair">
              <input type="number" min="0" step="1" placeholder="Min" aria-label="Minimum price" :value="minPrice" @input="setPrice('min', $event.target.value)" />
              <span>—</span>
              <input type="number" min="0" step="1" placeholder="Max" aria-label="Maximum price" :value="maxPrice" @input="setPrice('max', $event.target.value)" />
            </div>
          </div>
          <button type="button" class="k-side-clear" @click="clearFilters">CLEAR FILTERS</button>
        </aside>
      </div>

      <main class="k-catalog-main">
        <nav v-if="subCategories.length" class="k-mobile-top-categories" aria-label="Browse product categories">
          <div class="k-mobile-chip-track">
            <button type="button" class="k-mobile-category-chip" :class="{ 'is-active': !selectedSub }" :aria-pressed="!selectedSub" @click="chooseCategory('')">All {{ title }}</button>
            <button v-for="s in subCategories" :key="s.id" type="button" class="k-mobile-category-chip" :class="{ 'is-active': selectedSub === s.slug }" :aria-pressed="selectedSub === s.slug" @click="chooseCategory(s.slug)">{{ s.name }}</button>
          </div>
          <div class="k-mobile-discover">
            <span>DISCOVER</span>
            <RouterLink to="/curated/bestsellers" custom v-slot="{ navigate }"><button type="button" @click="navigate">Bestsellers</button></RouterLink>
            <RouterLink to="/page/limited-editions" custom v-slot="{ navigate }"><button type="button" @click="navigate">Limited Editions</button></RouterLink>
          </div>
        </nav>
        <div class="k-mobile-catalog-tools">
          <button type="button" aria-label="Open collection filters" @click="filtersOpen = true">☷ &nbsp; FILTER</button>
          <button type="button" aria-label="Change product sorting" @click="cycleSort">{{ SORT_LABEL[sort] }}</button>
        </div>
        <div class="collection-toolbar">
          <div>
            <span class="eyebrow">EXPLORE THE COLLECTION</span>
            <p class="collection-count">{{ loading ? 'Loading the collection…' : `${visible.length} of ${items.length} styles` }}</p>
          </div>
          <label class="k-sort">
            <span class="sr-only">Sort</span>
            <select :value="sort" aria-label="Sort the collection" @change="refine({ sort: $event.target.value })">
              <option value="">Featured</option>
              <option value="az">Name A–Z</option>
              <option value="za">Name Z–A</option>
              <option value="priceAsc">Price, low to high</option>
              <option value="priceDesc">Price, high to low</option>
            </select>
          </label>
        </div>
        <div class="active-filters">
          <button v-for="chip in activeFilters" :key="chip.label" type="button" class="k-filter-chip" @click="refine(chip.clear)">{{ chip.label }} ×</button>
        </div>

        <div v-if="loading" class="productGrid" aria-busy="true">
          <div v-for="n in 6" :key="n" class="product k-skeleton"><div class="productImg"></div><div class="productInfo"><span></span><span></span></div></div>
        </div>
        <div v-else-if="entry?.error" class="k-curated-empty">
          <h2 class="serif">The collection could not be loaded</h2>
          <p>{{ entry.error }}</p>
        </div>
        <div v-else-if="!visible.length" class="k-curated-empty">
          <h2 class="serif">{{ items.length ? 'No creations match' : 'A selection in preparation' }}</h2>
          <p>{{ items.length ? 'Try another category or widen the price range.' : 'These creations will be presented here once they are confirmed in the catalogue.' }}</p>
          <button v-if="items.length" type="button" class="k-side-clear" @click="clearFilters">CLEAR FILTERS</button>
        </div>
        <div v-else id="collectionProducts" class="productGrid">
          <ProductCard v-for="p in visible" :key="p.id" :product="p" />
        </div>
      </main>
    </div>

    <template v-if="filtersOpen">
      <div class="k-mobile-filter-backdrop" @click="filtersOpen = false"></div>
      <section class="k-mobile-filter-panel" role="dialog" aria-modal="true" aria-label="Refine collection">
        <div class="k-mobile-filter-head">
          <strong>REFINE YOUR SELECTION</strong>
          <button type="button" aria-label="Close filters" @click="filtersOpen = false">×</button>
        </div>
        <div id="k-mobile-filter-content">
          <aside class="k-catalog-sidebar" aria-label="Collection filters">
            <div class="k-side-block">
              <div class="k-side-label">COLLECTION</div>
              <RouterLink v-for="link in catalog.collectionLinks" :key="link.slug" :to="`/collection/${link.slug}`" custom v-slot="{ navigate }">
                <button type="button" class="k-side-link" :class="{ selected: link.slug === slug }" @click="navigate">{{ link.label }}</button>
              </RouterLink>
            </div>
            <div v-if="subCategories.length" class="k-side-block">
              <div class="k-side-label">CATEGORY</div>
              <button type="button" class="k-side-link" :class="{ selected: !selectedSub }" @click="chooseCategory('')">All {{ title }}</button>
              <button v-for="s in subCategories" :key="s.id" type="button" class="k-side-link" :class="{ selected: selectedSub === s.slug }" @click="chooseCategory(s.slug)">{{ s.name }}</button>
            </div>
            <div class="k-side-block">
              <div class="k-side-label">PRICE RANGE · QAR</div>
              <div class="k-price-pair">
                <input type="number" min="0" step="1" placeholder="Min" aria-label="Minimum price" :value="minPrice" @input="setPrice('min', $event.target.value)" />
                <span>—</span>
                <input type="number" min="0" step="1" placeholder="Max" aria-label="Maximum price" :value="maxPrice" @input="setPrice('max', $event.target.value)" />
              </div>
            </div>
            <button type="button" class="k-side-clear" @click="clearFilters">CLEAR FILTERS</button>
          </aside>
        </div>
        <div class="k-mobile-filter-foot"><button type="button" @click="filtersOpen = false">VIEW RESULTS</button></div>
      </section>
    </template>
  </div>
</template>

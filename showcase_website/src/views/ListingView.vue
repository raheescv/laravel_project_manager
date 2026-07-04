<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { fetchProducts } from '@/api/resources'
import EmptyState from '@/components/EmptyState.vue'
import ErrorBanner from '@/components/ErrorBanner.vue'
import FilterSidebar from '@/components/FilterSidebar.vue'
import ProductCard from '@/components/ProductCard.vue'
import SkeletonCard from '@/components/SkeletonCard.vue'
import { useBranchStore } from '@/stores/branch'
import { useFilterStore } from '@/stores/filters'

const branchStore = useBranchStore()
const filters = useFilterStore()

const products = ref([])
const pagination = ref(null)
const loading = ref(true)
const loadingMore = ref(false)
const error = ref(null)
const searchInput = ref(filters.search)
const drawerOpen = ref(false)
const sentinel = ref(null)

let searchTimer = null
let requestSeq = 0
let observer = null

const canLoadMore = computed(() => pagination.value?.has_more_pages && !loading.value)

function loadMore() {
  if (canLoadMore.value && !loadingMore.value) {
    load(pagination.value.current_page + 1)
  }
}

const listingTitle = computed(() => {
  const parts = []
  if (filters.brand) parts.push(filters.brand.name)
  parts.push(filters.category?.name || 'All products')
  if (filters.size) parts.push(`· size ${filters.size}`)
  return parts.join(' ')
})

const sortValue = computed({
  get: () => `${filters.sortBy}:${filters.sortDirection}`,
  set: (v) => {
    const [by, dir] = v.split(':')
    filters.sortBy = by
    filters.sortDirection = dir
  },
})

async function load(page = 1) {
  const seq = ++requestSeq
  if (page === 1) {
    loading.value = true
    error.value = null
  } else {
    loadingMore.value = true
  }
  try {
    const result = await fetchProducts({
      ...filters.productParams,
      branch_id: branchStore.currentId,
      per_page: 15,
      page,
    })
    if (seq !== requestSeq) return // stale response, a newer request superseded it
    pagination.value = result.pagination
    products.value = page === 1 ? result.data : [...products.value, ...result.data]
  } catch (e) {
    if (seq !== requestSeq) return
    error.value = e.message
  } finally {
    if (seq === requestSeq) {
      loading.value = false
      loadingMore.value = false
    }
  }
}

onMounted(() => {
  load()
  // Auto-load the next page when the sentinel scrolls into view (pre-fetch 400px early).
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting) loadMore()
    },
    { rootMargin: '400px 0px' },
  )
})

// The sentinel only mounts after the first page renders — (re)observe it whenever it appears.
watch(sentinel, (el, prev) => {
  if (prev) observer?.unobserve(prev)
  if (el) observer?.observe(el)
})

onBeforeUnmount(() => {
  observer?.disconnect()
  clearTimeout(searchTimer)
})

watch(
  () => [
    branchStore.currentId,
    filters.category?.id,
    filters.brand?.id,
    filters.size,
    filters.color,
    filters.minPrice,
    filters.maxPrice,
    filters.search,
    filters.sortBy,
    filters.sortDirection,
  ],
  () => load(1),
)

watch(searchInput, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    filters.search = value.trim()
  }, 350)
})

function clearAll() {
  filters.reset()
  searchInput.value = ''
}
</script>

<template>
  <main class="container listing anim-screen">
    <div class="listing__head">
      <h1 class="listing__title">{{ listingTitle }}</h1>
      <div class="listing__count">
        <span class="listing__count-num">{{ pagination?.total ?? '…' }}</span>
        products at {{ branchStore.currentName }}
      </div>
    </div>

    <!-- Top controls: search + sort + mobile filter toggle -->
    <div class="listing__controls">
      <input
        v-model="searchInput"
        class="listing__search"
        type="search"
        placeholder="Search products…"
      />
      <select v-model="sortValue" class="listing__select">
        <option value="name:asc">Name A–Z</option>
        <option value="name:desc">Name Z–A</option>
        <option value="price:asc">Price low → high</option>
        <option value="price:desc">Price high → low</option>
      </select>
      <button class="listing__filter-btn" @click="drawerOpen = true">
        Filters
        <span v-if="filters.activeCount" class="listing__filter-count">{{ filters.activeCount }}</span>
      </button>
    </div>

    <!-- Two-column layout is ALWAYS rendered so the filter sidebar stays put —
         even when a filter yields no results — and you can adjust or clear it.
         Error / loading / empty states render inside the results column. -->
    <div class="listing__layout">
      <!-- Sidebar (inline on desktop, drawer on mobile) -->
      <div class="listing__side" :class="{ 'listing__side--open': drawerOpen }">
        <div class="listing__side-scrim" @click="drawerOpen = false"></div>
        <div class="listing__side-panel">
          <button class="listing__side-close" @click="drawerOpen = false">Done</button>
          <FilterSidebar />
        </div>
      </div>

      <!-- Results -->
      <div class="listing__results">
        <!-- Error -->
        <div v-if="error" class="listing__state">
          <ErrorBanner :message="error" @retry="load(1)" />
        </div>

        <!-- Loading first page -->
        <div v-else-if="loading" class="listing__grid">
          <SkeletonCard v-for="n in 6" :key="n" />
        </div>

        <!-- No matches — sidebar remains beside this so filters can be changed -->
        <div v-else-if="!products.length" class="listing__state">
          <EmptyState />
          <button v-if="filters.activeCount" class="btn-dark listing__clear" @click="clearAll">
            Clear filters
          </button>
        </div>

        <!-- Results -->
        <template v-else>
          <div class="listing__grid">
            <ProductCard v-for="p in products" :key="p.id" :product="p" />
            <!-- Extra skeletons appended while the next page streams in -->
            <template v-if="loadingMore">
              <SkeletonCard v-for="n in 3" :key="`more-${n}`" />
            </template>
          </div>

          <!-- Infinite-scroll trigger -->
          <div ref="sentinel" class="listing__sentinel">
            <span v-if="loadingMore" class="listing__spinner"></span>
            <span v-else-if="!pagination?.has_more_pages" class="listing__end">
              You've reached the end · {{ pagination?.total }} products
            </span>
          </div>
        </template>
      </div>
    </div>
  </main>
</template>

<style scoped>
.listing {
  padding-top: 52px;
  padding-bottom: 96px;
}

.listing__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.listing__title {
  font-size: 42px;
  font-weight: 700;
  margin: 0;
}

.listing__count {
  font-size: 14px;
  color: var(--muted);
}

.listing__count-num {
  font-weight: 700;
  color: var(--ink);
}

/* ===== Top controls ===== */
.listing__controls {
  display: flex;
  gap: 10px;
  margin: 26px 0 30px;
  flex-wrap: wrap;
  align-items: center;
}

.listing__search,
.listing__select {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 99px;
  padding: 10px 18px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.15s ease;
}

.listing__search:focus,
.listing__select:focus {
  border-color: var(--gold);
}

.listing__search {
  flex: 1;
  min-width: 200px;
}

.listing__filter-btn {
  display: none;
  align-items: center;
  gap: 8px;
  background: var(--dark);
  color: var(--bg);
  border-radius: 99px;
  padding: 10px 22px;
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
}

.listing__filter-count {
  background: var(--gold);
  color: var(--dark);
  border-radius: 99px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
}

/* Empty / error state — centred inside the results column (the filter sidebar
   stays beside it, so filters can still be changed). */
.listing__state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  min-height: 46vh;
  gap: 20px;
}

.listing__clear {
  align-self: center;
}

/* ===== Layout ===== */
/* Flexbox (not grid) so the results column reliably fills all remaining width and
   never collapses to its content — keeps the same width for 1 or 2656 products. */
.listing__layout {
  display: flex;
  align-items: flex-start;
  gap: 32px;
  width: 100%;
}

/* Fixed-width filter column; sticky so the card stays in view while the grid scrolls.
   max-width + min-width:0 defeat the flexbox `min-width:auto` trap where the card's
   content would otherwise force the column far wider than 264px. */
.listing__side {
  flex: 0 0 264px;
  width: 264px;
  max-width: 264px;
  min-width: 0;
  position: sticky;
  top: 94px;
}

.listing__results {
  flex: 1 1 auto;
  min-width: 0; /* lets the flex item shrink below its content's intrinsic width */
}

.listing__grid {
  display: grid;
  /* Responsive: as many ~240px columns as fit (3–4+ by screen width), each filling
     its share of the row. */
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 22px;
  min-height: 40vh;
  align-content: start;
}

.listing__sentinel {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60px;
  margin-top: 36px;
}

.listing__spinner {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  border: 3px solid var(--line);
  border-top-color: var(--gold);
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.listing__end {
  font-size: 13px;
  color: var(--muted-2);
  letter-spacing: 0.02em;
}

/* ===== Drawer (mobile) scaffolding, hidden on desktop ===== */
.listing__side-scrim,
.listing__side-close {
  display: none;
}

@media (max-width: 900px) {
  .listing__filter-btn {
    display: inline-flex;
  }

  /* Sidebar becomes an off-canvas drawer */
  .listing__side {
    position: fixed;
    inset: 0;
    z-index: 80;
    pointer-events: none;
    visibility: hidden;
  }

  .listing__side--open {
    pointer-events: auto;
    visibility: visible;
  }

  .listing__side-scrim {
    display: block;
    position: absolute;
    inset: 0;
    background: rgba(18, 16, 12, 0.55);
    backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.25s ease;
  }

  .listing__side--open .listing__side-scrim {
    opacity: 1;
  }

  .listing__side-panel {
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: min(340px, 86vw);
    background: var(--bg);
    padding: 20px 16px;
    overflow-y: auto;
    transform: translateX(-100%);
    transition: transform 0.3s var(--ease-out);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3);
  }

  .listing__side--open .listing__side-panel {
    transform: translateX(0);
  }

  .listing__side-close {
    display: block;
    width: 100%;
    background: var(--dark);
    color: var(--bg);
    border-radius: 99px;
    padding: 12px;
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 16px;
    cursor: pointer;
  }

  /* Reset sticky positioning inside the drawer */
  .listing__side-panel :deep(.fs) {
    position: static;
    max-height: none;
    border: none;
    box-shadow: none;
    padding: 0;
  }
}

@media (max-width: 640px) {
  .listing__title {
    font-size: 30px;
  }

  .listing__grid {
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  }
}
</style>

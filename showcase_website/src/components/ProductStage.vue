<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import ErrorBox from '@/components/ErrorBox.vue'
import ProductCard from '@/components/ProductCard.vue'
import SkeletonCard from '@/components/SkeletonCard.vue'
import { t } from '@/i18n'
import { useCatalogStore } from '@/stores/catalog'

const catalog = useCatalogStore()

const searchInput = ref(catalog.q)
const sentinel = ref(null)
let searchTimer = null
let observer = null

const sortOptions = computed(() => [
  { value: 'name', label: t('sortName') },
  { value: 'priceAsc', label: t('sortPriceAsc') },
  { value: 'priceDesc', label: t('sortPriceDesc') },
])

const countText = computed(() =>
  catalog.productsLoading ? '' : t('countPairs', { n: catalog.productCount }),
)

// Typing → debounced store update; back/forward → input follows the store.
watch(searchInput, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    if (value.trim() !== catalog.q) catalog.setQuery(value)
  }, 250)
})
watch(
  () => catalog.q,
  (value) => {
    if (value !== searchInput.value.trim()) searchInput.value = value
  },
)

onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting) catalog.loadMore()
    },
    { rootMargin: '400px 0px' },
  )
})
watch(sentinel, (el, prev) => {
  if (prev) observer?.unobserve(prev)
  if (el) observer?.observe(el)
})
onBeforeUnmount(() => {
  observer?.disconnect()
  clearTimeout(searchTimer)
})
</script>

<template>
  <section id="products" class="stage stage--products">
    <div class="wrap">
      <header class="stage__head">
        <p class="eyebrow">{{ t('step3') }}</p>
        <h2 class="stage__title">
          {{ catalog.sized ? t('productsTitle') : t('productsTitleAll') }}
        </h2>
      </header>

      <div class="toolbar">
        <label class="field field--search">
          <svg class="field__icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
          </svg>
          <span class="sr-only">{{ t('searchPh') }}</span>
          <input
            v-model="searchInput"
            type="search"
            :placeholder="t('searchPh')"
            autocomplete="off"
          />
        </label>
        <div class="field field--sort">
          <span class="field__tag">{{ t('sort') }}</span>
          <select :value="catalog.sort" @change="catalog.setSort($event.target.value)">
            <option v-for="o in sortOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </div>
        <span class="toolbar__count">{{ countText }}</span>
      </div>
    </div>

    <div id="productGrid" class="wrap">
      <ErrorBox
        v-if="catalog.productsError"
        :message="catalog.productsError"
        @retry="catalog.loadProducts(1, true)"
      />

      <div v-else-if="catalog.productsLoading" class="grid grid--products" aria-busy="true">
        <SkeletonCard v-for="n in 8" :key="n" />
      </div>

      <div v-else-if="!catalog.products.length" class="empty">
        <span class="empty__rule"></span>
        <p class="empty__title">{{ t('emptyTitle') }}</p>
        <p class="empty__body">{{ t('emptyBody') }}</p>
        <button class="btn btn--primary" @click="catalog.setSize('all')">{{ t('emptyCta') }}</button>
      </div>

      <template v-else>
        <div class="grid grid--products">
          <ProductCard v-for="p in catalog.products" :key="p.id" :product="p" />
        </div>
        <div ref="sentinel" class="more">
          <span v-if="catalog.loadingMore" class="spinner" role="status"></span>
          <button
            v-else-if="catalog.pagination?.has_more_pages"
            class="btn btn--dark"
            @click="catalog.loadMore()"
          >
            {{ t('loadMore') }}
          </button>
          <span v-else class="more__end">{{ t('countPairs', { n: catalog.productCount }) }}</span>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup>
import { nextTick, reactive } from 'vue'

import ErrorBox from '@/components/ErrorBox.vue'
import { t } from '@/i18n'
import { useCatalogStore } from '@/stores/catalog'

const catalog = useCatalogStore()

// Logos that fail to load (missing file, bad URL) fall back to the monogram.
const brokenLogos = reactive(new Set())

function countLabel(n) {
  const count = Number(n) || 0
  if (count === 0) return t('noneHere')
  if (count === 1) return t('onePair')
  return `${count.toLocaleString('en-US')} ${t('pairs')}`
}

async function pick(brand) {
  await catalog.setBrand(brand)
  await nextTick()
  setTimeout(() => {
    document.getElementById('products')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 60)
}
</script>

<template>
  <section id="brands" class="stage stage--brands">
    <div class="wrap">
      <header class="stage__head">
        <p class="eyebrow">{{ t('step2') }}</p>
        <h2 class="stage__title">{{ t('brandTitle') }}</h2>
        <p class="lede">
          {{ catalog.sized ? t('brandLedeSized', { size: catalog.size }) : t('brandLedeAll') }}
        </p>
      </header>
    </div>

    <div id="brandGrid" class="wrap">
      <ErrorBox
        v-if="catalog.brandsError"
        :message="catalog.brandsError"
        @retry="catalog.loadBrands(true)"
      />

      <div v-else-if="catalog.brandsLoading && !catalog.brands.length" class="grid grid--brands" aria-busy="true">
        <div v-for="n in 10" :key="n" class="skel skel--brand"></div>
      </div>

      <div v-else class="grid grid--brands" :class="{ 'is-busy': catalog.brandsLoading }">
        <button
          class="brand"
          :class="{ 'is-on': !catalog.brand }"
          :aria-pressed="!catalog.brand"
          @click="pick(null)"
        >
          <span class="brand__well brand__well--all">
            <span class="brand__mono">ALL</span>
          </span>
          <span class="brand__meta">
            <span class="brand__name">{{ t('allBrands') }}</span>
            <span class="brand__count">{{ countLabel(catalog.brandsTotal) }}</span>
          </span>
        </button>

        <button
          v-for="b in catalog.brands"
          :key="b.id"
          class="brand"
          :class="{ 'is-on': catalog.brand === b.id, 'is-empty': !b.product_count }"
          :disabled="!b.product_count"
          :aria-pressed="catalog.brand === b.id"
          @click="pick(b)"
        >
          <span class="brand__well">
            <img
              v-if="b.image_path && !brokenLogos.has(b.id)"
              :src="b.image_path"
              alt=""
              loading="lazy"
              @error="brokenLogos.add(b.id)"
            />
            <span v-else class="brand__mono">{{ b.name }}</span>
          </span>
          <span class="brand__meta">
            <span class="brand__name">{{ b.name }}</span>
            <span class="brand__count">{{ countLabel(b.product_count) }}</span>
          </span>
        </button>
      </div>
    </div>
  </section>
</template>

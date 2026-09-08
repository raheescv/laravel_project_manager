<script setup>
import { nextTick } from 'vue'

import ErrorBox from '@/components/ErrorBox.vue'
import { t } from '@/i18n'
import { useCatalogStore } from '@/stores/catalog'
import { brandMark } from '@/utils/catalog'

const catalog = useCatalogStore()

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
          <span class="brand__mark"><span>ALL</span></span>
          <span class="brand__name">{{ t('allBrands') }}</span>
          <span class="brand__count">{{ countLabel(catalog.brandsTotal) }}</span>
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
          <span class="brand__mark">
            <img v-if="b.image_path" :src="b.image_path" :alt="b.name" loading="lazy" />
            <span v-else>{{ brandMark(b.name) }}</span>
          </span>
          <span class="brand__name">{{ b.name }}</span>
          <span class="brand__count">{{ countLabel(b.product_count) }}</span>
        </button>
      </div>
    </div>
  </section>
</template>

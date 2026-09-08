<script setup>
import { nextTick } from 'vue'

import ErrorBox from '@/components/ErrorBox.vue'
import SizeRail from '@/components/SizeRail.vue'
import { t } from '@/i18n'
import { useCatalogStore } from '@/stores/catalog'

const catalog = useCatalogStore()

async function pick(size) {
  await catalog.setSize(size)
  await nextTick()
  // Picking a size hands you to the brands; un-picking stays put.
  if (catalog.size) {
    setTimeout(() => {
      document.getElementById('brands')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }, 60)
  }
}
</script>

<template>
  <section id="size" class="stage stage--size">
    <div class="wrap size__grid">
      <div class="size__copy">
        <p class="eyebrow eyebrow--light">{{ t('step1') }}</p>
        <h1 class="hero__title">{{ t('sizeTitle') }}</h1>
        <p class="hero__lede">{{ t('sizeLede') }}</p>
        <div class="size__escape">
          <button
            class="btn btn--ghost-light"
            :aria-pressed="catalog.size === 'all'"
            @click="pick('all')"
          >
            {{ t('allSizes') }}
          </button>
          <span class="hint">{{ t('allSizesHint') }}</span>
        </div>
      </div>

      <ErrorBox v-if="catalog.sizesError" :message="catalog.sizesError" @retry="catalog.loadSizes(true)" />

      <div v-else-if="catalog.sizesLoading" class="size__rail" aria-busy="true">
        <div class="rail__meta"><span class="rail__label">{{ t('euLabel') }}</span></div>
        <div class="skel-row">
          <span v-for="n in 12" :key="n" class="skel skel--tick"></span>
        </div>
      </div>

      <template v-else>
        <SizeRail
          v-if="catalog.sizes.adult.length"
          :label="`${t('euLabel')} · ${t('adultSizes')}`"
          :sizes="catalog.sizes.adult"
          @pick="pick"
        />
        <SizeRail
          v-if="catalog.sizes.young.length"
          :label="`${t('euLabel')} · ${t('kidsSizes')}`"
          :sizes="catalog.sizes.young"
          @pick="pick"
        />
        <p v-if="!catalog.sizes.adult.length && !catalog.sizes.young.length" class="rail__empty">
          {{ t('noSizes') }}
        </p>
      </template>
    </div>
  </section>
</template>

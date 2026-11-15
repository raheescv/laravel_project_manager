<script setup>
import { computed, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { storeName } from '@/branding'
import BrandLogo from '@/components/BrandLogo.vue'
import { i18n, setLang, t } from '@/i18n'
import { useBagStore } from '@/stores/bag'
import { useCatalogStore } from '@/stores/catalog'

const route = useRoute()
const router = useRouter()
const catalog = useCatalogStore()
const bag = useBagStore()

const sizeChip = computed(() => {
  if (!catalog.size) return ''
  return catalog.size === 'all' ? t('allSizesChip') : `${t('yourSize')} ${catalog.size}`
})

/** Scroll to a catalogue stage — from the product page, go there first. */
async function jump(id) {
  if (route.name !== 'catalogue') {
    await router.push({ name: 'catalogue', query: catalog.toQuery() })
    await nextTick()
  }
  setTimeout(() => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 40)
}
</script>

<template>
  <header class="head">
    <div class="wrap head__bar">
      <router-link
        class="head__logo"
        :to="{ name: 'catalogue', query: catalog.toQuery() }"
        :aria-label="storeName"
      >
        <BrandLogo />
      </router-link>

      <div id="filterChips" class="head__filters">
        <span v-if="catalog.size" class="chip">
          <button class="chip__label" @click="jump('size')">{{ sizeChip }}</button>
          <button class="chip__x" :aria-label="t('changeSize')" @click="catalog.clearSize()">
            &times;
          </button>
        </span>
        <span v-if="catalog.brand" class="chip">
          <button class="chip__label" @click="jump('brands')">{{ catalog.brandLabel }}</button>
          <button class="chip__x" :aria-label="t('changeBrand')" @click="catalog.clearBrand()">
            &times;
          </button>
        </span>
      </div>

      <div class="head__tools">
        <div class="lang" role="group" aria-label="Language">
          <button class="lang__btn" :aria-pressed="i18n.lang === 'en'" @click="setLang('en')">
            EN
          </button>
          <button class="lang__btn" :aria-pressed="i18n.lang === 'ar'" @click="setLang('ar')">
            ع
          </button>
        </div>
        <button class="bagbtn" :aria-label="`${t('bag')} (${bag.count})`" @click="bag.show()">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 7h12l-1 13H7L6 7Z" />
            <path d="M9 7V5.5a3 3 0 0 1 6 0V7" />
          </svg>
          <span v-if="bag.count" class="bagbtn__n">{{ bag.count }}</span>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'

import { storeName } from '@/branding'
import AppFooter from '@/components/AppFooter.vue'
import AppHeader from '@/components/AppHeader.vue'
import BagDrawer from '@/components/BagDrawer.vue'
import ToastHost from '@/components/ToastHost.vue'
import { i18n, t } from '@/i18n'
import { useBagStore } from '@/stores/bag'
import { useShopsStore } from '@/stores/shops'

const route = useRoute()
const bag = useBagStore()
const shops = useShopsStore()

// Re-key the view on real page changes so the entrance animation replays.
const viewKey = computed(() =>
  route.name === 'product' ? `product-${route.params.id}` : 'catalogue',
)

// The bag drawer locks page scroll by fixing <body>; remember where we were.
let scrollY = 0
watch(
  () => bag.open,
  (open) => {
    const body = document.body
    if (open) {
      scrollY = window.scrollY
      body.style.top = `-${scrollY}px`
      body.classList.add('bag-open')
    } else {
      body.classList.remove('bag-open')
      body.style.top = ''
      window.scrollTo(0, scrollY)
    }
  },
)

watch(
  () => i18n.lang,
  () => {
    document.title = t('title', { store: storeName })
  },
  { immediate: true },
)

function onKey(e) {
  if (e.key === 'Escape' && bag.open) bag.close()
}
function onScroll() {
  document.documentElement.classList.toggle('is-scrolled', window.scrollY > 4)
}
function skipToProducts() {
  document.getElementById('products')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

onMounted(() => {
  document.addEventListener('keydown', onKey)
  window.addEventListener('scroll', onScroll, { passive: true })
  shops.load()
})
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKey)
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <button class="skip" @click="skipToProducts">{{ t('skip') }}</button>

  <AppHeader />

  <main id="view" :key="viewKey">
    <router-view />
  </main>

  <AppFooter />

  <div id="scrim" @click="bag.close()"></div>
  <BagDrawer />
  <ToastHost />
</template>

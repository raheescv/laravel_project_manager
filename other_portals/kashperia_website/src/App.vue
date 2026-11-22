<script setup>
import { onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import SiteDrawer from '@/components/SiteDrawer.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import SiteHeader from '@/components/SiteHeader.vue'
import { toastState } from '@/toast'
import { useBagStore } from '@/stores/bag'
import { useCatalogStore } from '@/stores/catalog'

const bag = useBagStore()
const catalog = useCatalogStore()
const route = useRoute()
const router = useRouter()

onMounted(() => {
  catalog.loadCategories()
  bag.loadConfig()
  // Back from Tap's payment page: show the outcome on the checkout page.
  if (bag.takeRedirect()) {
    router.replace({ name: 'checkout' }).then(() => bag.confirm())
  }
})

// Any navigation closes the menu / search / bag drawer, as the reference does.
watch(
  () => route.fullPath,
  () => bag.closeDrawer(),
)
</script>

<template>
  <SiteHeader />
  <div id="k-app">
    <RouterView />
  </div>
  <SiteFooter />
  <SiteDrawer />
  <div class="k-toast" :class="{ on: toastState.on }" role="status" aria-live="polite">{{ toastState.message }}</div>
</template>

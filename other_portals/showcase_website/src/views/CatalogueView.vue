<script setup>
import { watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import BrandStage from '@/components/BrandStage.vue'
import ProductStage from '@/components/ProductStage.vue'
import SizeStage from '@/components/SizeStage.vue'
import { useCatalogStore } from '@/stores/catalog'

const route = useRoute()
const router = useRouter()
const catalog = useCatalogStore()

// A shared URL wins; a bare #/ restores the device's last size + brand.
if (catalog.hasQuery(route.query)) catalog.applyQuery(route.query)
else router.replace({ name: 'catalogue', query: catalog.toQuery() })

// Back / forward and hand-edited URLs.
watch(
  () => route.query,
  (query) => {
    if (route.name === 'catalogue') catalog.applyQuery(query)
  },
)

catalog.ensureLoaded()
</script>

<template>
  <SizeStage />
  <BrandStage />
  <ProductStage />
</template>

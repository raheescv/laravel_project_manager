<script setup>
import { watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import BrandStage from '@/components/BrandStage.vue'
import ProductStage from '@/components/ProductStage.vue'
import SizeStage from '@/components/SizeStage.vue'
import { fetchBrands } from '@/api/resources'
import { useCatalogStore } from '@/stores/catalog'
import { collectionFilters } from '@/utils/catalog'

const route = useRoute()
const router = useRouter()
const catalog = useCatalogStore()

// An old store link (/collections/onitsuka-tiger, redirected by index.html as
// #/?collection=onitsuka-tiger) becomes the brand and search it names. The
// device's size stays: the store shows pairs on the shelf in your size.
if (route.query.collection != null) {
  const handle = String(route.query.collection)
  fetchBrands()
    .then((list) => collectionFilters(handle, Array.isArray(list) ? list : []))
    .catch(() => collectionFilters(handle, []))
    .then((filters) => {
      catalog.applyQuery({ size: catalog.size, brand: filters.brand, q: filters.q })
      catalog.brandName = filters.brandName
      router.replace({ name: 'catalogue', query: catalog.toQuery() })
    })
} else if (catalog.hasQuery(route.query)) {
  // A shared URL wins; a bare #/ restores the device's last size + brand.
  catalog.applyQuery(route.query)
} else {
  router.replace({ name: 'catalogue', query: catalog.toQuery() })
}

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

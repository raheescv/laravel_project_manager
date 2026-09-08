import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { fetchBranches } from '@/api/resources'

/** Showcase-visible shops: footer list + the per-shop stock rows on a product. */
export const useShopsStore = defineStore('shops', () => {
  const shops = ref([])
  const loading = ref(false)
  const loaded = ref(false)

  const ids = computed(() => new Set(shops.value.map((s) => s.id)))

  async function load(force = false) {
    if (loading.value || (loaded.value && !force)) return
    loading.value = true
    try {
      const list = await fetchBranches()
      shops.value = Array.isArray(list) ? list : []
      loaded.value = true
    } catch {
      /* footer just stays empty */
    } finally {
      loading.value = false
    }
  }

  return { shops, loading, loaded, ids, load }
})

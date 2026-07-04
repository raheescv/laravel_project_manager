import { defineStore } from 'pinia'

export const useFilterStore = defineStore('filters', {
  state: () => ({
    category: null, // { id, name }
    brand: null, // { id, name }
    size: null, // string
    color: null, // string
    minPrice: null, // number
    maxPrice: null, // number
    search: '',
    sortBy: 'name', // name | price
    sortDirection: 'asc',
  }),

  getters: {
    /** Chips shown on the listing screen, each linking back to its wizard step. */
    activeChips(state) {
      const chips = []
      if (state.category) chips.push({ label: 'Category', value: state.category.name, to: '/' })
      if (state.brand) chips.push({ label: 'Brand', value: state.brand.name, to: '/brands' })
      if (state.size) chips.push({ label: 'Size', value: state.size, to: '/sizes' })
      return chips
    },

    /** Number of active (non-default) filters — shown on the mobile toggle. */
    activeCount(state) {
      let n = 0
      if (state.category) n++
      if (state.brand) n++
      if (state.size) n++
      if (state.color) n++
      if (state.minPrice != null || state.maxPrice != null) n++
      if (state.search) n++
      return n
    },

    /** Query params for GET /products (branch added by the caller). */
    productParams(state) {
      return {
        main_category_id: state.category?.id ?? null,
        brand_id: state.brand?.id ?? null,
        size: state.size,
        color: state.color,
        min_price: state.minPrice ?? null,
        max_price: state.maxPrice ?? null,
        search: state.search || null,
        sort_by: state.sortBy,
        sort_direction: state.sortDirection,
        type: 'product',
      }
    },
  },

  actions: {
    reset() {
      this.$reset()
    },
  },
})

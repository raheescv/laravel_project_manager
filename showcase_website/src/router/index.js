import { createRouter, createWebHashHistory } from 'vue-router'

/**
 * Hash routing (#/, #/product/123) — matches the reference storefront and lets
 * the built bundle be dropped into any folder without server rewrite rules.
 * Catalogue state travels in the query string: ?size=42&brand=11&q=dunk&sort=priceAsc
 */
const router = createRouter({
  history: createWebHashHistory(),
  scrollBehavior(to, from, savedPosition) {
    // Changing a filter only rewrites the query — never yank the page around.
    if (to.name === 'catalogue' && from.name === 'catalogue') return false
    if (savedPosition) return savedPosition
    return { top: 0 }
  },
  routes: [
    { path: '/', name: 'catalogue', component: () => import('@/views/CatalogueView.vue') },
    {
      path: '/product/:id',
      name: 'product',
      component: () => import('@/views/ProductView.vue'),
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

export default router

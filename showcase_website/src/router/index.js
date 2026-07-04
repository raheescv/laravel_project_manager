import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    { path: '/', name: 'home', component: () => import('@/views/HomeView.vue') },
    { path: '/brands', name: 'brands', component: () => import('@/views/BrandView.vue') },
    { path: '/sizes', name: 'sizes', component: () => import('@/views/SizeView.vue') },
    { path: '/products', name: 'products', component: () => import('@/views/ListingView.vue') },
    {
      path: '/product/:id',
      name: 'product',
      component: () => import('@/views/ProductDetailView.vue'),
    },
    { path: '/branches', name: 'branches', component: () => import('@/views/BranchesView.vue') },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

export default router

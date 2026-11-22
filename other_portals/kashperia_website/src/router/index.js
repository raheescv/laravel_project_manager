import { createRouter, createWebHistory } from 'vue-router'

const PageView = () => import('@/views/PageView.vue')

/** Anchors of the reference site (index.html#kmu-about) → Maison sections. */
const LEGACY_ANCHORS = {
  'kmu-about': 'about',
  'maison-about': 'about',
  'maison-top': 'about',
  'kmu-craft': 'craftsmanship',
  'maison-craftsmanship': 'craftsmanship',
  'kmu-materials': 'materials',
  'maison-materials': 'materials',
}

/**
 * Clean URLs (/, /collection/women, /product/emerald, /maison/about) under the
 * build's base folder. Editorial pages are content fragments rendered by
 * PageView; the collections, product page and checkout run on /api/v1.
 */
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    // Refining a collection only rewrites the query — never yank the page around.
    if (to.name === 'collection' && from.name === 'collection' && to.params.slug === from.params.slug) return false
    // The Maison sections scroll themselves once their content is on the page.
    if (to.name === 'maison' && to.params.section) return false
    if (savedPosition) return savedPosition
    return { top: 0 }
  },
  routes: [
    { path: '/', name: 'home', component: PageView, props: { page: 'home' } },
    { path: '/maison/:section?', name: 'maison', component: PageView, props: { page: 'maison' } },
    { path: '/stories', name: 'stories', component: PageView, props: { page: 'stories' } },
    { path: '/boutiques', name: 'boutiques', component: PageView, props: { page: 'boutiques' } },
    { path: '/contact', name: 'contact', component: PageView, props: { page: 'contact' } },
    { path: '/page/:slug', name: 'page', component: PageView, props: (route) => ({ page: route.params.slug }) },
    { path: '/collection/:slug', name: 'collection', component: () => import('@/views/CollectionView.vue') },
    {
      path: '/curated/bestsellers',
      name: 'bestsellers',
      component: () => import('@/views/CollectionView.vue'),
      props: { curated: 'bestsellers' },
    },
    { path: '/product/:slug', name: 'product', component: () => import('@/views/ProductView.vue') },
    { path: '/checkout', name: 'checkout', component: () => import('@/views/CheckoutView.vue') },
    {
      path: '/:pathMatch(.*)*',
      // /kmu-about and friends; unknown paths go home.
      redirect: (to) => {
        const key = String(to.params.pathMatch?.[0] ?? to.path).replace(/^\/+/, '')
        return LEGACY_ANCHORS[key] ? `/maison/${LEGACY_ANCHORS[key]}` : '/'
      },
    },
  ],
})

// The reference's anchors typed onto an open page (no reload, so index.html
// never sees them) still open their Maison section.
window.addEventListener('hashchange', () => {
  const section = LEGACY_ANCHORS[window.location.hash.slice(1)]
  if (section) router.replace(`/maison/${section}`)
})

export default router

import { createRouter, createWebHashHistory } from 'vue-router'

import { session } from '@/session'

/**
 * Hash routing (#/, #/students/12): the built bundle works from any folder with
 * no server rewrite rules, and the API can link straight to a page — invite
 * emails open #/set-password/{token}, QPay returns to #/topups/{pun}.
 */
const router = createRouter({
  history: createWebHashHistory(),
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    // Switching tabs / months on the same child keeps the page where it is.
    if (to.name === from.name && to.params.id === from.params.id) return false
    return { top: 0 }
  },
  routes: [
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { guest: true } },
    { path: '/forgot-password', name: 'forgot', component: () => import('@/views/ForgotPasswordView.vue'), meta: { guest: true } },
    // Reachable signed in too: a reset link must work without signing out first.
    { path: '/set-password/:token', name: 'set-password', component: () => import('@/views/SetPasswordView.vue'), meta: { public: true } },

    { path: '/', name: 'home', component: () => import('@/views/HomeView.vue') },
    { path: '/students/:id(\\d+)', name: 'student', component: () => import('@/views/StudentView.vue') },
    { path: '/students/:id(\\d+)/bills/:saleId(\\d+)', name: 'bill', component: () => import('@/views/BillView.vue') },
    { path: '/students/:id(\\d+)/topup', name: 'topup', component: () => import('@/views/TopupView.vue') },
    { path: '/students/:id(\\d+)/pre-orders', name: 'pre-orders', component: () => import('@/views/PreOrdersView.vue') },
    { path: '/students/:id(\\d+)/pre-orders/weekly', name: 'pre-order-weekly', component: () => import('@/views/PreOrderEditView.vue') },
    {
      path: '/students/:id(\\d+)/pre-orders/days/:date(\\d{4}-\\d{2}-\\d{2})',
      name: 'pre-order-day',
      component: () => import('@/views/PreOrderEditView.vue'),
    },
    { path: '/topups/:pun', name: 'topup-result', component: () => import('@/views/TopupResultView.vue') },

    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

/** A page inside the portal to return to after sign-in — never another site. */
export function safeRedirect(value) {
  const path = String(value || '')
  return path.startsWith('/') && !path.startsWith('//') ? path : null
}

router.beforeEach((to) => {
  if (to.meta.public) return true
  // Already signed in: skip sign-in and go where the parent was heading.
  if (to.meta.guest) return session.signedIn ? safeRedirect(to.query.redirect) || { name: 'home' } : true
  // Keep where the parent was going (e.g. the QPay result page) for after sign-in.
  if (!session.signedIn) return { name: 'login', query: to.fullPath !== '/' ? { redirect: to.fullPath } : {} }
  return true
})

export default router

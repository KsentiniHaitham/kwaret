import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const adminMeta = { requiresAuth: true, requiresAdmin: true }

const routes = [
  { path: '/',         name: 'home',     component: () => import('@/pages/HomePage.vue') },
  { path: '/shop',     name: 'shop',     component: () => import('@/pages/ShopPage.vue') },
  { path: '/product/:slug', name: 'product', component: () => import('@/pages/ProductPage.vue') },
  { path: '/cart',     name: 'cart',     component: () => import('@/pages/CartPage.vue') },
  { path: '/checkout', name: 'checkout', component: () => import('@/pages/CheckoutPage.vue'), meta: { requiresAuth: true } },
  { path: '/login',         name: 'login',         component: () => import('@/pages/LoginPage.vue'),        meta: { guestOnly: true } },
  { path: '/register',      name: 'register',      component: () => import('@/pages/RegisterPage.vue'),    meta: { guestOnly: true } },
  { path: '/auth/callback', name: 'auth-callback', component: () => import('@/pages/OAuthCallbackPage.vue') },
  { path: '/orders',   name: 'orders',   component: () => import('@/pages/OrdersPage.vue'), meta: { requiresAuth: true } },
  { path: '/wallet',   name: 'wallet',   component: () => import('@/pages/WalletPage.vue'),  meta: { requiresAuth: true } },
  { path: '/support',  name: 'support',  component: () => import('@/pages/SupportPage.vue'),  meta: { requiresAuth: true } },

  // Admin — nested layout
  {
    path: '/admin',
    component: () => import('@/components/AdminLayout.vue'),
    meta: adminMeta,
    children: [
      { path: '',           name: 'admin',            component: () => import('@/pages/admin/AdminOverview.vue') },
      { path: 'products',   name: 'admin-products',   component: () => import('@/pages/admin/AdminProducts.vue') },
      { path: 'categories', name: 'admin-categories', component: () => import('@/pages/admin/AdminCategories.vue') },
      { path: 'orders',     name: 'admin-orders',     component: () => import('@/pages/admin/AdminOrders.vue') },
      { path: 'users',      name: 'admin-users',      component: () => import('@/pages/admin/AdminUsers.vue') },
      { path: 'recharges',  name: 'admin-recharges',  component: () => import('@/pages/admin/AdminRecharges.vue') },
      { path: 'tickets',    name: 'admin-tickets',    component: () => import('@/pages/admin/AdminTickets.vue') },
      { path: 'stats',      name: 'admin-stats',      component: () => import('@/pages/admin/AdminStats.vue') },
    ],
  },

  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.meta.requiresAdmin && !auth.user?.roles?.includes('ROLE_ADMIN')) {
    return { name: 'home' }
  }
  // Rediriger les utilisateurs déjà connectés vers l'accueil
  if (to.meta.guestOnly && auth.isLoggedIn) {
    return { name: 'home' }
  }
})

export default router

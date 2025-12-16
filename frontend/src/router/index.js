import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import OrdersView from '../views/OrdersView.vue'
import { auth } from '../utils/store/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta:{guest:true},
  },
  {
    path: '/',
    name: 'orders',
    component: OrdersView,
    meta:{requiresAuth:true}
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  if (auth.loading) return true

  if (to.meta.requiresAuth && !auth.user) {
    return { name: 'login' }
  }

  if (to.meta.guest && auth.user) {
    return { name: 'orders' }
  }

  return true
})

export default router
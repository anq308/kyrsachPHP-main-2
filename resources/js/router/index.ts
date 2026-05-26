import { createRouter, createWebHistory } from 'vue-router';
import { loadSession, sessionState } from '../session';

import HomePage from '../pages/HomePage.vue';
import CatalogPage from '../pages/CatalogPage.vue';
import MotorcyclePage from '../pages/MotorcyclePage.vue';
import ServicePage from '../pages/ServicePage.vue';
import ContactsPage from '../pages/ContactsPage.vue';
import ComparePage from '../pages/ComparePage.vue';
import CartPage from '../pages/CartPage.vue';
import CheckoutPage from '../pages/CheckoutPage.vue';
import LoginPage from '../pages/LoginPage.vue';
import RegisterPage from '../pages/RegisterPage.vue';
import ProfilePage from '../pages/ProfilePage.vue';
import FavoritesPage from '../pages/FavoritesPage.vue';
import AdminPage from '../pages/AdminPage.vue';
import NotFoundPage from '../pages/NotFoundPage.vue';

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    { path: '/', name: 'home', component: HomePage },
    { path: '/catalog', name: 'catalog', component: CatalogPage },
    { path: '/motorcycle/:id', name: 'motorcycle', component: MotorcyclePage, props: true },
    { path: '/service', name: 'service', component: ServicePage },
    { path: '/contacts', name: 'contacts', component: ContactsPage },
    { path: '/compare', name: 'compare', component: ComparePage },
    { path: '/cart', name: 'cart', component: CartPage },
    { path: '/checkout', name: 'checkout', component: CheckoutPage },
    { path: '/login', name: 'login', component: LoginPage, meta: { requiresGuest: true } },
    { path: '/register', name: 'register', component: RegisterPage, meta: { requiresGuest: true } },
    { path: '/profile', name: 'profile', component: ProfilePage, meta: { requiresAuth: true } },
    { path: '/favorites', name: 'favorites', component: FavoritesPage, meta: { requiresAuth: true } },
    { path: '/admin', name: 'admin', component: AdminPage, meta: { requiresAdmin: true } },
    { path: '/admin/dashboard', redirect: '/admin' },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundPage },
  ],
});

router.beforeEach(async (to) => {
  if (!sessionState.initialized) {
    try {
      await loadSession();
    } catch {
      sessionState.initialized = true;
    }
  }

  if (to.meta.requiresAdmin) {
    if (!sessionState.user) {
      return { name: 'login' };
    }

    if (!sessionState.user.is_admin) {
      return { name: 'home' };
    }
  }

  if (to.meta.requiresAuth && !sessionState.user) {
    return { name: 'login' };
  }

  if (to.meta.requiresGuest && sessionState.user) {
    return { name: 'home' };
  }

  return true;
});

export default router;

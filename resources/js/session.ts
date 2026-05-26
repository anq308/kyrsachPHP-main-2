import { reactive } from 'vue';
import api, { setCsrfToken } from './api';
import type { User } from './types';

export const sessionState = reactive({
  user: null as User | null,
  cartCount: 0,
  compareCount: 0,
  initialized: false,
});

export async function loadSession(): Promise<void> {
  const response = await api.get('/bootstrap');
  sessionState.user = response.data.user;
  sessionState.cartCount = response.data.cart_count ?? 0;
  sessionState.compareCount = response.data.compare_count ?? 0;
  sessionState.initialized = true;
  setCsrfToken(response.data.csrf_token);
}

export async function refreshMe(): Promise<void> {
  const response = await api.get('/me');
  sessionState.user = response.data.user;
}

export function clearSession(): void {
  sessionState.user = null;
  sessionState.cartCount = 0;
  sessionState.compareCount = 0;
}

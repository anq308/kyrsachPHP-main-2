import axios from 'axios';

const appBasePath = import.meta.env.BASE_URL.replace(/\/$/, '');
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || `${appBasePath}/api`;

const api = axios.create({
  baseURL: apiBaseUrl,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
  },
});

const csrfToken = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute('content');

export function setCsrfToken(token?: string | null): void {
  if (!token) {
    return;
  }

  api.defaults.headers.common['X-CSRF-TOKEN'] = token;

  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta) {
    meta.setAttribute('content', token);
  }
}

export async function refreshCsrfToken(): Promise<string | null> {
  const response = await api.get('/csrf-token');
  const token = response.data?.csrf_token ?? null;
  setCsrfToken(token);

  return token;
}

setCsrfToken(csrfToken);

export default api;

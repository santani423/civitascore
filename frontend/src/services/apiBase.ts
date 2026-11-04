/**
 * Satu-satunya sumber base URL API untuk browser. Browser memanggil backend
 * langsung (tanpa proxy) ke alamat VITE_API_BASE_URL — lihat .env.example
 * dan .env.production.
 */
export const API_BASE_URL = (
  (import.meta.env.VITE_API_BASE_URL as string | undefined) || 'http://localhost:8000/api/v1'
).replace(/\/+$/, '')

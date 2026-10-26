/**
 * Satu-satunya sumber base URL API untuk browser. Selalu relative (same-origin):
 * dev server Vite / web server produksi yang meneruskan `/api` ke backend
 * (lihat vite.config.ts dan public/.htaccess). Alamat backend asli tidak
 * pernah masuk ke bundle — jangan menambah variabel VITE_* untuk itu.
 */
export const API_BASE_URL = '/api/v1'

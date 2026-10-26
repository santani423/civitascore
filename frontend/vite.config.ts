import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv, type ProxyOptions } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Variabel tanpa prefix VITE_ hanya dibaca di sini (Node) — tidak pernah
  // masuk ke bundle browser. Target proxy tetap, bukan dari input pengguna.
  const env = loadEnv(mode, process.cwd(), '')
  const target = env.API_PROXY_TARGET || 'http://localhost:8000'

  const proxy: Record<string, ProxyOptions> = {
    '/api': {
      target,
      changeOrigin: true, // kirim Host backend (dibutuhkan virtual host / resolusi tenant)
      secure: true, // validasi TLS tetap aktif
      autoRewrite: true, // Location redirect backend ditulis ulang ke host frontend
      timeout: 60_000,
    },
  }

  return {
    plugins: [react()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: { port: 5173, proxy },
    preview: { proxy },
  }
})

import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import path from 'node:path'

// Vite config — https://vitejs.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    // Local machine only. Run `pnpm dev --host` to share on your network.
    host: 'localhost',
    port: 8443,
  },
  preview: {
    host: 'localhost',
    port: 8443,
  },
})

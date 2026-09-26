import { defineConfig } from 'vite'
import { fileURLToPath, URL } from 'node:url'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      ssr: 'resources/js/ssr.js',
      refresh: true,
    }),
    vue({
      template: {
        // Adresy w szablonach (np. /img/...) zostają bez zmian — pliki
        // serwuje Laravel, nie Vite.
        transformAssetUrls: { base: null, includeAbsolute: false },
      },
    }),
  ],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) },
    // Importy komponentów w kodzie są bez końcówki (np. '@/Shared/Layout').
    extensions: ['.mjs', '.js', '.json', '.vue'],
  },
  build: {
    // Starsze telefony pracowników (portal): nie wyżej niż ES2020.
    target: 'es2020',
  },
})

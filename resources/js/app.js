import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import axios from 'axios'

axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 419) {
      window.location.reload()
      return
    }
    return Promise.reject(error)
  },
)

createInertiaApp({
  // Każda strona to osobny plik, doładowywany przy pierwszym wejściu.
  resolve: name => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  // Wbudowany wskaźnik postępu (dawniej osobny @inertiajs/progress) — te same ustawienia.
  progress: { color: '#29d', delay: 250 },
  title: title => title ? `${title} - MKL CRM` : 'MKL CRM',
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})

import { createApp, h } from 'vue'
import { InertiaProgress } from '@inertiajs/progress'
import { createInertiaApp } from '@inertiajs/inertia-vue3'
import axios from 'axios'

InertiaProgress.init()

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
  resolve: name => require(`./Pages/${name}`),
  title: title => title ? `${title} - MKL CRM` : 'MKL CRM',
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})

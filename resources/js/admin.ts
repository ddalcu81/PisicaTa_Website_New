import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h, type DefineComponent } from 'vue'
import '../css/admin.css'

const pages = import.meta.glob<{ default: DefineComponent }>('./pages/*.vue')

void createInertiaApp({
  resolve: async name => {
    const page = pages[`./pages/${name}.vue`]
    if (!page) throw new Error(`Unknown editorial page: ${name}`)
    return (await page()).default
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) }).use(plugin).mount(el)
  },
})

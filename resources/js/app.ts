import '../css/app.css'
import '@fontsource-variable/inter'
import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
  title: (title) => (title ? `${title} · Banyira` : 'Banyira'),
  resolve: (name) => {
    const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue')
    const page = pages[`./Pages/${name}.vue`]
    if (!page) throw new Error(`Page not found: ${name}`)
    return page()
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) }).use(plugin).mount(el!)
  },
  progress: { color: '#1f5a43', delay: 150, showSpinner: false },
})

// Installable app shell for low-connectivity use (static assets only; no financial data is cached).
if ('serviceWorker' in navigator && import.meta.env.PROD) {
  window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}))
}

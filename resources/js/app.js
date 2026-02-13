import './bootstrap'
import { subscribeToPush } from './push';
window.subscribeToPush = subscribeToPush;

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { useTheme } from './composables/useTheme'

useTheme().initTheme()

createInertiaApp({
  resolve: name => {
    return import(`./Pages/${name}.vue`)
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})

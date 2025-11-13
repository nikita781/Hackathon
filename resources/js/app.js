import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia'
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import { useLangStore } from '@/store/lang'

// const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: title => `${title}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia()

        const vue = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast)
            .use(pinia)

        const lang = useLangStore(pinia)
        lang.fetchTranslations(localStorage.getItem('language') || 'ru')

        vue.mount(el)
    },
    progress: {
        color: '#4B5563',
    },
});

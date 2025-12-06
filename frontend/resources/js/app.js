import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import './bootstrap';

// Create Vue app
const appName = 'GXY E-Commerce';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createRouter({
                history: createWebHistory(),
                routes: [
                    // Routes will be added here
                ],
            }));

        app.mount(el);
    },
});

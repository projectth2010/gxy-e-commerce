import { createApp, h } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// Import Vue components
import App from './App.vue';
import Welcome from './Pages/Welcome.vue';
import Products from './Pages/Products.vue';

// Create Vue router for Vue components
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'welcome',
      component: Welcome
    },
    {
      path: '/products',
      name: 'products',
      component: Products
    }
  ]
});

// Initialize the Inertia app
createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
    return pages[`./Pages/${name}.jsx`];
  },
  setup({ el, App, props, plugin }) {
    // Create a Vue app for the main layout
    const vueApp = createApp({ render: () => h(App, props) });
    
    // Use Vue plugins
    vueApp.use(plugin);
    vueApp.use(router);
    
    // Mount the app
    vueApp.mount(el);
    
    return vueApp;
  },
});

// Navigation guard for authentication
router.beforeEach((to, from, next) => {
  const isAuthenticated = true; // Replace with your actual auth check
  
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!isAuthenticated) {
      next({ name: 'welcome' });
    } else {
      next();
    }
  } else {
    next();
  }
});

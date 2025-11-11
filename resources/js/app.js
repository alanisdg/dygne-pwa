import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';

// Lazy import Dashboard component
const Dashboard = () => import('./components/Dashboard.vue');

const routes = [
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', name: 'dashboard', component: Dashboard },
];

const router = createRouter({
    history: createWebHistory('/app'),
    routes,
});

// Simple auth guard (optional): if no token, send back to login page
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        // Send to root login page of Laravel
        window.location.href = '/';
        return;
    }
    next();
});

const App = {
    template: `
        <div class="min-h-screen bg-gray-50">
            <div class="max-w-3xl mx-auto p-4">
                <router-view />
            </div>
        </div>
    `,
};

createApp(App).use(router).mount('#app');

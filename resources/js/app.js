import './bootstrap';
import { createApp } from 'vue';

// Create a simple Vue component
const App = {
    template: `
        <div class="container mx-auto p-4">
            <h1 class="text-3xl font-bold mb-4">Welcome to Dygne PWA</h1>
            <p class="mb-4">This is a Laravel 12 application with Vue 3 and PWA support.</p>
            <button 
                @click="count++"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
                Clicked {{ count }} times
            </button>
        </div>
    `,
    data() {
        return {
            count: 0
        };
    }
};

// Mount the Vue app to the #app element
createApp(App).mount('#app');

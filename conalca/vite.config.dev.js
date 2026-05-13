import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from '@vitejs/plugin-react';

// Configuración específica para desarrollo
// No afecta producción
export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
            port: 5173,
        },
    },
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/dashboard.css",
                "resources/css/analysis.css",
                "resources/js/app.js",
                "resources/js/dashboard.js",
                "resources/js/dashboard-chart.js",
                "resources/js/dropzone.js",
                "resources/js/editCalendarModal.js",
                "resources/js/analysis.js",
                'resources/js/sidebar-server-metrics.js',
                'resources/js/app.jsx',
                'resources/js/quotes-react.jsx'
            ],
            refresh: true,
        }),
        react(),
    ],
});

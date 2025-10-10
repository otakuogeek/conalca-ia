import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from '@vitejs/plugin-react';

export default defineConfig({
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
                'resources/js/app.jsx'
            ],
            refresh: true,
        }),
        react(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                    ui: ['@fortawesome/fontawesome-free'],
                },
            },
        },
        chunkSizeWarningLimit: 1000,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
    },
    server: {
        hmr: {
            overlay: false,
        },
    },
    optimizeDeps: {
        include: ['react', 'react-dom', 'react-router-dom'],
    },
});

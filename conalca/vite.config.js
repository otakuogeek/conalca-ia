import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from '@vitejs/plugin-react';
import fs from 'fs';
import path from 'path';

// IP pública del servidor
const PUBLIC_IP = '13.56.4.123';
const VITE_PORT = 5173;

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
                'resources/js/sidebar-server-metrics.js',
                'resources/js/app.jsx',
                'resources/js/quotes-react.jsx',
                'resources/js/pages/driver-details.jsx'
            ],
            refresh: true,
        }),
        react(),
        // Plugin personalizado para escribir el archivo hot con IP pública
        {
            name: 'write-hot-file',
            configureServer(server) {
                server.httpServer?.once('listening', () => {
                    const hotFile = path.resolve(__dirname, 'public/hot');
                    fs.writeFileSync(hotFile, `http://${PUBLIC_IP}:${VITE_PORT}`);
                    console.log(`\n  ✅ Hot file written: http://${PUBLIC_IP}:${VITE_PORT}\n`);
                });
            },
        },
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
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '13.56.4.123',
            port: 5173,
            protocol: 'ws',
            overlay: false,
        },
    },
    optimizeDeps: {
        include: ['react', 'react-dom', 'react-router-dom'],
    },
});

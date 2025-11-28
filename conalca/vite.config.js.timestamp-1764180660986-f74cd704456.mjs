// vite.config.js
import { defineConfig } from "file:///home/ubuntu/conalca/conalca/node_modules/vite/dist/node/index.js";
import laravel from "file:///home/ubuntu/conalca/conalca/node_modules/laravel-vite-plugin/dist/index.mjs";
import react from "file:///home/ubuntu/conalca/conalca/node_modules/@vitejs/plugin-react/dist/index.mjs";
var vite_config_default = defineConfig({
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
        "resources/js/app.jsx",
        "resources/js/quotes-react.jsx"
      ],
      refresh: true,
      hotFile: "public/hot-dev"
      // Archivo hot personalizado para NO romper producción
    }),
    react()
  ],
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ["react", "react-dom"],
          ui: ["@fortawesome/fontawesome-free"]
        }
      }
    },
    chunkSizeWarningLimit: 1e3,
    minify: "terser",
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true
      }
    }
  },
  server: {
    port: process.env.VITE_PORT || 5173,
    strictPort: false,
    // Si el puerto está ocupado, usa el siguiente disponible
    host: true,
    // Permite acceso desde la red local
    hmr: {
      overlay: false,
      host: "localhost"
    },
    watch: {
      usePolling: true
      // Útil en algunos entornos de desarrollo
    }
  },
  optimizeDeps: {
    include: ["react", "react-dom", "react-router-dom"]
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCIvaG9tZS91YnVudHUvY29uYWxjYS9jb25hbGNhXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ZpbGVuYW1lID0gXCIvaG9tZS91YnVudHUvY29uYWxjYS9jb25hbGNhL3ZpdGUuY29uZmlnLmpzXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ltcG9ydF9tZXRhX3VybCA9IFwiZmlsZTovLy9ob21lL3VidW50dS9jb25hbGNhL2NvbmFsY2Evdml0ZS5jb25maWcuanNcIjtpbXBvcnQgeyBkZWZpbmVDb25maWcgfSBmcm9tIFwidml0ZVwiO1xuaW1wb3J0IGxhcmF2ZWwgZnJvbSBcImxhcmF2ZWwtdml0ZS1wbHVnaW5cIjtcbmltcG9ydCByZWFjdCBmcm9tICdAdml0ZWpzL3BsdWdpbi1yZWFjdCc7XG5cbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZyh7XG4gICAgcGx1Z2luczogW1xuICAgICAgICBsYXJhdmVsKHtcbiAgICAgICAgICAgIGlucHV0OiBbXG4gICAgICAgICAgICAgICAgXCJyZXNvdXJjZXMvY3NzL2FwcC5jc3NcIixcbiAgICAgICAgICAgICAgICBcInJlc291cmNlcy9jc3MvZGFzaGJvYXJkLmNzc1wiLFxuICAgICAgICAgICAgICAgIFwicmVzb3VyY2VzL2Nzcy9hbmFseXNpcy5jc3NcIixcbiAgICAgICAgICAgICAgICBcInJlc291cmNlcy9qcy9hcHAuanNcIixcbiAgICAgICAgICAgICAgICBcInJlc291cmNlcy9qcy9kYXNoYm9hcmQuanNcIixcbiAgICAgICAgICAgICAgICBcInJlc291cmNlcy9qcy9kYXNoYm9hcmQtY2hhcnQuanNcIixcbiAgICAgICAgICAgICAgICBcInJlc291cmNlcy9qcy9kcm9wem9uZS5qc1wiLFxuICAgICAgICAgICAgICAgIFwicmVzb3VyY2VzL2pzL2VkaXRDYWxlbmRhck1vZGFsLmpzXCIsXG4gICAgICAgICAgICAgICAgXCJyZXNvdXJjZXMvanMvYW5hbHlzaXMuanNcIixcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2pzL2FwcC5qc3gnLFxuICAgICAgICAgICAgICAgICdyZXNvdXJjZXMvanMvcXVvdGVzLXJlYWN0LmpzeCdcbiAgICAgICAgICAgIF0sXG4gICAgICAgICAgICByZWZyZXNoOiB0cnVlLFxuICAgICAgICAgICAgaG90RmlsZTogJ3B1YmxpYy9ob3QtZGV2JywgLy8gQXJjaGl2byBob3QgcGVyc29uYWxpemFkbyBwYXJhIE5PIHJvbXBlciBwcm9kdWNjaVx1MDBGM25cbiAgICAgICAgfSksXG4gICAgICAgIHJlYWN0KCksXG4gICAgXSxcbiAgICBidWlsZDoge1xuICAgICAgICByb2xsdXBPcHRpb25zOiB7XG4gICAgICAgICAgICBvdXRwdXQ6IHtcbiAgICAgICAgICAgICAgICBtYW51YWxDaHVua3M6IHtcbiAgICAgICAgICAgICAgICAgICAgdmVuZG9yOiBbJ3JlYWN0JywgJ3JlYWN0LWRvbSddLFxuICAgICAgICAgICAgICAgICAgICB1aTogWydAZm9ydGF3ZXNvbWUvZm9udGF3ZXNvbWUtZnJlZSddLFxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgICAgICBjaHVua1NpemVXYXJuaW5nTGltaXQ6IDEwMDAsXG4gICAgICAgIG1pbmlmeTogJ3RlcnNlcicsXG4gICAgICAgIHRlcnNlck9wdGlvbnM6IHtcbiAgICAgICAgICAgIGNvbXByZXNzOiB7XG4gICAgICAgICAgICAgICAgZHJvcF9jb25zb2xlOiB0cnVlLFxuICAgICAgICAgICAgICAgIGRyb3BfZGVidWdnZXI6IHRydWUsXG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgIH0sXG4gICAgc2VydmVyOiB7XG4gICAgICAgIHBvcnQ6IHByb2Nlc3MuZW52LlZJVEVfUE9SVCB8fCA1MTczLFxuICAgICAgICBzdHJpY3RQb3J0OiBmYWxzZSwgLy8gU2kgZWwgcHVlcnRvIGVzdFx1MDBFMSBvY3VwYWRvLCB1c2EgZWwgc2lndWllbnRlIGRpc3BvbmlibGVcbiAgICAgICAgaG9zdDogdHJ1ZSwgLy8gUGVybWl0ZSBhY2Nlc28gZGVzZGUgbGEgcmVkIGxvY2FsXG4gICAgICAgIGhtcjoge1xuICAgICAgICAgICAgb3ZlcmxheTogZmFsc2UsXG4gICAgICAgICAgICBob3N0OiAnbG9jYWxob3N0JyxcbiAgICAgICAgfSxcbiAgICAgICAgd2F0Y2g6IHtcbiAgICAgICAgICAgIHVzZVBvbGxpbmc6IHRydWUsIC8vIFx1MDBEQXRpbCBlbiBhbGd1bm9zIGVudG9ybm9zIGRlIGRlc2Fycm9sbG9cbiAgICAgICAgfSxcbiAgICB9LFxuICAgIG9wdGltaXplRGVwczoge1xuICAgICAgICBpbmNsdWRlOiBbJ3JlYWN0JywgJ3JlYWN0LWRvbScsICdyZWFjdC1yb3V0ZXItZG9tJ10sXG4gICAgfSxcbn0pO1xuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUFzUSxTQUFTLG9CQUFvQjtBQUNuUyxPQUFPLGFBQWE7QUFDcEIsT0FBTyxXQUFXO0FBRWxCLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQ3hCLFNBQVM7QUFBQSxJQUNMLFFBQVE7QUFBQSxNQUNKLE9BQU87QUFBQSxRQUNIO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLE1BQ0o7QUFBQSxNQUNBLFNBQVM7QUFBQSxNQUNULFNBQVM7QUFBQTtBQUFBLElBQ2IsQ0FBQztBQUFBLElBQ0QsTUFBTTtBQUFBLEVBQ1Y7QUFBQSxFQUNBLE9BQU87QUFBQSxJQUNILGVBQWU7QUFBQSxNQUNYLFFBQVE7QUFBQSxRQUNKLGNBQWM7QUFBQSxVQUNWLFFBQVEsQ0FBQyxTQUFTLFdBQVc7QUFBQSxVQUM3QixJQUFJLENBQUMsK0JBQStCO0FBQUEsUUFDeEM7QUFBQSxNQUNKO0FBQUEsSUFDSjtBQUFBLElBQ0EsdUJBQXVCO0FBQUEsSUFDdkIsUUFBUTtBQUFBLElBQ1IsZUFBZTtBQUFBLE1BQ1gsVUFBVTtBQUFBLFFBQ04sY0FBYztBQUFBLFFBQ2QsZUFBZTtBQUFBLE1BQ25CO0FBQUEsSUFDSjtBQUFBLEVBQ0o7QUFBQSxFQUNBLFFBQVE7QUFBQSxJQUNKLE1BQU0sUUFBUSxJQUFJLGFBQWE7QUFBQSxJQUMvQixZQUFZO0FBQUE7QUFBQSxJQUNaLE1BQU07QUFBQTtBQUFBLElBQ04sS0FBSztBQUFBLE1BQ0QsU0FBUztBQUFBLE1BQ1QsTUFBTTtBQUFBLElBQ1Y7QUFBQSxJQUNBLE9BQU87QUFBQSxNQUNILFlBQVk7QUFBQTtBQUFBLElBQ2hCO0FBQUEsRUFDSjtBQUFBLEVBQ0EsY0FBYztBQUFBLElBQ1YsU0FBUyxDQUFDLFNBQVMsYUFBYSxrQkFBa0I7QUFBQSxFQUN0RDtBQUNKLENBQUM7IiwKICAibmFtZXMiOiBbXQp9Cg==

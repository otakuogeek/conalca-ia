╔══════════════════════════════════════════════════════════════════════════════╗
║                    GUÍA DE DESARROLLO - CONALCA                              ║
╠══════════════════════════════════════════════════════════════════════════════╣
║                                                                              ║
║  PRODUCCIÓN (siempre activo, no tocar):                                      ║
║  ────────────────────────────────────                                        ║
║  URL: https://conalcaia.conalca.com.co                                       ║
║  Assets: /build/assets/ (compilados)                                         ║
║                                                                              ║
║  DESARROLLO (hot-reload, cambios en vivo):                                   ║
║  ─────────────────────────────────────────                                   ║
║  URL: http://localhost:8000                                                  ║
║  Assets: Vite HMR (actualizaciones instantáneas)                             ║
║                                                                              ║
╠══════════════════════════════════════════════════════════════════════════════╣
║  CÓMO INICIAR DESARROLLO:                                                    ║
╠══════════════════════════════════════════════════════════════════════════════╣
║                                                                              ║
║  OPCIÓN 1: Desde el servidor (SSH)                                           ║
║  ─────────────────────────────────                                           ║
║  1. Conéctate por SSH:                                                       ║
║     ssh ubuntu@13.56.4.123                                                   ║
║                                                                              ║
║  2. Ejecuta el script:                                                       ║
║     /home/ubuntu/conalca/start-dev.sh                                        ║
║                                                                              ║
║  3. Desde tu PC, abre túnel SSH:                                             ║
║     ssh -L 8000:localhost:8000 -L 5173:localhost:5173 ubuntu@13.56.4.123     ║
║                                                                              ║
║  4. Abre en tu navegador:                                                    ║
║     http://localhost:8000                                                    ║
║                                                                              ║
║  OPCIÓN 2: Dos terminales SSH                                                ║
║  ────────────────────────────                                                ║
║  Terminal 1 (Vite):                                                          ║
║     cd /home/ubuntu/conalca/conalca && npm run dev -- --host 0.0.0.0         ║
║                                                                              ║
║  Terminal 2 (Laravel):                                                       ║
║     cd /home/ubuntu/conalca/conalca                                          ║
║     echo "http://localhost:5173" > public/hot                                ║
║     php artisan serve --host=127.0.0.1 --port=8000                           ║
║                                                                              ║
╠══════════════════════════════════════════════════════════════════════════════╣
║  CÓMO VOLVER A PRODUCCIÓN:                                                   ║
╠══════════════════════════════════════════════════════════════════════════════╣
║                                                                              ║
║  1. Detener desarrollo (Ctrl+C o cerrar terminales)                          ║
║                                                                              ║
║  2. Eliminar archivo hot (si existe):                                        ║
║     rm -f /home/ubuntu/conalca/conalca/public/hot                            ║
║                                                                              ║
║  3. Si modificaste código, compilar para producción:                         ║
║     cd /home/ubuntu/conalca/conalca && npm run build                         ║
║                                                                              ║
║  4. Producción ya está corriendo en:                                         ║
║     https://conalcaia.conalca.com.co                                         ║
║                                                                              ║
╠══════════════════════════════════════════════════════════════════════════════╣
║  NOTAS IMPORTANTES:                                                          ║
╠══════════════════════════════════════════════════════════════════════════════╣
║                                                                              ║
║  • El archivo /public/hot activa modo desarrollo                             ║
║  • Sin ese archivo, Laravel usa /build/assets/ (producción)                  ║
║  • Producción NO se ve afectada mientras desarrollo está corriendo           ║
║  • Usa túnel SSH para desarrollo seguro (no exponer puertos)                 ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝

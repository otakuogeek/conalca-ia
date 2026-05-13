# 📄 MÓDULO: DOCUMENTOS

## 1. ¿Para qué es este módulo?
Es el **repositorio centralizado de archivos** del sistema. Sirve para almacenar y organizar todos los documentos asociados a clientes: RUT, cámara de comercio, contratos, certificados, documentos legales, etc.

## 2. ¿Qué hace?
- Permite **subir archivos** asociados a un cliente o contacto.
- **Categoriza** los archivos (legal, contrato, RUT, certificación, etc.).
- Permite **descargar** archivos.
- Muestra **detalles del cliente** con todos sus archivos agrupados.
- Soporta múltiples formatos (PDF, imágenes, Word, Excel).
- Almacena metadata: nombre original, tamaño, categoría, fecha de subida.

## 3. ¿Cómo funciona?

### Flujo de subida
```
1. Usuario entra a /documents
2. Selecciona cliente
3. Click en "Subir archivo" → modal con dropzone
4. Drag & drop o seleccionar archivo
5. Elige categoría (RUT, cámara comercio, etc.)
6. POST /documents → DocumentController@store
7. Archivo se guarda en storage/app/public/client_files/{client_id}/
8. Se crea registro en client_files con metadata
9. Retorna confirmación + lista actualizada
```

### Flujo de visualización
```
1. Usuario abre detalles del cliente
2. GET /documents/clients/{id}/details
3. Sistema agrupa archivos por categoría
4. Renderiza panel con preview/descarga por archivo
5. Click en archivo → descarga directa desde /storage/...
```

## 4. ¿Cómo actúa en el sistema?
Es un módulo **de soporte**. No tiene lógica de negocio compleja, pero es **crítico para auditoría y compliance**:
- Permite verificar que un cliente tiene RUT vigente antes de cotizar
- Conserva contratos firmados
- Sirve para reclamos / disputas legales
- Permite a SAC consultar documentos del cliente sin interrumpir al comercial

Se integra con **Clientes** (los archivos se asocian a un cliente_id) y opcionalmente a **Contactos** (algunos archivos son específicos de una persona).

## 5. Componentes del código

### Rutas
| Método | URL | Controlador | Función |
|--------|-----|-------------|---------|
| GET | `/documents` | `DocumentController@index` | Listado general |
| POST | `/documents` | `DocumentController@store` | Subir archivo |
| GET | `/documents/clients/{id}/details` | `DocumentController@getClientDetails` | Detalles cliente |
| GET | `/documents/clients/{id}/files/{category}` | `DocumentController@getClientFilesByCategory` | Por categoría |
| GET | `/api/client/{clientId}/files` | `DocumentController@getClientFiles` | API JSON |

### Controlador
**Archivo:** `app/Http/Controllers/DocumentController.php`

**Métodos:**
- `index(Request)` — listado paginado con filtros
- `store(Request)` — subir archivo (valida tipo MIME, tamaño máximo)
- `getClientFiles($clientId)` — JSON con archivos de un cliente
- `getClientDetails($id)` — vista detallada (cliente + archivos + contactos)
- `getClientFilesByCategory($id, $category)` — filtrado por categoría

### Vistas
**`resources/views/documents/`** — panel y componentes con dropzone (`dropzone.js`).

### Modelos
- **`Document`** — modelo legacy (documentos genéricos)
- **`ClientFile`** — archivos de cliente con campos `category`, `size`, `original_name`, `path`
- **`ContactFile`** — archivos de contactos

### Migraciones relevantes
- `2024_08_16_084735_create_contact_files_table.php`
- `2025_08_05_095336_add_category_and_size_to_contact_files_table.php`
- `2025_08_25_111251_create_client_files_table.php`

### Frontend
- **Dropzone.js** (`dropzone.js`) — drag & drop de archivos
- Validación frontend: tipos MIME, tamaño máximo
- Progreso de subida visible

## 6. Almacenamiento
- **Disco:** `local` (configurable con `FILESYSTEM_DISK`)
- **Ruta física:** `storage/app/public/client_files/{client_id}/`
- **URL pública:** `https://conalcaia.conalca.com.co/storage/client_files/{client_id}/{filename}`
- **Requiere:** `php artisan storage:link` para que el symlink funcione

## 7. Categorías de archivos
- **legal** — documentos legales
- **rut** — Registro Único Tributario
- **camara_comercio** — Cámara de Comercio
- **contrato** — contratos firmados
- **certificacion** — certificaciones varias
- **identificacion** — cédula del representante
- **financiero** — balance, estados financieros
- **otros** — otros documentos

(Las categorías están definidas en el frontend y no son obligatoriamente fijas; el campo es `varchar` por flexibilidad.)

## 8. Reglas de negocio
- **Tipos permitidos:** PDF, JPG, PNG, DOC, DOCX, XLS, XLSX
- **Tamaño máximo:** 10 MB por archivo (configurable)
- **Multi-archivo:** se pueden subir varios a la vez
- **No se elimina físicamente:** los archivos eliminados quedan en disco para auditoría (sólo se marca `deleted_at`)

## 9. Permisos
- **Ver:** todos los roles excepto PRICING
- **Subir:** todos los comerciales y SAC
- **Eliminar:** SUPER ADMIN o el creador del archivo

## 10. Mantenimiento
- **Espacio en disco:** monitorear `storage/app/public/client_files/` (puede crecer significativamente con muchos clientes).
- **Backup:** incluir esta carpeta en los backups regulares.
- **Limpieza:** considerar política de retención (ej: archivar archivos > 5 años a almacenamiento frío).

## 11. Archivos clave
- `app/Http/Controllers/DocumentController.php`
- `app/Models/Document.php`, `ClientFile.php`, `ContactFile.php`
- `resources/views/documents/`
- `resources/views/contacts/components/clientDetails.blade.php` (muestra archivos)

## 12. Relacionado con
- [02-Clientes](../02-Clientes/) — fuente de los clientes
- [04-Calendario](../04-Calendario/) — recordatorios de vencimiento de documentos (manual)

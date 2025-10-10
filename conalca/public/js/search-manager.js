/**
 * SearchManager - Sistema unificado de búsqueda en tiempo real
 * @version 2.0.0
 * @author Conalca Development Team
 */

console.log('🔄 Cargando SearchManager...');

class SearchManager {
    constructor(options = {}) {
        this.config = {
            debounceTime: 120,
            minQueryLength: 1,
            maxResults: 15,
            cacheSize: 50,
            retryAttempts: 3,
            retryDelay: 1000,
            // Opciones de filtrado en cliente
            enableClientFilter: true,
            matchStrategy: 'includes', // 'includes' | 'startsWith'
            preferStartsWithOnFirstChar: true,
            searchableFields: [], // si está vacío, se detectan automáticamente
            ...options
        };

        this.cache = new Map();
        this.currentRequest = null;
        this.searchTimeout = null;
        this.retryCount = 0;

        this.initializeElements();
        this.setupEventListeners();
        this.setupAccessibility();
    }

    /**
     * Inicializar elementos DOM
     */
    initializeElements() {
        this.searchInput = this.resolveElement(this.config.inputElement ?? this.config.inputId);
        this.resultsContainer = this.resolveElement(this.config.resultsElement ?? this.config.resultsId);
        this.loadingIndicator = this.resolveElement(this.config.loadingElement ?? this.config.loadingId);
        this.clearButton = this.resolveElement(this.config.clearElement ?? this.config.clearId);
        this.selectedInput = this.resolveElement(this.config.selectedElement ?? this.config.selectedId);

        if (!this.searchInput) {
            throw new Error(`Search input element not found: ${this.config.inputId}`);
        }

        this.validateElements();
    }

    /**
     * Resolver un elemento a partir de un ID (string) o un elemento DOM
     */
    resolveElement(ref) {
        if (!ref) return null;
        if (typeof ref === 'string') {
            return document.getElementById(ref) || null;
        }
        // Si es un Node/Element
        if (ref instanceof Element || (ref && typeof ref === 'object' && 'nodeType' in ref)) {
            return ref;
        }
        return null;
    }

    /**
     * Validar elementos requeridos
     */
    validateElements() {
        const required = ['searchInput', 'resultsContainer'];
        const missing = required.filter(prop => !this[prop]);
        
        if (missing.length > 0) {
            console.warn(`SearchManager: Missing elements: ${missing.join(', ')}`);
        }
    }

    /**
     * Configurar listeners de eventos
     */
    setupEventListeners() {
        // Eventos de búsqueda
        ['input', 'keyup', 'paste'].forEach(eventType => {
            this.searchInput.addEventListener(eventType, this.handleSearchInput.bind(this));
        });

        // Navegación por teclado
        this.searchInput.addEventListener('keydown', this.handleKeyNavigation.bind(this));

        // Cerrar al hacer clic fuera
        document.addEventListener('click', this.handleOutsideClick.bind(this));

        // Botón de limpieza
        if (this.clearButton) {
            this.clearButton.addEventListener('click', this.clearSearch.bind(this));
        }

        // Delegación de eventos para resultados
        if (this.resultsContainer) {
            this.resultsContainer.addEventListener('click', this.handleResultClick.bind(this));
            this.resultsContainer.addEventListener('dblclick', this.handleResultDoubleClick.bind(this));
            this.resultsContainer.addEventListener('mouseenter', this.handleResultHover.bind(this), true);
        }
    }

    /**
     * Configurar accesibilidad ARIA
     */
    setupAccessibility() {
        if (!this.searchInput || !this.resultsContainer) return;

        this.searchInput.setAttribute('role', 'combobox');
        this.searchInput.setAttribute('aria-autocomplete', 'list');
        this.searchInput.setAttribute('aria-haspopup', 'listbox');
        this.searchInput.setAttribute('aria-controls', this.config.resultsId);
        this.searchInput.setAttribute('aria-expanded', 'false');

        this.resultsContainer.setAttribute('role', 'listbox');
        this.resultsContainer.setAttribute('aria-label', 'Resultados de búsqueda');
    }

    /**
     * Manejar entrada de búsqueda con debounce
     */
    handleSearchInput(e) {
        const query = e.target.value.trim();

        // Cancelar búsqueda anterior
        this.cancelCurrentSearch();

        // Actualizar botón de limpieza
        this.updateClearButton(query);

        // Validar longitud mínima
        if (query.length === 0) {
            this.hideResults();
            return;
        }

        if (query.length < this.config.minQueryLength) {
            this.showMinimumCharsMessage();
            return;
        }

        // Verificar caché
        if (this.cache.has(query)) {
            this.displayResults(this.cache.get(query), query);
            return;
        }

        // Aplicar debounce
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, this.config.debounceTime);
    }

    /**
     * Manejar navegación por teclado
     */
    handleKeyNavigation(e) {
        switch (e.key) {
            case 'ArrowDown':
            case 'ArrowUp':
                e.preventDefault();
                this.navigateResults(e.key);
                break;
            case 'Enter':
                e.preventDefault();
                this.selectHighlighted();
                break;
            case 'Escape':
                this.handleEscape();
                break;
        }
    }

    /**
     * Manejar clic fuera del componente
     */
    handleOutsideClick(e) {
        if (!this.searchInput.contains(e.target) && 
            !this.resultsContainer?.contains(e.target)) {
            this.hideResults();
        }
    }

    /**
     * Realizar búsqueda con manejo de errores
     */
    async performSearch(query) {
        try {
            this.showLoading();
            
            const controller = new AbortController();
            this.currentRequest = controller;

            const response = await fetch(this.buildSearchUrl(query), {
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success !== false) {
                const results = this.config.transformResults ? 
                    this.config.transformResults(data) : 
                    (data.clients || data.data || data);
                
                // Filtrar/ordenar en cliente si aplica
                const processedResults = this.filterResults(results, query);

                this.cacheResults(query, processedResults);
                this.displayResults(processedResults, query);
                this.retryCount = 0;
            } else {
                throw new Error(data.message || 'Error en la búsqueda');
            }

        } catch (error) {
            this.handleSearchError(error, query);
        } finally {
            this.hideLoading();
            this.currentRequest = null;
        }
    }

    /**
     * Construir URL de búsqueda
     */
    buildSearchUrl(query) {
        const baseUrl = this.config.endpoint || this.config.apiEndpoint || '/api/clients/search';
        const params = new URLSearchParams({
            q: query,
            limit: this.config.maxResults,
            ...this.config.extraParams
        });
        return `${baseUrl}?${params}`;
    }

    /**
     * Filtrar resultados en cliente para priorizar/limitar por la query
     */
    filterResults(results, query) {
        if (!Array.isArray(results)) return [];
        const q = this.normalizeString(query);
        if (!this.config.enableClientFilter || q.length === 0) return results;

        const fields = this.config.searchableFields && this.config.searchableFields.length
            ? this.config.searchableFields
            : null; // autodetección por item

        const startsWithFirstChar = this.config.preferStartsWithOnFirstChar && q.length === 1;
        const mode = startsWithFirstChar ? 'startsWith' : (this.config.matchStrategy || 'includes');

        // Filtrado por modo
        const filtered = results.filter(item => {
            const text = this.getSearchableText(item, fields);
            if (!text) return false;
            if (mode === 'startsWith') return text.startsWith(q);
            return text.includes(q);
        });

        // Si con startsWith no hay nada y la query es 1 char, caer a includes
        if (startsWithFirstChar && filtered.length === 0) {
            return results.filter(item => this.getSearchableText(item, fields).includes(q));
        }

        return filtered;
    }

    /**
     * Obtener texto buscable de un item combinando campos relevantes
     */
    getSearchableText(item, fields = null) {
        const values = [];
        const tryPush = (v) => { if (v !== undefined && v !== null) values.push(String(v)); };

        if (fields) {
            fields.forEach(k => tryPush(this.getNestedValue(item, k)));
        } else {
            // Autodetectar campos comunes
            ['cliente','name','nombre','nit','documento','codigo','ciudad','email','telefono'].forEach(k => tryPush(item[k]));
        }

        return this.normalizeString(values.join(' | '));
    }

    /**
     * Normalizar texto (lowercase, sin acentos)
     */
    normalizeString(str) {
        return (str || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/\p{Diacritic}+/gu, '')
            .trim();
    }

    /**
     * Manejar errores de búsqueda con retry
     */
    handleSearchError(error, query) {
        if (error.name === 'AbortError') return;

        console.error('Search error:', error);

        if (this.retryCount < this.config.retryAttempts) {
            this.retryCount++;
            setTimeout(() => {
                this.performSearch(query);
            }, this.config.retryDelay * this.retryCount);
        } else {
            this.showError(error.message || 'Error de conexión');
            this.retryCount = 0;
        }
    }

    /**
     * Almacenar resultados en caché
     */
    cacheResults(query, results) {
        if (this.cache.size >= this.config.cacheSize) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
        this.cache.set(query, results);
    }

    /**
     * Mostrar resultados
     */
    displayResults(results, query = '') {
        if (!this.resultsContainer) return;

        if (!results || results.length === 0) {
            this.showEmptyState(query);
            return;
        }

        const html = results.map((item, index) => 
            this.renderResultItem(item, index, query)
        ).join('');

        this.resultsContainer.innerHTML = html;
        this.showResults();
        this.highlightFirstResult();
    }

    /**
     * Renderizar elemento de resultado
     */
    renderResultItem(item, index, query) {
        const safeData = this.encodeData(item);
        const isHighlighted = index === 0 ? 'highlighted bg-blue-50' : '';
        const ariaSelected = index === 0 ? 'true' : 'false';

        return `
            <div class="p-3 hover:bg-blue-50 focus:bg-blue-50 cursor-pointer border-b border-gray-100 
                        result-item transition-colors duration-150 ${isHighlighted}" 
                 data-item="${safeData}" 
                 data-index="${index}"
                 tabindex="0" 
                 role="option" 
                 aria-selected="${ariaSelected}"
                 id="result-${index}">
                ${this.renderItemContent(item, query)}
            </div>
        `;
    }

    /**
     * Renderizar contenido del elemento (debe ser sobrescrito)
     */
    renderItemContent(item, query) {
        const name = item.cliente || item.name || item.titulo || 'Sin nombre';
        const highlightedName = this.highlightMatch(name, query);
        
        return `
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900 text-sm">${highlightedName}</h4>
                    ${this.renderItemDetails(item, query)}
                </div>
                ${this.renderItemStatus(item)}
            </div>
        `;
    }

    /**
     * Renderizar detalles del elemento
     */
    renderItemDetails(item, query) {
        const details = [];
        
        if (item.documento) details.push(`Doc: ${this.highlightMatch(item.documento.toString(), query)}`);
        if (item.ciudad) details.push(this.highlightMatch(item.ciudad, query));
        if (item.telefono) details.push(`📞 ${this.highlightMatch(item.telefono.toString(), query)}`);
        if (item.email && item.email !== 'ninguno') details.push(`📧 ${this.highlightMatch(item.email, query)}`);

        return details.length > 0 ? 
            `<div class="text-xs text-gray-500 mt-1">${details.join(' • ')}</div>` : '';
    }

    /**
     * Renderizar estado del elemento
     */
    renderItemStatus(item) {
        const isActive = item.estado?.toLowerCase() === 'activo';
        const statusClass = isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600';
        const statusText = isActive ? '✅ Activo' : '⭕ Inactivo';

        return `
            <div class="text-right">
                <span class="px-2 py-1 text-xs rounded-full ${statusClass}">
                    ${statusText}
                </span>
            </div>
        `;
    }

    /**
     * Resaltar coincidencias en el texto
     */
    highlightMatch(text, query) {
        if (!text || !query) return text || '';
        
        const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escapedQuery})`, 'gi');
        return text.toString().replace(regex, '<mark class="bg-yellow-200 px-1 rounded font-semibold">$1</mark>');
    }

    /**
     * Navegar por resultados con teclado
     */
    navigateResults(direction) {
        const results = this.resultsContainer?.querySelectorAll('.result-item');
        if (!results || results.length === 0) return;

        let currentIndex = -1;
        results.forEach((result, index) => {
            if (result.classList.contains('highlighted')) {
                currentIndex = index;
            }
            result.classList.remove('highlighted', 'bg-blue-50');
            result.setAttribute('aria-selected', 'false');
        });

        if (direction === 'ArrowDown') {
            currentIndex = currentIndex < results.length - 1 ? currentIndex + 1 : 0;
        } else if (direction === 'ArrowUp') {
            currentIndex = currentIndex > 0 ? currentIndex - 1 : results.length - 1;
        }

        if (currentIndex >= 0 && currentIndex < results.length) {
            const activeItem = results[currentIndex];
            activeItem.classList.add('highlighted', 'bg-blue-50');
            activeItem.setAttribute('aria-selected', 'true');
            activeItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            
            this.searchInput.setAttribute('aria-activedescendant', `result-${currentIndex}`);
        }
    }

    /**
     * Seleccionar elemento resaltado
     */
    selectHighlighted() {
        const highlighted = this.resultsContainer?.querySelector('.result-item.highlighted');
        if (highlighted) {
            const data = this.decodeData(highlighted.dataset.item);
            this.selectItem(data);
        }
    }

    /**
     * Manejar clic en resultado
     */
    handleResultClick(e) {
        const item = e.target.closest('.result-item');
        if (!item) return;

        e.preventDefault();
        const data = this.decodeData(item.dataset.item);
        this.selectItem(data);
    }

    /**
     * Manejar doble clic en resultado
     */
    handleResultDoubleClick(e) {
        const item = e.target.closest('.result-item');
        if (!item) return;

        e.preventDefault();
        const data = this.decodeData(item.dataset.item);
        this.selectItem(data, true); // Flag para indicar selección rápida
    }

    /**
     * Manejar hover en resultado
     */
    handleResultHover(e) {
        if (!e.target.classList.contains('result-item')) return;

        this.resultsContainer.querySelectorAll('.result-item').forEach(r => {
            r.classList.remove('highlighted', 'bg-blue-50');
            r.setAttribute('aria-selected', 'false');
        });

        e.target.classList.add('highlighted', 'bg-blue-50');
        e.target.setAttribute('aria-selected', 'true');
    }

    /**
     * Seleccionar elemento
     */
    selectItem(data, isQuickSelect = false) {
        const displayValue = this.getDisplayValue(data);
        
        this.searchInput.value = displayValue;
        if (this.selectedInput) {
            this.selectedInput.value = JSON.stringify(data);
        }

        this.hideResults();
        this.hideLoading();
        this.updateClearButton('');

        // Rellenar campos relacionados
        if (this.config.fillFields) {
            this.fillRelatedFields(data);
        }

        // Disparar eventos
        this.dispatchChangeEvents();

        // Callback personalizado
        if (this.config.onSelect) {
            this.config.onSelect(data, isQuickSelect);
        }

        // Notificación
        if (isQuickSelect && this.config.showNotifications) {
            this.showNotification(`✅ ${displayValue} seleccionado`, 'success');
        }
    }

    /**
     * Obtener valor de visualización
     */
    getDisplayValue(data) {
        return data.cliente || data.name || data.titulo || 'Sin nombre';
    }

    /**
     * Rellenar campos relacionados
     */
    fillRelatedFields(data) {
        if (!this.config.fieldMapping) return;

        Object.entries(this.config.fieldMapping).forEach(([fieldName, dataKey]) => {
            const field = document.querySelector(`input[name="${fieldName}"], select[name="${fieldName}"], textarea[name="${fieldName}"]`);
            const value = this.getNestedValue(data, dataKey);
            
            if (field && value) {
                field.value = value;
                this.dispatchFieldEvents(field);
            }
        });
    }

    /**
     * Obtener valor anidado del objeto
     */
    getNestedValue(obj, path) {
        return path.split('.').reduce((current, key) => current?.[key], obj);
    }

    /**
     * Disparar eventos en campo
     */
    dispatchFieldEvents(field) {
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /**
     * Disparar eventos de cambio
     */
    dispatchChangeEvents() {
        this.searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        this.searchInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /**
     * Limpiar búsqueda
     */
    clearSearch() {
        this.searchInput.value = '';
        if (this.selectedInput) this.selectedInput.value = '';
        
        this.hideResults();
        this.hideLoading();
        this.updateClearButton('');
        this.cache.clear();

        // Limpiar campos relacionados
        if (this.config.clearFields) {
            this.config.clearFields.forEach(fieldName => {
                const field = document.querySelector(`input[name="${fieldName}"], select[name="${fieldName}"], textarea[name="${fieldName}"]`);
                if (field) {
                    field.value = '';
                    this.dispatchFieldEvents(field);
                }
            });
        }

        this.searchInput.focus();
        this.dispatchChangeEvents();
    }

    /**
     * Manejar tecla Escape
     */
    handleEscape() {
        if (this.searchInput.value.trim() !== '') {
            this.clearSearch();
        } else {
            this.hideResults();
            this.searchInput.blur();
        }
    }

    /**
     * Actualizar botón de limpieza
     */
    updateClearButton(value) {
        if (!this.clearButton) return;
        
        if (value.trim() !== '') {
            this.clearButton.classList.remove('hidden');
        } else {
            this.clearButton.classList.add('hidden');
        }
    }

    /**
     * Mostrar/ocultar resultados
     */
    showResults() {
        if (!this.resultsContainer) return;
        this.resultsContainer.classList.remove('hidden');
        this.searchInput.setAttribute('aria-expanded', 'true');
    }

    hideResults() {
        if (!this.resultsContainer) return;
        this.resultsContainer.classList.add('hidden');
        this.searchInput.setAttribute('aria-expanded', 'false');
    }

    /**
     * Mostrar/ocultar loading
     */
    showLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.classList.remove('hidden');
        }
    }

    hideLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.classList.add('hidden');
        }
    }

    /**
     * Mostrar mensaje de caracteres mínimos
     */
    showMinimumCharsMessage() {
        if (!this.resultsContainer) return;

        this.resultsContainer.innerHTML = `
            <div class="p-3 text-gray-400 text-center text-sm">
                <svg class="mx-auto h-6 w-6 text-gray-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Escribe al menos ${this.config.minQueryLength} caracteres para buscar
            </div>
        `;
        this.showResults();
    }

    /**
     * Mostrar estado vacío
     */
    showEmptyState(query) {
        if (!this.resultsContainer) return;

        this.resultsContainer.innerHTML = `
            <div class="p-4 text-gray-500 text-center">
                <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                No se encontraron resultados para "${query}"
            </div>
        `;
        this.showResults();
    }

    /**
     * Mostrar error
     */
    showError(message) {
        if (!this.resultsContainer) return;

        this.resultsContainer.innerHTML = `
            <div class="p-4 text-red-500 text-center">
                <svg class="mx-auto h-8 w-8 text-red-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm">${message}</span>
                <div class="mt-2">
                    <button class="retry-btn text-xs text-blue-600 hover:text-blue-800 underline">
                        🔄 Reintentar
                    </button>
                </div>
            </div>
        `;

        // Event listener para reintentar
        setTimeout(() => {
            const retryBtn = this.resultsContainer.querySelector('.retry-btn');
            if (retryBtn) {
                retryBtn.addEventListener('click', () => {
                    const query = this.searchInput.value.trim();
                    if (query.length >= this.config.minQueryLength) {
                        this.performSearch(query);
                    }
                });
            }
        }, 0);

        this.showResults();
    }

    /**
     * Resaltar primer resultado
     */
    highlightFirstResult() {
        const firstResult = this.resultsContainer?.querySelector('.result-item');
        if (firstResult) {
            firstResult.classList.add('highlighted', 'bg-blue-50');
            firstResult.setAttribute('aria-selected', 'true');
            this.searchInput.setAttribute('aria-activedescendant', 'result-0');
        }
    }

    /**
     * Mostrar notificación
     */
    showNotification(message, type = 'info') {
        const colors = {
            success: 'bg-green-100 border-green-400 text-green-700',
            error: 'bg-red-100 border-red-400 text-red-700',
            info: 'bg-blue-100 border-blue-400 text-blue-700'
        };

        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 border rounded-lg ${colors[type]} max-w-sm shadow-lg`;
        notification.innerHTML = `
            <div class="flex items-center">
                <span class="flex-1">${message}</span>
                <button class="ml-2 text-gray-500 hover:text-gray-700" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    /**
     * Cancelar búsqueda actual
     */
    cancelCurrentSearch() {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = null;
        }

        if (this.currentRequest) {
            this.currentRequest.abort();
            this.currentRequest = null;
        }
    }

    /**
     * Codificar datos para data attribute
     */
    encodeData(data) {
        return btoa(unescape(encodeURIComponent(JSON.stringify(data))));
    }

    /**
     * Decodificar datos de data attribute
     */
    decodeData(encodedData) {
        return JSON.parse(decodeURIComponent(escape(atob(encodedData))));
    }

    /**
     * Destruir instancia
     */
    destroy() {
        this.cancelCurrentSearch();
        this.cache.clear();
        
        // Remover event listeners
        // (Los event listeners se limpiarán automáticamente cuando se elimine el DOM)
    }
}

// Export para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SearchManager;
}

// Global para uso directo
if (typeof window !== 'undefined') {
    window.SearchManager = SearchManager;
    console.log('✅ SearchManager cargado y disponible globalmente');
}
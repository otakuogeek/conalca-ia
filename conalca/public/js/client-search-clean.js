/**
 * ClientSearch - Implementación específica para búsqueda de clientes
 * Extiende SearchManager con funcionalidades específicas para clientes
 */

class ClientSearch extends SearchManager {
    constructor(options = {}) {
        const defaultConfig = {
            inputId: 'company_search',
            resultsId: 'search_results',
            loadingId: 'search_loading',
            clearId: 'clear_search',
            selectedId: 'selected_company',
            endpoint: '/api/clients/search',
            minQueryLength: 2,
            maxResults: 15,
            debounceTime: 150,
            showNotifications: true,
            fillFields: true,
            fieldMapping: {
                'nit': 'documento',
                'documento': 'documento',
                'address': 'direccion',
                'direccion': 'direccion',
                'phone': 'telefono',
                'telefono': 'telefono',
                'email': 'email',
                'city': 'ciudad',
                'ciudad': 'ciudad',
                'contact_name': 'contacto',
                'contacto': 'contacto',
                'employee_type': 'cargo',
                'cargo': 'cargo'
            },
            clearFields: [
                'nit', 'documento', 'address', 'direccion', 
                'phone', 'telefono', 'email', 'city', 'ciudad', 
                'contact_name', 'contacto', 'employee_type', 'cargo'
            ],
            transformResults: (data) => data.clients || data.data || data,
            onSelect: (client, isQuickSelect) => {
                console.log(`Cliente seleccionado: ${client.cliente || client.name}`, { client, isQuickSelect });
                
                // Cerrar modal en selección rápida
                if (isQuickSelect) {
                    const modal = document.querySelector('[x-show="open"]');
                    if (modal) {
                        modal.dispatchEvent(new CustomEvent('close-modal'));
                    }
                }
            }
        };

        super({ ...defaultConfig, ...options });
    }

    /**
     * Renderizar contenido específico del cliente
     */
    renderItemContent(client, query) {
        const clientName = client.cliente || client.contacto || 'Cliente';
        const highlightedName = this.highlightMatch(clientName, query);
        
        return `
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900 text-sm">${highlightedName}</h4>
                    ${this.renderClientDetails(client, query)}
                    ${this.renderClientExtras(client, query)}
                </div>
                ${this.renderClientStatus(client)}
            </div>
        `;
    }

    /**
     * Renderizar detalles del cliente
     */
    renderClientDetails(client, query) {
        const details = [];
        
        if (client.documento) {
            details.push(`Doc: ${this.highlightMatch(client.documento.toString(), query)}`);
        }
        if (client.ciudad) {
            details.push(this.highlightMatch(client.ciudad, query));
        }

        return details.length > 0 ? 
            `<div class="text-xs text-gray-500 mt-1">${details.join(' • ')}</div>` : '';
    }

    /**
     * Renderizar información extra del cliente
     */
    renderClientExtras(client, query) {
        const extras = [];
        
        if (client.telefono) {
            extras.push(`📞 ${this.highlightMatch(client.telefono.toString(), query)}`);
        }
        if (client.email && client.email !== 'ninguno') {
            extras.push(`📧 ${this.highlightMatch(client.email, query)}`);
        }

        let extrasHtml = '';
        if (extras.length > 0) {
            extrasHtml += `<div class="text-xs text-gray-600 mt-1 space-x-2">${extras.join(' ')}</div>`;
        }

        if (client.contacto) {
            extrasHtml += `<div class="text-xs text-purple-600 mt-1">👤 ${this.highlightMatch(client.contacto, query)}</div>`;
        }
        if (client.cargo) {
            extrasHtml += `<div class="text-xs text-blue-600 mt-1">💼 ${this.highlightMatch(client.cargo, query)}</div>`;
        }

        return extrasHtml;
    }

    /**
     * Renderizar estado del cliente
     */
    renderClientStatus(client) {
        const isActive = client.estado?.toLowerCase() === 'activo';
        const statusClass = isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600';
        const statusText = isActive ? '✅ Activo' : '⭕ Inactivo';
        
        return `
            <div class="text-right">
                <span class="px-2 py-1 text-xs rounded-full ${statusClass}">
                    ${statusText}
                </span>
                ${client.match_field ? `<div class="text-xs text-gray-500 mt-1">🎯 ${client.match_field}</div>` : ''}
            </div>
        `;
    }

    /**
     * Obtener valor de visualización para clientes
     */
    getDisplayValue(client) {
        return client.cliente || client.contacto || 'Cliente';
    }
}

/**
 * CompanySearch - Implementación para búsqueda de empresas
 */
class CompanySearch extends SearchManager {
    constructor(options = {}) {
        const defaultConfig = {
            inputId: 'company-search',
            resultsId: 'search-results',
            loadingId: 'search-loading',
            endpoint: '/api/search/companies',
            minQueryLength: 2,
            maxResults: 10,
            debounceTime: 400,
            showNotifications: false,
            transformResults: (data) => data,
            onSelect: (company) => {
                console.log(`Empresa seleccionada: ${company.name}`);
            }
        };

        super({ ...defaultConfig, ...options });
    }

    /**
     * Renderizar contenido específico de la empresa
     */
    renderItemContent(company, query) {
        const isInactive = company.estado === 'INACTIVO';
        const companyName = company.name || company.cliente || 'Sin nombre';
        const highlightedName = this.highlightMatch(companyName, query);
        
        return `
            <div class="font-bold ${isInactive ? 'text-red-700' : 'text-gray-800'}">
                ${isInactive ? '🔒' : '✅'} ${highlightedName}
            </div>
            <div class="text-sm text-gray-600">
                ${company.documento} | ${company.telefono} | ${company.ciudad}
            </div>
            ${isInactive ? '<div class="text-xs text-red-600 mt-1">⚠️ Empresa inactiva</div>' : ''}
        `;
    }

    /**
     * Seleccionar empresa con confirmación para inactivas
     */
    selectItem(company, isQuickSelect = false) {
        const isInactive = company.estado === 'INACTIVO';
        
        if (isInactive) {
            if (confirm(`¿Usar empresa inactiva: ${company.name}?`)) {
                super.selectItem(company, isQuickSelect);
            }
        } else {
            super.selectItem(company, isQuickSelect);
        }
    }

    /**
     * Obtener valor de visualización para empresas
     */
    getDisplayValue(company) {
        return company.name || company.cliente || 'Sin nombre';
    }
}

// Funciones de inicialización
function initializeClientSearch(options = {}) {
    // Prevenir doble inicialización
    if (window.__clientSearchInitialized) return;
    window.__clientSearchInitialized = true;

    try {
        const clientSearch = new ClientSearch(options);
        window.clientSearchInstance = clientSearch;
        return clientSearch;
    } catch (error) {
        console.error('Error initializing client search:', error);
        return null;
    }
}

function initializeCompanySearch(options = {}) {
    // Prevenir doble inicialización
    if (window.__companySearchInitialized) return;
    window.__companySearchInitialized = true;

    try {
        const companySearch = new CompanySearch(options);
        window.companySearchInstance = companySearch;
        return companySearch;
    } catch (error) {
        console.error('Error initializing company search:', error);
        return null;
    }
}

// Auto-inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar búsqueda de clientes si existe el elemento
    if (document.getElementById('company_search')) {
        initializeClientSearch();
    }
    
    // Inicializar búsqueda de empresas si existe el elemento
    if (document.getElementById('company-search')) {
        initializeCompanySearch();
    }
});

// Exports
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { 
        ClientSearch, 
        CompanySearch, 
        initializeClientSearch, 
        initializeCompanySearch 
    };
}

if (typeof window !== 'undefined') {
    window.ClientSearch = ClientSearch;
    window.CompanySearch = CompanySearch;
    window.initializeClientSearch = initializeClientSearch;
    window.initializeCompanySearch = initializeCompanySearch;
}
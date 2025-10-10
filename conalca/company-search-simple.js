// Búsqueda AJAX Simplificada para Empresas
console.log('🚀 Script AJAX de búsqueda cargado');

function setupCompanySearch() {
    console.log('🔧 Configurando búsqueda de empresas...');
    
    // Buscar elementos
    const searchField = document.getElementById('company-search');
    const resultsBox = document.getElementById('search-results');
    const loadingBox = document.getElementById('search-loading');
    
    if (!searchField) {
        console.error('❌ Campo de búsqueda no encontrado');
        return;
    }
    
    console.log('✅ Campo encontrado, añadiendo eventos...');
    
    let searchTimer;
    
    // Evento principal de búsqueda
    searchField.addEventListener('input', function() {
        const searchText = this.value.trim();
        console.log('📝 Búsqueda:', searchText);
        
        // Limpiar timer anterior
        clearTimeout(searchTimer);
        
        // Si es muy corto, ocultar resultados
        if (searchText.length < 2) {
            hideResults();
            return;
        }
        
        // Esperar 400ms antes de buscar
        searchTimer = setTimeout(() => {
            searchCompanies(searchText);
        }, 400);
    });
    
    // Función de búsqueda AJAX
    function searchCompanies(query) {
        console.log('🔍 Buscando:', query);
        
        showLoading();
        
        fetch(`/api/search/companies?q=${encodeURIComponent(query)}`)
            .then(response => {
                console.log('📡 Respuesta:', response.status);
                return response.json();
            })
            .then(companies => {
                console.log('📊 Empresas:', companies.length);
                showResults(companies);
            })
            .catch(error => {
                console.error('❌ Error:', error);
                showError();
            });
    }
    
    // Mostrar resultados
    function showResults(companies) {
        hideLoading();
        
        if (!resultsBox) return;
        
        resultsBox.innerHTML = '';
        
        if (companies.length === 0) {
            resultsBox.innerHTML = '<div class="p-3 text-gray-500">No hay empresas</div>';
        } else {
            companies.forEach(company => {
                const isInactive = company.estado === 'INACTIVO';
                const item = document.createElement('div');
                
                item.className = `p-3 cursor-pointer border-b hover:bg-gray-50 ${
                    isInactive ? 'bg-red-50 border-l-4 border-red-500' : ''
                }`;
                
                item.innerHTML = `
                    <div class="font-bold ${isInactive ? 'text-red-700' : 'text-gray-800'}">
                        ${isInactive ? '🔒' : '✅'} ${company.name}
                    </div>
                    <div class="text-sm text-gray-600">
                        ${company.documento} | ${company.telefono} | ${company.ciudad}
                    </div>
                    ${isInactive ? '<div class="text-xs text-red-600 mt-1">⚠️ Empresa inactiva</div>' : ''}
                `;
                
                item.addEventListener('click', () => {
                    if (isInactive) {
                        if (confirm(`¿Usar empresa inactiva: ${company.name}?`)) {
                            selectCompany(company);
                        }
                    } else {
                        selectCompany(company);
                    }
                });
                
                resultsBox.appendChild(item);
            });
        }
        
        resultsBox.classList.remove('hidden');
    }
    
    // Seleccionar empresa
    function selectCompany(company) {
        console.log('🎯 Seleccionada:', company.name);
        searchField.value = company.name;
        hideResults();
        
        // Disparar evento para Livewire
        const event = new Event('input', { bubbles: true });
        searchField.dispatchEvent(event);
    }
    
    // Funciones auxiliares
    function showLoading() {
        if (loadingBox) loadingBox.classList.remove('hidden');
        hideResults();
    }
    
    function hideLoading() {
        if (loadingBox) loadingBox.classList.add('hidden');
    }
    
    function hideResults() {
        if (resultsBox) resultsBox.classList.add('hidden');
    }
    
    function showError() {
        hideLoading();
        if (resultsBox) {
            resultsBox.innerHTML = '<div class="p-3 text-red-500">Error al buscar</div>';
            resultsBox.classList.remove('hidden');
        }
    }
    
    // Evento para ocultar al perder foco
    searchField.addEventListener('blur', () => {
        setTimeout(hideResults, 200);
    });
    
    console.log('✅ Búsqueda configurada correctamente');
}

// Inicializar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupCompanySearch);
} else {
    setupCompanySearch();
}

// Reintentar cada segundo hasta encontrar el elemento
let retryCount = 0;
const retryInterval = setInterval(() => {
    if (document.getElementById('company-search')) {
        setupCompanySearch();
        clearInterval(retryInterval);
    } else if (++retryCount > 10) {
        console.warn('⚠️ No se pudo inicializar búsqueda después de 10 intentos');
        clearInterval(retryInterval);
    }
}, 1000);

console.log('🏁 Script de búsqueda listo');
@extends('layout.app')

@section('title')
    {{ 'Contactos' }}
@endsection

@push('scripts')
    @vite('resources/js/dropzone.js')
    <script src="{{ asset('js/client-assignments.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const contactRadios = document.querySelectorAll('.radio');
            const contactDetail = document.getElementById('contact-details');
            const uploadedDocumentsContainer = document.getElementById('uploaded-documents');

            contactRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        const contact = JSON.parse(this.getAttribute('data-contact-id'));
                        
                        // Helper function to safely set element values
                        const setElementValue = (id, value) => {
                            const element = document.getElementById(id);
                            if (element) {
                                element.value = value || '';
                            }
                        };
                        
                        setElementValue('nit_update', contact?.nit);
                        setElementValue('sector_update', contact?.sector);
                        setElementValue('address_update', contact?.address);
                        setElementValue('email_update', contact?.email);
                        setElementValue('position_update', contact?.position);
                        setElementValue('contact_update', contact?.contact);
                        setElementValue('phone_update', contact?.phone);
                        setElementValue('city_update', contact?.city);
                        setElementValue('main_contact_update', contact?.main_contact);
                        setElementValue('contact_title_update', contact?.contact_title);
                        setElementValue('address_2_update', contact?.address_2);
                        setElementValue('email_2_update', contact?.email_2);
                        setElementValue('company_update', contact?.company);
                        setElementValue('projected_value_update', contact?.projected_value);
                        setElementValue('date_update', contact?.date);
                        setElementValue('city_2_update', contact?.city_2);
                        setElementValue('document_update', contact?.document);
                        setElementValue('id_update', contact?.id);
                        setElementValue('contact_id_document', contact?.id);

                        // Safely show contact details if element exists
                        if (contactDetail) {
                            contactDetail.style.display = 'flex';
                        }
                        
                        if (contact?.files && contact.files.length > 0) {
                            if (uploadedDocumentsContainer) {
                                uploadedDocumentsContainer.innerHTML = '';
                            }
                            contact.files.forEach(file => {
                                const fileContainer = document.createElement('div');
                                fileContainer.className = 'file-preview';
                                fileContainer.style.cssText = 'display: flex; flex-direction: column; align-items: center; padding: 0.5rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb; transition: all 0.2s; cursor: pointer;';
                                
                                fileContainer.addEventListener('mouseover', function() {
                                    this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
                                    this.style.transform = 'translateY(-2px)';
                                });
                                
                                fileContainer.addEventListener('mouseout', function() {
                                    this.style.boxShadow = 'none';
                                    this.style.transform = 'translateY(0)';
                                });
                                
                                const icon = document.createElement('div');
                                icon.style.cssText = 'display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; background: #fef3c7; border-radius: 8px; margin-bottom: 0.5rem;';
                                icon.innerHTML = `
                                    <svg style="width: 1.5rem; height: 1.5rem; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                `;
                                
                                const fileName = document.createElement('span');
                                fileName.style.cssText = 'font-size: 0.75rem; color: #374151; text-align: center; line-height: 1.2; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';
                                fileName.textContent = file.name.length > 12 ? file.name.substring(0, 12) + '...' : file.name;
                                fileName.title = file.name;
                                
                                fileContainer.appendChild(icon);
                                fileContainer.appendChild(fileName);
                                
                                // Crear el enlace para abrir el archivo
                                const fileLink = document.createElement('a');
                                fileLink.href = `/uploads/${file.file_path || file.name}`;
                                fileLink.target = '_blank';
                                fileLink.style.cssText = 'display: block; width: 100%; height: 100%; text-decoration: none;';
                                fileLink.appendChild(fileContainer);
                                
                                if (uploadedDocumentsContainer) {
                                    uploadedDocumentsContainer.appendChild(fileLink);
                                }
                            });
                        } else {
                            if (uploadedDocumentsContainer) {
                                uploadedDocumentsContainer.innerHTML = '<p style="color: #6b7280; text-align: center; grid-column: 1 / -1; padding: 1rem;">No hay documentos disponibles</p>';
                            }
                        }
                    } else {
                        if (contactDetail) {
                            contactDetail.style.display = 'none';
                        }
                    }
                });
            });

        });
    </script>
@endpush

@section('content')
    <section
        class="w-full bg-[#F9F9F9] text-[#232323] pt-[2.37rem] md:pl-11 md:pr-[1.11rem] pb-[3.81rem] flex flex-col h-full overflow-y-auto
    scrollbar-default">
        @component('contacts.components.headContacts', ['clients' => $clients])
        @endcomponent
        <div class="flex w-full flex-col md:flex-row md:gap-[1.44rem]">
            <div class="w-full md:w-[59%] mb-6 md:mb-0">
                @component('contacts.components.contactsTable', ['clients' => $clients])
                @endcomponent
            </div>
            {{-- client detail --}}
            <div class="w-full md:w-[42%] h-[54rem] bg-white rounded-[1.1875rem] shadow-md flex-col items-start overflow-y-auto
            scrollbar-default pl-[1.06rem] pr-[1.69rem] py-2"
                id="contact-details">
                @component('contacts.components.clientDetails')
                @endcomponent
            </div>
        </div>
        @component('contacts.components.createContactModal')
        @endcomponent

        @component('contacts.components.assignModal')
        @endcomponent

        <script>
            let currentClientId = null;
            let searchTimeout = null;
            
            // Search functionality
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('searchInput');
                
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        clearTimeout(searchTimeout);
                        const query = this.value;
                        
                        searchTimeout = setTimeout(() => {
                            performSearch(query);
                        }, 500); // Wait 500ms after user stops typing
                    });
                }
                
                // Handle client selection for details
                updateClientSelectors();
            });
            
            function performSearch(query) {
                const url = new URL(window.location.href);
                
                if (query.trim()) {
                    url.searchParams.set('search', query);
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.delete('page'); // Reset to first page
                
                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTableContainer = doc.getElementById('clientsTableContainer');
                    
                    if (newTableContainer) {
                        document.getElementById('clientsTableContainer').innerHTML = newTableContainer.innerHTML;
                        updateClientSelectors(); // Re-attach event listeners
                        updatePaginationLinks(); // Add pagination event listeners
                        
                        // Update URL without reloading the page
                        history.pushState(null, '', url.toString());
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
            }
            
            function updatePaginationLinks() {
                // Add click handlers for pagination links to work with AJAX
                const paginationLinks = document.querySelectorAll('#clientsTableContainer a[href*="page="]');
                
                paginationLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const url = this.href;
                        
                        fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newTableContainer = doc.getElementById('clientsTableContainer');
                            
                            if (newTableContainer) {
                                document.getElementById('clientsTableContainer').innerHTML = newTableContainer.innerHTML;
                                updateClientSelectors();
                                updatePaginationLinks(); // Re-attach pagination listeners
                                
                                // Update URL
                                history.pushState(null, '', url);
                                
                                // Scroll to top of table
                                document.getElementById('clientsTableContainer').scrollIntoView({ 
                                    behavior: 'smooth', 
                                    block: 'start' 
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Pagination error:', error);
                        });
                    });
                });
            }
            
            function updateClientSelectors() {
                const clientRadios = document.querySelectorAll('.client-selector');
                const clientDetail = document.getElementById('contact-details');

                clientRadios.forEach(radio => {
                    radio.removeEventListener('change', handleClientSelection); // Remove old listeners
                    radio.addEventListener('change', handleClientSelection);
                });
                
                // Initialize pagination links
                updatePaginationLinks();
            }
            
            function handleClientSelection() {
                if (this.checked) {
                    const clientId = this.getAttribute('data-client-id');
                    loadClientDetails(clientId);
                }
            }
            
            function loadClientDetails(clientId) {
                fetch(`/clients/${clientId}/details`)
                .then(response => response.json())
                .then(client => {
                    const clientDetail = document.getElementById('contact-details');
                    
                    // Populate client details using the correct field names that match the controller
                    document.getElementById('name_update').value = client?.cliente || '';
                    document.getElementById('document_update').value = client?.documento || '';
                    document.getElementById('company_name_update').value = client?.cliente || '';
                    document.getElementById('phone_update').value = client?.telefono || '';
                    document.getElementById('personal_cell_update').value = client?.celular || '';
                    document.getElementById('address_update').value = client?.direccion || '';
                    document.getElementById('email_update').value = client?.email || '';
                    document.getElementById('city_update').value = client?.ciudad || '';
                    document.getElementById('id_update').value = client?.id || '';
                    
                    // Update header with client name
                    document.getElementById('client_company_name').textContent = client?.cliente || 'Cliente seleccionado';
                    
                    // Enable the update button
                    const updateBtn = document.getElementById('update_client_btn');
                    if (updateBtn) {
                        updateBtn.disabled = false;
                        updateBtn.style.opacity = '1';
                        updateBtn.style.cursor = 'pointer';
                    }
                    
                    clientDetail.style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error loading client details:', error);
                    alert('Error al cargar los detalles del cliente');
                });
            }
            
            // Global function to open create modal
            function openCreateClientModal() {
                console.log('Opening create client modal...');
                const modal = document.getElementById('createContactModal');
                
                if (modal) {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    
                    // Add entrance animation
                    setTimeout(() => {
                        modal.style.opacity = '1';
                        const modalContent = modal.querySelector('div[style*="transform: scale(0.95)"]');
                        if (modalContent) {
                            modalContent.style.transform = 'scale(1)';
                        }
                    }, 10);
                } else {
                    console.error('Modal not found!');
                }
            }
            
            // Global function to close create modal
            function closeCreateClientModal() {
                console.log('Closing create client modal...');
                const modal = document.getElementById('createContactModal');
                
                if (modal) {
                    // Add exit animation
                    const modalContent = modal.querySelector('div[style*="transform: scale(1)"]');
                    if (modalContent) {
                        modalContent.style.transform = 'scale(0.95)';
                    }
                    
                    setTimeout(() => {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                        
                        // Reset form
                        const form = modal.querySelector('form');
                        if (form) form.reset();
                    }, 200);
                } else {
                    console.error('Modal not found!');
                }
            }

            // Global function to open assign modal
            function openAssignModal(clientId) {
                @if(auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL']))
                console.log('Opening assign modal for client:', clientId);
                currentClientId = clientId;
                const modal = document.getElementById('assignModal');
                
                if (modal) {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    loadAssignedUsers(clientId);
                }
                @endif
            }

            // Global function to close assign modal
            function closeAssignModal() {
                console.log('Closing assign modal...');
                const modal = document.getElementById('assignModal');
                
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    currentClientId = null;
                    
                    // Reset form
                    document.getElementById('userSelect').value = '';
                    document.getElementById('assignedUsersContainer').innerHTML = '';
                }
            }

            // Function to assign user
            function assignUser() {
                const userSelect = document.getElementById('userSelect');
                const userId = userSelect.value;
                
                if (!userId || !currentClientId) {
                    alert('Por favor seleccione un usuario');
                    return;
                }

                // Make AJAX request to assign user
                fetch('/clients/assign', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        client_id: currentClientId,
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        userSelect.value = '';
                        loadAssignedUsers(currentClientId);
                        location.reload(); // Reload to update the table
                    } else {
                        alert(data.message || 'Error al asignar usuario');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al asignar usuario');
                });
            }

            // Function to load assigned users
            function loadAssignedUsers(clientId) {
                fetch(`/clients/${clientId}/assigned-users`)
                .then(response => response.json())
                .then(users => {
                    const container = document.getElementById('assignedUsersContainer');
                    container.innerHTML = '';
                    
                    if (users.length === 0) {
                        container.innerHTML = '<p style="color: #6b7280; font-size: 0.875rem;">No hay usuarios asignados</p>';
                        return;
                    }
                    
                    users.forEach(user => {
                        const userDiv = document.createElement('div');
                        userDiv.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: #f9fafb; border-radius: 6px; margin-bottom: 0.5rem;';
                        userDiv.innerHTML = `
                            <span style="font-size: 0.875rem; color: #374151;">${user.name}</span>
                            <button onclick="removeAssignment(${user.id})" style="color: #ef4444; background: none; border: none; cursor: pointer; font-size: 0.75rem; padding: 0.25rem;">
                                Quitar
                            </button>
                        `;
                        container.appendChild(userDiv);
                    });
                })
                .catch(error => {
                    console.error('Error loading assigned users:', error);
                });
            }

            // Function to remove assignment
            function removeAssignment(userId) {
                if (!confirm('¿Está seguro de quitar esta asignación?')) {
                    return;
                }

                fetch('/clients/remove-assignment', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        client_id: currentClientId,
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadAssignedUsers(currentClientId);
                        location.reload(); // Reload to update the table
                    } else {
                        alert(data.message || 'Error al quitar asignación');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al quitar asignación');
                });
            }
            
            // Close modals when clicking outside
            document.addEventListener('click', function(event) {
                const createModal = document.getElementById('createContactModal');
                const assignModal = document.getElementById('assignModal');
                
                if (createModal && event.target === createModal) {
                    closeCreateClientModal();
                }
                
                if (assignModal && event.target === assignModal) {
                    closeAssignModal();
                }
            });
            
            // Close modals on ESC key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeCreateClientModal();
                    closeAssignModal();
                }
            });
        </script>
    </section>
@endsection

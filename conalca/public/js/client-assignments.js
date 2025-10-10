/**
 * Client Assignment Management JavaScript
 * Handles assignment and unassignment of users to clients
 */

class ClientAssignmentManager {
    constructor() {
        this.initializeEventListeners();
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    initializeEventListeners() {
        // Delegated event listeners for dynamically created elements
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('assign-user-btn')) {
                this.handleAssignUser(e);
            }
            
            if (e.target.classList.contains('remove-assignment-btn')) {
                this.handleRemoveAssignment(e);
            }
            
            if (e.target.classList.contains('view-assignments-btn')) {
                this.handleViewAssignments(e);
            }
        });

        // Form submission for assignment modal
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'assignUserForm') {
                e.preventDefault();
                this.submitAssignment(e.target);
            }
        });
    }

    /**
     * Handle assign user button click
     */
    async handleAssignUser(event) {
        const clientId = event.target.dataset.clientId;
        const clientName = event.target.dataset.clientName;
        
        if (!clientId) {
            this.showAlert('Error: ID de cliente no encontrado', 'error');
            return;
        }

        try {
            // Load available users for assignment
            await this.loadAvailableUsers(clientId);
            
            // Update modal title
            const modal = document.getElementById('assignUserModal');
            const titleElement = modal.querySelector('.modal-title');
            if (titleElement) {
                titleElement.textContent = `Asignar Usuario a: ${clientName}`;
            }
            
            // Set client ID in form
            const clientIdInput = modal.querySelector('#assign_client_id');
            if (clientIdInput) {
                clientIdInput.value = clientId;
            }
            
            // Show modal (assuming Bootstrap modal)
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
            
        } catch (error) {
            console.error('Error loading assignment modal:', error);
            this.showAlert('Error al cargar la modal de asignación', 'error');
        }
    }

    /**
     * Load available users for assignment
     */
    async loadAvailableUsers(clientId) {
        try {
            // Get users with commercial roles
            const response = await fetch('/api/users/commercial-roles', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Authorization': `Bearer ${this.getAuthToken()}`
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load users');
            }

            const users = await response.json();
            this.populateUserSelect(users);
            
        } catch (error) {
            console.error('Error loading users:', error);
            this.showAlert('Error al cargar los usuarios disponibles', 'error');
        }
    }

    /**
     * Populate user select dropdown
     */
    populateUserSelect(users) {
        const select = document.getElementById('assign_user_id');
        if (!select) return;

        // Clear existing options
        select.innerHTML = '<option value="">Seleccionar Usuario...</option>';

        // Add user options
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = `${user.name} (${user.email})`;
            select.appendChild(option);
        });
    }

    /**
     * Submit assignment form
     */
    async submitAssignment(form) {
        const formData = new FormData(form);
        const data = {
            client_id: formData.get('client_id'),
            user_id: formData.get('user_id')
        };

        if (!data.client_id || !data.user_id) {
            this.showAlert('Por favor selecciona un usuario', 'warning');
            return;
        }

        try {
            const response = await fetch('/api/clients/assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Authorization': `Bearer ${this.getAuthToken()}`
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok) {
                this.showAlert(result.success || 'Usuario asignado exitosamente', 'success');
                this.closeModal('assignUserModal');
                this.refreshClientTable();
            } else {
                this.showAlert(result.error || 'Error al asignar usuario', 'error');
            }

        } catch (error) {
            console.error('Error assigning user:', error);
            this.showAlert('Error de conexión al asignar usuario', 'error');
        }
    }

    /**
     * Handle view assignments button click
     */
    async handleViewAssignments(event) {
        const clientId = event.target.dataset.clientId;
        const clientName = event.target.dataset.clientName;
        
        if (!clientId) {
            this.showAlert('Error: ID de cliente no encontrado', 'error');
            return;
        }

        try {
            const response = await fetch(`/api/clients/${clientId}/assigned-users`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Authorization': `Bearer ${this.getAuthToken()}`
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load assignments');
            }

            const assignedUsers = await response.json();
            this.displayAssignments(clientId, clientName, assignedUsers);
            
        } catch (error) {
            console.error('Error loading assignments:', error);
            this.showAlert('Error al cargar las asignaciones', 'error');
        }
    }

    /**
     * Display assignments in modal
     */
    displayAssignments(clientId, clientName, assignedUsers) {
        const modal = document.getElementById('viewAssignmentsModal');
        const titleElement = modal.querySelector('.modal-title');
        const bodyElement = modal.querySelector('.assignments-list');

        if (titleElement) {
            titleElement.textContent = `Usuarios Asignados a: ${clientName}`;
        }

        if (bodyElement) {
            if (assignedUsers.length === 0) {
                bodyElement.innerHTML = '<p class="text-muted">No hay usuarios asignados a este cliente.</p>';
            } else {
                bodyElement.innerHTML = assignedUsers.map(user => `
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong>${user.name}</strong><br>
                            <small class="text-muted">${user.email}</small>
                        </div>
                        <button type="button" 
                                class="btn btn-sm btn-outline-danger remove-assignment-btn"
                                data-client-id="${clientId}"
                                data-user-id="${user.id}"
                                data-user-name="${user.name}">
                            Desasignar
                        </button>
                    </div>
                `).join('');
            }
        }

        // Show modal
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }

    /**
     * Handle remove assignment
     */
    async handleRemoveAssignment(event) {
        const clientId = event.target.dataset.clientId;
        const userId = event.target.dataset.userId;
        const userName = event.target.dataset.userName;

        if (!confirm(`¿Estás seguro de desasignar a ${userName} de este cliente?`)) {
            return;
        }

        try {
            const response = await fetch('/api/clients/remove-assignment', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Authorization': `Bearer ${this.getAuthToken()}`
                },
                body: JSON.stringify({
                    client_id: clientId,
                    user_id: userId
                })
            });

            const result = await response.json();

            if (response.ok) {
                this.showAlert(result.success || 'Usuario desasignado exitosamente', 'success');
                // Remove the assignment from the current view
                event.target.closest('.d-flex').remove();
                this.refreshClientTable();
            } else {
                this.showAlert(result.error || 'Error al desasignar usuario', 'error');
            }

        } catch (error) {
            console.error('Error removing assignment:', error);
            this.showAlert('Error de conexión al desasignar usuario', 'error');
        }
    }

    /**
     * Show alert message
     */
    showAlert(message, type = 'info') {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Find container or create one
        let container = document.querySelector('.alert-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'alert-container';
            document.body.insertBefore(container, document.body.firstChild);
        }

        container.appendChild(alertDiv);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    /**
     * Close modal
     */
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        }
    }

    /**
     * Refresh client table
     */
    refreshClientTable() {
        // This could trigger a page reload or AJAX refresh of the table
        location.reload();
    }

    /**
     * Get authentication token (adjust based on your auth implementation)
     */
    getAuthToken() {
        // If using Sanctum with SPA authentication
        return localStorage.getItem('auth_token') || '';
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.clientAssignmentManager = new ClientAssignmentManager();
});

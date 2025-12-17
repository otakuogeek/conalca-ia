import React, { useEffect, useState } from 'react';
import { fetchUsers, getAssignableRoles } from '../../services/userService';
import UserList from './UserList';
import UserForm from './UserForm';
import { FaUsers, FaSearch, FaFilter, FaPlus, FaTimes, FaUserShield } from 'react-icons/fa';
import { HiSparkles } from 'react-icons/hi';

const UserPanel = () => {
  const [users, setUsers] = useState([]);
  const [roles, setRoles] = useState([]);
  const [filters, setFilters] = useState({ role: '', status: '', name: '' });
  const [userToEdit, setUserToEdit] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [showModal, setShowModal] = useState(false);

  const loadUsers = async () => {
    try {
      const res = await fetchUsers(filters);
      setUsers(res.data);
    } catch (err) {
      console.error('Failed to fetch users', err);
    }
  };

  const loadRoles = async () => {
    try {
      const res = await getAssignableRoles();
      setRoles(res.data);
    } catch (err) {
      console.error('Failed to load roles', err);
    }
  };

  useEffect(() => {
    loadRoles();
  }, []);

  useEffect(() => {
    loadUsers();
  }, [filters]);

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100/50 to-gray-100 p-4 md:p-6 font-['Inter',sans-serif]">
      <div className="max-w-7xl mx-auto">
        {/* Header Section */}
        <div className="mb-8">
          <div className="bg-white/90 backdrop-blur-sm rounded-3xl shadow-sm border border-orange-100/50 overflow-hidden">
            <div className="relative bg-white p-8 border-b border-gray-200/50">
              <div className="relative flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="w-14 h-14 flex items-center justify-center bg-orange-500/10 rounded-2xl border border-orange-200/50">
                    <FaUserShield className="text-2xl text-orange-600" />
                  </div>
                  <div>
                    <h1 className="text-3xl md:text-4xl font-bold text-gray-900 flex items-center gap-3">
                      Panel de Administración
                      <HiSparkles className="text-orange-500 animate-pulse" />
                    </h1>
                    <p className="text-gray-600 text-lg mt-1">Gestiona usuarios y permisos de forma intuitiva</p>
                  </div>
                </div>
                {/* Action Button */}
                {!userToEdit && (
                  <div>
                    <button
                      onClick={() => setShowModal(true)}
                      className="group px-6 py-4 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
                    >
                      <div className="flex items-center gap-3">
                        <FaPlus className="text-lg transition-transform group-hover:rotate-90 duration-300" />
                        Crear nuevo usuario
                      </div>
                    </button>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Filters Section */}
        <div className="mb-6">
          <div className="bg-white/95 backdrop-blur-sm rounded-3xl shadow-sm border border-gray-200/60 p-6">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-8 h-8 flex items-center justify-center rounded-xl bg-blue-50 border border-blue-200/50">
                <FaFilter className="text-blue-600 text-sm" />
              </div>
              <h3 className="text-lg font-medium text-gray-800">Filtros de búsqueda</h3>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <FaSearch className="text-gray-400 text-sm" />
                </div>
                <input
                  placeholder="Buscar por nombre..."
                  className="w-full pl-12 pr-4 py-3 bg-white border border-gray-300/50 rounded-2xl focus:border-blue-400/70 focus:ring-2 focus:ring-blue-100/50 focus:outline-none transition-all duration-200 text-gray-700 placeholder-gray-400"
                  onChange={e => setFilters({ ...filters, name: e.target.value })}
                />
              </div>
              
              <div className="relative">
                <select 
                  className="w-full px-4 py-3 bg-white border border-gray-300/50 rounded-2xl focus:border-blue-400/70 focus:ring-2 focus:ring-blue-100/50 focus:outline-none transition-all duration-200 text-gray-700 appearance-none cursor-pointer"
                  onChange={e => setFilters({ ...filters, role: e.target.value })}
                >
                  <option value="">🎭 Todos los roles</option>
                  {roles.map(role => (
                    <option key={role} value={role}>{role}</option>
                  ))}
                </select>
                <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                  <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
              
              <div className="relative">
                <select 
                  className="w-full px-4 py-3 bg-white border border-gray-300/50 rounded-2xl focus:border-blue-400/70 focus:ring-2 focus:ring-blue-100/50 focus:outline-none transition-all duration-200 text-gray-700 appearance-none cursor-pointer"
                  onChange={e => setFilters({ ...filters, status: e.target.value })}
                >
                  <option value="">⚡ Todos los estados</option>
                  <option value="1">✅ Activo</option>
                  <option value="0">❌ Inactivo</option>
                </select>
                <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                  <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

       
        {/* Modal for User Form */}
        {(showModal || userToEdit) && (
          <div 
            className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-start justify-center pt-8 p-4 z-[99990] overflow-y-auto"
            onClick={(e) => {
              if (e.target === e.currentTarget) {
                setShowModal(false);
                setUserToEdit(null);
              }
            }}
          >
            <div className="bg-white rounded-2xl shadow-2xl border border-gray-200 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
              <div className="sticky top-0 bg-white border-b border-gray-100 p-4 flex items-center justify-between rounded-t-2xl">
                <h2 className="text-xl font-semibold text-gray-900">
                  {userToEdit ? 'Editar Usuario' : 'Crear Nuevo Usuario'}
                </h2>
                <button
                  onClick={() => {
                    setShowModal(false);
                    setUserToEdit(null);
                  }}
                  className="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200"
                >
                  <FaTimes className="text-gray-500 hover:text-gray-700" />
                </button>
              </div>
              <div className="p-6">
                <UserForm
                  onUserCreated={() => {
                    loadUsers();
                    setShowModal(false);
                    setUserToEdit(null);
                  }}
                  userToEdit={userToEdit}
                  onCancelEdit={() => {
                    setUserToEdit(null);
                    setShowModal(false);
                  }}
                />
              </div>
            </div>
          </div>
        )}

        {/* Users List */}
        <div className="transform transition-all duration-500 ease-out">
          <UserList
            users={users}
            onRefresh={loadUsers}
            onEditUser={(user) => {
              setUserToEdit(user);
              setShowModal(true);
            }}
          />
        </div>
      </div>
    </div>
  );
};

export default UserPanel;

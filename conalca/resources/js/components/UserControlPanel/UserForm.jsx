import React, { useState, useEffect } from 'react';
import { createUser, updateUser, getAssignableRoles, getCurrentUser, getPotentialParents  } from '../../services/userService';
import { FaUser, FaEnvelope, FaLock, FaUserShield, FaSave, FaTimes, FaUserPlus, FaEdit, FaExclamationTriangle } from 'react-icons/fa';
import { HiSparkles } from 'react-icons/hi';

const UserForm = ({ onUserCreated, userToEdit = null, onCancelEdit }) => {
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    role: '',
  });
  const [error, setError] = useState(null);
  const [roles, setRoles] = useState([]);
  const [currentUser, setCurrentUser] = useState(null);
  const [parentOptions, setParentOptions] = useState([]);
  const [parentId, setParentId] = useState('');


  useEffect(() => {
    if (userToEdit) {
      setForm({
        name: userToEdit.name,
        email: userToEdit.email,
        password: '', // No se edita por defecto
        role: userToEdit.roles[0]?.name || '',
      });
    } else {
      setForm({ name: '', email: '', password: '', role: '' });
    }
  }, [userToEdit]);

  useEffect(() => {
    const fetchRoles = async () => {
      try {
        const response = await getAssignableRoles();
        setRoles(response.data);
      } catch (err) {
        setError('Error loading roles');
      }
    };
    fetchRoles();

    const fetchCurrentUser = async () => {
        try {
        const res = await getCurrentUser();
        setCurrentUser(res.data);
        } catch (err) {
        setError('Failed to load current user');
        }
    };
    fetchCurrentUser();
  }, []);

    useEffect(() => {
        const loadParents = async () => {
            if (form.role && currentUser?.roles?.[0]?.name === 'SUPER ADMIN') {
            try {
                const response = await getPotentialParents(form.role);
                setParentOptions(response.data);
            } catch {
                setParentOptions([]);
            }
            } else {
            setParentOptions([]);
            }
        };
        loadParents();
    }, [form.role, currentUser]);


    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (userToEdit) {
            const updateData = {
              name: form.name,
              email: form.email,
            };

            if (form.password.trim() !== '') {
              updateData.password = form.password;
            }

            // Evitar enviar el rol si el usuario está editando su propio perfil
            const isEditingOwnUser = currentUser?.id === userToEdit.id;
            if (!isEditingOwnUser) {
              updateData.role = form.role;
            }


            if (form.password.trim() !== '') {
                updateData.password = form.password;
            }

            await updateUser(userToEdit.id, updateData);
            } else {
            await createUser({
            ...form,
            parent_id: parentId || currentUser?.id,
            });
            }

            setForm({ name: '', email: '', password: '', role: '' });
            setError(null);
            onUserCreated();
            if (onCancelEdit) onCancelEdit();
        } catch (err) {
            setError(err.response?.data?.error || 'An error occurred');
        }
    };


  return (
    <div className="bg-white">
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="space-y-2">
            <label className="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
              <FaUser className="text-gray-500" />
              Nombre completo
            </label>
            <div className="relative">
              <input
                type="text"
                placeholder="Ingrese el nombre completo"
                className="w-full px-4 py-3 pl-11 bg-white border border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all duration-200 text-gray-700 placeholder-gray-400"
                value={form.name}
                onChange={e => setForm({ ...form, name: e.target.value })}
                required
              />
              <FaUser className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400" />
            </div>
          </div>
          
          <div className="space-y-2">
            <label className="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
              <FaEnvelope className="text-gray-500" />
              Correo electrónico
            </label>
            <div className="relative">
              <input
                type="email"
                placeholder="ejemplo@correo.com"
                className="w-full px-4 py-3 pl-11 bg-white border border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all duration-200 text-gray-700 placeholder-gray-400"
                value={form.email}
                onChange={e => setForm({ ...form, email: e.target.value })}
                required
              />
              <FaEnvelope className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400" />
            </div>
          </div>
        </div>
        
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="space-y-2">
            <label className="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
              <FaLock className="text-gray-500" />
              {userToEdit ? "Nueva contraseña (opcional)" : "Contraseña"}
            </label>
            <div className="relative">
              <input
                type="password"
                placeholder={userToEdit ? "Dejar vacío para mantener actual" : "Ingrese una contraseña segura"}
                className="w-full px-4 py-3 pl-11 bg-white border border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all duration-200 text-gray-700 placeholder-gray-400"
                value={form.password}
                onChange={e => setForm({ ...form, password: e.target.value })}
              />
              <FaLock className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400" />
            </div>
          </div>
          
          <div className="space-y-2">
            <label className="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
              <FaUserShield className="text-orange-600" />
              Rol del usuario
            </label>
            <div className="relative">
              <select
                className="w-full px-4 py-3 pl-11 bg-white border border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all duration-200 text-gray-700 appearance-none cursor-pointer"
                value={form.role}
                onChange={e => setForm({ ...form, role: e.target.value })}
                required={!userToEdit}
                disabled={userToEdit && currentUser?.id === userToEdit.id}
              >
                <option value="">Seleccionar rol</option>
                {roles.map(role => (
                  <option key={role} value={role}>
                    {role}
                  </option>
                ))}
              </select>
              <FaUserShield className="absolute left-4 top-1/2 transform -translate-y-1/2 text-orange-500" />
              <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>
        </div>
        
        {form.role &&
          currentUser?.roles?.[0]?.name === 'SUPER ADMIN' &&
          ['GERENTE DE CUENTA', 'SAC', 'ASISTENTE COMERCIAL'].includes(form.role) && (
            <div className="space-y-2">
              <label className="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                <FaUserShield className="text-purple-600" />
                Supervisor
              </label>
              <div className="relative">
                <select
                  className="w-full px-4 py-3 pl-11 bg-white border border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all duration-200 text-gray-700 appearance-none cursor-pointer"
                  value={parentId}
                  onChange={e => setParentId(e.target.value)}
                  required
                >
                  <option value="">Seleccionar supervisor</option>
                  {parentOptions.map(parent => (
                    <option key={parent.id} value={parent.id}>{parent.name}</option>
                  ))}
                </select>
                <FaUserShield className="absolute left-4 top-1/2 transform -translate-y-1/2 text-purple-500" />
                <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                  <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>
        )}
        
        {userToEdit && currentUser?.id === userToEdit.id && (
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center gap-3">
            <FaExclamationTriangle className="text-amber-600 text-lg flex-shrink-0" />
            <div>
              <p className="text-amber-800 font-medium">Edición de perfil propio</p>
              <p className="text-amber-700 text-sm">No puedes cambiar tu propio rol por seguridad</p>
            </div>
          </div>
        )}
        
        <div className="flex gap-4 pt-4">
          <button 
            className="group flex-1 px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5" 
            type="submit"
          >
            <div className="flex items-center justify-center gap-3">
              <FaSave className="text-lg group-hover:rotate-6 transition-transform duration-300" />
              {userToEdit ? 'Actualizar Usuario' : 'Crear Usuario'}
            </div>
          </button>
          
          {userToEdit && (
            <button 
              type="button" 
              className="group px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5" 
              onClick={onCancelEdit}
            >
              <div className="flex items-center justify-center gap-3">
                <FaTimes className="text-lg group-hover:rotate-90 transition-transform duration-300" />
                Cancelar
              </div>
            </button>
          )}
        </div>
        
        {error && (
          <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
            <FaExclamationTriangle className="text-red-600 text-lg flex-shrink-0" />
            <div>
              <p className="text-red-800 font-medium">Error</p>
              <p className="text-red-700 text-sm">{error}</p>
            </div>
          </div>
        )}
      </form>
    </div>
  );
};

export default UserForm;

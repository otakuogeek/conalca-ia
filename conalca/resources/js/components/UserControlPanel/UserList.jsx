import React from 'react';
import DataTable from 'react-data-table-component';
import { deleteUser } from '../../services/userService';
import { FaUserEdit, FaUsers, FaUserCheck, FaUserTimes, FaCrown, FaShieldAlt } from 'react-icons/fa';
import { MdDelete, MdEmail, MdPerson } from 'react-icons/md';
import { HiSparkles } from 'react-icons/hi';

const UserList = ({ users, onRefresh, onEditUser }) => {
  const getRoleIcon = (role) => {
    switch (role) {
      case 'SUPER ADMIN': return <FaCrown className="text-amber-500" />;
      case 'JEFE COMERCIAL': return <FaShieldAlt className="text-orange-500" />;
      case 'GERENTE DE CUENTA': return <FaUserCheck className="text-orange-400" />;
      case 'SAC': return <FaUsers className="text-amber-400" />;
      case 'ASISTENTE COMERCIAL': return <MdPerson className="text-orange-300" />;
      case 'PRICING': return <HiSparkles className="text-amber-500" />;
      default: return <FaUsers className="text-gray-400" />;
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm('¿Estás seguro de que deseas desactivar este usuario?')) {
      await deleteUser(id);
      onRefresh();
    }
  };

  const columns = [
    {
      name: 'Usuario',
      cell: row => (
        <div className="flex items-center gap-3 py-2">
          <div className="w-10 h-10 bg-gradient-to-br from-indigo-100 to-purple-200 rounded-full flex items-center justify-center text-indigo-700 font-medium text-sm shadow-sm border border-indigo-200/50">
            {row.name.charAt(0).toUpperCase()}
          </div>
          <div>
            <div className="font-medium text-gray-800 flex items-center gap-2">
              <MdPerson className="text-gray-500 text-sm" />
              {row.name}
            </div>
            <div className="text-sm text-gray-500 flex items-center gap-2">
              <MdEmail className="text-gray-400 text-xs" />
              {row.email}
            </div>
          </div>
        </div>
      ),
      sortable: true,
      minWidth: '280px',
    },
    {
      name: 'Rol',
      cell: row => (
        <div className="flex items-center gap-2">
          {getRoleIcon(row.roles[0]?.name)}
          <span className="font-medium text-gray-700 px-3 py-1 bg-gray-50 border border-gray-200/60 rounded-full text-sm">
            {row.roles[0]?.name || 'Sin rol'}
          </span>
        </div>
      ),
      sortable: true,
      minWidth: '200px',
    },
    {
      name: 'Estado',
      cell: row => (
        <div className="flex items-center gap-2">
          {row.active ? (
            <FaUserCheck className="text-green-500" />
          ) : (
            <FaUserTimes className="text-red-500" />
          )}
          <span
            className={`px-4 py-2 text-xs font-bold rounded-full border-2 ${
              row.active 
                ? 'bg-green-50 text-green-700 border-green-200' 
                : 'bg-red-50 text-red-700 border-red-200'
            }`}
          >
            {row.active ? 'Activo' : 'Inactivo'}
          </span>
        </div>
      ),
      sortable: true,
      center: true,
      minWidth: '150px',
    },
    {
      name: 'Acciones',
      cell: row => (
        <div className="flex gap-2 py-2">
          <button
            onClick={() => onEditUser(row)}
            className="group flex items-center gap-2 px-4 py-2 bg-blue-50 border border-blue-200/60 text-blue-700 hover:bg-blue-100 hover:border-blue-300/60 rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
          >
            <FaUserEdit className="text-sm group-hover:rotate-6 transition-transform duration-200" />
            Editar
          </button>
          <button
            onClick={() => handleDelete(row.id)}
            className="group flex items-center gap-2 px-4 py-2 bg-red-50 border border-red-200/60 text-red-700 hover:bg-red-100 hover:border-red-300/60 rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
          >
            <MdDelete className="text-sm group-hover:rotate-6 transition-transform duration-200" />
            Desactivar
          </button>
        </div>
      ),
      width: '270px',
      center: true,
    },
  ];

  return (
    <div className="bg-white/95 backdrop-blur-sm rounded-3xl shadow-sm border border-gray-200/60 overflow-hidden">
      <div className="relative bg-white p-6 border-b border-gray-200/50">
        <div className="relative flex items-center gap-4">
          <div className="w-12 h-12 flex items-center justify-center bg-indigo-50 rounded-2xl border border-indigo-200/50">
            <FaUsers className="text-xl text-indigo-600" />
          </div>
          <div>
            <h3 className="text-2xl font-bold text-gray-900 flex items-center gap-3">
              Lista de Usuarios
              <HiSparkles className="text-orange-500 animate-pulse" />
            </h3>
            <p className="text-gray-600 text-sm mt-1">
              Total: {users.length} usuario{users.length !== 1 ? 's' : ''}
            </p>
          </div>
        </div>
      </div>
      
      <div className="p-6">
        <DataTable
          columns={columns}
          data={users}
          pagination
          highlightOnHover
          striped
          responsive
          paginationComponentOptions={{
            rowsPerPageText: 'Filas por página:',
            rangeSeparatorText: 'de',
            noRowsPerPage: false,
            selectAllRowsItem: false,
          }}
          noDataComponent={
            <div className="py-12 text-center">
              <FaUsers className="mx-auto text-4xl text-gray-300 mb-4" />
              <p className="text-gray-500 text-lg font-medium">No hay usuarios que mostrar</p>
              <p className="text-gray-400 text-sm">Ajusta tus filtros o crea un nuevo usuario</p>
            </div>
          }
          customStyles={{
            headRow: {
              style: {
                backgroundColor: '#f8fafc',
                borderBottom: '1px solid #e2e8f0',
                color: '#334155',
                fontWeight: '600',
                minHeight: '60px',
              },
            },
            headCells: {
              style: {
                fontSize: '14px',
                fontWeight: '600',
                color: '#475569',
                paddingTop: '16px',
                paddingBottom: '16px',
                textTransform: 'uppercase',
                letterSpacing: '0.025em',
              },
            },
            rows: {
              style: {
                minHeight: '80px',
                borderBottom: '1px solid #f1f5f9',
                '&:nth-child(odd)': {
                  backgroundColor: '#fefefe',
                },
                '&:hover': {
                  backgroundColor: '#f8fafc',
                  cursor: 'pointer',
                  transform: 'translateY(-1px)',
                  boxShadow: '0 2px 4px -1px rgba(0, 0, 0, 0.1)',
                  transition: 'all 0.2s ease',
                },
              },
            },
            cells: {
              style: {
                fontSize: '14px',
                color: '#374151',
                paddingTop: '16px',
                paddingBottom: '16px',
                paddingLeft: '20px',
                paddingRight: '20px',
              },
            },
            pagination: {
              style: {
                borderTop: '1px solid #e2e8f0',
                backgroundColor: '#f8fafc',
                color: '#475569',
                minHeight: '60px',
              },
            },
          }}
        />
      </div>
    </div>
  );
};

export default UserList;



import React, { useState } from "react";

const COLOR_OPTIONS = [
  { color: "#E9F1F2", name: "Azul Claro" },
  { color: "#FAEFDB", name: "Naranja Claro" },
  { color: "#FAF5E8", name: "Amarillo Claro" },
  { color: "#FADBDB", name: "Rosa Claro" },
  { color: "#DDEBF3", name: "Azul Pálido" },
  { color: "#C7FFD1", name: "Verde Claro" },
  { color: "#FFD6E0", name: "Rosa Suave" },
  { color: "#FFF1C9", name: "Amarillo Suave" },
  { color: "#EFEFEF", name: "Gris Claro" },
  { color: "#FFD1C5", name: "Coral Claro" }
];

export default function CreateColumnModal({ open, onClose, onCreate }) {
  const [name, setName] = useState('');
  const [color, setColor] = useState(COLOR_OPTIONS[0].color);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!name.trim()) return;
    onCreate({ name: name.trim(), color });
    setName('');
    setColor(COLOR_OPTIONS[0].color);
  };

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-[99990] flex items-start justify-center pt-20 bg-black bg-opacity-50 backdrop-blur-sm p-4 overflow-y-auto">
      <div className="bg-white p-8 rounded-2xl shadow-2xl min-w-[400px] max-w-md w-full transform transition-all">
        <div className="flex items-center gap-3 mb-6">
          <div className="w-10 h-10 bg-gradient-to-br from-[#FF7C32] to-[#FF6B1A] rounded-xl flex items-center justify-center">
            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-gray-900">Nueva Columna</h2>
        </div>
        
        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Nombre de la columna
            </label>
            <input
              className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-[#FF7C32] focus:ring-0 transition-colors text-gray-900 placeholder-gray-400"
              placeholder="Ej: En revisión, Aprobado, etc."
              value={name}
              maxLength={40}
              onChange={e => setName(e.target.value)}
              required
              autoFocus
            />
            <p className="text-xs text-gray-500 mt-1">{name.length}/40 caracteres</p>
          </div>
          
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-3">
              Color de la columna
            </label>
            <div className="grid grid-cols-5 gap-3">
              {COLOR_OPTIONS.map(option => (
                <button
                  key={option.color}
                  type="button"
                  onClick={() => setColor(option.color)}
                  className={`relative w-12 h-12 rounded-xl transition-all duration-200 hover:scale-110 ${
                    color === option.color 
                      ? 'ring-3 ring-[#FF7C32] ring-offset-2 shadow-lg' 
                      : 'hover:shadow-md'
                  }`}
                  style={{ backgroundColor: option.color }}
                  title={option.name}
                >
                  {color === option.color && (
                    <div className="absolute inset-0 flex items-center justify-center">
                      <svg className="w-5 h-5 text-gray-700" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                      </svg>
                    </div>
                  )}
                </button>
              ))}
            </div>
          </div>
          
          <div className="flex gap-3 pt-4">
            <button
              type="button"
              className="flex-1 px-6 py-3 border-2 border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 font-medium"
              onClick={onClose}
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={!name.trim()}
              className="flex-1 px-6 py-3 bg-gradient-to-r from-[#FF7C32] to-[#FF6B1A] text-white rounded-xl hover:from-[#FF6B1A] hover:to-[#FF5A09] disabled:from-gray-300 disabled:to-gray-400 disabled:cursor-not-allowed transition-all duration-200 font-medium shadow-lg hover:shadow-xl"
            >
              Crear Columna
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
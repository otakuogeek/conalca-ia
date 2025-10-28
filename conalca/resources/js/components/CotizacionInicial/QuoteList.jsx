// resources/js/components/CotizacionInicial/QuoteList.jsx
import React from 'react';
import PropTypes from 'prop-types';

const QuoteList = ({ quotes = [], onOpenQuote }) => {
  // Componente para mostrar la lista de cotizaciones existentes
  // Por ahora es básico, se puede expandir según necesidades

  if (!quotes.length) {
    return (
      <div className="w-full flex items-center justify-center py-12">
        <div className="text-center">
          <div className="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
          <h3 className="text-lg font-medium text-gray-700 mb-2">No hay cotizaciones</h3>
          <p className="text-gray-500">Crea tu primera cotización haciendo clic en el botón superior.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full mt-8">
      <h2 className="text-xl font-semibold text-gray-800 mb-6">Cotizaciones Recientes</h2>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {quotes.map((quote, index) => (
          <QuoteCard key={index} quote={quote} onOpen={() => onOpenQuote(quote)} />
        ))}
      </div>
    </div>
  );
};

const QuoteCard = ({ quote, onOpen }) => {
  return (
    <div className="bg-white rounded-lg shadow-md border border-gray-200 p-6 hover:shadow-lg transition-shadow duration-200">
      <div className="flex items-start justify-between mb-4">
        <div>
          <h3 className="text-lg font-semibold text-gray-800 mb-1">
            {quote.numero || 'IDC001'}
          </h3>
          <p className="text-sm text-gray-600 uppercase font-medium">
            {quote.empresa || 'A&A CONSULTORIA E INGENIERIA S A S'}
          </p>
        </div>
        <span className="bg-orange-100 text-orange-800 text-xs font-medium px-2 py-1 rounded-full">
          {quote.estado || 'En proceso'}
        </span>
      </div>

      <div className="space-y-2 mb-4">
        <div className="flex justify-between items-center text-sm">
          <span className="text-gray-500">Origen:</span>
          <span className="text-gray-800 font-medium">{quote.origen || 'Bogotá'}</span>
        </div>
        <div className="flex justify-between items-center text-sm">
          <span className="text-gray-500">Destino:</span>
          <span className="text-gray-800 font-medium">{quote.destino || 'Medellín'}</span>
        </div>
        <div className="flex justify-between items-center text-sm">
          <span className="text-gray-500">Fecha:</span>
          <span className="text-gray-800 font-medium">
            {quote.fecha || new Date().toLocaleDateString()}
          </span>
        </div>
        <div className="flex justify-between items-center text-sm">
          <span className="text-gray-500">Valor:</span>
          <span className="text-green-600 font-semibold">
            ${quote.valor ? Number(quote.valor).toLocaleString() : '0'}
          </span>
        </div>
      </div>

      <div className="flex gap-2">
        <button
          onClick={onOpen}
          className="flex-1 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium py-2 px-4 rounded-lg transition-colors duration-200"
        >
          Ver Detalles
        </button>
        <button
          className="flex items-center justify-center w-10 h-10 border border-gray-300 hover:border-orange-500 hover:text-orange-500 text-gray-500 rounded-lg transition-colors duration-200"
          title="Más opciones"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
          </svg>
        </button>
      </div>
    </div>
  );
};

QuoteList.propTypes = {
  quotes: PropTypes.array,
  onOpenQuote: PropTypes.func.isRequired
};

QuoteCard.propTypes = {
  quote: PropTypes.object.isRequired,
  onOpen: PropTypes.func.isRequired
};

export default QuoteList;
// resources/js/quotes-react.jsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import { QuoteIndex } from './components/CotizacionInicial';

console.log('quotes-react.jsx loaded!');

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Searching for container...');
    const container = document.getElementById('quote-index-react-root');
    
    console.log('Container found:', container);
    
    if (container) {
        console.log('Creating React root...');
        const root = createRoot(container);
        
        console.log('Rendering QuoteIndex component...');
        root.render(<QuoteIndex />);
        
        console.log('✅ QuoteIndex React component mounted successfully!');
    } else {
        console.error('❌ Container #quote-index-react-root not found');
        console.log('Available containers:', document.querySelectorAll('[id]'));
    }
});

// También intentar después de un timeout por si acaso
setTimeout(() => {
    console.log('Timeout check - Searching for container again...');
    const container = document.getElementById('quote-index-react-root');
    if (container && !container.hasChildNodes()) {
        console.log('Container found on timeout, trying to mount...');
        const root = createRoot(container);
        root.render(<QuoteIndex />);
        console.log('✅ QuoteIndex mounted via timeout!');
    }
}, 1000);
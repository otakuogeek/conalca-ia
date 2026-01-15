/**
 * Utilidades para el chat de cotizaciones
 * @module CotizacionInicial/utils/chatUtils
 */

/**
 * Elimina formato markdown del texto
 * @param {string} text - Texto con posible formato markdown
 * @returns {string} Texto limpio sin markdown
 */
export const stripMarkdown = (text = '') => {
    return text
        // remove headings like ### Title
        .replace(/^#{1,6}\s*/gm, '')
        // bold / italic (**text**, __text__, *text*, _text_)
        .replace(/(\*\*|__)(.*?)\1/g, '$2')
        .replace(/(\*|_)(.*?)\1/g, '$2')
        // inline code `code`
        .replace(/`([^`]+)`/g, '$1')
        // links [label](url)
        .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
        // collapse multiple spaces (but keep newlines intact)
        .replace(/[ \t]{2,}/g, ' ')
        // optional: limit extra blank lines if needed
        .replace(/\n{3,}/g, '\n\n')
        .trim();
};

/**
 * Formatea un timestamp a formato de hora legible
 * @param {string|Date} timestamp - Timestamp a formatear
 * @returns {string} Hora formateada (HH:MM)
 */
export const formatMessageTime = (timestamp) => {
    const defaultTime = () => new Date().toLocaleTimeString('es-CO', {
        hour: '2-digit',
        minute: '2-digit'
    });

    if (!timestamp) {
        return defaultTime();
    }

    try {
        // Si ya es una hora formateada (HH:MM:SS o HH:MM), devolverla
        if (typeof timestamp === 'string' && /^\d{1,2}:\d{2}/.test(timestamp)) {
            return timestamp;
        }

        // Intentar parsear como fecha
        const date = new Date(timestamp);

        // Verificar si es fecha válida
        if (isNaN(date.getTime())) {
            return defaultTime();
        }

        return date.toLocaleTimeString('es-CO', {
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch {
        return defaultTime();
    }
};

/**
 * Detecta si el mensaje menciona un número de ruta específico
 * @param {string} messageText - Texto del mensaje
 * @returns {number|null} Índice de la ruta (base 0) o null si no se detecta
 */
export const detectRouteFromMessage = (messageText) => {
    if (!messageText) return null;

    const lower = messageText.toLowerCase();

    const patterns = [
        /(?:ruta|route)\s*(?:n[uú]mero|#|num\.?)?\s*(\d+)/i,
        /(?:cambiar?|editar?|modificar?|actualizar?)\s+(?:la\s+)?(?:ruta|route)\s*(\d+)/i,
        /(?:en\s+la\s+)?ruta\s*(\d+)/i,
        /(?:para\s+la\s+)?ruta\s*(\d+)/i,
        /(?:de\s+la\s+)?ruta\s*(\d+)/i,
        /ruta\s+(\d+)/i,
    ];

    for (const pattern of patterns) {
        const match = lower.match(pattern);
        if (match && match[1]) {
            const routeNum = parseInt(match[1], 10);
            return routeNum - 1; // Convertir a índice base 0
        }
    }

    return null;
};

/**
 * Obtiene el token CSRF de la página
 * @returns {string|null} Token CSRF o null
 */
export const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
};

export const normalizeRole = name =>
  name?.toUpperCase()
       .normalize('NFD')           // quita tildes
       .replace(/[\u0300-\u036f]/g,'')
       .replace(/\s+/g, '_');      // “ASISTENTE COMERCIAL” -> “ASISTENTE_COMERCIAL”
<?php

namespace App\Helpers;

class SilogtranHelper
{
    /**
     * Normaliza el código de cliente (debe ser numérico)
     */
    public static function normalizeClientCode($value)
    {
        // Si es numérico, devolver como está
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        // Si tiene formato CLI001, CLI002, etc., extraer el número
        if (preg_match('/CLI(\d+)/', $value, $matches)) {
            return (int) $matches[1];
        }
        
        // Por defecto, devolver 1
        return 1;
    }

    /**
     * Normaliza el código de moneda
     * Acepta: COP, USD, VEF y los convierte a formato Silogtran
     */
    public static function normalizeCurrency($value)
    {
        $map = [
            'COP' => 'PESOS',
            'PESOS' => 'PESOS',
            'USD' => 'DOLARES',
            'DOLARES' => 'DOLARES',
            'VEF' => 'BOLIVARES',
            'BOLIVARES' => 'BOLIVARES',
            'VES' => 'BOLIVARES FUERTES',
            'BOLIVARES FUERTES' => 'BOLIVARES FUERTES',
        ];

        $upper = strtoupper(trim($value ?? ''));
        return $map[$upper] ?? 'PESOS';
    }

    /**
     * Normaliza el tipo de viaje
     */
    public static function normalizeTripType($value)
    {
        $upper = strtoupper(trim($value ?? ''));
        
        $map = [
            'NACIONAL' => 'NACIONAL',
            'INTERNACIONAL' => 'INTERNACIONAL',
            'URBANO' => 'URBANO',
        ];

        return $map[$upper] ?? 'NACIONAL';
    }

    /**
     * Normaliza el medio/fuente de solicitud
     */
    public static function normalizeRequestSource($value)
    {
        $upper = strtoupper(trim($value ?? ''));
        
        // Mapeo de valores comunes
        $map = [
            'WEB' => 'PAGINA WEB',
            'PAGINA WEB' => 'PAGINA WEB',
            'TELEFONO' => 'TELEFONO DESPACHADOR',
            'TELEFONO DESPACHADOR' => 'TELEFONO DESPACHADOR',
            'TELEFONO ATENCION CLIENTE' => 'TELEFONO ATENCION CLIENTE',
            'MAIL' => 'MAIL',
            'EMAIL' => 'MAIL',
            'FAX' => 'FAX',
            'SIA' => 'SIA',
        ];

        return $map[$upper] ?? 'PAGINA WEB';
    }

    /**
     * Normaliza código de ciudad (debe ser código DIVIPOLA completo de 8 dígitos)
     */
    public static function normalizeCityCode($value)
    {
        // Si ya es numérico y tiene 8 dígitos, devolver como está
        if (is_numeric($value) && strlen($value) == 8) {
            return (int) $value;
        }

        // Si tiene 5 dígitos (código corto), agregar 000 al final
        if (is_numeric($value) && strlen($value) == 5) {
            return (int) ($value . '000');
        }

        // Si es texto que contiene números, extraer
        if (preg_match('/(\d{5,8})/', $value, $matches)) {
            $code = $matches[1];
            if (strlen($code) == 5) {
                return (int) ($code . '000');
            }
            return (int) $code;
        }

        // Por defecto, Bogotá
        return 11001000;
    }

    /**
     * Normaliza el tipo de flete
     */
    public static function normalizeFreightType($value)
    {
        $upper = strtoupper(trim($value ?? ''));
        
        // Valores válidos: CARGA SUELTA, CUPO, CONSOLIDADO, EXPRESO, GALON, VAN, CONTENEDOR
        $map = [
            'CARGA SUELTA' => 'CARGA SUELTA',
            'SUELTA' => 'CARGA SUELTA',
            'FLETE TERRESTRE' => 'CARGA SUELTA',
            'TERRESTRE' => 'CARGA SUELTA',
            'CUPO' => 'CUPO',
            'CONSOLIDADO' => 'CONSOLIDADO',
            'EXPRESO' => 'EXPRESO',
            'GALON' => 'GALON',
            'VAN' => 'VAN',
            'CONTENEDOR' => 'CONTENEDOR',
        ];

        return $map[$upper] ?? 'CARGA SUELTA';
    }

    /**
     * Normaliza valores numéricos (elimina texto, deja solo números)
     */
    public static function normalizeNumeric($value, $default = 1)
    {
        // Si es numérico, devolver
        if (is_numeric($value)) {
            return $value;
        }

        // Intentar extraer número
        if (preg_match('/(\d+(?:\.\d+)?)/', $value, $matches)) {
            return $matches[1];
        }

        return $default;
    }

    /**
     * Normaliza valores SI/NO
     */
    public static function normalizeYesNo($value)
    {
        if (is_bool($value)) {
            return $value ? 'SI' : 'NO';
        }

        $upper = strtoupper(trim($value ?? ''));
        
        $yesValues = ['SI', 'YES', 'TRUE', '1', 'SÍ'];
        
        return in_array($upper, $yesValues) ? 'SI' : 'NO';
    }

    /**
     * Normaliza el tipo de operación
     */
    public static function normalizeOperationType($value)
    {
        $upper = strtoupper(trim($value ?? ''));
        
        $map = [
            'DISTRIBUCION' => 'DISTRIBUCION',
            'TRANSPORTE' => 'DISTRIBUCION',
            'CARGA' => 'DISTRIBUCION',
        ];

        return $map[$upper] ?? 'DISTRIBUCION';
    }

    /**
     * Normaliza el centro de costos de despacho
     * Silogtran tiene una lista específica de centros de costos permitidos
     */
    public static function normalizeCostCenter($value)
    {
        $upper = strtoupper(trim($value ?? ''));
        
        // Lista de centros de costos válidos según el error de Silogtran
        // Lista oficial tomada del mensaje de error de la API
        $validCenters = [
            'ALMACENAMIENTO CALI',
            'CONALCA PAGOS ANT',
            'CONALCA CALI',
            'YUMBO-BAVARIA',
            'CARTAGENA-BAVARIA',
            'ALMACENAMIENTO MOSQUERA',
            'ARMENIA-BAVARIA',
            'SANTAMARTA-BAVARIA',
            'CONALCA MANIZALEZ',
            'CONALCA IPIALES',
            'TUNJA-BAVARIA',
            'CONALCA PEREIRA',
            'CONALCA BARRANQUILLA',
            'CONALCA SANTA MARTA',
            'CONALCA BUCARAMANGA',
            'CONALCA UBATE',
            'ALMACENAMIENTO',
            'CONALCA MEDELLIN',
            'BUENAVENTURA PANTOS',
            'CONALCA BUENAVENTURA',
            'OTM CONALCA  BAQ',
            'OTM CONALCA SNMT',
            'OTM CONALCA CTG',
            'BUN MAERSK DEDICADO',
            'BOG MAERSK DEDICADO',
            'CLO MAERSK DEDICADO',
            'CONALCA CARTAGENA',
            'OTM CONALCA BUN',
            'CONALCA BOGOTA',
            'OTM CONALCA BTA',
            // NOTA: TRANSLIDHER BOGOTA NO está en la lista oficial de Silogtran
        ];
        
        // Si el valor está en la lista, devolverlo tal cual
        if (in_array($upper, $validCenters)) {
            return $upper;
        }
        
        // Mapeo de variantes comunes a valores válidos
        $map = [
            'BOGOTA' => 'CONALCA BOGOTA',
            'CONALCA' => 'CONALCA BOGOTA',
            'CALI' => 'CONALCA CALI',
            'MEDELLIN' => 'CONALCA MEDELLIN',
            'BARRANQUILLA' => 'CONALCA BARRANQUILLA',
            'CARTAGENA' => 'CONALCA CARTAGENA',
            'BUCARAMANGA' => 'CONALCA BUCARAMANGA',
            'PEREIRA' => 'CONALCA PEREIRA',
            'MANIZALES' => 'CONALCA MANIZALEZ',
            'IPIALES' => 'CONALCA IPIALES',
            'SANTA MARTA' => 'CONALCA SANTA MARTA',
            'BUENAVENTURA' => 'CONALCA BUENAVENTURA',
            'UBATE' => 'CONALCA UBATE',
            // TRANSLIDHER no es válido, convertir a CONALCA BOGOTA
            'TRANSLIDHER' => 'CONALCA BOGOTA',
        ];
        
        // Buscar coincidencias parciales
        foreach ($map as $key => $validCenter) {
            if (strpos($upper, $key) !== false) {
                return $validCenter;
            }
        }
        
        // Por defecto, devolver el primero de la lista (ALMACENAMIENTO CALI)
        // o el que más sentido haga para tu operación
        return 'CONALCA BOGOTA';
    }
}

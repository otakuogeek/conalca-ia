<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * 🔧 SERVICIO DE PREPROCESAMIENTO DE TEXTO
 * 
 * Normaliza y limpia el texto de entrada para mejorar la extracción de datos.
 * Separa palabras pegadas, corrige errores comunes y estandariza el formato.
 */
class TextPreprocessorService
{
    /**
     * Palabras clave que frecuentemente se pegan con otras palabras
     * Ordenadas por longitud descendente para evitar conflictos
     */
    private static array $keywordsThatNeedSpace = [
        // Separadores de múltiples rutas (más largos primero)
        'adicionalmente' => ' adicionalmente ',
        'adicional' => ' adicional ',
        'también' => ' también ',
        'tambien' => ' también ',
        'además' => ' además ',
        'ademas' => ' además ',
        
        // Verbos de solicitud
        'requiero' => ' requiero ',
        'necesito' => ' necesito ',
        'solicito' => ' solicito ',
        
        // Preposiciones y conectores
        'porunvalor' => ' por un valor ',
        'porun' => ' por un ',
        'porel' => ' por el ',
        'porla' => ' por la ',
        'deun' => ' de un ',
        'dela' => ' de la ',
        'delvalor' => ' del valor ',
        'conun' => ' con un ',
        'sinun' => ' sin un ',
        'enun' => ' en un ',
        'ala' => ' a la ',
        'ael' => ' a el ',
        
        // Unidades y medidas
        'toneladas' => ' toneladas ',
        'tonelada' => ' tonelada ',
        'kilogramos' => ' kilogramos ',
        'kilogramo' => ' kilogramo ',
        'millones' => ' millones ',
        'millón' => ' millón ',
        'millon' => ' millón ',
        'unidades' => ' unidades ',
        'unidad' => ' unidad ',
        
        // Tipos de empaque
        'encajas' => ' en cajas ',
        'ensacos' => ' en sacos ',
        'enbultos' => ' en bultos ',
        'enpallets' => ' en pallets ',
        'enestibas' => ' en estibas ',
        
        // Tara
        'sintara' => ' sin tara ',
        'contara' => ' con tara ',
        'incluyetara' => ' incluye tara ',
        'noincluyetara' => ' no incluye tara ',
        
        // Valor declarado
        'valordeclarado' => ' valor declarado ',
        'valorasegurado' => ' valor asegurado ',
        
        // Vehículos
        'tipovehículo' => ' tipo vehículo ',
        'tipovehiculo' => ' tipo vehículo ',
        'tipodevehículo' => ' tipo de vehículo ',
        'tipodevehiculo' => ' tipo de vehículo ',
        
        // Cotización
        'cotización' => ' cotización ',
        'cotizacion' => ' cotización ',
    ];

    /**
     * Patrones regex para separar palabras pegadas comunes
     * Formato: [patrón => reemplazo]
     */
    private static array $regexPatterns = [
        // Palabra + "por" + siguiente palabra (ej: "neumáticospor" -> "neumáticos por")
        '/([a-záéíóúñ]{3,})(por)\s/ui' => '$1 $2 ',
        
        // Palabra + "de" + siguiente palabra (ej: "cajasde" -> "cajas de")
        '/([a-záéíóúñ]{3,})(de)\s/ui' => '$1 $2 ',
        
        // Palabra + "en" + siguiente palabra (ej: "cajsen" -> si aplica)
        '/([a-záéíóúñ]{3,})(en)\s/ui' => '$1 $2 ',
        
        // Palabra + "con" + siguiente palabra
        '/([a-záéíóúñ]{3,})(con)\s/ui' => '$1 $2 ',
        
        // Palabra + "sin" + siguiente palabra
        '/([a-záéíóúñ]{3,})(sin)\s/ui' => '$1 $2 ',
        
        // Palabra + "son" + siguiente palabra (ej: "cajsson" -> "cajas son")
        '/([a-záéíóúñ]{3,})(son)\s/ui' => '$1 $2 ',
        
        // Número + unidad pegada (ej: "13toneladas" -> "13 toneladas")
        '/(\d+)(toneladas?|kg|kilos?|kilogramos?|millones?|unidades?)/ui' => '$1 $2',
        
        // Palabra + "adicional" (ej: "cajasadicional" -> "cajas adicional")
        '/([a-záéíóúñ]{3,})(adicional)/ui' => '$1 $2',
        
        // Palabra + "también" (ej: "cajastambién" -> "cajas también")
        '/([a-záéíóúñ]{3,})(tambi[eé]n)/ui' => '$1 $2',
        
        // Palabra + "requiero/necesito/solicito"
        '/([a-záéíóúñ]{3,})(requiero|necesito|solicito)/ui' => '$1 $2',
        
        // "valor" + número pegado (ej: "valor30" -> "valor 30")
        '/(valor)\s*(\d+)/ui' => '$1 $2',
        
        // Número + "millones" pegado sin espacio antes
        '/(\d+)(mill[oó]n(?:es)?)/ui' => '$1 $2',
        
        // Cotización pegada con preposición (ej: "cotizaciónde" -> "cotización de")
        '/(cotizaci[oó]n)(de|desde|para)/ui' => '$1 $2',
        
        // Preposición "a" pegada con ciudad (ej: "aBuenaventura" -> "a Buenaventura")
        // NOTA: Solo cuando hay espacio antes y mayúscula después
        '/\s(a)([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)/u' => ' $1 $2',
        
        // Ciudad pegada con preposición larga (NO incluir "a" porque causa falsos positivos como Buenaventur-a)
        '/([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)(hacia|hasta)\s/u' => '$1 $2 ',
    ];

    /**
     * Correcciones de errores ortográficos comunes
     */
    private static array $spellingCorrections = [
        'toneldas' => 'toneladas',
        'tonelaads' => 'toneladas',
        'toneldaas' => 'toneladas',
        'kilograos' => 'kilogramos',
        'kiligramos' => 'kilogramos',
        'millnes' => 'millones',
        'milones' => 'millones',
        'milllones' => 'millones',
        'unidaes' => 'unidades',
        'unidads' => 'unidades',
        'cotizcion' => 'cotización',
        'cotizaion' => 'cotización',
        'cotizacin' => 'cotización',
        'bueanventura' => 'buenaventura',
        'buenavetnura' => 'buenaventura',
        'cartagean' => 'cartagena',
        'cartgena' => 'cartagena',
        'bogta' => 'bogotá',
        'bogotá' => 'bogotá',
        'medellin' => 'medellín',
        'meedllin' => 'medellín',
        'barranquila' => 'barranquilla',
        'barranqilla' => 'barranquilla',
        'bucaramnaga' => 'bucaramanga',
        'bucramanga' => 'bucaramanga',
        'vehiculo' => 'vehículo',
        'vehicluo' => 'vehículo',
        'patineta' => 'patineta',
        'dobletrqoue' => 'dobletroque',
        'dobletroqeu' => 'dobletroque',
        'tractocmaion' => 'tractocamión',
        'neumaticos' => 'neumáticos',
        'neuamticos' => 'neumáticos',
        'plasticos' => 'plásticos',
        'platsicos' => 'plásticos',
    ];

    /**
     * 🚀 MÉTODO PRINCIPAL: Preprocesa el texto completo
     * 
     * @param string $text Texto original del usuario
     * @return string Texto normalizado y limpio
     */
    public static function preprocess(string $text): string
    {
        $originalText = $text;
        
        Log::info('🔧 TextPreprocessor: Iniciando preprocesamiento', [
            'original_length' => strlen($text),
            'preview' => substr($text, 0, 100)
        ]);

        // 1. Normalizar espacios múltiples y saltos de línea
        $text = self::normalizeWhitespace($text);
        
        // 2. Corregir errores ortográficos comunes
        $text = self::fixSpellingErrors($text);
        
        // 3. Separar palabras clave pegadas (usando keywords estáticos)
        $text = self::separateKeywords($text);
        
        // 4. Aplicar patrones regex para separaciones más complejas
        $text = self::applyRegexPatterns($text);
        
        // 5. Normalizar espacios nuevamente después de las separaciones
        $text = self::normalizeWhitespace($text);
        
        // 6. Normalizar acentos y caracteres especiales
        $text = self::normalizeAccents($text);

        $hasChanges = $text !== $originalText;
        
        Log::info('🔧 TextPreprocessor: Preprocesamiento completado', [
            'has_changes' => $hasChanges,
            'final_length' => strlen($text),
            'preview' => substr($text, 0, 100)
        ]);

        if ($hasChanges) {
            Log::info('📝 TextPreprocessor: Cambios realizados', [
                'original' => substr($originalText, 0, 200),
                'processed' => substr($text, 0, 200)
            ]);
        }

        return $text;
    }

    /**
     * Normaliza espacios en blanco múltiples y saltos de línea
     */
    private static function normalizeWhitespace(string $text): string
    {
        // Preservar saltos de línea pero normalizar espacios
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        // Normalizar múltiples saltos de línea a uno solo
        $text = preg_replace('/\n\s*\n/', "\n", $text);
        
        // Eliminar espacios al inicio y final de cada línea
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);
        
        return trim($text);
    }

    /**
     * Corrige errores ortográficos comunes
     */
    private static function fixSpellingErrors(string $text): string
    {
        $textLower = mb_strtolower($text);
        
        foreach (self::$spellingCorrections as $wrong => $correct) {
            // Buscar la palabra incorrecta (case insensitive)
            $pattern = '/\b' . preg_quote($wrong, '/') . '\b/ui';
            $text = preg_replace($pattern, $correct, $text);
        }
        
        return $text;
    }

    /**
     * Separa palabras clave que están pegadas
     */
    private static function separateKeywords(string $text): string
    {
        $textLower = mb_strtolower($text);
        
        // Ordenar keywords por longitud descendente para evitar conflictos
        $keywords = self::$keywordsThatNeedSpace;
        uksort($keywords, function($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        foreach ($keywords as $keyword => $replacement) {
            // Solo aplicar si la palabra está pegada (sin espacios alrededor)
            // Buscar el keyword precedido o seguido por letras
            $pattern = '/([a-záéíóúñ])(' . preg_quote($keyword, '/') . ')([a-záéíóúñ])?/ui';
            
            $text = preg_replace_callback($pattern, function($matches) use ($replacement) {
                $before = $matches[1] ?? '';
                $after = $matches[3] ?? '';
                return $before . $replacement . $after;
            }, $text);
        }
        
        return $text;
    }

    /**
     * Aplica patrones regex para separaciones más complejas
     */
    private static function applyRegexPatterns(string $text): string
    {
        foreach (self::$regexPatterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        return $text;
    }

    /**
     * Normaliza acentos y caracteres especiales
     */
    private static function normalizeAccents(string $text): string
    {
        // Asegurar que ciertos acentos comunes estén correctos
        $replacements = [
            'bogota' => 'bogotá',
            'medellin' => 'medellín',
            'cotizacion' => 'cotización',
            'vehiculo' => 'vehículo',
            'tambien' => 'también',
            'ademas' => 'además',
            'millon' => 'millón',
        ];
        
        foreach ($replacements as $without => $with) {
            // Solo reemplazar si la palabra está en minúsculas y sin acento
            $pattern = '/\b' . preg_quote($without, '/') . '\b/u';
            $text = preg_replace($pattern, $with, $text);
        }
        
        return $text;
    }

    /**
     * 🎯 Método específico para detectar si el texto necesita preprocesamiento
     * Útil para decidir si aplicar el preprocesamiento o no
     */
    public static function needsPreprocessing(string $text): bool
    {
        // Verificar si hay palabras pegadas comunes
        $patterns = [
            '/[a-záéíóúñ](adicional|también|además|requiero|necesito)/ui',
            '/[a-záéíóúñ](por|de|en|con|sin)\s/ui',
            '/\d+(toneladas?|millones?|unidades?)/ui',
            '/(cajas|sacos|bultos)(adicional|también|por|de)/ui',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 🔍 Método de debug para ver qué cambios se harían
     */
    public static function debug(string $text): array
    {
        $original = $text;
        $processed = self::preprocess($text);
        
        return [
            'original' => $original,
            'processed' => $processed,
            'has_changes' => $original !== $processed,
            'original_length' => strlen($original),
            'processed_length' => strlen($processed),
            'needs_preprocessing' => self::needsPreprocessing($original),
        ];
    }
}

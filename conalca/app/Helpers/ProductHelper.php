<?php

namespace App\Helpers;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductHelper
{
    /**
     * Mapeo de nombres de productos a códigos numéricos
     * Para compatibilidad con sistemas legacy que usan nombres
     */
    private static $productNameToCodeMap = [
        'MAIZ' => 93,
        'BULTOS' => 5,
        // Agregar más mapeos según sea necesario
    ];

    /**
     * Convierte un código de producto de texto a numérico
     * 
     * @param string|int $productCode
     * @return int|null
     */
    public static function normalizeProductCode($productCode)
    {
        // Si ya es numérico, retornarlo
        if (is_numeric($productCode)) {
            return (int) $productCode;
        }

        // Si es texto, buscar en el mapeo
        $upperCode = strtoupper(trim($productCode));
        
        if (isset(self::$productNameToCodeMap[$upperCode])) {
            Log::info("ProductHelper: Convirtiendo '$productCode' a código " . self::$productNameToCodeMap[$upperCode]);
            return self::$productNameToCodeMap[$upperCode];
        }

        // Buscar en la base de datos
        $product = Product::where('producto_nombre', $upperCode)->first();
        if ($product) {
            Log::info("ProductHelper: Encontrado '$productCode' en BD con código " . $product->producto_codigo);
            return $product->producto_codigo;
        }

        // Buscar con LIKE
        $product = Product::where('producto_nombre', 'LIKE', '%' . $upperCode . '%')->first();
        if ($product) {
            Log::info("ProductHelper: Encontrado '$productCode' (LIKE) en BD con código " . $product->producto_codigo);
            return $product->producto_codigo;
        }

        Log::warning("ProductHelper: No se pudo convertir '$productCode' a código numérico");
        return null;
    }

    /**
     * Validar si un código de producto es válido
     * 
     * @param mixed $productCode
     * @return bool
     */
    public static function isValidProductCode($productCode)
    {
        $normalizedCode = self::normalizeProductCode($productCode);
        return $normalizedCode !== null && Product::where('producto_codigo', $normalizedCode)->exists();
    }

    /**
     * Obtener producto por código normalizado
     * 
     * @param mixed $productCode
     * @return Product|null
     */
    public static function getProductByCode($productCode)
    {
        $normalizedCode = self::normalizeProductCode($productCode);
        
        if ($normalizedCode === null) {
            return null;
        }

        return Product::where('producto_codigo', $normalizedCode)->first();
    }
}
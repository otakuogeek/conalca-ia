<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Nombre explícito de la tabla
    protected $table = 'products';
    
    // Especificar la clave primaria
    protected $primaryKey = 'producto_codigo';
    
    // Indicar que la clave primaria no es auto-incremental
    public $incrementing = false;
    
    // Tipo de la clave primaria
    protected $keyType = 'int';
    
    // Deshabilitar timestamps si la tabla no los tiene
    public $timestamps = false;

    // Campos asignables masivamente
    protected $fillable = [
        'producto_codigo',
        'producto_codigo_ministerio',
        'producto_nombre',
        'tippro_nombre',
        'producto_fechacreacion',
        'natcar_nombre',
        'usuario_nombre',
    ];

    /**
     * Buscar producto por código o nombre
     * Maneja tanto códigos numéricos como nombres de texto
     */
    public static function findByCodeOrName($identifier)
    {
        // Si es numérico, buscar por código
        if (is_numeric($identifier)) {
            return static::where('producto_codigo', $identifier)->first();
        }
        
        // Si es texto, buscar primero por nombre exacto
        $product = static::where('producto_nombre', $identifier)->first();
        
        // Si no se encuentra, buscar por nombre con LIKE
        if (!$product) {
            $product = static::where('producto_nombre', 'LIKE', '%' . $identifier . '%')->first();
        }
        
        return $product;
    }

    /**
     * Obtener el código numérico de un producto dado su nombre
     */
    public static function getCodeByName($name)
    {
        $product = static::where('producto_nombre', $name)->first();
        return $product ? $product->producto_codigo : null;
    }

}

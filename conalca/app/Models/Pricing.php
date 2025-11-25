<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'documents',
        'vehicle_type',
        'extra',
        'price_extra',
        'price_extra2',
        'download_target',
        'load_target',
        'price_person',
        'event',
        'type_send',
        'save_box',
        'time_day',
        'return',
        'container',
        'complements',
        'download_price',
        'iva',
        'person_download',
        'rent',
        'download',
        'load',
        'store',
        'scales',
        'time',
        'weight',
        'vehicle_extra',
        'download_destiny',
        'download_destiny_iva',
        'price_complements',
        'price_documents',
        'load_tulan',
        'volume',
        'price_month',
        'price_aux_month',
        'price_week',
        'price_aux_week',
        'price_day',
        'price_aux_day',
        'condition',
        'weight_from',
        'weight_to',
        'type_pricing',
        'origin',
        'destination',
        'price',
    ];

    /**
     * Get all of the cotizacion_models for the Pricing
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cotizacion_models(): HasMany
    {
        return $this->hasMany(CotizacionModel::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    // use HasFactory, SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'codigo',
        'documento',
        'direccion',
        'telefono',
        'tipoclie',
        'email',
        'openai_thread_id',
        'openai_current_run',
        'actividad',
        'ciudad',
        'cliente',
        'vendedor_nombre',
        'contacto',
        'cargo',
        'celular',
        'fax',
        'personal',
        'fecha',
        'codigocontable',
        'tipcli_codigo',
        'ciudad_codigo_radicacion',
        'cencos_codigo',
        'cliente_sucursal',
        'sucursal_nombre',
        'estado',
        'tipo_documento',
        'vigenciacamara',
        'listaclinton',
        'estadosfinancieros',
        'cliente_informa',
        'cliente_referencias',
        'cliente_acuerdos',
        'cliente_visitas',
        'cliente_fechavigenciabasc',
        'cliente_fechavigenciacalidad',
        'cliente_fechavigenciaacuerdos',
        'cliente_fechavigenciavisitas',
        'cliente_basc',
        'cliente_diasvencimientofactura',
        'MyUnknownColumn',
        // Campos legacy que pueden existir
        'name',
        'company_name',
        'location',
        'phone_numbers',
        'address',
        'personal_cell',
        'chamber_validity',
        'document_code',
        'branch_office',
        'sales_representative',
        'clinton_list_check',
        'financial_statements',
        'created_at_doc',
    ];

    protected $guarded = [];

    /**
     * Get all of the cotizaciones for the Client
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cotizaciones(): HasMany
    {
        return $this->hasMany(CotizacionModel::class);
    }

    /**
     * Get all of the solicitud_transportes for the Client
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function solicitud_transportes(): HasMany
    {
        return $this->hasMany(SolicitudTransporte::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'documento', 'documento');
    }

    public function assignments()
    {
        return $this->hasMany(ClientUserAssignment::class);
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'client_user_assignments', 'client_id', 'user_id')
                    ->withPivot('assigned_by')
                    ->withTimestamps();
    }

    /**
     * Get all files for this client
     */
    public function files()
    {
        return $this->hasMany(ClientFile::class);
    }
}

import React, { useState } from 'react';
import { createPricing, updatePricing } from '../../services/pricings';
import { IoClose } from 'react-icons/io5';

/* ───────────────── SUB-COMPONENTES ESTABLES ───────────────── */
const FieldInput = React.memo(function FieldInput({
  value, name, label, type = 'text', required = false, onChange,
}) {
  return (
    <div className="flex flex-col mb-2">
      <label className="text-xs font-medium text-gray-600 mb-1">
        {label} {required && '*'}
      </label>
      <input
        type={type}
        name={name}
        value={value ?? ''}
        onChange={onChange}
        required={required}
        className="border rounded px-2 py-1 text-sm"
      />
    </div>
  );
});

const FieldSelect = React.memo(function FieldSelect({
  value, name, label, options, required = false, onChange,
}) {
  return (
    <div className="flex flex-col mb-2">
      <label className="text-xs font-medium text-gray-600 mb-1">
        {label} {required && '*'}
      </label>
      <select
        name={name}
        value={value}
        onChange={onChange}
        required={required}
        className="border rounded px-2 py-1 text-sm bg-white"
      >
        <option value="">— Seleccionar —</option>
        {options.map(opt => (
          <option key={opt} value={opt}>{opt}</option>
        ))}
      </select>
    </div>
  );
});

/* ───────────────── CONSTANTES ───────────────── */
const VEHICLE_OPTIONS = [
  'Turbo', 'Sencillo', 'Tractomula', 'Camioneta',
  'Doble Troque', 'Mula S2', 'Tracto Mula S3', 'Automovil',
];

const REQUIRED = ['origin', 'destination', 'price', 'weight', 'vehicle_type'];

/* ───────────────── COMPONENTE PRINCIPAL ───────────────── */
export default function PricingDetailForm({
  solicitation,
  onSaved,         // refrescar modal
  onListRefresh,   // refrescar tabla
  onClose,
}) {
  /* Estado con TODAS las columnas */
  const [form, setForm] = useState({
    solicitation_id: solicitation.id,
    /* básicos ------------------------------------------------ */
    type_pricing: '',
    price: solicitation.price ?? '',
    origin: solicitation.origin,
    destination: solicitation.destination,
    vehicle_type: '',
    /* urbano / ipiales -------------------------------------- */
    documents: '', extra: '', price_extra: '', price_extra2: '',
    download_target: '', load_target: '', price_person: '',
    /* san miguel -------------------------------------------- */
    event: '', type_send: '', save_box: '', time_day: '', return: '',
    container: '', complements: '', download_price: '', iva: '',
    person_download: '',
    /* perú --------------------------------------------------- */
    rent: '', download: '', load: '', store: '', scales: '',
    time: '', weight: '',
    /* trueca tulcán ----------------------------------------- */
    vehicle_extra: '', download_destiny: '', download_destiny_iva: '',
    price_complements: '', price_documents: '', load_tulan: '',
    /* bogotá dedicados -------------------------------------- */
    volume: '', price_month: '', price_aux_month: '', price_week: '',
    price_aux_week: '', price_day: '', price_aux_day: '', condition: '',
    weight_from: '', weight_to: '',
  });

  /* ─── handlers ─── */
  const handle = e => setForm({ ...form, [e.target.name]: e.target.value });

  const submit = async e => {
    e.preventDefault();
    /* Validación rápida en front-end */
    for (const f of REQUIRED) {
      if (!form[f] || String(form[f]).trim() === '') {
        alert(`El campo "${campoLabel(f)}" es obligatorio`);
        return;
      }
    }

    /* Guardado en API */
    if (solicitation.pricing_id) {
      await updatePricing(solicitation.pricing_id, form);
    } else {
      await createPricing(form);
    }

    /* Callbacks */
    onSaved?.();        // refresca modal
    onListRefresh?.();  // refresca listado
    onClose();          // cierra pop-up
  };

  /* Traducción de etiquetas para campos obligatorios */
  const labels = {
    origin: 'Origen',
    destination: 'Destino',
    price: 'Precio',
    weight: 'Peso',
    vehicle_type: 'Tipo de vehículo',
    type_pricing: 'Tipo de cotización',
    documents: 'Documentos',
    extra: 'Extra',
    price_extra: 'Precio extra',
    price_extra2: 'Precio extra 2',
    download_target: 'Punto de descarga',
    load_target: 'Punto de carga',
    price_person: 'Precio por persona',
    event: 'Evento',
    type_send: 'Tipo de envío',
    save_box: 'Caja de ahorro',
    time_day: 'Tiempo (días)',
    return: 'Retorno',
    container: 'Contenedor',
    complements: 'Complementos',
    download_price: 'Precio descarga',
    iva: 'IVA',
    person_download: 'Persona descarga',
    rent: 'Renta',
    download: 'Descarga',
    load: 'Carga',
    store: 'Almacén',
    scales: 'Básculas',
    time: 'Tiempo',
    vehicle_extra: 'Vehículo extra',
    download_destiny: 'Descarga destino',
    download_destiny_iva: 'Descarga destino (IVA)',
    price_complements: 'Precio complementos',
    price_documents: 'Precio documentos',
    load_tulan: 'Carga Tulán',
    volume: 'Volumen',
    price_month: 'Precio mes',
    price_aux_month: 'Precio aux. mes',
    price_week: 'Precio semana',
    price_aux_week: 'Precio aux. semana',
    price_day: 'Precio día',
    price_aux_day: 'Precio aux. día',
    condition: 'Condición',
    weight_from: 'Peso desde',
    weight_to: 'Peso hasta'
  };
  const campoLabel = (name) => labels[name] || name.replace(/_/g, ' ');

  /* ─── definición de grupos ─── */
  const groups = [
    {
      title: 'Básico',
      fields: ['type_pricing', 'price', 'origin', 'destination', 'documents'],
      extras: [(
        <FieldSelect
          key="vehicle_type"
          name="vehicle_type"
          label="Tipo de vehículo"
          value={form.vehicle_type}
          options={VEHICLE_OPTIONS}
          required
          onChange={handle}
        />
      )],
    },
    {
      title: 'Urbano / Ipiales',
      fields: ['extra', 'price_extra', 'price_extra2', 'download_target',
               'load_target', 'price_person'],
    },
    {
      title: 'San Miguel',
      fields: ['event', 'type_send', 'save_box', 'time_day', 'return', 'container',
               'complements', 'download_price', 'iva', 'person_download'],
    },
    {
      title: 'Perú',
      fields: ['rent', 'download', 'load', 'store', 'scales', 'time', 'weight'],
    },
    {
      title: 'Trueca Tulcán',
      fields: ['vehicle_extra', 'download_destiny', 'download_destiny_iva',
               'price_complements', 'price_documents', 'load_tulan'],
    },
    {
      title: 'Bogotá dedicados',
      fields: ['volume', 'price_month', 'price_aux_month', 'price_week',
               'price_aux_week', 'price_day', 'price_aux_day', 'condition',
               'weight_from', 'weight_to'],
    },
  ];

  const renderGroup = ({ title, fields, extras = [] }) => (
    <fieldset key={title} className="border p-3 rounded mb-4">
      <legend className="text-xs font-bold text-gray-500 px-1">{title}</legend>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
        {fields.map(f => (
          <FieldInput
            key={f}
            name={f}
            label={campoLabel(f)}
            value={form[f]}
            onChange={handle}
            required={REQUIRED.includes(f)}
            type={(f === 'price' || f.match(/price|weight/)) ? 'number' : 'text'}
          />
        ))}
        {extras}
      </div>
    </fieldset>
  );

  /* ─── UI ─── */
  return (
    <div className="fixed inset-0 bg-black/50 z-[99990] flex items-start justify-center pt-8 overflow-y-auto backdrop-blur-sm">
      <div className="bg-white w-full max-w-3xl rounded-lg shadow-xl p-6 overflow-y-auto max-h-[90vh] relative">
        <button
          onClick={onClose}
          className="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
          aria-label="Cerrar"
        >
          <IoClose size={24} />
        </button>
        
        <h3 className="text-xl font-bold mb-4 text-[#FF7C32]">
           {solicitation.pricing_id ? 'Editar cotización' : 'Crear cotización'}
        </h3>

        <form onSubmit={submit}>
          {groups.map(renderGroup)}

          <div className="text-right">
            <button className="bg-[#FF7C32] hover:bg-[#ff974e] text-white px-6 py-2 rounded">
              Guardar
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
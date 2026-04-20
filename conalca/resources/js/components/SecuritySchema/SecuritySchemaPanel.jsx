import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
    fetchSecuritySchemaData,
    searchProducts,
    addProduct,
    deleteProduct,
    createPriceRange,
    updatePriceRange,
    deletePriceRange,
    searchClients,
    assignClient,
    unassignClient,
    saveUserOverride,
    deleteUserOverride,
} from '../../services/securitySchemaService';

const formatCurrency = (value) => {
    if (!value && value !== 0) return '';
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(value);
};

const parseCurrency = (str) => {
    if (!str) return null;
    return parseInt(String(str).replace(/[^0-9]/g, ''), 10) || null;
};

const CATEGORY_LABELS = {
    alto_riesgo_nivel_1: 'Mercancía de Alto Riesgo — Primer Nivel',
    alto_riesgo_nivel_2: 'Mercancía de Alto Riesgo — Segundo Nivel',
    bajo_riesgo: 'Mercancía de Bajo Riesgo',
    no_amparada: 'Mercancía NO Amparada por Nuestra Póliza',
};

const CATEGORY_COLORS = {
    alto_riesgo_nivel_1: { tab: 'border-red-600 text-red-700 bg-red-50', dot: '🔴', badge: 'bg-red-50 text-red-700 border-red-200' },
    alto_riesgo_nivel_2: { tab: 'border-orange-500 text-orange-600 bg-orange-50', dot: '🟠', badge: 'bg-orange-50 text-orange-700 border-orange-200' },
    bajo_riesgo: { tab: 'border-green-500 text-green-600 bg-green-50', dot: '🟢', badge: 'bg-green-50 text-green-700 border-green-200' },
    no_amparada: { tab: 'border-gray-500 text-gray-600 bg-gray-50', dot: '🚫', badge: 'bg-gray-50 text-gray-700 border-gray-200' },
};

// ─── Subcomponente: Formulario de medidas de seguridad ──────────
const MeasureForm = ({ label, value, onChange }) => (
    <div className="border rounded-lg p-3 bg-gray-50">
        <h5 className="font-semibold text-sm mb-2 text-gray-700">{label}</h5>
        <div className="grid grid-cols-2 gap-2">
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={value.gps} onChange={e => onChange({ ...value, gps: e.target.checked })}
                    className="rounded text-orange-500 focus:ring-orange-400" />
                GPS
            </label>
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={value.candado_satelital} onChange={e => onChange({ ...value, candado_satelital: e.target.checked })}
                    className="rounded text-orange-500 focus:ring-orange-400" />
                Candado Satelital
            </label>
            <div className="flex items-center gap-2 text-sm">
                <label className="whitespace-nowrap">Acomp. Vehicular:</label>
                <input type="number" min="0" max="10" value={value.acompanamiento_vehicular}
                    onChange={e => onChange({ ...value, acompanamiento_vehicular: parseInt(e.target.value) || 0 })}
                    className="w-16 rounded border-gray-300 text-sm focus:ring-orange-400 focus:border-orange-400" />
            </div>
            <div className="flex items-center gap-2 text-sm">
                <label className="whitespace-nowrap">Acomp. Motorizado:</label>
                <input type="number" min="0" max="10" value={value.acompanamiento_motorizado}
                    onChange={e => onChange({ ...value, acompanamiento_motorizado: parseInt(e.target.value) || 0 })}
                    className="w-16 rounded border-gray-300 text-sm focus:ring-orange-400 focus:border-orange-400" />
            </div>
        </div>
    </div>
);

// ─── Subcomponente: Fila de rango de precios ────────────────────
const PriceRangeRow = ({ range, onDelete, onEdit, onEditOverride, onResetOverride, isSuperAdmin, userOverride }) => {
    const displayRange = userOverride || range;
    const nacional = displayRange.measures?.find(m => m.scope === 'nacional') || {};
    const urbano = displayRange.measures?.find(m => m.scope === 'urbano') || {};
    const hasOverride = !!userOverride;

    const renderMeasures = (m) => {
        const parts = [];
        if (m.gps) parts.push('GPS');
        if (m.candado_satelital) parts.push('Candado Satelital');
        if (m.acompanamiento_vehicular > 0) parts.push(`${m.acompanamiento_vehicular} Acomp. Vehicular`);
        if (m.acompanamiento_motorizado > 0) parts.push(`${m.acompanamiento_motorizado} Acomp. Motorizado`);
        return parts.length ? parts.join(' + ') : 'N.A.';
    };

    return (
        <tr className={`border-b hover:bg-orange-50 transition-colors ${hasOverride ? 'bg-blue-50/30' : ''}`}>
            <td className="px-3 py-2 text-sm">
                {displayRange.price_from ? formatCurrency(displayRange.price_from) : '$0'}
                {hasOverride && range.price_from !== displayRange.price_from && (
                    <span className="block text-xs text-gray-400 line-through">{range.price_from ? formatCurrency(range.price_from) : '$0'}</span>
                )}
            </td>
            <td className="px-3 py-2 text-sm">
                {formatCurrency(displayRange.price_to)}
                {hasOverride && range.price_to !== displayRange.price_to && (
                    <span className="block text-xs text-gray-400 line-through">{formatCurrency(range.price_to)}</span>
                )}
            </td>
            <td className="px-3 py-2 text-sm">{renderMeasures(nacional)}</td>
            <td className="px-3 py-2 text-sm">{renderMeasures(urbano)}</td>
            <td className="px-3 py-2 text-sm text-center">
                {isSuperAdmin ? (
                    <>
                        <button onClick={() => onEdit(range)} className="text-blue-600 hover:text-blue-800 mr-2" title="Editar">
                            <svg className="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </button>
                        <button onClick={() => onDelete(range.id)} className="text-red-500 hover:text-red-700" title="Eliminar">
                            <svg className="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </>
                ) : (
                    <div className="flex items-center justify-center gap-1">
                        <button onClick={() => onEditOverride(range)} className="text-blue-600 hover:text-blue-800" title="Personalizar rango">
                            <svg className="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </button>
                        {hasOverride && (
                            <button onClick={() => onResetOverride(range.id)} className="text-gray-400 hover:text-gray-600" title="Restaurar valores del administrador">
                                <svg className="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </button>
                        )}
                        {hasOverride && (
                            <span className="ml-1 text-xs bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded-full">Personalizado</span>
                        )}
                    </div>
                )}
            </td>
        </tr>
    );
};

// ─── Subcomponente: Modal de Rango de Precio ────────────────────
const PriceRangeModal = ({ isOpen, onClose, onSave, editingRange, category }) => {
    const emptyMeasure = { gps: false, candado_satelital: false, acompanamiento_vehicular: 0, acompanamiento_motorizado: 0 };
    const [priceFrom, setPriceFrom] = useState('');
    const [priceTo, setPriceTo] = useState('');
    const [nacional, setNacional] = useState({ ...emptyMeasure });
    const [urbano, setUrbano] = useState({ ...emptyMeasure });
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (editingRange) {
            setPriceFrom(editingRange.price_from || '');
            setPriceTo(editingRange.price_to || '');
            const nm = editingRange.measures?.find(m => m.scope === 'nacional') || {};
            const um = editingRange.measures?.find(m => m.scope === 'urbano') || {};
            setNacional({
                gps: nm.gps || false,
                candado_satelital: nm.candado_satelital || false,
                acompanamiento_vehicular: nm.acompanamiento_vehicular || 0,
                acompanamiento_motorizado: nm.acompanamiento_motorizado || 0,
            });
            setUrbano({
                gps: um.gps || false,
                candado_satelital: um.candado_satelital || false,
                acompanamiento_vehicular: um.acompanamiento_vehicular || 0,
                acompanamiento_motorizado: um.acompanamiento_motorizado || 0,
            });
        } else {
            setPriceFrom('');
            setPriceTo('');
            setNacional({ ...emptyMeasure });
            setUrbano({ ...emptyMeasure });
        }
    }, [editingRange, isOpen]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        const priceToVal = parseCurrency(priceTo);
        if (!priceToVal) return;
        setSaving(true);
        try {
            await onSave({
                category,
                price_from: parseCurrency(priceFrom),
                price_to: priceToVal,
                nacional,
                urbano,
            }, editingRange?.id);
            onClose();
        } catch (err) {
            console.error('Error saving price range:', err);
        } finally {
            setSaving(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" onClick={onClose}>
            <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" onClick={e => e.stopPropagation()}>
                <div className="p-6">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-bold text-gray-800">
                            {editingRange ? 'Editar' : 'Nuevo'} Rango — {CATEGORY_LABELS[category] || category}
                        </h3>
                        <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Desde ($)</label>
                                <input type="text" value={priceFrom} onChange={e => setPriceFrom(e.target.value)}
                                    placeholder="0 (vacío = desde 0)"
                                    className="w-full rounded-lg border-gray-300 focus:ring-orange-400 focus:border-orange-400" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Hasta ($) *</label>
                                <input type="text" value={priceTo} onChange={e => setPriceTo(e.target.value)}
                                    placeholder="Ej: 150000000" required
                                    className="w-full rounded-lg border-gray-300 focus:ring-orange-400 focus:border-orange-400" />
                            </div>
                        </div>
                        <MeasureForm label="Recorridos Nacionales" value={nacional} onChange={setNacional} />
                        <MeasureForm label="Recorridos Urbanos" value={urbano} onChange={setUrbano} />
                        <div className="flex justify-end gap-2 pt-2">
                            <button type="button" onClick={onClose}
                                className="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm">
                                Cancelar
                            </button>
                            <button type="submit" disabled={saving}
                                className="px-4 py-2 rounded-lg bg-[#FF7C32] text-white hover:bg-orange-600 text-sm disabled:opacity-50">
                                {saving ? 'Guardando...' : (editingRange ? 'Actualizar' : 'Crear')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

// ─── Subcomponente: Modal de Override para Comercial ─────────────
const CommercialOverrideModal = ({ isOpen, onClose, onSave, baseRange, existingOverride, category }) => {
    const emptyMeasure = { gps: false, candado_satelital: false, acompanamiento_vehicular: 0, acompanamiento_motorizado: 0 };
    const [priceFrom, setPriceFrom] = useState('');
    const [priceTo, setPriceTo] = useState('');
    const [nacional, setNacional] = useState({ ...emptyMeasure });
    const [urbano, setUrbano] = useState({ ...emptyMeasure });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    // Valores mínimos del admin
    const baseNacional = baseRange?.measures?.find(m => m.scope === 'nacional') || emptyMeasure;
    const baseUrbano = baseRange?.measures?.find(m => m.scope === 'urbano') || emptyMeasure;

    useEffect(() => {
        if (!isOpen || !baseRange) return;
        setError('');

        if (existingOverride) {
            setPriceFrom(existingOverride.price_from || '');
            setPriceTo(existingOverride.price_to || '');
            const nm = existingOverride.measures?.find(m => m.scope === 'nacional') || {};
            const um = existingOverride.measures?.find(m => m.scope === 'urbano') || {};
            setNacional({
                gps: nm.gps || false, candado_satelital: nm.candado_satelital || false,
                acompanamiento_vehicular: nm.acompanamiento_vehicular || 0, acompanamiento_motorizado: nm.acompanamiento_motorizado || 0,
            });
            setUrbano({
                gps: um.gps || false, candado_satelital: um.candado_satelital || false,
                acompanamiento_vehicular: um.acompanamiento_vehicular || 0, acompanamiento_motorizado: um.acompanamiento_motorizado || 0,
            });
        } else {
            // Inicializar con valores del admin
            setPriceFrom(baseRange.price_from || '');
            setPriceTo(baseRange.price_to || '');
            setNacional({
                gps: baseNacional.gps || false, candado_satelital: baseNacional.candado_satelital || false,
                acompanamiento_vehicular: baseNacional.acompanamiento_vehicular || 0, acompanamiento_motorizado: baseNacional.acompanamiento_motorizado || 0,
            });
            setUrbano({
                gps: baseUrbano.gps || false, candado_satelital: baseUrbano.candado_satelital || false,
                acompanamiento_vehicular: baseUrbano.acompanamiento_vehicular || 0, acompanamiento_motorizado: baseUrbano.acompanamiento_motorizado || 0,
            });
        }
    }, [baseRange, existingOverride, isOpen]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        const priceToVal = parseCurrency(priceTo);
        const priceFromVal = parseCurrency(priceFrom);
        if (!priceToVal) return;

        // Validación local
        if (priceToVal < baseRange.price_to) {
            setError(`El valor "Hasta" no puede ser menor a ${formatCurrency(baseRange.price_to)} (valor base del administrador)`);
            return;
        }
        if (priceFromVal !== null && baseRange.price_from !== null && priceFromVal < baseRange.price_from) {
            setError(`El valor "Desde" no puede ser menor a ${formatCurrency(baseRange.price_from)} (valor base del administrador)`);
            return;
        }

        setSaving(true);
        try {
            await onSave({
                base_range_id: baseRange.id,
                price_from: priceFromVal,
                price_to: priceToVal,
                nacional,
                urbano,
            });
            onClose();
        } catch (err) {
            const msg = err.response?.data?.message || 'Error al guardar';
            setError(msg);
        } finally {
            setSaving(false);
        }
    };

    if (!isOpen || !baseRange) return null;

    // Helper para el MeasureForm con restricciones mínimas
    const MeasureFormWithMin = ({ label, value, onChange, baseMeasure }) => (
        <div className="border rounded-lg p-3 bg-gray-50">
            <h5 className="font-semibold text-sm mb-2 text-gray-700">{label}</h5>
            <div className="grid grid-cols-2 gap-2">
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={value.gps}
                        disabled={baseMeasure.gps}
                        onChange={e => onChange({ ...value, gps: e.target.checked })}
                        className="rounded text-blue-500 focus:ring-blue-400 disabled:opacity-60" />
                    GPS {baseMeasure.gps && <span className="text-xs text-gray-400">(mínimo)</span>}
                </label>
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={value.candado_satelital}
                        disabled={baseMeasure.candado_satelital}
                        onChange={e => onChange({ ...value, candado_satelital: e.target.checked })}
                        className="rounded text-blue-500 focus:ring-blue-400 disabled:opacity-60" />
                    Candado Satelital {baseMeasure.candado_satelital && <span className="text-xs text-gray-400">(mínimo)</span>}
                </label>
                <div className="flex items-center gap-2 text-sm">
                    <label className="whitespace-nowrap">Acomp. Vehicular:</label>
                    <input type="number" min={baseMeasure.acompanamiento_vehicular || 0} max="10" value={value.acompanamiento_vehicular}
                        onChange={e => onChange({ ...value, acompanamiento_vehicular: Math.max(baseMeasure.acompanamiento_vehicular || 0, parseInt(e.target.value) || 0) })}
                        className="w-16 rounded border-gray-300 text-sm focus:ring-blue-400 focus:border-blue-400" />
                    {baseMeasure.acompanamiento_vehicular > 0 && <span className="text-xs text-gray-400">(mín: {baseMeasure.acompanamiento_vehicular})</span>}
                </div>
                <div className="flex items-center gap-2 text-sm">
                    <label className="whitespace-nowrap">Acomp. Motorizado:</label>
                    <input type="number" min={baseMeasure.acompanamiento_motorizado || 0} max="10" value={value.acompanamiento_motorizado}
                        onChange={e => onChange({ ...value, acompanamiento_motorizado: Math.max(baseMeasure.acompanamiento_motorizado || 0, parseInt(e.target.value) || 0) })}
                        className="w-16 rounded border-gray-300 text-sm focus:ring-blue-400 focus:border-blue-400" />
                    {baseMeasure.acompanamiento_motorizado > 0 && <span className="text-xs text-gray-400">(mín: {baseMeasure.acompanamiento_motorizado})</span>}
                </div>
            </div>
        </div>
    );

    return (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" onClick={onClose}>
            <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" onClick={e => e.stopPropagation()}>
                <div className="p-6">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-bold text-gray-800">
                            Personalizar Rango — {CATEGORY_LABELS[category] || category}
                        </h3>
                        <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {/* Info del rango base */}
                    <div className="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                        <strong>Valores mínimos del administrador:</strong>
                        <span className="ml-2">Desde {baseRange.price_from ? formatCurrency(baseRange.price_from) : '$0'} — Hasta {formatCurrency(baseRange.price_to)}</span>
                        <p className="text-xs mt-1 text-amber-600">Solo puedes aumentar los valores, nunca reducirlos por debajo de los configurados por el administrador.</p>
                    </div>

                    {error && (
                        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            {error}
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Desde ($)</label>
                                <input type="text" value={priceFrom} onChange={e => setPriceFrom(e.target.value)}
                                    placeholder={baseRange.price_from ? String(baseRange.price_from) : '0'}
                                    className="w-full rounded-lg border-gray-300 focus:ring-blue-400 focus:border-blue-400" />
                                <p className="text-xs text-gray-400 mt-1">Mínimo: {baseRange.price_from ? formatCurrency(baseRange.price_from) : '$0'}</p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Hasta ($) *</label>
                                <input type="text" value={priceTo} onChange={e => setPriceTo(e.target.value)}
                                    placeholder={String(baseRange.price_to)} required
                                    className="w-full rounded-lg border-gray-300 focus:ring-blue-400 focus:border-blue-400" />
                                <p className="text-xs text-gray-400 mt-1">Mínimo: {formatCurrency(baseRange.price_to)}</p>
                            </div>
                        </div>
                        <MeasureFormWithMin label="Recorridos Nacionales" value={nacional} onChange={setNacional} baseMeasure={baseNacional} />
                        <MeasureFormWithMin label="Recorridos Urbanos" value={urbano} onChange={setUrbano} baseMeasure={baseUrbano} />
                        <div className="flex justify-end gap-2 pt-2">
                            <button type="button" onClick={onClose}
                                className="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm">
                                Cancelar
                            </button>
                            <button type="submit" disabled={saving}
                                className="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm disabled:opacity-50">
                                {saving ? 'Guardando...' : 'Guardar Personalización'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

// ─── Sección Informativa ────────────────────────────────────────
const InformativeSection = () => (
    <div className="space-y-4">
        {/* Mercancía No Amparada */}
        <div className="bg-white rounded-2xl shadow-lg overflow-hidden border border-red-300">
            <div className="bg-red-700 text-white p-4">
                <h3 className="text-lg font-semibold flex items-center gap-2">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    Mercancía NO Amparada por Nuestra Póliza
                </h3>
            </div>
            <div className="p-4">
                <p className="text-sm text-gray-700 leading-relaxed">
                    Animales, vivos, armas de fuego, explosivos, antigüedades, artesanías, esculturas y obras de arte, joyas, metales preciosos y/o semipreciosos, documentos o títulos valor, dinero y/o valores, cartas geográficas, mapas y/o planos.
                </p>
            </div>
        </div>

        {/* Cobertura para Maquinaria Usada */}
        <div className="bg-white rounded-2xl shadow-lg overflow-hidden border border-blue-300">
            <div className="bg-blue-600 text-white p-4">
                <h3 className="text-lg font-semibold flex items-center gap-2">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Cobertura para Maquinaria Usada
                </h3>
            </div>
            <div className="p-4">
                <p className="text-sm text-gray-700 leading-relaxed">
                    La cobertura de Saqueo y Avería para Equipo y Maquinaria usada se sub limita al 50% del valor declarado de la mercancía de la cobertura básica por despacho, siempre y cuando estos equipos, maquinaria y mercancía usada se afecten con ocasión de un evento no amparado por la cobertura básica. El vicio propio, desgaste natural, falta de mantenimiento y daños o perdidas pre-Existentes se excluyen de esta cobertura. Esto significa que para daños menores y avería particular los amparos serán del 50%.
                </p>
            </div>
        </div>

        {/* Responsabilidad */}
        <div className="bg-white rounded-2xl shadow-lg overflow-hidden border border-amber-300">
            <div className="bg-amber-600 text-white p-4">
                <h3 className="text-lg font-semibold flex items-center gap-2">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Responsabilidad
                </h3>
            </div>
            <div className="p-4 space-y-3">
                <div className="flex gap-3">
                    <span className="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">1</span>
                    <p className="text-sm text-gray-700 leading-relaxed">
                        La póliza de Conalca ampara las mercancías de sus clientes por subrogación (en caso de presentarse una novedad que hiciera responsable a Conalca por la mercancía el cliente cobrará a su compañía de seguros y esta compañía de seguros del cliente cobrará a su vez a la empresa de seguros de Conalca; Conalca responderá de forma directa únicamente los conceptos avería de menor cuantía hasta un máximo de 5 SMLMV).
                    </p>
                </div>
                <div className="flex gap-3">
                    <span className="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">2</span>
                    <p className="text-sm text-gray-700 leading-relaxed">
                        El cliente deberá a más tardar al momento de la instrucción de cargue suministrar el valor real de la mercancía según los términos Incoterms y tendrá en cuenta que la legislación colombiana sanciona con el 20% al momento de una indemnización si se presenta sub-valoración o hiper-valoración.
                    </p>
                </div>
            </div>
        </div>
    </div>
);

// ─── Subcomponente: Búsqueda de Clientes ────────────────────────
const ClientAssignmentSection = ({ assignedClients, onRefresh }) => {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [searching, setSearching] = useState(false);
    const [showDropdown, setShowDropdown] = useState(false);
    const [adding, setAdding] = useState(false);
    const searchRef = useRef(null);
    const debounceRef = useRef(null);

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (searchRef.current && !searchRef.current.contains(e.target)) {
                setShowDropdown(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleSearch = (value) => {
        setSearchQuery(value);
        if (debounceRef.current) clearTimeout(debounceRef.current);
        if (value.length < 2) {
            setSearchResults([]);
            setShowDropdown(false);
            return;
        }
        debounceRef.current = setTimeout(async () => {
            setSearching(true);
            try {
                const { data } = await searchClients(value);
                setSearchResults(data);
                setShowDropdown(true);
            } catch (err) {
                console.error('Error searching clients:', err);
            } finally {
                setSearching(false);
            }
        }, 300);
    };

    const handleSelect = async (client) => {
        setAdding(true);
        try {
            await assignClient(client.id);
            setSearchQuery('');
            setSearchResults([]);
            setShowDropdown(false);
            onRefresh();
        } catch (err) {
            console.error('Error assigning client:', err);
        } finally {
            setAdding(false);
        }
    };

    const handleRemove = async (assignmentId) => {
        if (!confirm('¿Desasignar este cliente del esquema de seguridad?')) return;
        try {
            await unassignClient(assignmentId);
            onRefresh();
        } catch (err) {
            console.error('Error unassigning client:', err);
        }
    };

    return (
        <div className="bg-white rounded-2xl shadow-lg overflow-hidden" style={{ border: '1px solid #ffbf69' }}>
            <div className="text-white p-4" style={{ background: 'linear-gradient(to right, #2563eb, #3b82f6)' }}>
                <h3 className="text-lg font-semibold flex items-center gap-2">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Clientes Asignados al Esquema de Seguridad
                </h3>
                <p className="text-sm text-blue-100 mt-1">
                    Asigna los clientes a los cuales aplica este esquema de seguridad.
                </p>
            </div>
            <div className="p-4">
                <div className="relative mb-4" ref={searchRef}>
                    <div className="relative">
                        <input type="text" value={searchQuery} onChange={e => handleSearch(e.target.value)}
                            placeholder="Buscar cliente por nombre, documento o código..."
                            className="w-full rounded-lg border-gray-300 focus:ring-blue-400 focus:border-blue-400 text-sm pl-10" />
                        <svg className="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        {searching && (
                            <div className="absolute right-3 top-1/2 -translate-y-1/2">
                                <div className="animate-spin rounded-full h-4 w-4 border-2 border-blue-200 border-t-blue-500"></div>
                            </div>
                        )}
                    </div>
                    {showDropdown && searchResults.length > 0 && (
                        <div className="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            {searchResults.map(c => (
                                <button key={c.id} onClick={() => handleSelect(c)} disabled={adding}
                                    className="w-full text-left px-4 py-2.5 text-sm hover:bg-blue-50 transition-colors flex items-center gap-3 border-b border-gray-100 last:border-0 disabled:opacity-50">
                                    <span className="text-xs text-gray-400 font-mono w-12">#{c.codigo}</span>
                                    <span className="text-gray-800 flex-1">{c.cliente}</span>
                                    <span className="text-xs text-gray-400">{c.documento}</span>
                                </button>
                            ))}
                        </div>
                    )}
                    {showDropdown && searchResults.length === 0 && !searching && searchQuery.length >= 2 && (
                        <div className="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-sm text-gray-500 text-center">
                            No se encontraron clientes para "{searchQuery}"
                        </div>
                    )}
                </div>

                {assignedClients.length === 0 ? (
                    <p className="text-gray-500 text-sm text-center py-4">No hay clientes asignados al esquema de seguridad.</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead>
                                <tr className="border-b-2 border-gray-200">
                                    <th className="px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Código</th>
                                    <th className="px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Cliente</th>
                                    <th className="px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Documento</th>
                                    <th className="px-3 py-2 text-xs font-semibold text-gray-600 uppercase text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {assignedClients.map(a => (
                                    <tr key={a.id} className="border-b hover:bg-blue-50 transition-colors">
                                        <td className="px-3 py-2 text-sm font-mono text-gray-500">#{a.client?.codigo}</td>
                                        <td className="px-3 py-2 text-sm text-gray-800">{a.client?.cliente}</td>
                                        <td className="px-3 py-2 text-sm text-gray-500">{a.client?.documento}</td>
                                        <td className="px-3 py-2 text-sm text-center">
                                            <button onClick={() => handleRemove(a.id)} className="text-red-500 hover:text-red-700" title="Desasignar">
                                                <svg className="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
};

// ─── Componente principal ───────────────────────────────────────
const SecuritySchemaPanel = () => {
    const [products, setProducts] = useState({
        alto_riesgo_nivel_1: [], alto_riesgo_nivel_2: [],
        bajo_riesgo: [], no_amparada: [],
    });
    const [priceRanges, setPriceRanges] = useState({
        alto_riesgo_nivel_1: [], alto_riesgo_nivel_2: [],
        bajo_riesgo: [], bajo_riesgo_quimicos: [],
    });
    const [assignedClients, setAssignedClients] = useState([]);
    const [isSuperAdmin, setIsSuperAdmin] = useState(false);
    const [userOverrides, setUserOverrides] = useState({});
    const [loading, setLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [searching, setSearching] = useState(false);
    const [showDropdown, setShowDropdown] = useState(false);
    const [addingProduct, setAddingProduct] = useState(false);
    const [activeTab, setActiveTab] = useState('alto_riesgo_nivel_1');
    const searchRef = useRef(null);
    const debounceRef = useRef(null);

    // Modal state
    const [modalOpen, setModalOpen] = useState(false);
    const [editingRange, setEditingRange] = useState(null);

    // Commercial override modal state
    const [overrideModalOpen, setOverrideModalOpen] = useState(false);
    const [overrideBaseRange, setOverrideBaseRange] = useState(null);

    const loadData = useCallback(async () => {
        try {
            setLoading(true);
            const { data } = await fetchSecuritySchemaData();
            setProducts({
                alto_riesgo_nivel_1: data.products?.alto_riesgo_nivel_1 || [],
                alto_riesgo_nivel_2: data.products?.alto_riesgo_nivel_2 || [],
                bajo_riesgo: data.products?.bajo_riesgo || [],
                no_amparada: data.products?.no_amparada || [],
            });
            setPriceRanges({
                alto_riesgo_nivel_1: data.price_ranges?.alto_riesgo_nivel_1 || [],
                alto_riesgo_nivel_2: data.price_ranges?.alto_riesgo_nivel_2 || [],
                bajo_riesgo: data.price_ranges?.bajo_riesgo || [],
                bajo_riesgo_quimicos: data.price_ranges?.bajo_riesgo_quimicos || [],
            });
            setAssignedClients(data.assigned_clients || []);
            setIsSuperAdmin(data.is_super_admin || false);
            setUserOverrides(data.user_overrides || {});
        } catch (err) {
            console.error('Error loading security schema:', err);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { loadData(); }, [loadData]);

    // Cerrar dropdown al hacer click afuera
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (searchRef.current && !searchRef.current.contains(e.target)) {
                setShowDropdown(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // ─── Búsqueda de Productos ──────────────────────────────────
    const handleSearch = (value) => {
        setSearchQuery(value);
        if (debounceRef.current) clearTimeout(debounceRef.current);
        if (value.length < 2) {
            setSearchResults([]);
            setShowDropdown(false);
            return;
        }
        debounceRef.current = setTimeout(async () => {
            setSearching(true);
            try {
                const { data } = await searchProducts(value, activeTab);
                setSearchResults(data);
                setShowDropdown(true);
            } catch (err) {
                console.error('Error searching products:', err);
            } finally {
                setSearching(false);
            }
        }, 300);
    };

    const handleSelectProduct = async (product) => {
        setAddingProduct(true);
        try {
            await addProduct(product.producto_codigo, activeTab);
            setSearchQuery('');
            setSearchResults([]);
            setShowDropdown(false);
            loadData();
        } catch (err) {
            console.error('Error adding product:', err);
        } finally {
            setAddingProduct(false);
        }
    };

    // ─── Productos ──────────────────────────────────────────────
    const handleDeleteProduct = async (id) => {
        if (!confirm('¿Eliminar este producto de alto riesgo?')) return;
        try {
            await deleteProduct(id);
            loadData();
        } catch (err) {
            console.error('Error deleting product:', err);
        }
    };

    // ─── Rangos ─────────────────────────────────────────────────
    const handleSaveRange = async (data, existingId) => {
        if (existingId) {
            await updatePriceRange(existingId, data);
        } else {
            await createPriceRange(data);
        }
        loadData();
    };

    const handleDeleteRange = async (id) => {
        if (!confirm('¿Eliminar este rango de precio?')) return;
        try {
            await deletePriceRange(id);
            loadData();
        } catch (err) {
            console.error('Error deleting range:', err);
        }
    };

    const handleEditRange = (range) => {
        setEditingRange(range);
        setModalOpen(true);
    };

    const openNewRange = () => {
        setEditingRange(null);
        setModalOpen(true);
    };

    // ─── Override Comercial ─────────────────────────────────────
    const handleEditOverride = (baseRange) => {
        setOverrideBaseRange(baseRange);
        setOverrideModalOpen(true);
    };

    const handleSaveOverride = async (data) => {
        await saveUserOverride(data);
        loadData();
    };

    const handleResetOverride = async (baseRangeId) => {
        if (!confirm('¿Restaurar este rango a los valores del administrador?')) return;
        try {
            await deleteUserOverride(baseRangeId);
            loadData();
        } catch (err) {
            console.error('Error resetting override:', err);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-[400px]">
                <div className="animate-spin rounded-full h-12 w-12 border-4 border-orange-200 border-t-orange-500"></div>
            </div>
        );
    }

    const categoryKeys = Object.keys(CATEGORY_LABELS);

    // Categories that have price ranges (not "no_amparada")
    const PRICE_RANGE_CATEGORIES = ['alto_riesgo_nivel_1', 'alto_riesgo_nivel_2', 'bajo_riesgo'];

    return (
        <div className="space-y-8">
            {/* ───── Asignación de Clientes (Solo Super Admin) ───── */}
            {isSuperAdmin && (
                <ClientAssignmentSection
                    assignedClients={assignedClients}
                    onRefresh={loadData}
                />
            )}

            {/* ───── Clientes Asignados (Vista Comercial) ───── */}
            {!isSuperAdmin && assignedClients.length > 0 && (
                <div className="bg-white rounded-2xl shadow-lg overflow-hidden" style={{ border: '1px solid #ffbf69' }}>
                    <div className="text-white p-4" style={{ background: 'linear-gradient(to right, #2563eb, #3b82f6)' }}>
                        <h3 className="text-lg font-semibold flex items-center gap-2">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Clientes con Esquema de Seguridad
                        </h3>
                    </div>
                    <div className="p-4">
                        <div className="flex flex-wrap gap-2">
                            {assignedClients.map(a => (
                                <span key={a.id}
                                    className="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-sm font-medium border border-blue-200">
                                    <span className="text-blue-400 text-xs">#{a.client?.codigo}</span>
                                    {a.client?.cliente}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {/* ───── Tabs por Categoría con Productos + Rangos ───── */}
            <div className="bg-white rounded-2xl shadow-lg overflow-hidden" style={{ border: '1px solid #ffbf69' }}>
                <div className="flex border-b overflow-x-auto">
                    {categoryKeys.map(key => {
                        const colors = CATEGORY_COLORS[key];
                        const isActive = activeTab === key;
                        const productCount = (products[key] || []).length;
                        return (
                            <button key={key} onClick={() => { setActiveTab(key); setSearchQuery(''); setSearchResults([]); setShowDropdown(false); }}
                                className={`flex-1 py-3 px-3 text-xs sm:text-sm font-semibold transition-colors whitespace-nowrap ${isActive
                                    ? `border-b-2 ${colors.tab}`
                                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'}`}>
                                {colors.dot} {CATEGORY_LABELS[key]}
                                {productCount > 0 && (
                                    <span className="ml-1 text-xs bg-gray-200 text-gray-600 rounded-full px-1.5 py-0.5">{productCount}</span>
                                )}
                            </button>
                        );
                    })}
                </div>

                <div className="p-4">
                    {/* ─── Productos de esta categoría ─── */}
                    <div className="mb-6">
                        <div className="flex justify-between items-center mb-3">
                            <div>
                                <h4 className="font-semibold text-gray-800">
                                    Productos — {CATEGORY_LABELS[activeTab]}
                                </h4>
                                <p className="text-xs text-gray-500 mt-1">
                                    {activeTab === 'no_amparada'
                                        ? 'Productos que NO son amparados por nuestra póliza.'
                                        : activeTab.startsWith('bajo')
                                            ? 'Productos clasificados en esta categoría. Los productos no agregados a ninguna categoría se consideran de bajo riesgo.'
                                            : 'Agrega los productos que clasifican en esta categoría de riesgo.'}
                                </p>
                            </div>
                        </div>

                        {isSuperAdmin && (
                            <div className="relative mb-4" ref={searchRef}>
                                <div className="relative">
                                    <input type="text" value={searchQuery} onChange={e => handleSearch(e.target.value)}
                                        placeholder="Buscar producto por nombre (ej: Computador, Celular, Textil...)"
                                        className="w-full rounded-lg border-gray-300 focus:ring-orange-400 focus:border-orange-400 text-sm pl-10" />
                                    <svg className="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    {searching && (
                                        <div className="absolute right-3 top-1/2 -translate-y-1/2">
                                            <div className="animate-spin rounded-full h-4 w-4 border-2 border-orange-200 border-t-orange-500"></div>
                                        </div>
                                    )}
                                </div>
                                {showDropdown && searchResults.length > 0 && (
                                    <div className="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                        {searchResults.map(p => (
                                            <button key={p.producto_codigo} onClick={() => handleSelectProduct(p)} disabled={addingProduct}
                                                className="w-full text-left px-4 py-2.5 text-sm hover:bg-orange-50 transition-colors flex items-center gap-2 border-b border-gray-100 last:border-0 disabled:opacity-50">
                                                <span className="text-xs text-gray-400 font-mono w-12">#{p.producto_codigo}</span>
                                                <span className="text-gray-800">{p.producto_nombre}</span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                                {showDropdown && searchResults.length === 0 && !searching && searchQuery.length >= 2 && (
                                    <div className="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-sm text-gray-500 text-center">
                                        No se encontraron productos para "{searchQuery}"
                                    </div>
                                )}
                            </div>
                        )}

                        {(products[activeTab] || []).length === 0 ? (
                            <p className="text-gray-500 text-sm text-center py-3">No hay productos configurados en esta categoría.</p>
                        ) : (
                            <div className="flex flex-wrap gap-2">
                                {(products[activeTab] || []).map(p => {
                                    const badgeClass = CATEGORY_COLORS[activeTab]?.badge || 'bg-gray-50 text-gray-700 border-gray-200';
                                    return (
                                        <span key={p.id}
                                            className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-medium border ${badgeClass}`}>
                                            <span className="text-xs opacity-60">#{p.product_code}</span>
                                            {p.name}
                                            {isSuperAdmin && (
                                                <button onClick={() => handleDeleteProduct(p.id)} className="ml-1 opacity-50 hover:opacity-100">
                                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            )}
                                        </span>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* ─── Rangos de Precio (solo para categorías con rangos) ─── */}
                    {PRICE_RANGE_CATEGORIES.includes(activeTab) && (
                        <>
                            <hr className="my-4 border-gray-200" />
                            <div className="flex justify-between items-center mb-4">
                                <div>
                                    <h4 className="font-semibold text-gray-800">
                                        Rangos de Valor Declarado — {CATEGORY_LABELS[activeTab]}
                                    </h4>
                                    <p className="text-xs text-gray-500 mt-1">
                                        Configura las medidas de seguridad según el valor declarado de la mercancía.
                                    </p>
                                </div>
                                {isSuperAdmin && (
                                    <button onClick={openNewRange}
                                        className="px-4 py-2 bg-[#FF7C32] text-white rounded-lg hover:bg-orange-600 text-sm font-medium whitespace-nowrap">
                                        + Nuevo Rango
                                    </button>
                                )}
                            </div>

                            {(priceRanges[activeTab] || []).length === 0 ? (
                                <div className="text-center py-8">
                                    <svg className="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p className="text-gray-500 text-sm">
                                        {isSuperAdmin
                                            ? 'No hay rangos configurados. Haz clic en "Nuevo Rango" para comenzar.'
                                            : 'No hay rangos configurados para esta categoría.'}
                                    </p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left">
                                        <thead>
                                            <tr className="border-b-2 border-gray-200">
                                                <th className="px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Desde</th>
                                                <th className="px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Hasta</th>
                                                <th className="px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Recorridos Nacionales</th>
                                                <th className="px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Recorridos Urbanos</th>
                                                <th className="px-3 py-2 text-xs font-semibold text-gray-600 uppercase text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(priceRanges[activeTab] || []).map(range => (
                                                <PriceRangeRow key={range.id} range={range}
                                                    onDelete={handleDeleteRange} onEdit={handleEditRange}
                                                    onEditOverride={handleEditOverride}
                                                    onResetOverride={handleResetOverride}
                                                    isSuperAdmin={isSuperAdmin}
                                                    userOverride={userOverrides[range.id] || null} />
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>

            {/* ───── Información Importante (Siempre visible) ───── */}
            <InformativeSection />

            {/* Modal (Solo Super Admin) */}
            {isSuperAdmin && (
                <PriceRangeModal
                    isOpen={modalOpen}
                    onClose={() => { setModalOpen(false); setEditingRange(null); }}
                    onSave={handleSaveRange}
                    editingRange={editingRange}
                    category={activeTab}
                />
            )}

            {/* Modal Override (Comerciales) */}
            {!isSuperAdmin && (
                <CommercialOverrideModal
                    isOpen={overrideModalOpen}
                    onClose={() => { setOverrideModalOpen(false); setOverrideBaseRange(null); }}
                    onSave={handleSaveOverride}
                    baseRange={overrideBaseRange}
                    existingOverride={overrideBaseRange ? (userOverrides[overrideBaseRange.id] || null) : null}
                    category={activeTab}
                />
            )}
        </div>
    );
};

export default SecuritySchemaPanel;

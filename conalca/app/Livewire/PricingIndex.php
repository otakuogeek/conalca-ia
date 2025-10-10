<?php

namespace App\Livewire;

use App\Imports\PricingSMImport;
use App\Exports\PricingTemplateExport;
use App\Exports\SimplePricingTemplateExport;
use App\Models\Pricing;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class PricingIndex extends Component
{
    use WithFileUploads;
    public $pricings;
    public $showModal = false;
    public $filter_origin = '';
    public $filter_destination = '';
    public $type_pricing = '';
    public $showModalType = '';
    public $documents = '';
    public $vehicle_type = '';
    public $extra = '';
    public $price_extra = '';
    public $price_extra2 = '';
    public $download_target = '';
    public $load_target = '';
    public $iva = '';
    public $price_person = '';
    public $origin = '';
    public $destination = '';
    public $price = '';
    public $event = '';
    public $type_send = '';
    public $save_box = '';
    public $time_day = '';
    public $return = '';
    public $container = '';
    public $complements = '';
    public $download_price = '';
    public $person_download = '';
    public $rent = '';
    public $download = '';
    public $load = '';
    public $store = '';
    public $scales = '';
    public $time = '';
    public $weight = '';
    public $vehicle_extra = '';
    public $download_destiny = '';
    public $download_destiny_iva = '';
    public $price_complements = '';
    public $price_documents = '';
    public $load_tulan = '';
    public $volume = '';
    public $price_month = 0;
    public $price_aux_month = 0;
    public $price_week = 0;
    public $price_aux_week = 0;
    public $price_day = 0;
    public $price_aux_day = 0;
    public $condition = '';
    public $showTable = false;
    public $pricingId = null;
    public $file;
    public $export_type_pricing = '';
    public $import_type_pricing = '';

    // Export/Import properties
    public $exportPricingType = '';
    public $importPricingType = '';
    public $importFile = null;

    public function render()
    {
        return view('livewire.pricing-index');
    }

    public function mount()
    {
        $this->pricings = collect(); // Initialize as empty collection
    }

    public function updatedTypePricing()
    {
        // When type_pricing changes, clear additional filters and search
        $this->filter_origin = '';
        $this->filter_destination = '';
        
        if ($this->type_pricing) {
            $this->getPricings();
            $this->showTable = true;
        } else {
            $this->showTable = false;
        }
    }

    public function updatedFilterOrigin()
    {
        // Auto-search when origin filter changes (but only if we have a type_pricing selected)
        if ($this->type_pricing) {
            $this->getPricings();
            $this->showTable = true;
        }
    }

    public function updatedFilterDestination()
    {
        // Auto-search when destination filter changes (but only if we have a type_pricing selected)
        if ($this->type_pricing) {
            $this->getPricings();
            $this->showTable = true;
        }
    }

    public function importData(){
        if (empty($this->import_type_pricing)) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Por favor seleccione el tipo de pricing antes de importar.'
            ]);
            return;
        }

        if (!$this->file) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Por favor seleccione un archivo para importar.'
            ]);
            return;
        }

        try {
            Excel::import(new PricingSMImport($this->import_type_pricing), $this->file);
            $this->dispatch('alert', [
                'type' => 'success',
                'message' => 'Datos importados exitosamente.'
            ]);
            $this->file = null;
            $this->import_type_pricing = '';
        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Error al importar los datos: ' . $e->getMessage()
            ]);
        }
    }

    public function exportTemplate()
    {
        if (empty($this->exportPricingType)) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Por favor selecciona un tipo de pricing para exportar.'
            ]);
            return;
        }

        try {
            \Log::info('Livewire Export Template Called', [
                'pricing_type' => $this->exportPricingType
            ]);

            // Store the pricing type in session for the controller
            session(['export_pricing_type' => $this->exportPricingType]);

            // Dispatch event to trigger download via JavaScript
            $this->dispatch('downloadTemplate', ['type' => $this->exportPricingType]);

        } catch (\Exception $e) {
            \Log::error('Livewire Export Error', [
                'error' => $e->getMessage(),
                'pricing_type' => $this->exportPricingType
            ]);

            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Error al exportar template: ' . $e->getMessage()
            ]);
        }
    }

    public function importTemplate()
    {
        if (empty($this->importPricingType)) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Por favor selecciona un tipo de pricing para importar.'
            ]);
            return;
        }

        if (!$this->importFile) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Por favor selecciona un archivo para importar.'
            ]);
            return;
        }

        try {
            \Log::info('Importing template', [
                'pricing_type' => $this->importPricingType,
                'file' => $this->importFile->getClientOriginalName()
            ]);

            // Import the file
            Excel::import(new PricingSMImport($this->importPricingType), $this->importFile);

            $this->dispatch('alert', [
                'type' => 'success',
                'message' => 'Template importado exitosamente. Los registros existentes han sido actualizados y los nuevos han sido creados.'
            ]);

            // Reset the form
            $this->importFile = null;
            $this->importPricingType = '';

            // Refresh the pricing list
            $this->getPricings();

        } catch (\Exception $e) {
            \Log::error('Import Error', [
                'error' => $e->getMessage(),
                'pricing_type' => $this->importPricingType
            ]);

            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Error al importar template: ' . $e->getMessage()
            ]);
        }
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->showModalType = $this->type_pricing; // Set the modal type when opening
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->showModalType = '';
        $this->resetData();
    }

    public function setModal()
    {
        $this->showModalType = $this->type_pricing;
        // Don't reset data when changing type in modal - let user see their changes
    }

    public function openTable(){
        $this->getPricings();
        $this->showTable = true;
    }

    public function searchPricings(){
        // Validate that type_pricing is selected
        if (empty($this->type_pricing)) {
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => 'Por favor seleccione un Tipo de Pricing primero.'
            ]);
            return;
        }

        $this->getPricings();
        $this->showTable = true;
    }

    public function resetTable(){
        $this->showTable = false;
        $this->showModalType = $this->type_pricing;
    }

    public function getPricings(){
        $query = Pricing::query();

        // Always filter by type_pricing if selected
        if ($this->type_pricing) {
            $query->where('type_pricing', $this->type_pricing);
        }

        // Apply additional filters only if they have values
        if (!empty($this->filter_origin)) {
            $query->where('origin', 'like', '%' . $this->filter_origin . '%');
        }

        if (!empty($this->filter_destination)) {
            $query->where('destination', 'like', '%' . $this->filter_destination . '%');
        }

        $this->pricings = $query->get();
    }

    public function clearFilters(){
        $this->filter_origin = '';
        $this->filter_destination = '';
        $this->type_pricing = '';
        $this->showTable = false;
        $this->pricings = collect();
    }

    public function resetFilters(){
        $this->filter_origin = '';
        $this->filter_destination = '';
        // Keep type_pricing selected, just clear additional filters
        if ($this->type_pricing) {
            $this->getPricings();
        }
    }

    public function savePricing()
    {
        // Basic validation
        if (empty($this->type_pricing)) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Por favor seleccione un tipo de pricing.'
            ]);
            return;
        }

        $data = [
            'type_pricing' => $this->type_pricing,
            'documents' => $this->documents,
            'vehicle_type' => $this->vehicle_type,
            'extra' => $this->extra,
            'price_extra' => $this->price_extra,
            'price_extra2' => $this->price_extra2,
            'download_target' => $this->download_target,
            'load_target' => $this->load_target,
            'iva' => $this->iva,
            'price_person' => $this->price_person,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'price' => $this->price,
            'event' => $this->event,
            'type_send' => $this->type_send,
            'save_box' => $this->save_box,
            'time_day' => $this->time_day,
            'return' => $this->return,
            'container' => $this->container,
            'complements' => $this->complements,
            'download_price' => $this->download_price,
            'person_download' => $this->person_download,
            'rent' => $this->rent,
            'download' => $this->download,
            'load' => $this->load,
            'store' => $this->store,
            'scales' => $this->scales,
            'time' => $this->time,
            'weight' => $this->weight,
            'vehicle_extra' => $this->vehicle_extra,
            'download_destiny' => $this->download_destiny,
            'download_destiny_iva' => $this->download_destiny_iva,
            'price_complements' => $this->price_complements,
            'price_documents' => $this->price_documents,
            'load_tulan' => $this->load_tulan,
            'volume' => $this->volume,
            'price_month' => $this->price_month,
            'price_aux_month' => $this->price_aux_month,
            'price_week' => $this->price_week,
            'price_aux_week' => $this->price_aux_week,
            'price_day' => $this->price_day,
            'price_aux_day' => $this->price_aux_day,
            'condition' => $this->condition
        ];

        try {
            if ($this->pricingId) {
                $pricing = Pricing::find($this->pricingId);
                $pricing->update($data);
                $this->dispatch('alert', [
                    'type' => 'success',
                    'message' => 'Pricing actualizado exitosamente.'
                ]);
            } else {
                Pricing::create($data);
                $this->dispatch('alert', [
                    'type' => 'success',
                    'message' => 'Pricing creado exitosamente.'
                ]);
            }

            $this->closeModal();
            $this->resetData();
            $this->getPricings();
        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Error al guardar el pricing: ' . $e->getMessage()
            ]);
        }
    }

    public function editPricing($id)
    {
        $pricing = Pricing::find($id);
        
        if (!$pricing) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Pricing no encontrado.'
            ]);
            return;
        }

        $this->pricingId = $pricing->id;
        $this->type_pricing = $pricing->type_pricing;
        $this->documents = $pricing->documents;
        $this->vehicle_type = $pricing->vehicle_type;
        $this->extra = $pricing->extra;
        $this->price_extra = $pricing->price_extra;
        $this->price_extra2 = $pricing->price_extra2;
        $this->download_target = $pricing->download_target;
        $this->load_target = $pricing->load_target;
        $this->iva = $pricing->iva;
        $this->price_person = $pricing->price_person;
        $this->origin = $pricing->origin;
        $this->destination = $pricing->destination;
        $this->price = $pricing->price;
        $this->event = $pricing->event;
        $this->type_send = $pricing->type_send;
        $this->save_box = $pricing->save_box;
        $this->time_day = $pricing->time_day;
        $this->return = $pricing->return;
        $this->container = $pricing->container;
        $this->complements = $pricing->complements;
        $this->download_price = $pricing->download_price;
        $this->person_download = $pricing->person_download;
        $this->rent = $pricing->rent;
        $this->download = $pricing->download;
        $this->load = $pricing->load;
        $this->store = $pricing->store;
        $this->scales = $pricing->scales;
        $this->time = $pricing->time;
        $this->weight = $pricing->weight;
        $this->vehicle_extra = $pricing->vehicle_extra;
        $this->download_destiny = $pricing->download_destiny;
        $this->download_destiny_iva = $pricing->download_destiny_iva;
        $this->price_complements = $pricing->price_complements;
        $this->price_documents = $pricing->price_documents;
        $this->load_tulan = $pricing->load_tulan;
        $this->volume = $pricing->volume;
        $this->price_month = $pricing->price_month;
        $this->price_aux_month = $pricing->price_aux_month;
        $this->price_week = $pricing->price_week;
        $this->price_aux_week = $pricing->price_aux_week;
        $this->price_day = $pricing->price_day;
        $this->price_aux_day = $pricing->price_aux_day;
        $this->condition = $pricing->condition;

        $this->showModalType = $this->type_pricing; // Set modal type based on pricing type
        $this->openModal();
    }

    public function deletePricing($id)
    {
        try {
            $pricing = Pricing::find($id);
            if ($pricing) {
                $pricing->delete();
                $this->dispatch('alert', [
                    'type' => 'success',
                    'message' => 'Pricing eliminado exitosamente.'
                ]);
            } else {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'Pricing no encontrado.'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Error al eliminar el pricing: ' . $e->getMessage()
            ]);
        }
        
        $this->getPricings();
    }

    private function resetData()
    {
        $this->pricingId = null;
        $this->documents = '';
        $this->vehicle_type = '';
        $this->extra = '';
        $this->price_extra = '';
        $this->price_extra2 = '';
        $this->download_target = '';
        $this->load_target = '';
        $this->iva = '';
        $this->price_person = '';
        $this->origin = '';
        $this->destination = '';
        $this->price = '';
        $this->event = '';
        $this->type_send = '';
        $this->save_box = '';
        $this->time_day = '';
        $this->return = '';
        $this->container = '';
        $this->complements = '';
        $this->download_price = '';
        $this->person_download = '';
        $this->rent = '';
        $this->download = '';
        $this->load = '';
        $this->store = '';
        $this->scales = '';
        $this->time = '';
        $this->weight = '';
        $this->vehicle_extra = '';
        $this->download_destiny = '';
        $this->download_destiny_iva = '';
        $this->price_complements = '';
        $this->price_documents = '';
        $this->load_tulan = '';
        $this->volume = '';
        $this->price_month = 0;
        $this->price_aux_month = 0;
        $this->price_week = 0;
        $this->price_aux_week = 0;
        $this->price_day = 0;
        $this->price_aux_day = 0;
        $this->condition = '';
    }
}

/**
 * jQuery plugin for SortableJS
 * Simple wrapper to make SortableJS work with jQuery syntax
 */
(function($) {
    'use strict';
    
    if (!window.Sortable) {
        console.error('SortableJS not loaded. Please include Sortable.min.js before jquery-sortable.js');
        return;
    }
    
    $.fn.sortable = function(options) {
        return this.each(function() {
            var $this = $(this);
            
            // Si ya tiene sortable, destruirlo primero
            if ($this.data('sortable')) {
                $this.data('sortable').destroy();
            }
            
            // Crear nueva instancia de Sortable
            var sortable = new Sortable(this, options || {});
            
            // Guardar instancia en data
            $this.data('sortable', sortable);
        });
    };
    
    // Método para obtener la instancia de Sortable
    $.fn.sortableInstance = function() {
        return this.data('sortable');
    };
    
})(jQuery);

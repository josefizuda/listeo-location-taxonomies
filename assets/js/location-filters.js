/**
 * Listeo Location Taxonomies - Frontend JS
 * Handles hierarchical filtering for Estado > Cidade > Bairro
 */

(function($) {
    'use strict';

    var ListeoLocationFilters = {

        init: function() {
            this.bindEvents();
            this.initializeSelects();
        },

        bindEvents: function() {
            // Estado change event (both filter and search form)
            $(document).on('change', '#estado-filter, #location-search-estado', this.handleEstadoChange.bind(this));

            // Cidade change event (both filter and search form)
            $(document).on('change', '#cidade-filter, #location-search-cidade', this.handleCidadeChange.bind(this));

            // Initialize on document ready
            $(document).ready(function() {
                // If we're on a filtered page, populate child selects
                var selectedEstado = $('#estado-filter').val();
                if (selectedEstado) {
                    ListeoLocationFilters.loadCidades(selectedEstado, true);
                }

                var selectedCidade = $('#cidade-filter').val();
                if (selectedCidade) {
                    ListeoLocationFilters.loadBairros(selectedCidade, true);
                }
            });

            // Handle form submission to ensure filters are applied
            $(document).on('submit', '.main-search-form, .listeo-search-form', this.handleFormSubmit.bind(this));
        },

        initializeSelects: function() {
            // Initialize Bootstrap Select (selectpicker) used by Listeo
            if ($.fn.selectpicker) {
                $('.listeo-location-filter').selectpicker({
                    liveSearch: true,
                    liveSearchPlaceholder: 'Pesquisar...',
                    liveSearchNormalize: true,
                    noneSelectedText: '',
                    size: 7
                });
            }
        },

        handleEstadoChange: function(e) {
            var estadoSlug = $(e.target).val();
            var estadoId = $(e.target).find(':selected').data('term-id');
            var isSearchForm = $(e.target).attr('id') === 'location-search-estado';

            // Determine which selects to update
            var cidadeSelector = isSearchForm ? '#location-search-cidade' : '#cidade-filter';
            var bairroSelector = isSearchForm ? '#location-search-bairro' : '#bairro-filter';

            // Reset and disable cidade and bairro
            this.resetSelect(cidadeSelector, listeoLocationData.strings.select_city);
            this.resetSelect(bairroSelector, listeoLocationData.strings.select_neighborhood);

            if (estadoSlug) {
                // Get term ID from slug
                if (!estadoId) {
                    estadoId = this.getTermIdFromSelect($(e.target).attr('id'), estadoSlug);
                }

                this.loadCidades(estadoId, false, isSearchForm);
            } else {
                this.disableSelect(cidadeSelector);
                this.disableSelect(bairroSelector);
            }

            // Trigger Listeo's search update if available
            this.triggerListeoUpdate();
        },

        handleCidadeChange: function(e) {
            var cidadeSlug = $(e.target).val();
            var cidadeId = $(e.target).find(':selected').data('term-id');
            var isSearchForm = $(e.target).attr('id') === 'location-search-cidade';

            // Determine which select to update
            var bairroSelector = isSearchForm ? '#location-search-bairro' : '#bairro-filter';

            // Reset bairro
            this.resetSelect(bairroSelector, listeoLocationData.strings.select_neighborhood);

            if (cidadeSlug) {
                // Get term ID from slug
                if (!cidadeId) {
                    cidadeId = this.getTermIdFromSelect($(e.target).attr('id'), cidadeSlug);
                }

                this.loadBairros(cidadeId, false, isSearchForm);
            } else {
                this.disableSelect(bairroSelector);
            }

            // Trigger Listeo's search update if available
            this.triggerListeoUpdate();
        },

        loadCidades: function(estadoId, preserveSelection, isSearchForm) {
            var self = this;
            var cidadeSelector = isSearchForm ? '#location-search-cidade' : '#cidade-filter';
            var $cidadeSelect = $(cidadeSelector);
            var currentValue = preserveSelection ? $cidadeSelect.val() : '';

            // Show loading state
            $cidadeSelect.prop('disabled', true).html('<option>' + listeoLocationData.strings.loading + '</option>');

            $.ajax({
                url: listeoLocationData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_cidades_by_estado',
                    estado_id: estadoId,
                    nonce: listeoLocationData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.populateCidadeSelect(response.data, currentValue, isSearchForm);
                    } else {
                        console.error('Error loading cities:', response.data.message);
                        self.disableSelect(cidadeSelector);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error loading cities:', error);
                    self.disableSelect(cidadeSelector);
                }
            });
        },

        loadBairros: function(cidadeId, preserveSelection, isSearchForm) {
            var self = this;
            var bairroSelector = isSearchForm ? '#location-search-bairro' : '#bairro-filter';
            var $bairroSelect = $(bairroSelector);
            var currentValue = preserveSelection ? $bairroSelect.val() : '';

            // Show loading state
            $bairroSelect.prop('disabled', true).html('<option>' + listeoLocationData.strings.loading + '</option>');

            $.ajax({
                url: listeoLocationData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_bairros_by_cidade',
                    cidade_id: cidadeId,
                    nonce: listeoLocationData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.populateBairroSelect(response.data, currentValue, isSearchForm);
                    } else {
                        console.error('Error loading neighborhoods:', response.data.message);
                        self.disableSelect(bairroSelector);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error loading neighborhoods:', error);
                    self.disableSelect(bairroSelector);
                }
            });
        },

        populateCidadeSelect: function(cidades, selectedValue, isSearchForm) {
            var cidadeSelector = isSearchForm ? '#location-search-cidade' : '#cidade-filter';
            var $select = $(cidadeSelector);
            var html = '<option value="">' + listeoLocationData.strings.select_city + '</option>';

            if (cidades && cidades.length > 0) {
                $.each(cidades, function(index, cidade) {
                    var selected = selectedValue === cidade.slug ? ' selected' : '';
                    html += '<option value="' + cidade.slug + '" data-term-id="' + cidade.id + '"' + selected + '>' +
                            cidade.name + ' (' + cidade.count + ')' +
                            '</option>';
                });
            }

            $select.html(html).prop('disabled', false);

            // Refresh select2/chosen if active
            this.refreshSelect(cidadeSelector);

            // If a city was selected, load its neighborhoods
            if (selectedValue) {
                var cidadeId = this.getTermIdFromSelect(cidadeSelector, selectedValue);
                if (cidadeId) {
                    this.loadBairros(cidadeId, true, isSearchForm);
                }
            }
        },

        populateBairroSelect: function(bairros, selectedValue, isSearchForm) {
            var bairroSelector = isSearchForm ? '#location-search-bairro' : '#bairro-filter';
            var $select = $(bairroSelector);
            var html = '<option value="">' + listeoLocationData.strings.select_neighborhood + '</option>';

            if (bairros && bairros.length > 0) {
                $.each(bairros, function(index, bairro) {
                    var selected = selectedValue === bairro.slug ? ' selected' : '';
                    html += '<option value="' + bairro.slug + '" data-term-id="' + bairro.id + '"' + selected + '>' +
                            bairro.name + ' (' + bairro.count + ')' +
                            '</option>';
                });
            }

            $select.html(html).prop('disabled', false);

            // Refresh select2/chosen if active
            this.refreshSelect(bairroSelector);
        },

        resetSelect: function(selector, placeholder) {
            var $select = $(selector);
            $select.html('<option value="">' + placeholder + '</option>');
            this.refreshSelect(selector);
        },

        disableSelect: function(selector) {
            var $select = $(selector);
            $select.prop('disabled', true);

            if ($.fn.selectpicker) {
                $select.selectpicker('destroy');
                $select.selectpicker({
                    liveSearch: true,
                    liveSearchPlaceholder: 'Pesquisar...',
                    liveSearchNormalize: true,
                    noneSelectedText: '',
                    size: 7
                });
            }
        },

        refreshSelect: function(selector) {
            var $select = $(selector);

            if ($.fn.selectpicker) {
                // Destroy and re-initialize to ensure live search works
                $select.selectpicker('destroy');
                $select.selectpicker({
                    liveSearch: true,
                    liveSearchPlaceholder: 'Pesquisar...',
                    liveSearchNormalize: true,
                    noneSelectedText: '',
                    size: 7
                });
            }
        },

        getTermIdFromSelect: function(selector, slug) {
            var termId = $(selector + ' option[value="' + slug + '"]').data('term-id');
            return termId || null;
        },

        handleFormSubmit: function(e) {
            // Ensure disabled selects don't submit empty values
            $('.listeo-location-filter:disabled').prop('disabled', false).val('');
        },

        triggerListeoUpdate: function() {
            // Trigger custom event for Listeo theme to update results
            $(document).trigger('listeo-location-filter-change');

            // If Listeo uses a specific trigger function, call it
            if (typeof window.listeo_update_search !== 'undefined') {
                window.listeo_update_search();
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ListeoLocationFilters.init();
    });

    // Make available globally
    window.ListeoLocationFilters = ListeoLocationFilters;

})(jQuery);

<?php
/**
 * Search Fields Integration
 *
 * @package Listeo_Location_Taxonomies
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for adding search fields
 */
class Listeo_Location_Search_Fields {

    /**
     * The single instance of the class
     * @var Listeo_Location_Search_Fields
     */
    protected static $_instance = null;

    /**
     * Main Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // AJAX actions for loading hierarchical data
        add_action('wp_ajax_get_cidades_by_estado', array($this, 'ajax_get_cidades_by_estado'));
        add_action('wp_ajax_nopriv_get_cidades_by_estado', array($this, 'ajax_get_cidades_by_estado'));

        add_action('wp_ajax_get_bairros_by_cidade', array($this, 'ajax_get_bairros_by_cidade'));
        add_action('wp_ajax_nopriv_get_bairros_by_cidade', array($this, 'ajax_get_bairros_by_cidade'));

        // Register shortcodes for search fields
        add_shortcode('listeo_location_search', array($this, 'location_search_shortcode'));
        add_shortcode('listeo_location_search_form', array($this, 'location_search_form_shortcode'));
        add_shortcode('listeo_location_search_tabs', array($this, 'location_search_tabs_shortcode'));
        add_shortcode('listeo_search_form_tabs', array($this, 'search_form_tabs_shortcode'));
        add_shortcode('listeo_search_form_tabs_vertical', array($this, 'search_form_tabs_vertical_shortcode'));
        add_shortcode('listeo_estado_field', array($this, 'estado_field_shortcode'));
        add_shortcode('listeo_cidade_field', array($this, 'cidade_field_shortcode'));
        add_shortcode('listeo_bairro_field', array($this, 'bairro_field_shortcode'));
        add_shortcode('listeo_listing_category_field', array($this, 'listing_category_field_shortcode'));
        add_shortcode('listeo_tipo_de_negocio_field', array($this, 'tipo_de_negocio_field_shortcode'));
        add_shortcode('listeo_tipo_de_imovel_field', array($this, 'tipo_de_imovel_field_shortcode'));

        // Add fields to Listeo search form if the filter exists
        add_filter('listeo_search_form_fields', array($this, 'add_location_fields_to_search_form'), 10, 1);
    }

    /**
     * AJAX: Get cities by state
     */
    public function ajax_get_cidades_by_estado() {
        check_ajax_referer('listeo_location_nonce', 'nonce');

        $estado_id = isset($_POST['estado_id']) ? absint($_POST['estado_id']) : 0;

        if (!$estado_id) {
            wp_send_json_error(array('message' => __('ID de estado inválido', 'listeo-location-taxonomies')));
        }

        // Debug: Log the estado_id received
        error_log('AJAX get_cidades_by_estado - Estado ID: ' . $estado_id);

        $cidades = get_terms(array(
            'taxonomy' => 'cidade',
            'hide_empty' => false, // Changed to false for debugging
            'meta_query' => array(
                array(
                    'key' => 'parent_estado',
                    'value' => $estado_id,
                    'compare' => '='
                )
            )
        ));

        // Debug: Log query results
        error_log('AJAX get_cidades_by_estado - Total cidades found: ' . (is_array($cidades) ? count($cidades) : 0));

        if (is_wp_error($cidades)) {
            error_log('AJAX get_cidades_by_estado - Error: ' . $cidades->get_error_message());
            wp_send_json_error(array('message' => $cidades->get_error_message()));
        }

        $response = array();
        foreach ($cidades as $cidade) {
            $parent_estado = get_term_meta($cidade->term_id, 'parent_estado', true);
            error_log('Cidade: ' . $cidade->name . ' (ID: ' . $cidade->term_id . ') - parent_estado: ' . $parent_estado);

            $response[] = array(
                'id' => $cidade->term_id,
                'name' => $cidade->name,
                'slug' => $cidade->slug,
                'count' => $cidade->count
            );
        }

        error_log('AJAX get_cidades_by_estado - Returning ' . count($response) . ' cidades');
        wp_send_json_success($response);
    }

    /**
     * AJAX: Get neighborhoods by city
     */
    public function ajax_get_bairros_by_cidade() {
        check_ajax_referer('listeo_location_nonce', 'nonce');

        $cidade_id = isset($_POST['cidade_id']) ? absint($_POST['cidade_id']) : 0;

        if (!$cidade_id) {
            wp_send_json_error(array('message' => __('ID de cidade inválido', 'listeo-location-taxonomies')));
        }

        $bairros = get_terms(array(
            'taxonomy' => 'bairro',
            'hide_empty' => true,
            'meta_query' => array(
                array(
                    'key' => 'parent_cidade',
                    'value' => $cidade_id,
                    'compare' => '='
                )
            )
        ));

        if (is_wp_error($bairros)) {
            wp_send_json_error(array('message' => $bairros->get_error_message()));
        }

        $response = array();
        foreach ($bairros as $bairro) {
            $response[] = array(
                'id' => $bairro->term_id,
                'name' => $bairro->name,
                'slug' => $bairro->slug,
                'count' => $bairro->count
            );
        }

        wp_send_json_success($response);
    }

    /**
     * Complete location search shortcode
     * Usage: [listeo_location_search]
     */
    public function location_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_labels' => 'true',
            'placeholder_estado' => __('Selecione um Estado', 'listeo-location-taxonomies'),
            'placeholder_cidade' => __('Selecione uma Cidade', 'listeo-location-taxonomies'),
            'placeholder_bairro' => __('Selecione um Bairro', 'listeo-location-taxonomies'),
        ), $atts);

        ob_start();
        ?>
        <div class="listeo-location-search-wrapper">
            <?php if ($atts['show_labels'] === 'true') : ?>
                <h4><?php _e('Localização', 'listeo-location-taxonomies'); ?></h4>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <?php echo $this->get_estado_field($atts['placeholder_estado']); ?>
                </div>
                <div class="col-md-4">
                    <?php echo $this->get_cidade_field($atts['placeholder_cidade']); ?>
                </div>
                <div class="col-md-4">
                    <?php echo $this->get_bairro_field($atts['placeholder_bairro']); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Complete search form with submit button
     * Usage: [listeo_location_search_form]
     */
    public function location_search_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'action_url' => home_url('/listings/'),
            'button_text' => __('Buscar', 'listeo-location-taxonomies'),
            'placeholder_estado' => __('Estado', 'listeo-location-taxonomies'),
            'placeholder_cidade' => __('Cidade', 'listeo-location-taxonomies'),
            'placeholder_bairro' => __('Bairro', 'listeo-location-taxonomies'),
            'placeholder_tipo' => __('Tipo de Imóvel', 'listeo-location-taxonomies'),
            'show_category' => 'true',
        ), $atts);

        $estados = get_terms(array(
            'taxonomy' => 'estado',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        ob_start();
        ?>
        <div class="listeo-location-search-form-wrapper">
            <form class="listeo-location-search-form" action="<?php echo esc_url($atts['action_url']); ?>" method="get">
                <div class="location-search-fields-row">

                    <?php if ($atts['show_category'] === 'true') : ?>
                        <div class="location-search-field location-field-category">
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'listing_category',
                                'hide_empty' => true,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ));
                            ?>
                            <select name="tax-listing_category" class="selectpicker" data-placeholder="<?php echo esc_attr($atts['placeholder_tipo']); ?>" title="<?php echo esc_attr($atts['placeholder_tipo']); ?>">
                                <option value=""><?php echo esc_html($atts['placeholder_tipo']); ?></option>
                                <?php if (!is_wp_error($categories) && !empty($categories)) : ?>
                                    <?php foreach ($categories as $category) : ?>
                                        <option value="<?php echo esc_attr($category->slug); ?>">
                                            <?php echo esc_html($category->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="location-search-field location-field-estado">
                        <select name="tax-estado" id="location-search-estado" class="listeo-location-filter selectpicker" data-placeholder="<?php echo esc_attr($atts['placeholder_estado']); ?>" title="<?php echo esc_attr($atts['placeholder_estado']); ?>">
                            <option value=""><?php echo esc_html($atts['placeholder_estado']); ?></option>
                            <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                                <?php foreach ($estados as $estado) : ?>
                                    <option value="<?php echo esc_attr($estado->slug); ?>" data-term-id="<?php echo esc_attr($estado->term_id); ?>">
                                        <?php echo esc_html($estado->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="location-search-field location-field-cidade">
                        <select name="tax-cidade" id="location-search-cidade" class="listeo-location-filter selectpicker" data-placeholder="<?php echo esc_attr($atts['placeholder_cidade']); ?>" title="<?php echo esc_attr($atts['placeholder_cidade']); ?>" disabled>
                            <option value=""><?php echo esc_html($atts['placeholder_cidade']); ?></option>
                        </select>
                    </div>

                    <div class="location-search-field location-field-bairro">
                        <select name="tax-bairro" id="location-search-bairro" class="listeo-location-filter selectpicker" data-placeholder="<?php echo esc_attr($atts['placeholder_bairro']); ?>" title="<?php echo esc_attr($atts['placeholder_bairro']); ?>" disabled>
                            <option value=""><?php echo esc_html($atts['placeholder_bairro']); ?></option>
                        </select>
                    </div>

                    <div class="location-search-field location-field-submit">
                        <button type="submit" class="button listeo-location-search-button">
                            <i class="fa fa-search"></i> <?php echo esc_html($atts['button_text']); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <style>
        .listeo-location-search-form-wrapper {
            margin: 20px 0;
        }
        .location-search-fields-row {
            display: flex;
            gap: 10px;
            align-items: stretch;
            flex-wrap: wrap;
        }
        .location-search-field {
            flex: 1;
            min-width: 150px;
        }
        .location-field-submit {
            flex: 0 0 auto;
            min-width: 150px;
        }
        .listeo-location-search-button {
            width: 100%;
            height: 100%;
            min-height: 50px;
            background: #f91942;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .listeo-location-search-button:hover {
            background: #d91739;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 25, 66, 0.3);
        }
        .listeo-location-search-button i {
            margin-right: 8px;
        }
        .location-search-field .bootstrap-select {
            width: 100% !important;
        }
        .location-search-field .bootstrap-select .btn {
            height: 50px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: white;
            text-align: left;
            padding: 0 15px;
        }
        .location-search-field .bootstrap-select .btn:focus {
            outline: none;
            border-color: #f91942;
            box-shadow: 0 0 0 3px rgba(249, 25, 66, 0.1);
        }
        @media (max-width: 768px) {
            .location-search-fields-row {
                flex-direction: column;
            }
            .location-search-field,
            .location-field-submit {
                width: 100%;
                min-width: 100%;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            console.log('=== LOCATION SEARCH FORM DEBUG ===');
            console.log('Initializing location search form selectpicker...');

            // Initialize selectpicker for this form
            if (typeof $.fn.selectpicker !== 'undefined') {
                $('.listeo-location-search-form select').selectpicker();
                console.log('✓ Selectpicker initialized for location form');
            } else {
                console.error('✗ Selectpicker not available!');
            }

            // Ensure the location filters are initialized
            if (typeof window.ListeoLocationFilters !== 'undefined') {
                console.log('✓ ListeoLocationFilters is available');
            } else {
                console.error('✗ ListeoLocationFilters not loaded!');
            }

            // Test event binding
            $('#location-search-estado').on('change', function() {
                console.log('Estado changed! Value:', $(this).val());
                console.log('Selected option data-term-id:', $(this).find(':selected').data('term-id'));
            });

            console.log('=== END DEBUG ===');
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Search form with tabs (Comprar/Alugar)
     * Usage: [listeo_location_search_tabs]
     */
    public function location_search_tabs_shortcode($atts) {
        $atts = shortcode_atts(array(
            'action_url' => home_url('/listings/'),
            'button_text_comprar' => __('Buscar para Comprar', 'listeo-location-taxonomies'),
            'button_text_alugar' => __('Buscar para Alugar', 'listeo-location-taxonomies'),
        ), $atts);

        $estados = get_terms(array(
            'taxonomy' => 'estado',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $categories = get_terms(array(
            'taxonomy' => 'listing_category',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        ob_start();
        ?>
        <div class="listeo-location-tabs-wrapper">
            <!-- Tabs Navigation -->
            <ul class="location-tabs-nav" role="tablist">
                <li class="tab-nav-item active">
                    <a href="#tab-comprar" role="tab" aria-selected="true">Comprar</a>
                </li>
                <li class="tab-nav-item">
                    <a href="#tab-alugar" role="tab" aria-selected="false">Alugar</a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="location-tabs-content">
                <!-- Tab Comprar -->
                <div id="tab-comprar" class="location-tab-pane active" role="tabpanel">
                    <form class="listeo-location-search-form" action="<?php echo esc_url($atts['action_url']); ?>" method="get">
                        <input type="hidden" name="_classifieds_tab_tipo_de_negocio" value="_classifieds_tab_tipo_de_negocio_comprar">

                        <div class="location-search-fields-row">
                            <!-- Estado -->
                            <div class="location-search-field">
                                <select name="tax-estado" id="comprar-estado" class="listeo-location-filter selectpicker" data-placeholder="Estado" title="Estado">
                                    <option value="">Estado</option>
                                    <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                                        <?php foreach ($estados as $estado) : ?>
                                            <option value="<?php echo esc_attr($estado->slug); ?>" data-term-id="<?php echo esc_attr($estado->term_id); ?>">
                                                <?php echo esc_html($estado->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Cidade -->
                            <div class="location-search-field">
                                <select name="tax-cidade" id="comprar-cidade" class="listeo-location-filter selectpicker" data-placeholder="Cidade" title="Cidade" disabled>
                                    <option value="">Cidade</option>
                                </select>
                            </div>

                            <!-- Bairro -->
                            <div class="location-search-field">
                                <select name="tax-bairro" id="comprar-bairro" class="listeo-location-filter selectpicker" data-placeholder="Bairro" title="Bairro" disabled>
                                    <option value="">Bairro</option>
                                </select>
                            </div>

                            <!-- Tipo de Imóvel -->
                            <div class="location-search-field">
                                <select name="tax-listing_category" class="selectpicker" data-placeholder="Tipo de Imóvel" title="Tipo de Imóvel">
                                    <option value="">Tipo de Imóvel</option>
                                    <?php if (!is_wp_error($categories) && !empty($categories)) : ?>
                                        <?php foreach ($categories as $category) : ?>
                                            <option value="<?php echo esc_attr($category->slug); ?>">
                                                <?php echo esc_html($category->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Botão Buscar -->
                            <div class="location-search-field location-field-submit">
                                <button type="submit" class="button listeo-location-search-button">
                                    <i class="fa fa-search"></i> <?php echo esc_html($atts['button_text_comprar']); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab Alugar -->
                <div id="tab-alugar" class="location-tab-pane" role="tabpanel">
                    <form class="listeo-location-search-form" action="<?php echo esc_url($atts['action_url']); ?>" method="get">
                        <input type="hidden" name="_classifieds_tab_tipo_de_negocio" value="_classifieds_tab_tipo_de_negocio_alugar">

                        <div class="location-search-fields-row">
                            <!-- Estado -->
                            <div class="location-search-field">
                                <select name="tax-estado" id="alugar-estado" class="listeo-location-filter selectpicker" data-placeholder="Estado" title="Estado">
                                    <option value="">Estado</option>
                                    <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                                        <?php foreach ($estados as $estado) : ?>
                                            <option value="<?php echo esc_attr($estado->slug); ?>" data-term-id="<?php echo esc_attr($estado->term_id); ?>">
                                                <?php echo esc_html($estado->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Cidade -->
                            <div class="location-search-field">
                                <select name="tax-cidade" id="alugar-cidade" class="listeo-location-filter selectpicker" data-placeholder="Cidade" title="Cidade" disabled>
                                    <option value="">Cidade</option>
                                </select>
                            </div>

                            <!-- Bairro -->
                            <div class="location-search-field">
                                <select name="tax-bairro" id="alugar-bairro" class="listeo-location-filter selectpicker" data-placeholder="Bairro" title="Bairro" disabled>
                                    <option value="">Bairro</option>
                                </select>
                            </div>

                            <!-- Tipo de Imóvel -->
                            <div class="location-search-field">
                                <select name="tax-listing_category" class="selectpicker" data-placeholder="Tipo de Imóvel" title="Tipo de Imóvel">
                                    <option value="">Tipo de Imóvel</option>
                                    <?php if (!is_wp_error($categories) && !empty($categories)) : ?>
                                        <?php foreach ($categories as $category) : ?>
                                            <option value="<?php echo esc_attr($category->slug); ?>">
                                                <?php echo esc_html($category->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Botão Buscar -->
                            <div class="location-search-field location-field-submit">
                                <button type="submit" class="button listeo-location-search-button">
                                    <i class="fa fa-search"></i> <?php echo esc_html($atts['button_text_alugar']); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
        .listeo-location-tabs-wrapper {
            margin: 20px 0;
        }

        /* Tabs Navigation */
        .location-tabs-nav {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            border-bottom: 2px solid #e0e0e0;
        }

        .location-tabs-nav .tab-nav-item {
            margin: 0;
            padding: 0;
        }

        .location-tabs-nav .tab-nav-item a {
            display: block;
            padding: 15px 30px;
            text-decoration: none;
            color: #666;
            font-weight: 600;
            font-size: 16px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .location-tabs-nav .tab-nav-item.active a,
        .location-tabs-nav .tab-nav-item a:hover {
            color: #f91942;
            border-bottom-color: #f91942;
        }

        /* Tab Content */
        .location-tabs-content {
            padding: 20px 0;
        }

        .location-tab-pane {
            display: none;
        }

        .location-tab-pane.active {
            display: block;
        }

        /* Form Fields */
        .location-search-fields-row {
            display: flex;
            gap: 10px;
            align-items: stretch;
            flex-wrap: wrap;
        }

        .location-search-field {
            flex: 1;
            min-width: 150px;
        }

        .location-field-submit {
            flex: 0 0 auto;
            min-width: 150px;
        }

        .listeo-location-search-button {
            width: 100%;
            height: 100%;
            min-height: 50px;
            background: #f91942;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .listeo-location-search-button:hover {
            background: #d91739;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 25, 66, 0.3);
        }

        .listeo-location-search-button i {
            margin-right: 8px;
        }

        .location-search-field .bootstrap-select {
            width: 100% !important;
        }

        .location-search-field .bootstrap-select .btn {
            height: 50px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: white;
            text-align: left;
            padding: 0 15px;
        }

        .location-search-field .bootstrap-select .btn:focus {
            outline: none;
            border-color: #f91942;
            box-shadow: 0 0 0 3px rgba(249, 25, 66, 0.1);
        }

        @media (max-width: 768px) {
            .location-search-fields-row {
                flex-direction: column;
            }
            .location-search-field,
            .location-field-submit {
                width: 100%;
                min-width: 100%;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Initialize selectpicker
            if (typeof $.fn.selectpicker !== 'undefined') {
                $('.listeo-location-tabs-wrapper select').selectpicker();
            }

            // Tab switching
            $('.location-tabs-nav a').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');

                // Update nav
                $('.location-tabs-nav .tab-nav-item').removeClass('active');
                $(this).parent().addClass('active');

                // Update content
                $('.location-tab-pane').removeClass('active');
                $(target).addClass('active');
            });

            // Bind Estado changes for both tabs
            $('#comprar-estado').on('change', function() {
                handleEstadoChangeTabs($(this), '#comprar-cidade', '#comprar-bairro');
            });

            $('#alugar-estado').on('change', function() {
                handleEstadoChangeTabs($(this), '#alugar-cidade', '#alugar-bairro');
            });

            // Bind Cidade changes for both tabs
            $('#comprar-cidade').on('change', function() {
                handleCidadeChangeTabs($(this), '#comprar-bairro');
            });

            $('#alugar-cidade').on('change', function() {
                handleCidadeChangeTabs($(this), '#alugar-bairro');
            });

            function handleEstadoChangeTabs($estadoSelect, cidadeSelector, bairroSelector) {
                var estadoSlug = $estadoSelect.val();
                var estadoId = $estadoSelect.find(':selected').data('term-id');
                var $cidadeSelect = $(cidadeSelector);
                var $bairroSelect = $(bairroSelector);

                // Reset cidade and bairro
                $cidadeSelect.html('<option value="">Cidade</option>').prop('disabled', true).selectpicker('refresh');
                $bairroSelect.html('<option value="">Bairro</option>').prop('disabled', true).selectpicker('refresh');

                if (!estadoSlug || !estadoId) {
                    return;
                }

                // Load cidades
                $cidadeSelect.html('<option value="">Carregando...</option>').selectpicker('refresh');

                $.ajax({
                    url: listeoLocationData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_cidades_by_estado',
                        estado_id: estadoId,
                        nonce: listeoLocationData.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var html = '<option value="">Cidade</option>';
                            $.each(response.data, function(index, cidade) {
                                html += '<option value="' + cidade.slug + '" data-term-id="' + cidade.id + '">' + cidade.name + '</option>';
                            });
                            $cidadeSelect.html(html).prop('disabled', false).selectpicker('refresh');
                        }
                    }
                });
            }

            function handleCidadeChangeTabs($cidadeSelect, bairroSelector) {
                var cidadeSlug = $cidadeSelect.val();
                var cidadeId = $cidadeSelect.find(':selected').data('term-id');
                var $bairroSelect = $(bairroSelector);

                // Reset bairro
                $bairroSelect.html('<option value="">Bairro</option>').prop('disabled', true).selectpicker('refresh');

                if (!cidadeSlug || !cidadeId) {
                    return;
                }

                // Load bairros
                $bairroSelect.html('<option value="">Carregando...</option>').selectpicker('refresh');

                $.ajax({
                    url: listeoLocationData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_bairros_by_cidade',
                        cidade_id: cidadeId,
                        nonce: listeoLocationData.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var html = '<option value="">Bairro</option>';
                            $.each(response.data, function(index, bairro) {
                                html += '<option value="' + bairro.slug + '" data-term-id="' + bairro.id + '">' + bairro.name + '</option>';
                            });
                            $bairroSelect.html(html).prop('disabled', false).selectpicker('refresh');
                        }
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Search form with 3 tabs (Tudo/Venda/Aluguel)
     * Usage: [listeo_search_form_tabs]
     */
    public function search_form_tabs_shortcode($atts) {
        $atts = shortcode_atts(array(
            'action_url' => home_url('/listings/'),
            'button_text' => __('Buscar', 'listeo-location-taxonomies'),
        ), $atts);

        $estados = get_terms(array(
            'taxonomy' => 'estado',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $tipos_imovel = get_terms(array(
            'taxonomy' => 'tipo_de_imovel',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        ob_start();
        ?>
        <div class="listeo-search-tabs-wrapper">
            <!-- Tabs Navigation -->
            <ul class="search-tabs-nav" role="tablist">
                <li class="tab-nav-item active">
                    <a href="#tab-tudo" role="tab" aria-selected="true">Tudo</a>
                </li>
                <li class="tab-nav-item">
                    <a href="#tab-venda" role="tab" aria-selected="false">Venda</a>
                </li>
                <li class="tab-nav-item">
                    <a href="#tab-aluguel" role="tab" aria-selected="false">Aluguel</a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="search-tabs-content">
                <!-- Tab Tudo -->
                <div id="tab-tudo" class="search-tab-pane active" role="tabpanel">
                    <form class="listeo-search-form" action="<?php echo esc_url($atts['action_url']); ?>" method="get">
                        <div class="search-fields-row">
                            <!-- Estado -->
                            <div class="search-field">
                                <label>Estado</label>
                                <select name="tax-estado" id="tudo-estado" class="selectpicker" data-placeholder="Estado" title="Estado">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                                        <?php foreach ($estados as $estado) : ?>
                                            <option value="<?php echo esc_attr($estado->slug); ?>" data-term-id="<?php echo esc_attr($estado->term_id); ?>">
                                                <?php echo esc_html($estado->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Cidade -->
                            <div class="search-field">
                                <label>Cidade</label>
                                <select name="tax-cidade" id="tudo-cidade" class="selectpicker" data-placeholder="Cidade" title="Cidade" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Bairro -->
                            <div class="search-field">
                                <label>Bairro</label>
                                <select name="tax-bairro" id="tudo-bairro" class="selectpicker" data-placeholder="Bairro" title="Bairro" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Tipo de Imóvel -->
                            <div class="search-field">
                                <label>Tipo de Imóvel</label>
                                <select name="tax-tipo_de_imovel" class="selectpicker" data-placeholder="Tipo de Imóvel" title="Tipo">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($tipos_imovel) && !empty($tipos_imovel)) : ?>
                                        <?php foreach ($tipos_imovel as $tipo) : ?>
                                            <option value="<?php echo esc_attr($tipo->slug); ?>">
                                                <?php echo esc_html($tipo->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Botão Buscar -->
                            <div class="search-field field-submit">
                                <button type="submit" class="button search-button">
                                    <i class="fa fa-search"></i> <?php echo esc_html($atts['button_text']); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab Venda -->
                <div id="tab-venda" class="search-tab-pane" role="tabpanel">
                    <form class="listeo-search-form" action="<?php echo esc_url($atts['action_url']); ?>" method="get">
                        <input type="hidden" name="tax-tipo_de_negocio" value="venda">

                        <div class="search-fields-row">
                            <!-- Estado -->
                            <div class="search-field">
                                <label>Estado</label>
                                <select name="tax-estado" id="venda-estado" class="selectpicker" data-placeholder="Estado" title="Estado">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                                        <?php foreach ($estados as $estado) : ?>
                                            <option value="<?php echo esc_attr($estado->slug); ?>" data-term-id="<?php echo esc_attr($estado->term_id); ?>">
                                                <?php echo esc_html($estado->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Cidade -->
                            <div class="search-field">
                                <label>Cidade</label>
                                <select name="tax-cidade" id="venda-cidade" class="selectpicker" data-placeholder="Cidade" title="Cidade" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Bairro -->
                            <div class="search-field">
                                <label>Bairro</label>
                                <select name="tax-bairro" id="venda-bairro" class="selectpicker" data-placeholder="Bairro" title="Bairro" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Tipo de Imóvel -->
                            <div class="search-field">
                                <label>Tipo de Imóvel</label>
                                <select name="tax-tipo_de_imovel" class="selectpicker" data-placeholder="Tipo de Imóvel" title="Tipo">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($tipos_imovel) && !empty($tipos_imovel)) : ?>
                                        <?php foreach ($tipos_imovel as $tipo) : ?>
                                            <option value="<?php echo esc_attr($tipo->slug); ?>">
                                                <?php echo esc_html($tipo->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Botão Buscar -->
                            <div class="search-field field-submit">
                                <button type="submit" class="button search-button">
                                    <i class="fa fa-search"></i> <?php echo esc_html($atts['button_text']); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab Aluguel -->
                <div id="tab-aluguel" class="search-tab-pane" role="tabpanel">
                    <form class="listeo-search-form" action="<?php echo esc_url($atts['action_url']); ?>" method="get">
                        <input type="hidden" name="tax-tipo_de_negocio" value="aluguel">

                        <div class="search-fields-row">
                            <!-- Estado -->
                            <div class="search-field">
                                <label>Estado</label>
                                <select name="tax-estado" id="aluguel-estado" class="selectpicker" data-placeholder="Estado" title="Estado">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                                        <?php foreach ($estados as $estado) : ?>
                                            <option value="<?php echo esc_attr($estado->slug); ?>" data-term-id="<?php echo esc_attr($estado->term_id); ?>">
                                                <?php echo esc_html($estado->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Cidade -->
                            <div class="search-field">
                                <label>Cidade</label>
                                <select name="tax-cidade" id="aluguel-cidade" class="selectpicker" data-placeholder="Cidade" title="Cidade" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Bairro -->
                            <div class="search-field">
                                <label>Bairro</label>
                                <select name="tax-bairro" id="aluguel-bairro" class="selectpicker" data-placeholder="Bairro" title="Bairro" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Tipo de Imóvel -->
                            <div class="search-field">
                                <label>Tipo de Imóvel</label>
                                <select name="tax-tipo_de_imovel" class="selectpicker" data-placeholder="Tipo de Imóvel" title="Tipo">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($tipos_imovel) && !empty($tipos_imovel)) : ?>
                                        <?php foreach ($tipos_imovel as $tipo) : ?>
                                            <option value="<?php echo esc_attr($tipo->slug); ?>">
                                                <?php echo esc_html($tipo->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Botão Buscar -->
                            <div class="search-field field-submit">
                                <button type="submit" class="button search-button">
                                    <i class="fa fa-search"></i> <?php echo esc_html($atts['button_text']); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
        .listeo-search-tabs-wrapper {
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }

        /* Tabs Navigation */
        .search-tabs-nav {
            display: flex;
            list-style: none;
            margin: 0 0 20px 0;
            padding: 0;
            border-bottom: 2px solid #e0e0e0;
        }

        .search-tabs-nav .tab-nav-item {
            margin: 0;
            padding: 0;
            flex: 1;
            text-align: center;
        }

        .search-tabs-nav .tab-nav-item a {
            display: block;
            padding: 15px 30px;
            text-decoration: none;
            color: #666;
            font-weight: 600;
            font-size: 16px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .search-tabs-nav .tab-nav-item.active a,
        .search-tabs-nav .tab-nav-item a:hover {
            color: #f91942;
            border-bottom-color: #f91942;
        }

        /* Tab Content */
        .search-tabs-content {
            padding: 10px 0 0 0;
        }

        .search-tab-pane {
            display: none;
        }

        .search-tab-pane.active {
            display: block;
        }

        /* Form Fields */
        .search-fields-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .search-field {
            flex: 1;
            min-width: 150px;
        }

        .search-field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .field-submit {
            flex: 0 0 auto;
            min-width: 150px;
        }

        .field-submit label {
            opacity: 0;
            margin-bottom: 8px;
            height: 20px;
        }

        .search-button {
            width: 100%;
            height: 50px;
            background: #f91942;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background: #d91739;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 25, 66, 0.3);
        }

        .search-button i {
            margin-right: 8px;
        }

        .search-field .bootstrap-select {
            width: 100% !important;
        }

        .search-field .bootstrap-select .btn {
            height: 50px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: white;
            text-align: left;
            padding: 0 15px;
        }

        .search-field .bootstrap-select .btn:focus {
            outline: none;
            border-color: #f91942;
            box-shadow: 0 0 0 3px rgba(249, 25, 66, 0.1);
        }

        @media (max-width: 768px) {
            .search-fields-row {
                flex-direction: column;
            }
            .search-field,
            .field-submit {
                width: 100%;
                min-width: 100%;
            }
            .search-tabs-nav {
                flex-direction: column;
            }
            .search-tabs-nav .tab-nav-item {
                border-bottom: 1px solid #e0e0e0;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Initialize selectpicker
            if (typeof $.fn.selectpicker !== 'undefined') {
                $('.listeo-search-tabs-wrapper select').selectpicker();
            }

            // Tab switching
            $('.search-tabs-nav a').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');

                // Update nav
                $('.search-tabs-nav .tab-nav-item').removeClass('active');
                $(this).parent().addClass('active');

                // Update content
                $('.search-tab-pane').removeClass('active');
                $(target).addClass('active');
            });

            // Bind Estado changes for all tabs
            $('#tudo-estado, #venda-estado, #aluguel-estado').on('change', function() {
                var prefix = $(this).attr('id').split('-')[0];
                handleEstadoChangeNew($(this), '#' + prefix + '-cidade', '#' + prefix + '-bairro');
            });

            // Bind Cidade changes for all tabs
            $('#tudo-cidade, #venda-cidade, #aluguel-cidade').on('change', function() {
                var prefix = $(this).attr('id').split('-')[0];
                handleCidadeChangeNew($(this), '#' + prefix + '-bairro');
            });

            function handleEstadoChangeNew($estadoSelect, cidadeSelector, bairroSelector) {
                var estadoSlug = $estadoSelect.val();
                var estadoId = $estadoSelect.find(':selected').data('term-id');
                var $cidadeSelect = $(cidadeSelector);
                var $bairroSelect = $(bairroSelector);

                console.log('handleEstadoChangeNew called');
                console.log('Estado Slug:', estadoSlug);
                console.log('Estado ID:', estadoId);
                console.log('Cidade Selector:', cidadeSelector);
                console.log('listeoLocationData:', listeoLocationData);

                // Reset cidade and bairro
                $cidadeSelect.html('<option value="">Selecione</option>').prop('disabled', true).selectpicker('refresh');
                $bairroSelect.html('<option value="">Selecione</option>').prop('disabled', true).selectpicker('refresh');

                if (!estadoSlug || !estadoId) {
                    console.log('Estado slug or ID is empty, aborting');
                    return;
                }

                // Load cidades
                $cidadeSelect.html('<option value="">Carregando...</option>').selectpicker('refresh');

                console.log('Making AJAX call to:', listeoLocationData.ajaxurl);
                $.ajax({
                    url: listeoLocationData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_cidades_by_estado',
                        estado_id: estadoId,
                        nonce: listeoLocationData.nonce
                    },
                    success: function(response) {
                        console.log('AJAX Success Response:', response);
                        if (response.success && response.data) {
                            console.log('Found', response.data.length, 'cidades');
                            var html = '<option value="">Selecione</option>';
                            $.each(response.data, function(index, cidade) {
                                html += '<option value="' + cidade.slug + '" data-term-id="' + cidade.id + '">' + cidade.name + '</option>';
                            });
                            $cidadeSelect.html(html).prop('disabled', false).selectpicker('refresh');
                        } else {
                            console.log('No data in response or not successful');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        console.error('Response:', xhr.responseText);
                        $cidadeSelect.html('<option value="">Erro ao carregar</option>').selectpicker('refresh');
                    }
                });
            }

            function handleCidadeChangeNew($cidadeSelect, bairroSelector) {
                var cidadeSlug = $cidadeSelect.val();
                var cidadeId = $cidadeSelect.find(':selected').data('term-id');
                var $bairroSelect = $(bairroSelector);

                // Reset bairro
                $bairroSelect.html('<option value="">Selecione</option>').prop('disabled', true).selectpicker('refresh');

                if (!cidadeSlug || !cidadeId) {
                    return;
                }

                // Load bairros
                $bairroSelect.html('<option value="">Carregando...</option>').selectpicker('refresh');

                $.ajax({
                    url: listeoLocationData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_bairros_by_cidade',
                        cidade_id: cidadeId,
                        nonce: listeoLocationData.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var html = '<option value="">Selecione</option>';
                            $.each(response.data, function(index, bairro) {
                                html += '<option value="' + bairro.slug + '" data-term-id="' + bairro.id + '">' + bairro.name + '</option>';
                            });
                            $bairroSelect.html(html).prop('disabled', false).selectpicker('refresh');
                        }
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Search form with 3 tabs VERTICAL layout (Tudo/Venda/Aluguel)
     * Usage: [listeo_search_form_tabs_vertical]
     */
    public function search_form_tabs_vertical_shortcode($atts) {
        $atts = shortcode_atts(array(
            'action_url' => home_url('/listings/'),
            'button_text' => __('Buscar', 'listeo-location-taxonomies'),
        ), $atts);

        $estados = get_terms(array(
            'taxonomy' => 'estado',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $tipos_imovel = get_terms(array(
            'taxonomy' => 'tipo_de_imovel',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        ob_start();
        ?>
        <div class="listeo-search-tabs-vertical-wrapper">
            <!-- Tabs Navigation -->
            <ul class="search-tabs-nav-vertical" role="tablist">
                <li class="tab-nav-item active">
                    <a href="#vtab-tudo" role="tab" aria-selected="true">Tudo</a>
                </li>
                <li class="tab-nav-item">
                    <a href="#vtab-venda" role="tab" aria-selected="false">Venda</a>
                </li>
                <li class="tab-nav-item">
                    <a href="#vtab-aluguel" role="tab" aria-selected="false">Aluguel</a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="search-tabs-content-vertical">
                <!-- Tab Tudo -->
                <div id="vtab-tudo" class="search-tab-pane-vertical active" role="tabpanel">
                    <form class="listeo-search-form-vertical" action="<?php echo esc_url($atts['action_url']); ?>" method="get">
                        <div class="search-fields-vertical">
                            <!-- Estado -->
                            <div class="search-field-vertical">
                                <label>Estado</label>
                                <select name="tax-estado" id="vtudo-estado" class="selectpicker" data-placeholder="Estado" title="Estado">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                                        <?php foreach ($estados as $estado) : ?>
                                            <option value="<?php echo esc_attr($estado->slug); ?>" data-term-id="<?php echo esc_attr($estado->term_id); ?>">
                                                <?php echo esc_html($estado->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Cidade -->
                            <div class="search-field-vertical">
                                <label>Cidade</label>
                                <select name="tax-cidade" id="vtudo-cidade" class="selectpicker" data-placeholder="Cidade" title="Cidade" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Bairro -->
                            <div class="search-field-vertical">
                                <label>Bairro</label>
                                <select name="tax-bairro" id="vtudo-bairro" class="selectpicker" data-placeholder="Bairro" title="Bairro" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Tipo de Imóvel -->
                            <div class="search-field-vertical">
                                <label>Tipo de Imóvel</label>
                                <select name="tax-tipo_de_imovel" class="selectpicker" data-placeholder="Tipo de Imóvel" title="Tipo">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($tipos_imovel) && !empty($tipos_imovel)) : ?>
                                        <?php foreach ($tipos_imovel as $tipo) : ?>
                                            <option value="<?php echo esc_attr($tipo->slug); ?>">
                                                <?php echo esc_html($tipo->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Botão Buscar -->
                            <div class="search-field-vertical">
                                <button type="submit" class="button search-button-vertical">
                                    <i class="fa fa-search"></i> <?php echo esc_html($atts['button_text']); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab Venda -->
                <div id="vtab-venda" class="search-tab-pane-vertical" role="tabpanel">
                    <form class="listeo-search-form-vertical" action="<?php echo esc_url($atts['action_url']); ?>" method="get">
                        <input type="hidden" name="tax-tipo_de_negocio" value="venda">

                        <div class="search-fields-vertical">
                            <!-- Estado -->
                            <div class="search-field-vertical">
                                <label>Estado</label>
                                <select name="tax-estado" id="vvenda-estado" class="selectpicker" data-placeholder="Estado" title="Estado">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                                        <?php foreach ($estados as $estado) : ?>
                                            <option value="<?php echo esc_attr($estado->slug); ?>" data-term-id="<?php echo esc_attr($estado->term_id); ?>">
                                                <?php echo esc_html($estado->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Cidade -->
                            <div class="search-field-vertical">
                                <label>Cidade</label>
                                <select name="tax-cidade" id="vvenda-cidade" class="selectpicker" data-placeholder="Cidade" title="Cidade" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Bairro -->
                            <div class="search-field-vertical">
                                <label>Bairro</label>
                                <select name="tax-bairro" id="vvenda-bairro" class="selectpicker" data-placeholder="Bairro" title="Bairro" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Tipo de Imóvel -->
                            <div class="search-field-vertical">
                                <label>Tipo de Imóvel</label>
                                <select name="tax-tipo_de_imovel" class="selectpicker" data-placeholder="Tipo de Imóvel" title="Tipo">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($tipos_imovel) && !empty($tipos_imovel)) : ?>
                                        <?php foreach ($tipos_imovel as $tipo) : ?>
                                            <option value="<?php echo esc_attr($tipo->slug); ?>">
                                                <?php echo esc_html($tipo->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Botão Buscar -->
                            <div class="search-field-vertical">
                                <button type="submit" class="button search-button-vertical">
                                    <i class="fa fa-search"></i> <?php echo esc_html($atts['button_text']); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab Aluguel -->
                <div id="vtab-aluguel" class="search-tab-pane-vertical" role="tabpanel">
                    <form class="listeo-search-form-vertical" action="<?php echo esc_url($atts['action_url']); ?>" method="get">
                        <input type="hidden" name="tax-tipo_de_negocio" value="aluguel">

                        <div class="search-fields-vertical">
                            <!-- Estado -->
                            <div class="search-field-vertical">
                                <label>Estado</label>
                                <select name="tax-estado" id="valuguel-estado" class="selectpicker" data-placeholder="Estado" title="Estado">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                                        <?php foreach ($estados as $estado) : ?>
                                            <option value="<?php echo esc_attr($estado->slug); ?>" data-term-id="<?php echo esc_attr($estado->term_id); ?>">
                                                <?php echo esc_html($estado->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Cidade -->
                            <div class="search-field-vertical">
                                <label>Cidade</label>
                                <select name="tax-cidade" id="valuguel-cidade" class="selectpicker" data-placeholder="Cidade" title="Cidade" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Bairro -->
                            <div class="search-field-vertical">
                                <label>Bairro</label>
                                <select name="tax-bairro" id="valuguel-bairro" class="selectpicker" data-placeholder="Bairro" title="Bairro" disabled>
                                    <option value="">Selecione</option>
                                </select>
                            </div>

                            <!-- Tipo de Imóvel -->
                            <div class="search-field-vertical">
                                <label>Tipo de Imóvel</label>
                                <select name="tax-tipo_de_imovel" class="selectpicker" data-placeholder="Tipo de Imóvel" title="Tipo">
                                    <option value="">Selecione</option>
                                    <?php if (!is_wp_error($tipos_imovel) && !empty($tipos_imovel)) : ?>
                                        <?php foreach ($tipos_imovel as $tipo) : ?>
                                            <option value="<?php echo esc_attr($tipo->slug); ?>">
                                                <?php echo esc_html($tipo->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Botão Buscar -->
                            <div class="search-field-vertical">
                                <button type="submit" class="button search-button-vertical">
                                    <i class="fa fa-search"></i> <?php echo esc_html($atts['button_text']); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
        .listeo-search-tabs-vertical-wrapper {
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }

        /* Tabs Navigation - Vertical */
        .search-tabs-nav-vertical {
            display: flex;
            flex-direction: column;
            list-style: none;
            margin: 0 0 20px 0;
            padding: 0;
            gap: 5px;
        }

        .search-tabs-nav-vertical .tab-nav-item {
            margin: 0;
            padding: 0;
        }

        .search-tabs-nav-vertical .tab-nav-item a {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: #666;
            font-weight: 600;
            font-size: 15px;
            border-left: 3px solid transparent;
            background: #f5f5f5;
            transition: all 0.3s ease;
            border-radius: 4px;
        }

        .search-tabs-nav-vertical .tab-nav-item.active a,
        .search-tabs-nav-vertical .tab-nav-item a:hover {
            color: #f91942;
            border-left-color: #f91942;
            background: #fff;
        }

        /* Tab Content */
        .search-tabs-content-vertical {
            padding: 0;
        }

        .search-tab-pane-vertical {
            display: none;
        }

        .search-tab-pane-vertical.active {
            display: block;
        }

        /* Form Fields - Vertical */
        .search-fields-vertical {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .search-field-vertical {
            width: 100%;
        }

        .search-field-vertical label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .search-button-vertical {
            width: 100%;
            height: 50px;
            background: #f91942;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-button-vertical:hover {
            background: #d91739;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 25, 66, 0.3);
        }

        .search-button-vertical i {
            margin-right: 8px;
        }

        .search-field-vertical .bootstrap-select {
            width: 100% !important;
        }

        .search-field-vertical .bootstrap-select .btn {
            height: 50px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: white;
            text-align: left;
            padding: 0 15px;
            width: 100%;
        }

        .search-field-vertical .bootstrap-select .btn:focus {
            outline: none;
            border-color: #f91942;
            box-shadow: 0 0 0 3px rgba(249, 25, 66, 0.1);
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Initialize selectpicker
            if (typeof $.fn.selectpicker !== 'undefined') {
                $('.listeo-search-tabs-vertical-wrapper select').selectpicker();
            }

            // Tab switching
            $('.search-tabs-nav-vertical a').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');

                // Update nav
                $('.search-tabs-nav-vertical .tab-nav-item').removeClass('active');
                $(this).parent().addClass('active');

                // Update content
                $('.search-tab-pane-vertical').removeClass('active');
                $(target).addClass('active');
            });

            // Bind Estado changes for all vertical tabs
            $('#vtudo-estado, #vvenda-estado, #valuguel-estado').on('change', function() {
                var prefix = $(this).attr('id').substring(1).split('-')[0];
                handleEstadoChangeVertical($(this), '#v' + prefix + '-cidade', '#v' + prefix + '-bairro');
            });

            // Bind Cidade changes for all vertical tabs
            $('#vtudo-cidade, #vvenda-cidade, #valuguel-cidade').on('change', function() {
                var prefix = $(this).attr('id').substring(1).split('-')[0];
                handleCidadeChangeVertical($(this), '#v' + prefix + '-bairro');
            });

            function handleEstadoChangeVertical($estadoSelect, cidadeSelector, bairroSelector) {
                var estadoSlug = $estadoSelect.val();
                var estadoId = $estadoSelect.find(':selected').data('term-id');
                var $cidadeSelect = $(cidadeSelector);
                var $bairroSelect = $(bairroSelector);

                // Reset cidade and bairro
                $cidadeSelect.html('<option value="">Selecione</option>').prop('disabled', true).selectpicker('refresh');
                $bairroSelect.html('<option value="">Selecione</option>').prop('disabled', true).selectpicker('refresh');

                if (!estadoSlug || !estadoId) {
                    return;
                }

                // Load cidades
                $cidadeSelect.html('<option value="">Carregando...</option>').selectpicker('refresh');

                $.ajax({
                    url: listeoLocationData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_cidades_by_estado',
                        estado_id: estadoId,
                        nonce: listeoLocationData.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var html = '<option value="">Selecione</option>';
                            $.each(response.data, function(index, cidade) {
                                html += '<option value="' + cidade.slug + '" data-term-id="' + cidade.id + '">' + cidade.name + '</option>';
                            });
                            $cidadeSelect.html(html).prop('disabled', false).selectpicker('refresh');
                        }
                    }
                });
            }

            function handleCidadeChangeVertical($cidadeSelect, bairroSelector) {
                var cidadeSlug = $cidadeSelect.val();
                var cidadeId = $cidadeSelect.find(':selected').data('term-id');
                var $bairroSelect = $(bairroSelector);

                // Reset bairro
                $bairroSelect.html('<option value="">Selecione</option>').prop('disabled', true).selectpicker('refresh');

                if (!cidadeSlug || !cidadeId) {
                    return;
                }

                // Load bairros
                $bairroSelect.html('<option value="">Carregando...</option>').selectpicker('refresh');

                $.ajax({
                    url: listeoLocationData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_bairros_by_cidade',
                        cidade_id: cidadeId,
                        nonce: listeoLocationData.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var html = '<option value="">Selecione</option>';
                            $.each(response.data, function(index, bairro) {
                                html += '<option value="' + bairro.slug + '" data-term-id="' + bairro.id + '">' + bairro.name + '</option>';
                            });
                            $bairroSelect.html(html).prop('disabled', false).selectpicker('refresh');
                        }
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Estado field shortcode
     */
    public function estado_field_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Selecione um Estado', 'listeo-location-taxonomies'),
        ), $atts);

        return $this->get_estado_field($atts['placeholder']);
    }

    /**
     * Cidade field shortcode
     */
    public function cidade_field_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Selecione uma Cidade', 'listeo-location-taxonomies'),
        ), $atts);

        return $this->get_cidade_field($atts['placeholder']);
    }

    /**
     * Bairro field shortcode
     */
    public function bairro_field_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Selecione um Bairro', 'listeo-location-taxonomies'),
        ), $atts);

        return $this->get_bairro_field($atts['placeholder']);
    }

    /**
     * Listing Category field shortcode
     */
    public function listing_category_field_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Tipo de Imóvel', 'listeo-location-taxonomies'),
        ), $atts);

        return $this->get_listing_category_field($atts['placeholder']);
    }

    /**
     * Tipo de Negócio field shortcode
     */
    public function tipo_de_negocio_field_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Tipo de Negócio', 'listeo-location-taxonomies'),
        ), $atts);

        return $this->get_tipo_de_negocio_field($atts['placeholder']);
    }

    /**
     * Tipo de Imóvel field shortcode
     */
    public function tipo_de_imovel_field_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Tipo de Imóvel', 'listeo-location-taxonomies'),
        ), $atts);

        return $this->get_tipo_de_imovel_field($atts['placeholder']);
    }

    /**
     * Get Estado field HTML
     */
    private function get_estado_field($placeholder = '') {
        if (empty($placeholder)) {
            $placeholder = __('Selecione um Estado', 'listeo-location-taxonomies');
        }

        $estados = get_terms(array(
            'taxonomy' => 'estado',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $selected_estado = get_query_var('tax-estado');

        ob_start();
        ?>
        <select name="tax-estado" id="estado-filter" class="listeo-location-filter selectpicker" data-placeholder="<?php echo esc_attr($placeholder); ?>">
            <option value=""><?php echo esc_html($placeholder); ?></option>
            <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                <?php foreach ($estados as $estado) : ?>
                    <option value="<?php echo esc_attr($estado->slug); ?>" data-term-id="<?php echo esc_attr($estado->term_id); ?>" <?php selected($selected_estado, $estado->slug); ?>>
                        <?php echo esc_html($estado->name); ?> (<?php echo $estado->count; ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Get Cidade field HTML
     */
    private function get_cidade_field($placeholder = '') {
        if (empty($placeholder)) {
            $placeholder = __('Selecione uma Cidade', 'listeo-location-taxonomies');
        }

        $selected_cidade = get_query_var('tax-cidade');
        $selected_estado = get_query_var('tax-estado');

        // Get cidades based on selected estado
        $cidades_args = array(
            'taxonomy' => 'cidade',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        );

        // If estado is selected, filter cidades
        if ($selected_estado) {
            $estado_term = get_term_by('slug', $selected_estado, 'estado');
            if ($estado_term) {
                $cidades_args['meta_query'] = array(
                    array(
                        'key' => 'parent_estado',
                        'value' => $estado_term->term_id,
                        'compare' => '='
                    )
                );
            }
        }

        $cidades = get_terms($cidades_args);

        ob_start();
        ?>
        <select name="tax-cidade" id="cidade-filter" class="listeo-location-filter selectpicker" data-placeholder="<?php echo esc_attr($placeholder); ?>" <?php echo empty($selected_estado) ? 'disabled' : ''; ?>>
            <option value=""><?php echo esc_html($placeholder); ?></option>
            <?php if (!is_wp_error($cidades) && !empty($cidades)) : ?>
                <?php foreach ($cidades as $cidade) : ?>
                    <option value="<?php echo esc_attr($cidade->slug); ?>" data-term-id="<?php echo esc_attr($cidade->term_id); ?>" <?php selected($selected_cidade, $cidade->slug); ?>>
                        <?php echo esc_html($cidade->name); ?> (<?php echo $cidade->count; ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Get Bairro field HTML
     */
    private function get_bairro_field($placeholder = '') {
        if (empty($placeholder)) {
            $placeholder = __('Selecione um Bairro', 'listeo-location-taxonomies');
        }

        $selected_bairro = get_query_var('tax-bairro');
        $selected_cidade = get_query_var('tax-cidade');

        // Get bairros based on selected cidade
        $bairros_args = array(
            'taxonomy' => 'bairro',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        );

        // If cidade is selected, filter bairros
        if ($selected_cidade) {
            $cidade_term = get_term_by('slug', $selected_cidade, 'cidade');
            if ($cidade_term) {
                $bairros_args['meta_query'] = array(
                    array(
                        'key' => 'parent_cidade',
                        'value' => $cidade_term->term_id,
                        'compare' => '='
                    )
                );
            }
        }

        $bairros = get_terms($bairros_args);

        ob_start();
        ?>
        <select name="tax-bairro" id="bairro-filter" class="listeo-location-filter selectpicker" data-placeholder="<?php echo esc_attr($placeholder); ?>" <?php echo empty($selected_cidade) ? 'disabled' : ''; ?>>
            <option value=""><?php echo esc_html($placeholder); ?></option>
            <?php if (!is_wp_error($bairros) && !empty($bairros)) : ?>
                <?php foreach ($bairros as $bairro) : ?>
                    <option value="<?php echo esc_attr($bairro->slug); ?>" data-term-id="<?php echo esc_attr($bairro->term_id); ?>" <?php selected($selected_bairro, $bairro->slug); ?>>
                        <?php echo esc_html($bairro->name); ?> (<?php echo $bairro->count; ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Get Listing Category field HTML
     */
    private function get_listing_category_field($placeholder = '') {
        if (empty($placeholder)) {
            $placeholder = __('Tipo de Imóvel', 'listeo-location-taxonomies');
        }

        $categories = get_terms(array(
            'taxonomy' => 'listing_category',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $selected_category = get_query_var('tax-listing_category');

        ob_start();
        ?>
        <select name="tax-listing_category" id="listing-category-filter" class="listeo-location-filter selectpicker" data-placeholder="<?php echo esc_attr($placeholder); ?>">
            <option value=""><?php echo esc_html($placeholder); ?></option>
            <?php if (!is_wp_error($categories) && !empty($categories)) : ?>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?php echo esc_attr($category->slug); ?>" data-term-id="<?php echo esc_attr($category->term_id); ?>" <?php selected($selected_category, $category->slug); ?>>
                        <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Get Tipo de Negócio field HTML
     */
    private function get_tipo_de_negocio_field($placeholder = '') {
        if (empty($placeholder)) {
            $placeholder = __('Tipo de Negócio', 'listeo-location-taxonomies');
        }

        $tipos = get_terms(array(
            'taxonomy' => 'tipo_de_negocio',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $selected_tipo = get_query_var('tax-tipo_de_negocio');

        ob_start();
        ?>
        <select name="tax-tipo_de_negocio" id="tipo-negocio-filter" class="listeo-location-filter selectpicker" data-placeholder="<?php echo esc_attr($placeholder); ?>">
            <option value=""><?php echo esc_html($placeholder); ?></option>
            <?php if (!is_wp_error($tipos) && !empty($tipos)) : ?>
                <?php foreach ($tipos as $tipo) : ?>
                    <option value="<?php echo esc_attr($tipo->slug); ?>" data-term-id="<?php echo esc_attr($tipo->term_id); ?>" <?php selected($selected_tipo, $tipo->slug); ?>>
                        <?php echo esc_html($tipo->name); ?> (<?php echo $tipo->count; ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Get Tipo de Imóvel field HTML
     */
    private function get_tipo_de_imovel_field($placeholder = '') {
        if (empty($placeholder)) {
            $placeholder = __('Tipo de Imóvel', 'listeo-location-taxonomies');
        }

        $tipos = get_terms(array(
            'taxonomy' => 'tipo_de_imovel',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $selected_tipo = get_query_var('tax-tipo_de_imovel');

        ob_start();
        ?>
        <select name="tax-tipo_de_imovel" id="tipo-imovel-filter" class="listeo-location-filter selectpicker" data-placeholder="<?php echo esc_attr($placeholder); ?>">
            <option value=""><?php echo esc_html($placeholder); ?></option>
            <?php if (!is_wp_error($tipos) && !empty($tipos)) : ?>
                <?php foreach ($tipos as $tipo) : ?>
                    <option value="<?php echo esc_attr($tipo->slug); ?>" data-term-id="<?php echo esc_attr($tipo->term_id); ?>" <?php selected($selected_tipo, $tipo->slug); ?>>
                        <?php echo esc_html($tipo->name); ?> (<?php echo $tipo->count; ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Add location fields to Listeo search form
     * This hook may or may not exist in the theme, so it's optional
     */
    public function add_location_fields_to_search_form($fields) {
        // Add our location fields to the search form
        $location_fields = $this->location_search_shortcode(array());

        if (is_array($fields)) {
            $fields['location_taxonomies'] = $location_fields;
        }

        return $fields;
    }
}

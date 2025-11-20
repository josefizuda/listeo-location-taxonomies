<?php
/**
 * Query Integration
 *
 * @package Listeo_Location_Taxonomies
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for integrating location taxonomies with WP_Query
 */
class Listeo_Location_Query_Integration {

    /**
     * The single instance of the class
     * @var Listeo_Location_Query_Integration
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
        // Register query vars
        add_filter('query_vars', array($this, 'add_query_vars'));

        // Modify main query for location filters
        add_action('pre_get_posts', array($this, 'modify_query_for_location_filters'));

        // Add data attributes for AJAX filters (compatible with Listeo)
        add_filter('listeo/listings-list-data-tags', array($this, 'add_location_data_tags'), 10, 2);
    }

    /**
     * Register custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'tax-estado';
        $vars[] = 'tax-cidade';
        $vars[] = 'tax-bairro';
        $vars[] = 'estado';
        $vars[] = 'cidade';
        $vars[] = 'bairro';

        return $vars;
    }

    /**
     * Modify query to handle location filters
     */
    public function modify_query_for_location_filters($query) {
        // Only modify the main query on the frontend
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        // Get location filter values
        $estado = $this->get_filter_value('estado');
        $cidade = $this->get_filter_value('cidade');
        $bairro = $this->get_filter_value('bairro');

        // If no location filters are set, return
        if (!$estado && !$cidade && !$bairro) {
            return;
        }

        // Get existing tax_query or create new one
        $tax_query = $query->get('tax_query');
        if (!is_array($tax_query)) {
            $tax_query = array();
        }

        // Ensure we have a relation set
        if (!isset($tax_query['relation'])) {
            $tax_query['relation'] = 'AND';
        }

        // Add estado filter
        if ($estado) {
            $tax_query[] = array(
                'taxonomy' => 'estado',
                'field'    => 'slug',
                'terms'    => is_array($estado) ? $estado : array($estado),
                'operator' => 'IN'
            );
        }

        // Add cidade filter
        if ($cidade) {
            $tax_query[] = array(
                'taxonomy' => 'cidade',
                'field'    => 'slug',
                'terms'    => is_array($cidade) ? $cidade : array($cidade),
                'operator' => 'IN'
            );
        }

        // Add bairro filter
        if ($bairro) {
            $tax_query[] = array(
                'taxonomy' => 'bairro',
                'field'    => 'slug',
                'terms'    => is_array($bairro) ? $bairro : array($bairro),
                'operator' => 'IN'
            );
        }

        // Set the modified tax_query
        $query->set('tax_query', $tax_query);
    }

    /**
     * Get filter value from query vars or $_GET
     * Supports both formats: tax-estado and estado
     */
    private function get_filter_value($taxonomy) {
        // Try with tax- prefix first (Listeo format)
        $value = get_query_var('tax-' . $taxonomy);

        // If not found, try without prefix
        if (empty($value)) {
            $value = get_query_var($taxonomy);
        }

        // If still not found, check $_GET directly
        if (empty($value)) {
            if (isset($_GET['tax-' . $taxonomy])) {
                $value = sanitize_text_field($_GET['tax-' . $taxonomy]);
            } elseif (isset($_GET[$taxonomy])) {
                $value = sanitize_text_field($_GET[$taxonomy]);
            }
        }

        // Handle array values
        if (is_array($value)) {
            $value = array_map('sanitize_text_field', $value);
        } elseif (is_string($value)) {
            $value = sanitize_text_field($value);
        }

        return $value;
    }

    /**
     * Add location data tags for AJAX filtering (Listeo compatibility)
     * This filter is used by Listeo theme to add data attributes to listing containers
     */
    public function add_location_data_tags($search_data, $post_id = null) {
        $estado = $this->get_filter_value('estado');
        $cidade = $this->get_filter_value('cidade');
        $bairro = $this->get_filter_value('bairro');

        if ($estado) {
            $search_data .= ' data-estado="' . esc_attr(is_array($estado) ? implode(',', $estado) : $estado) . '" ';
        }

        if ($cidade) {
            $search_data .= ' data-cidade="' . esc_attr(is_array($cidade) ? implode(',', $cidade) : $cidade) . '" ';
        }

        if ($bairro) {
            $search_data .= ' data-bairro="' . esc_attr(is_array($bairro) ? implode(',', $bairro) : $bairro) . '" ';
        }

        return $search_data;
    }

    /**
     * Get listings by location
     * Helper function to query listings by location taxonomies
     *
     * @param array $args Query arguments
     * @return WP_Query
     */
    public static function get_listings_by_location($args = array()) {
        $defaults = array(
            'post_type' => 'listing',
            'posts_per_page' => 10,
            'estado' => '',
            'cidade' => '',
            'bairro' => '',
        );

        $args = wp_parse_args($args, $defaults);

        // Extract location parameters
        $estado = $args['estado'];
        $cidade = $args['cidade'];
        $bairro = $args['bairro'];

        // Remove from args as they're not WP_Query parameters
        unset($args['estado'], $args['cidade'], $args['bairro']);

        // Build tax_query
        $tax_query = array('relation' => 'AND');

        if (!empty($estado)) {
            $tax_query[] = array(
                'taxonomy' => 'estado',
                'field'    => 'slug',
                'terms'    => $estado,
            );
        }

        if (!empty($cidade)) {
            $tax_query[] = array(
                'taxonomy' => 'cidade',
                'field'    => 'slug',
                'terms'    => $cidade,
            );
        }

        if (!empty($bairro)) {
            $tax_query[] = array(
                'taxonomy' => 'bairro',
                'field'    => 'slug',
                'terms'    => $bairro,
            );
        }

        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }

        return new WP_Query($args);
    }

    /**
     * Get location breadcrumb
     * Returns hierarchical location string (Estado > Cidade > Bairro)
     *
     * @param int $post_id Post ID
     * @return string
     */
    public static function get_location_breadcrumb($post_id) {
        $breadcrumb = array();

        // Get estado
        $estados = wp_get_post_terms($post_id, 'estado');
        if (!is_wp_error($estados) && !empty($estados)) {
            $breadcrumb[] = $estados[0]->name;
        }

        // Get cidade
        $cidades = wp_get_post_terms($post_id, 'cidade');
        if (!is_wp_error($cidades) && !empty($cidades)) {
            $breadcrumb[] = $cidades[0]->name;
        }

        // Get bairro
        $bairros = wp_get_post_terms($post_id, 'bairro');
        if (!is_wp_error($bairros) && !empty($bairros)) {
            $breadcrumb[] = $bairros[0]->name;
        }

        return implode(' > ', $breadcrumb);
    }

    /**
     * Check if listing matches location filters
     * Useful for AJAX filtering
     *
     * @param int $post_id Post ID
     * @param string $estado Estado slug
     * @param string $cidade Cidade slug
     * @param string $bairro Bairro slug
     * @return bool
     */
    public static function listing_matches_location($post_id, $estado = '', $cidade = '', $bairro = '') {
        // Check estado
        if (!empty($estado)) {
            if (!has_term($estado, 'estado', $post_id)) {
                return false;
            }
        }

        // Check cidade
        if (!empty($cidade)) {
            if (!has_term($cidade, 'cidade', $post_id)) {
                return false;
            }
        }

        // Check bairro
        if (!empty($bairro)) {
            if (!has_term($bairro, 'bairro', $post_id)) {
                return false;
            }
        }

        return true;
    }
}

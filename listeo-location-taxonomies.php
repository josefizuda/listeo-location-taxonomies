<?php
/**
 * Plugin Name: Listeo - Taxonomias de Localização
 * Plugin URI: https://github.com/fastwp/listeo-location-taxonomies
 * Description: Adiciona taxonomias hierárquicas de Estado, Cidade e Bairro para o tema Listeo, com integração completa ao sistema de filtros e busca hierárquica.
 * Version: 2.0.0
 * Author: Fastwp
 * Author URI: https://fastwp.com.br
 * Text Domain: listeo-location-taxonomies
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
final class Listeo_Location_Taxonomies {

    /**
     * Plugin version
     * @var string
     */
    const VERSION = '2.0.0';

    /**
     * The single instance of the class
     * @var Listeo_Location_Taxonomies
     */
    protected static $_instance = null;

    /**
     * Main Instance
     * Ensures only one instance of the plugin is loaded or can be loaded
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
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        define('LISTEO_LOC_TAX_VERSION', self::VERSION);
        define('LISTEO_LOC_TAX_PLUGIN_FILE', __FILE__);
        define('LISTEO_LOC_TAX_PLUGIN_PATH', plugin_dir_path(__FILE__));
        define('LISTEO_LOC_TAX_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once LISTEO_LOC_TAX_PLUGIN_PATH . 'includes/class-taxonomies.php';
        require_once LISTEO_LOC_TAX_PLUGIN_PATH . 'includes/class-search-fields.php';
        require_once LISTEO_LOC_TAX_PLUGIN_PATH . 'includes/class-query-integration.php';
        require_once LISTEO_LOC_TAX_PLUGIN_PATH . 'includes/class-admin.php';
    }

    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        // Initialize plugin components
        add_action('plugins_loaded', array($this, 'init'), 0);

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load plugin textdomain
        load_plugin_textdomain('listeo-location-taxonomies', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Initialize components
        Listeo_Location_Taxonomies_Register::instance();
        Listeo_Location_Search_Fields::instance();
        Listeo_Location_Query_Integration::instance();
        Listeo_Location_Admin::instance();

        // Trigger action after plugin is fully loaded
        do_action('listeo_location_taxonomies_loaded');
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue CSS
        wp_enqueue_style(
            'listeo-location-filters',
            LISTEO_LOC_TAX_PLUGIN_URL . 'assets/css/location-filters.css',
            array(),
            LISTEO_LOC_TAX_VERSION
        );

        // Enqueue JS
        wp_enqueue_script(
            'listeo-location-filters',
            LISTEO_LOC_TAX_PLUGIN_URL . 'assets/js/location-filters.js',
            array('jquery'),
            LISTEO_LOC_TAX_VERSION,
            true
        );

        // Localize script
        wp_localize_script('listeo-location-filters', 'listeoLocationData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('listeo_location_nonce'),
            'strings' => array(
                'select_state' => __('Selecione um Estado', 'listeo-location-taxonomies'),
                'select_city' => __('Selecione uma Cidade', 'listeo-location-taxonomies'),
                'select_neighborhood' => __('Selecione um Bairro', 'listeo-location-taxonomies'),
                'loading' => __('Carregando...', 'listeo-location-taxonomies'),
            )
        ));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on taxonomy edit pages
        if ('edit-tags.php' === $hook || 'term.php' === $hook) {
            wp_enqueue_style(
                'listeo-location-admin',
                LISTEO_LOC_TAX_PLUGIN_URL . 'assets/css/location-admin.css',
                array(),
                LISTEO_LOC_TAX_VERSION
            );
        }
    }

    /**
     * Add plugin action links
     */
    public function plugin_action_links($links) {
        $action_links = array(
            'settings' => '<a href="' . admin_url('edit-tags.php?taxonomy=estado&post_type=listing') . '">' . __('Estados', 'listeo-location-taxonomies') . '</a>',
        );
        return array_merge($action_links, $links);
    }

    /**
     * Get plugin version
     */
    public function get_version() {
        return self::VERSION;
    }
}

/**
 * Returns the main instance of Listeo_Location_Taxonomies
 */
function Listeo_Location_Taxonomies() {
    return Listeo_Location_Taxonomies::instance();
}

// Initialize the plugin
Listeo_Location_Taxonomies();

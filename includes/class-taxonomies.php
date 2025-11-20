<?php
/**
 * Register Location Taxonomies
 *
 * @package Listeo_Location_Taxonomies
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for registering location taxonomies
 */
class Listeo_Location_Taxonomies_Register {

    /**
     * The single instance of the class
     * @var Listeo_Location_Taxonomies_Register
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
        add_action('init', array($this, 'register_taxonomies'), 5);
        add_action('admin_init', array($this, 'add_taxonomy_columns'));
    }

    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        // Get the post type(s) to attach taxonomies to
        $post_types = apply_filters('listeo_location_taxonomies_post_types', array('listing'));

        // Register Estado (State) taxonomy
        $this->register_estado_taxonomy($post_types);

        // Register Cidade (City) taxonomy
        $this->register_cidade_taxonomy($post_types);

        // Register Bairro (Neighborhood) taxonomy
        $this->register_bairro_taxonomy($post_types);

        // Register Tipo de Negócio taxonomy
        $this->register_tipo_de_negocio_taxonomy($post_types);

        // Register Tipo de Imóvel taxonomy
        $this->register_tipo_de_imovel_taxonomy($post_types);
    }

    /**
     * Register Estado (State) taxonomy
     */
    private function register_estado_taxonomy($post_types) {
        $labels = array(
            'name'                       => _x('Estados', 'taxonomy general name', 'listeo-location-taxonomies'),
            'singular_name'              => _x('Estado', 'taxonomy singular name', 'listeo-location-taxonomies'),
            'search_items'               => __('Buscar Estados', 'listeo-location-taxonomies'),
            'popular_items'              => __('Estados Populares', 'listeo-location-taxonomies'),
            'all_items'                  => __('Todos os Estados', 'listeo-location-taxonomies'),
            'parent_item'                => __('Estado Pai', 'listeo-location-taxonomies'),
            'parent_item_colon'          => __('Estado Pai:', 'listeo-location-taxonomies'),
            'edit_item'                  => __('Editar Estado', 'listeo-location-taxonomies'),
            'update_item'                => __('Atualizar Estado', 'listeo-location-taxonomies'),
            'add_new_item'               => __('Adicionar Novo Estado', 'listeo-location-taxonomies'),
            'new_item_name'              => __('Nome do Novo Estado', 'listeo-location-taxonomies'),
            'separate_items_with_commas' => __('Separe estados com vírgulas', 'listeo-location-taxonomies'),
            'add_or_remove_items'        => __('Adicionar ou remover estados', 'listeo-location-taxonomies'),
            'choose_from_most_used'      => __('Escolher dos estados mais usados', 'listeo-location-taxonomies'),
            'not_found'                  => __('Nenhum estado encontrado', 'listeo-location-taxonomies'),
            'menu_name'                  => __('Estados', 'listeo-location-taxonomies'),
            'back_to_items'              => __('&larr; Voltar para Estados', 'listeo-location-taxonomies'),
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __('Estados para classificação de imóveis', 'listeo-location-taxonomies'),
            'public'                => true,
            'publicly_queryable'    => true,
            'hierarchical'          => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => true,
            'show_in_rest'          => true,
            'show_tagcloud'         => false,
            'show_in_quick_edit'    => true,
            'show_admin_column'     => true,
            'meta_box_cb'           => 'post_categories_meta_box',
            'rewrite'               => array(
                'slug'         => 'estado',
                'with_front'   => false,
                'hierarchical' => true,
            ),
            'query_var'             => 'estado',
            'update_count_callback' => '_update_post_term_count',
        );

        register_taxonomy('estado', $post_types, $args);
    }

    /**
     * Register Cidade (City) taxonomy
     */
    private function register_cidade_taxonomy($post_types) {
        $labels = array(
            'name'                       => _x('Cidades', 'taxonomy general name', 'listeo-location-taxonomies'),
            'singular_name'              => _x('Cidade', 'taxonomy singular name', 'listeo-location-taxonomies'),
            'search_items'               => __('Buscar Cidades', 'listeo-location-taxonomies'),
            'popular_items'              => __('Cidades Populares', 'listeo-location-taxonomies'),
            'all_items'                  => __('Todas as Cidades', 'listeo-location-taxonomies'),
            'parent_item'                => __('Estado', 'listeo-location-taxonomies'),
            'parent_item_colon'          => __('Estado:', 'listeo-location-taxonomies'),
            'edit_item'                  => __('Editar Cidade', 'listeo-location-taxonomies'),
            'update_item'                => __('Atualizar Cidade', 'listeo-location-taxonomies'),
            'add_new_item'               => __('Adicionar Nova Cidade', 'listeo-location-taxonomies'),
            'new_item_name'              => __('Nome da Nova Cidade', 'listeo-location-taxonomies'),
            'separate_items_with_commas' => __('Separe cidades com vírgulas', 'listeo-location-taxonomies'),
            'add_or_remove_items'        => __('Adicionar ou remover cidades', 'listeo-location-taxonomies'),
            'choose_from_most_used'      => __('Escolher das cidades mais usadas', 'listeo-location-taxonomies'),
            'not_found'                  => __('Nenhuma cidade encontrada', 'listeo-location-taxonomies'),
            'menu_name'                  => __('Cidades', 'listeo-location-taxonomies'),
            'back_to_items'              => __('&larr; Voltar para Cidades', 'listeo-location-taxonomies'),
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __('Cidades para classificação de imóveis', 'listeo-location-taxonomies'),
            'public'                => true,
            'publicly_queryable'    => true,
            'hierarchical'          => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => true,
            'show_in_rest'          => true,
            'show_tagcloud'         => false,
            'show_in_quick_edit'    => true,
            'show_admin_column'     => true,
            'meta_box_cb'           => 'post_categories_meta_box',
            'rewrite'               => array(
                'slug'         => 'cidade',
                'with_front'   => false,
                'hierarchical' => true,
            ),
            'query_var'             => 'cidade',
            'update_count_callback' => '_update_post_term_count',
        );

        register_taxonomy('cidade', $post_types, $args);
    }

    /**
     * Register Bairro (Neighborhood) taxonomy
     */
    private function register_bairro_taxonomy($post_types) {
        $labels = array(
            'name'                       => _x('Bairros', 'taxonomy general name', 'listeo-location-taxonomies'),
            'singular_name'              => _x('Bairro', 'taxonomy singular name', 'listeo-location-taxonomies'),
            'search_items'               => __('Buscar Bairros', 'listeo-location-taxonomies'),
            'popular_items'              => __('Bairros Populares', 'listeo-location-taxonomies'),
            'all_items'                  => __('Todos os Bairros', 'listeo-location-taxonomies'),
            'parent_item'                => __('Cidade', 'listeo-location-taxonomies'),
            'parent_item_colon'          => __('Cidade:', 'listeo-location-taxonomies'),
            'edit_item'                  => __('Editar Bairro', 'listeo-location-taxonomies'),
            'update_item'                => __('Atualizar Bairro', 'listeo-location-taxonomies'),
            'add_new_item'               => __('Adicionar Novo Bairro', 'listeo-location-taxonomies'),
            'new_item_name'              => __('Nome do Novo Bairro', 'listeo-location-taxonomies'),
            'separate_items_with_commas' => __('Separe bairros com vírgulas', 'listeo-location-taxonomies'),
            'add_or_remove_items'        => __('Adicionar ou remover bairros', 'listeo-location-taxonomies'),
            'choose_from_most_used'      => __('Escolher dos bairros mais usados', 'listeo-location-taxonomies'),
            'not_found'                  => __('Nenhum bairro encontrado', 'listeo-location-taxonomies'),
            'menu_name'                  => __('Bairros', 'listeo-location-taxonomies'),
            'back_to_items'              => __('&larr; Voltar para Bairros', 'listeo-location-taxonomies'),
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __('Bairros para classificação de imóveis', 'listeo-location-taxonomies'),
            'public'                => true,
            'publicly_queryable'    => true,
            'hierarchical'          => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => true,
            'show_in_rest'          => true,
            'show_tagcloud'         => false,
            'show_in_quick_edit'    => true,
            'show_admin_column'     => true,
            'meta_box_cb'           => 'post_categories_meta_box',
            'rewrite'               => array(
                'slug'         => 'bairro',
                'with_front'   => false,
                'hierarchical' => true,
            ),
            'query_var'             => 'bairro',
            'update_count_callback' => '_update_post_term_count',
        );

        register_taxonomy('bairro', $post_types, $args);
    }

    /**
     * Add custom columns to taxonomy admin pages
     */
    public function add_taxonomy_columns() {
        // Estado columns
        add_filter('manage_edit-estado_columns', array($this, 'estado_columns'));
        add_filter('manage_estado_custom_column', array($this, 'estado_column_content'), 10, 3);

        // Cidade columns
        add_filter('manage_edit-cidade_columns', array($this, 'cidade_columns'));
        add_filter('manage_cidade_custom_column', array($this, 'cidade_column_content'), 10, 3);

        // Bairro columns
        add_filter('manage_edit-bairro_columns', array($this, 'bairro_columns'));
        add_filter('manage_bairro_custom_column', array($this, 'bairro_column_content'), 10, 3);
    }

    /**
     * Add columns for Estado taxonomy
     */
    public function estado_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['name'] = $columns['name'];
        $new_columns['listings'] = __('Imóveis', 'listeo-location-taxonomies');
        $new_columns['slug'] = $columns['slug'];
        return $new_columns;
    }

    /**
     * Display content for Estado custom columns
     */
    public function estado_column_content($content, $column_name, $term_id) {
        if ($column_name === 'listings') {
            $term = get_term($term_id, 'estado');
            return $term->count;
        }
        return $content;
    }

    /**
     * Add columns for Cidade taxonomy
     */
    public function cidade_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['name'] = $columns['name'];
        $new_columns['parent_estado'] = __('Estado', 'listeo-location-taxonomies');
        $new_columns['listings'] = __('Imóveis', 'listeo-location-taxonomies');
        $new_columns['slug'] = $columns['slug'];
        return $new_columns;
    }

    /**
     * Display content for Cidade custom columns
     */
    public function cidade_column_content($content, $column_name, $term_id) {
        if ($column_name === 'parent_estado') {
            $parent_id = get_term_meta($term_id, 'parent_estado', true);
            if ($parent_id) {
                $parent = get_term($parent_id, 'estado');
                if ($parent && !is_wp_error($parent)) {
                    return '<a href="' . admin_url('term.php?taxonomy=estado&tag_ID=' . $parent->term_id) . '">' . esc_html($parent->name) . '</a>';
                }
            }
            return '—';
        }
        if ($column_name === 'listings') {
            $term = get_term($term_id, 'cidade');
            return $term->count;
        }
        return $content;
    }

    /**
     * Add columns for Bairro taxonomy
     */
    public function bairro_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['name'] = $columns['name'];
        $new_columns['parent_cidade'] = __('Cidade', 'listeo-location-taxonomies');
        $new_columns['listings'] = __('Imóveis', 'listeo-location-taxonomies');
        $new_columns['slug'] = $columns['slug'];
        return $new_columns;
    }

    /**
     * Display content for Bairro custom columns
     */
    public function bairro_column_content($content, $column_name, $term_id) {
        if ($column_name === 'parent_cidade') {
            $parent_id = get_term_meta($term_id, 'parent_cidade', true);
            if ($parent_id) {
                $parent = get_term($parent_id, 'cidade');
                if ($parent && !is_wp_error($parent)) {
                    return '<a href="' . admin_url('term.php?taxonomy=cidade&tag_ID=' . $parent->term_id) . '">' . esc_html($parent->name) . '</a>';
                }
            }
            return '—';
        }
        if ($column_name === 'listings') {
            $term = get_term($term_id, 'bairro');
            return $term->count;
        }
        return $content;
    }

    /**
     * Register Tipo de Negócio taxonomy
     */
    private function register_tipo_de_negocio_taxonomy($post_types) {
        $labels = array(
            'name'                       => _x('Tipos de Negócio', 'taxonomy general name', 'listeo-location-taxonomies'),
            'singular_name'              => _x('Tipo de Negócio', 'taxonomy singular name', 'listeo-location-taxonomies'),
            'search_items'               => __('Buscar Tipos de Negócio', 'listeo-location-taxonomies'),
            'popular_items'              => __('Tipos de Negócio Populares', 'listeo-location-taxonomies'),
            'all_items'                  => __('Todos os Tipos de Negócio', 'listeo-location-taxonomies'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Editar Tipo de Negócio', 'listeo-location-taxonomies'),
            'update_item'                => __('Atualizar Tipo de Negócio', 'listeo-location-taxonomies'),
            'add_new_item'               => __('Adicionar Novo Tipo de Negócio', 'listeo-location-taxonomies'),
            'new_item_name'              => __('Nome do Novo Tipo de Negócio', 'listeo-location-taxonomies'),
            'separate_items_with_commas' => __('Separe tipos de negócio com vírgulas', 'listeo-location-taxonomies'),
            'add_or_remove_items'        => __('Adicionar ou remover tipos de negócio', 'listeo-location-taxonomies'),
            'choose_from_most_used'      => __('Escolher dos tipos de negócio mais usados', 'listeo-location-taxonomies'),
            'not_found'                  => __('Nenhum tipo de negócio encontrado', 'listeo-location-taxonomies'),
            'menu_name'                  => __('Tipos de Negócio', 'listeo-location-taxonomies'),
            'back_to_items'              => __('&larr; Voltar para Tipos de Negócio', 'listeo-location-taxonomies'),
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __('Tipos de negócio para classificação de imóveis (Venda, Aluguel)', 'listeo-location-taxonomies'),
            'public'                => true,
            'publicly_queryable'    => true,
            'hierarchical'          => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => true,
            'show_in_rest'          => true,
            'show_tagcloud'         => false,
            'show_in_quick_edit'    => true,
            'show_admin_column'     => true,
            'meta_box_cb'           => 'post_categories_meta_box',
            'rewrite'               => array(
                'slug'         => 'tipo-de-negocio',
                'with_front'   => false,
                'hierarchical' => false,
            ),
            'query_var'             => 'tipo-de-negocio',
            'update_count_callback' => '_update_post_term_count',
        );

        register_taxonomy('tipo_de_negocio', $post_types, $args);
    }

    /**
     * Register Tipo de Imóvel taxonomy
     */
    private function register_tipo_de_imovel_taxonomy($post_types) {
        $labels = array(
            'name'                       => _x('Tipos de Imóvel', 'taxonomy general name', 'listeo-location-taxonomies'),
            'singular_name'              => _x('Tipo de Imóvel', 'taxonomy singular name', 'listeo-location-taxonomies'),
            'search_items'               => __('Buscar Tipos de Imóvel', 'listeo-location-taxonomies'),
            'popular_items'              => __('Tipos de Imóvel Populares', 'listeo-location-taxonomies'),
            'all_items'                  => __('Todos os Tipos de Imóvel', 'listeo-location-taxonomies'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Editar Tipo de Imóvel', 'listeo-location-taxonomies'),
            'update_item'                => __('Atualizar Tipo de Imóvel', 'listeo-location-taxonomies'),
            'add_new_item'               => __('Adicionar Novo Tipo de Imóvel', 'listeo-location-taxonomies'),
            'new_item_name'              => __('Nome do Novo Tipo de Imóvel', 'listeo-location-taxonomies'),
            'separate_items_with_commas' => __('Separe tipos de imóvel com vírgulas', 'listeo-location-taxonomies'),
            'add_or_remove_items'        => __('Adicionar ou remover tipos de imóvel', 'listeo-location-taxonomies'),
            'choose_from_most_used'      => __('Escolher dos tipos de imóvel mais usados', 'listeo-location-taxonomies'),
            'not_found'                  => __('Nenhum tipo de imóvel encontrado', 'listeo-location-taxonomies'),
            'menu_name'                  => __('Tipos de Imóvel', 'listeo-location-taxonomies'),
            'back_to_items'              => __('&larr; Voltar para Tipos de Imóvel', 'listeo-location-taxonomies'),
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __('Tipos de imóvel para classificação (Casa, Apartamento, etc)', 'listeo-location-taxonomies'),
            'public'                => true,
            'publicly_queryable'    => true,
            'hierarchical'          => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => true,
            'show_in_rest'          => true,
            'show_tagcloud'         => false,
            'show_in_quick_edit'    => true,
            'show_admin_column'     => true,
            'meta_box_cb'           => 'post_categories_meta_box',
            'rewrite'               => array(
                'slug'         => 'tipo-de-imovel',
                'with_front'   => false,
                'hierarchical' => false,
            ),
            'query_var'             => 'tipo-de-imovel',
            'update_count_callback' => '_update_post_term_count',
        );

        register_taxonomy('tipo_de_imovel', $post_types, $args);
    }
}

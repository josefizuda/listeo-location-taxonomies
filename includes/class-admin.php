<?php
/**
 * Admin functionality
 *
 * @package Listeo_Location_Taxonomies
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for admin functionality
 */
class Listeo_Location_Admin {

    /**
     * The single instance of the class
     * @var Listeo_Location_Admin
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
        // Add meta fields for cidade (select parent estado)
        add_action('cidade_add_form_fields', array($this, 'add_estado_field_to_cidade'));
        add_action('cidade_edit_form_fields', array($this, 'edit_estado_field_to_cidade'));
        add_action('created_cidade', array($this, 'save_cidade_estado_meta'));
        add_action('edited_cidade', array($this, 'save_cidade_estado_meta'));

        // Add meta fields for bairro (select parent cidade)
        add_action('bairro_add_form_fields', array($this, 'add_cidade_field_to_bairro'));
        add_action('bairro_edit_form_fields', array($this, 'edit_cidade_field_to_bairro'));
        add_action('created_bairro', array($this, 'save_bairro_cidade_meta'));
        add_action('edited_bairro', array($this, 'save_bairro_cidade_meta'));

        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));

        // Add admin menu for IBGE import
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Handle AJAX import
        add_action('wp_ajax_listeo_import_ibge_estados', array($this, 'ajax_import_ibge_estados'));
        add_action('wp_ajax_listeo_import_ibge_cidades', array($this, 'ajax_import_ibge_cidades'));

        // Handle AJAX delete
        add_action('wp_ajax_listeo_delete_all_estados', array($this, 'ajax_delete_all_estados'));
        add_action('wp_ajax_listeo_delete_all_cidades', array($this, 'ajax_delete_all_cidades'));
        add_action('wp_ajax_listeo_delete_all_bairros', array($this, 'ajax_delete_all_bairros'));

        // Handle AJAX populate
        add_action('wp_ajax_listeo_populate_tipo_negocio', array($this, 'ajax_populate_tipo_negocio'));
        add_action('wp_ajax_listeo_populate_tipo_imovel', array($this, 'ajax_populate_tipo_imovel'));

        // Handle AJAX repair relationships
        add_action('wp_ajax_listeo_repair_relationships', array($this, 'ajax_repair_relationships'));
    }

    /**
     * Add Estado field to Cidade add form
     */
    public function add_estado_field_to_cidade() {
        $estados = get_terms(array(
            'taxonomy' => 'estado',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        ?>
        <div class="form-field term-parent-estado-wrap">
            <label for="parent_estado"><?php _e('Estado', 'listeo-location-taxonomies'); ?></label>
            <select name="parent_estado" id="parent_estado" class="postform">
                <option value=""><?php _e('Selecione um Estado', 'listeo-location-taxonomies'); ?></option>
                <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                    <?php foreach ($estados as $estado) : ?>
                        <option value="<?php echo esc_attr($estado->term_id); ?>">
                            <?php echo esc_html($estado->name); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <p class="description"><?php _e('Selecione o estado ao qual esta cidade pertence.', 'listeo-location-taxonomies'); ?></p>
        </div>
        <?php
    }

    /**
     * Add Estado field to Cidade edit form
     */
    public function edit_estado_field_to_cidade($term) {
        $parent_estado = get_term_meta($term->term_id, 'parent_estado', true);
        $estados = get_terms(array(
            'taxonomy' => 'estado',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        ?>
        <tr class="form-field term-parent-estado-wrap">
            <th scope="row">
                <label for="parent_estado"><?php _e('Estado', 'listeo-location-taxonomies'); ?></label>
            </th>
            <td>
                <select name="parent_estado" id="parent_estado" class="postform">
                    <option value=""><?php _e('Selecione um Estado', 'listeo-location-taxonomies'); ?></option>
                    <?php if (!is_wp_error($estados) && !empty($estados)) : ?>
                        <?php foreach ($estados as $estado) : ?>
                            <option value="<?php echo esc_attr($estado->term_id); ?>" <?php selected($parent_estado, $estado->term_id); ?>>
                                <?php echo esc_html($estado->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <p class="description"><?php _e('Selecione o estado ao qual esta cidade pertence.', 'listeo-location-taxonomies'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save Cidade -> Estado relationship
     */
    public function save_cidade_estado_meta($term_id) {
        if (isset($_POST['parent_estado'])) {
            $parent_estado = absint($_POST['parent_estado']);
            update_term_meta($term_id, 'parent_estado', $parent_estado);
        }
    }

    /**
     * Add Cidade field to Bairro add form
     */
    public function add_cidade_field_to_bairro() {
        $cidades = get_terms(array(
            'taxonomy' => 'cidade',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        ?>
        <div class="form-field term-parent-cidade-wrap">
            <label for="parent_cidade"><?php _e('Cidade', 'listeo-location-taxonomies'); ?></label>
            <select name="parent_cidade" id="parent_cidade" class="postform">
                <option value=""><?php _e('Selecione uma Cidade', 'listeo-location-taxonomies'); ?></option>
                <?php if (!is_wp_error($cidades) && !empty($cidades)) : ?>
                    <?php foreach ($cidades as $cidade) : ?>
                        <?php
                        $parent_estado_id = get_term_meta($cidade->term_id, 'parent_estado', true);
                        $estado_name = '';
                        if ($parent_estado_id) {
                            $estado = get_term($parent_estado_id, 'estado');
                            if ($estado && !is_wp_error($estado)) {
                                $estado_name = ' (' . $estado->name . ')';
                            }
                        }
                        ?>
                        <option value="<?php echo esc_attr($cidade->term_id); ?>">
                            <?php echo esc_html($cidade->name . $estado_name); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <p class="description"><?php _e('Selecione a cidade à qual este bairro pertence.', 'listeo-location-taxonomies'); ?></p>
        </div>
        <?php
    }

    /**
     * Add Cidade field to Bairro edit form
     */
    public function edit_cidade_field_to_bairro($term) {
        $parent_cidade = get_term_meta($term->term_id, 'parent_cidade', true);
        $cidades = get_terms(array(
            'taxonomy' => 'cidade',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        ?>
        <tr class="form-field term-parent-cidade-wrap">
            <th scope="row">
                <label for="parent_cidade"><?php _e('Cidade', 'listeo-location-taxonomies'); ?></label>
            </th>
            <td>
                <select name="parent_cidade" id="parent_cidade" class="postform">
                    <option value=""><?php _e('Selecione uma Cidade', 'listeo-location-taxonomies'); ?></option>
                    <?php if (!is_wp_error($cidades) && !empty($cidades)) : ?>
                        <?php foreach ($cidades as $cidade) : ?>
                            <?php
                            $parent_estado_id = get_term_meta($cidade->term_id, 'parent_estado', true);
                            $estado_name = '';
                            if ($parent_estado_id) {
                                $estado = get_term($parent_estado_id, 'estado');
                                if ($estado && !is_wp_error($estado)) {
                                    $estado_name = ' (' . $estado->name . ')';
                                }
                            }
                            ?>
                            <option value="<?php echo esc_attr($cidade->term_id); ?>" <?php selected($parent_cidade, $cidade->term_id); ?>>
                                <?php echo esc_html($cidade->name . $estado_name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <p class="description"><?php _e('Selecione a cidade à qual este bairro pertence.', 'listeo-location-taxonomies'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save Bairro -> Cidade relationship
     */
    public function save_bairro_cidade_meta($term_id) {
        if (isset($_POST['parent_cidade'])) {
            $parent_cidade = absint($_POST['parent_cidade']);
            update_term_meta($term_id, 'parent_cidade', $parent_cidade);
        }
    }

    /**
     * Display admin notices
     */
    public function admin_notices() {
        $screen = get_current_screen();

        // Show notice on cidade taxonomy page if no estados exist
        if ($screen && $screen->taxonomy === 'cidade') {
            $estados = get_terms(array(
                'taxonomy' => 'estado',
                'hide_empty' => false,
                'fields' => 'ids'
            ));

            if (empty($estados) || is_wp_error($estados)) {
                ?>
                <div class="notice notice-warning">
                    <p>
                        <?php _e('Atenção: Você precisa criar Estados primeiro antes de adicionar Cidades.', 'listeo-location-taxonomies'); ?>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=estado&post_type=listing'); ?>">
                            <?php _e('Criar Estados agora', 'listeo-location-taxonomies'); ?>
                        </a>
                    </p>
                </div>
                <?php
            }
        }

        // Show notice on bairro taxonomy page if no cidades exist
        if ($screen && $screen->taxonomy === 'bairro') {
            $cidades = get_terms(array(
                'taxonomy' => 'cidade',
                'hide_empty' => false,
                'fields' => 'ids'
            ));

            if (empty($cidades) || is_wp_error($cidades)) {
                ?>
                <div class="notice notice-warning">
                    <p>
                        <?php _e('Atenção: Você precisa criar Cidades primeiro antes de adicionar Bairros.', 'listeo-location-taxonomies'); ?>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=cidade&post_type=listing'); ?>">
                            <?php _e('Criar Cidades agora', 'listeo-location-taxonomies'); ?>
                        </a>
                    </p>
                </div>
                <?php
            }
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=listing',
            __('Importar IBGE', 'listeo-location-taxonomies'),
            __('Importar IBGE', 'listeo-location-taxonomies'),
            'manage_options',
            'listeo-import-ibge',
            array($this, 'render_import_page')
        );
    }

    /**
     * Render import page
     */
    public function render_import_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Importar Estados e Cidades do IBGE', 'listeo-location-taxonomies'); ?></h1>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Importar Estados', 'listeo-location-taxonomies'); ?></h2>
                <p><?php _e('Clique no botão abaixo para importar todos os estados brasileiros da API do IBGE.', 'listeo-location-taxonomies'); ?></p>

                <button type="button" id="import-estados-btn" class="button button-primary">
                    <?php _e('Importar Estados', 'listeo-location-taxonomies'); ?>
                </button>

                <div id="import-estados-progress" style="display:none; margin-top: 15px;">
                    <div class="notice notice-info inline">
                        <p id="import-estados-status"><?php _e('Importando...', 'listeo-location-taxonomies'); ?></p>
                    </div>
                </div>

                <div id="import-estados-result" style="margin-top: 15px;"></div>

                <hr style="margin: 20px 0;">

                <button type="button" id="delete-all-estados-btn" class="button button-link-delete">
                    <?php _e('🗑️ Remover Todos os Estados', 'listeo-location-taxonomies'); ?>
                </button>
                <div id="delete-estados-result" style="margin-top: 15px;"></div>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Importar Cidades', 'listeo-location-taxonomies'); ?></h2>
                <p><?php _e('Selecione um estado e clique no botão para importar todas as cidades daquele estado.', 'listeo-location-taxonomies'); ?></p>
                <p><strong><?php _e('Nota:', 'listeo-location-taxonomies'); ?></strong> <?php _e('Você precisa importar os estados primeiro.', 'listeo-location-taxonomies'); ?></p>

                <p>
                    <label for="select-estado">
                        <?php _e('Selecione o Estado:', 'listeo-location-taxonomies'); ?>
                    </label>
                    <select id="select-estado" class="regular-text">
                        <option value=""><?php _e('Selecione um Estado', 'listeo-location-taxonomies'); ?></option>
                        <?php
                        $estados = get_terms(array(
                            'taxonomy' => 'estado',
                            'hide_empty' => false,
                            'orderby' => 'name',
                            'order' => 'ASC'
                        ));

                        if (!is_wp_error($estados) && !empty($estados)) {
                            foreach ($estados as $estado) {
                                // Extract UF from slug (format: estado-uf)
                                $uf = strtoupper(str_replace('estado-', '', $estado->slug));
                                echo '<option value="' . esc_attr($uf) . '" data-term-id="' . $estado->term_id . '">' . esc_html($estado->name) . ' (' . $uf . ')</option>';
                            }
                        }
                        ?>
                    </select>
                </p>

                <button type="button" id="import-cidades-btn" class="button button-primary" disabled>
                    <?php _e('Importar Cidades', 'listeo-location-taxonomies'); ?>
                </button>

                <div id="import-cidades-progress" style="display:none; margin-top: 15px;">
                    <div class="notice notice-info inline">
                        <p id="import-cidades-status"><?php _e('Importando...', 'listeo-location-taxonomies'); ?></p>
                    </div>
                </div>

                <div id="import-cidades-result" style="margin-top: 15px;"></div>

                <hr style="margin: 20px 0;">

                <button type="button" id="delete-all-cidades-btn" class="button button-link-delete">
                    <?php _e('🗑️ Remover Todas as Cidades', 'listeo-location-taxonomies'); ?>
                </button>
                <div id="delete-cidades-result" style="margin-top: 15px;"></div>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Gerenciar Bairros', 'listeo-location-taxonomies'); ?></h2>
                <p><?php _e('Remova todos os bairros cadastrados.', 'listeo-location-taxonomies'); ?></p>

                <button type="button" id="delete-all-bairros-btn" class="button button-link-delete">
                    <?php _e('🗑️ Remover Todos os Bairros', 'listeo-location-taxonomies'); ?>
                </button>
                <div id="delete-bairros-result" style="margin-top: 15px;"></div>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Popular Tipos de Negócio', 'listeo-location-taxonomies'); ?></h2>
                <p><?php _e('Adicione os tipos de negócio padrão (Venda e Aluguel).', 'listeo-location-taxonomies'); ?></p>

                <button type="button" id="populate-tipo-negocio-btn" class="button button-primary">
                    <?php _e('Popular Tipos de Negócio', 'listeo-location-taxonomies'); ?>
                </button>

                <div id="populate-tipo-negocio-result" style="margin-top: 15px;"></div>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Popular Tipos de Imóvel', 'listeo-location-taxonomies'); ?></h2>
                <p><?php _e('Adicione os tipos de imóvel padrão.', 'listeo-location-taxonomies'); ?></p>

                <button type="button" id="populate-tipo-imovel-btn" class="button button-primary">
                    <?php _e('Popular Tipos de Imóvel', 'listeo-location-taxonomies'); ?>
                </button>

                <div id="populate-tipo-imovel-result" style="margin-top: 15px;"></div>
            </div>

            <!-- Repair Relationships Card -->
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Reparar Relacionamentos', 'listeo-location-taxonomies'); ?></h2>
                <p><?php _e('Se as cidades não aparecem ao selecionar um estado, use esta ferramenta para reparar automaticamente os relacionamentos Estado → Cidade.', 'listeo-location-taxonomies'); ?></p>

                <button type="button" id="repair-relationships-btn" class="button button-secondary">
                    <?php _e('Reparar Relacionamentos Agora', 'listeo-location-taxonomies'); ?>
                </button>

                <div id="repair-relationships-result" style="margin-top: 15px;"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Enable/disable import cidades button based on estado selection
            $('#select-estado').on('change', function() {
                if ($(this).val()) {
                    $('#import-cidades-btn').prop('disabled', false);
                } else {
                    $('#import-cidades-btn').prop('disabled', true);
                }
            });

            // Import Estados
            $('#import-estados-btn').on('click', function() {
                var $btn = $(this);
                var $progress = $('#import-estados-progress');
                var $result = $('#import-estados-result');

                $btn.prop('disabled', true).text('<?php _e('Importando...', 'listeo-location-taxonomies'); ?>');
                $progress.show();
                $result.empty();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'listeo_import_ibge_estados',
                        nonce: '<?php echo wp_create_nonce('listeo_import_ibge'); ?>'
                    },
                    success: function(response) {
                        $progress.hide();
                        $btn.prop('disabled', false).text('<?php _e('Importar Estados', 'listeo-location-taxonomies'); ?>');

                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                            // Reload page to update estado select
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $progress.hide();
                        $btn.prop('disabled', false).text('<?php _e('Importar Estados', 'listeo-location-taxonomies'); ?>');
                        $result.html('<div class="notice notice-error inline"><p><?php _e('Erro ao importar estados.', 'listeo-location-taxonomies'); ?></p></div>');
                    }
                });
            });

            // Import Cidades
            $('#import-cidades-btn').on('click', function() {
                var $btn = $(this);
                var $progress = $('#import-cidades-progress');
                var $result = $('#import-cidades-result');
                var uf = $('#select-estado').val();
                var estadoTermId = $('#select-estado option:selected').data('term-id');

                if (!uf) {
                    alert('<?php _e('Selecione um estado primeiro!', 'listeo-location-taxonomies'); ?>');
                    return;
                }

                $btn.prop('disabled', true).text('<?php _e('Importando...', 'listeo-location-taxonomies'); ?>');
                $progress.show();
                $result.empty();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'listeo_import_ibge_cidades',
                        uf: uf,
                        estado_term_id: estadoTermId,
                        nonce: '<?php echo wp_create_nonce('listeo_import_ibge'); ?>'
                    },
                    success: function(response) {
                        $progress.hide();
                        $btn.prop('disabled', false).text('<?php _e('Importar Cidades', 'listeo-location-taxonomies'); ?>');

                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $progress.hide();
                        $btn.prop('disabled', false).text('<?php _e('Importar Cidades', 'listeo-location-taxonomies'); ?>');
                        $result.html('<div class="notice notice-error inline"><p><?php _e('Erro ao importar cidades.', 'listeo-location-taxonomies'); ?></p></div>');
                    }
                });
            });

            // Delete Estados
            $('#delete-all-estados-btn').on('click', function() {
                if (!confirm('<?php _e('Tem certeza que deseja remover TODOS os estados? Esta ação não pode ser desfeita!', 'listeo-location-taxonomies'); ?>')) {
                    return;
                }

                var $btn = $(this);
                var $result = $('#delete-estados-result');

                $btn.prop('disabled', true).text('<?php _e('Removendo...', 'listeo-location-taxonomies'); ?>');
                $result.empty();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'listeo_delete_all_estados',
                        nonce: '<?php echo wp_create_nonce('listeo_import_ibge'); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('<?php _e('🗑️ Remover Todos os Estados', 'listeo-location-taxonomies'); ?>');

                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('<?php _e('🗑️ Remover Todos os Estados', 'listeo-location-taxonomies'); ?>');
                        $result.html('<div class="notice notice-error inline"><p><?php _e('Erro ao remover estados.', 'listeo-location-taxonomies'); ?></p></div>');
                    }
                });
            });

            // Delete Cidades
            $('#delete-all-cidades-btn').on('click', function() {
                if (!confirm('<?php _e('Tem certeza que deseja remover TODAS as cidades? Esta ação não pode ser desfeita!', 'listeo-location-taxonomies'); ?>')) {
                    return;
                }

                var $btn = $(this);
                var $result = $('#delete-cidades-result');

                $btn.prop('disabled', true).text('<?php _e('Removendo...', 'listeo-location-taxonomies'); ?>');
                $result.empty();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'listeo_delete_all_cidades',
                        nonce: '<?php echo wp_create_nonce('listeo_import_ibge'); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('<?php _e('🗑️ Remover Todas as Cidades', 'listeo-location-taxonomies'); ?>');

                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('<?php _e('🗑️ Remover Todas as Cidades', 'listeo-location-taxonomies'); ?>');
                        $result.html('<div class="notice notice-error inline"><p><?php _e('Erro ao remover cidades.', 'listeo-location-taxonomies'); ?></p></div>');
                    }
                });
            });

            // Delete Bairros
            $('#delete-all-bairros-btn').on('click', function() {
                if (!confirm('<?php _e('Tem certeza que deseja remover TODOS os bairros? Esta ação não pode ser desfeita!', 'listeo-location-taxonomies'); ?>')) {
                    return;
                }

                var $btn = $(this);
                var $result = $('#delete-bairros-result');

                $btn.prop('disabled', true).text('<?php _e('Removendo...', 'listeo-location-taxonomies'); ?>');
                $result.empty();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'listeo_delete_all_bairros',
                        nonce: '<?php echo wp_create_nonce('listeo_import_ibge'); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('<?php _e('🗑️ Remover Todos os Bairros', 'listeo-location-taxonomies'); ?>');

                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('<?php _e('🗑️ Remover Todos os Bairros', 'listeo-location-taxonomies'); ?>');
                        $result.html('<div class="notice notice-error inline"><p><?php _e('Erro ao remover bairros.', 'listeo-location-taxonomies'); ?></p></div>');
                    }
                });
            });

            // Populate Tipo de Negócio
            $('#populate-tipo-negocio-btn').on('click', function() {
                var $btn = $(this);
                var $result = $('#populate-tipo-negocio-result');

                $btn.prop('disabled', true).text('<?php _e('Populando...', 'listeo-location-taxonomies'); ?>');
                $result.empty();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'listeo_populate_tipo_negocio',
                        nonce: '<?php echo wp_create_nonce('listeo_import_ibge'); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('<?php _e('Popular Tipos de Negócio', 'listeo-location-taxonomies'); ?>');

                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('<?php _e('Popular Tipos de Negócio', 'listeo-location-taxonomies'); ?>');
                        $result.html('<div class="notice notice-error inline"><p><?php _e('Erro ao popular tipos de negócio.', 'listeo-location-taxonomies'); ?></p></div>');
                    }
                });
            });

            // Populate Tipo de Imóvel
            $('#populate-tipo-imovel-btn').on('click', function() {
                var $btn = $(this);
                var $result = $('#populate-tipo-imovel-result');

                $btn.prop('disabled', true).text('<?php _e('Populando...', 'listeo-location-taxonomies'); ?>');
                $result.empty();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'listeo_populate_tipo_imovel',
                        nonce: '<?php echo wp_create_nonce('listeo_import_ibge'); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('<?php _e('Popular Tipos de Imóvel', 'listeo-location-taxonomies'); ?>');

                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('<?php _e('Popular Tipos de Imóvel', 'listeo-location-taxonomies'); ?>');
                        $result.html('<div class="notice notice-error inline"><p><?php _e('Erro ao popular tipos de imóvel.', 'listeo-location-taxonomies'); ?></p></div>');
                    }
                });
            });

            // Repair Relationships
            $('#repair-relationships-btn').on('click', function() {
                var $btn = $(this);
                var $result = $('#repair-relationships-result');

                if (!confirm('<?php _e('Esta ação irá reparar os relacionamentos de TODAS as cidades. Continuar?', 'listeo-location-taxonomies'); ?>')) {
                    return;
                }

                $btn.prop('disabled', true).text('<?php _e('Reparando...', 'listeo-location-taxonomies'); ?>');
                $result.html('<div class="notice notice-info inline"><p><?php _e('Processando...', 'listeo-location-taxonomies'); ?></p></div>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'listeo_repair_relationships',
                        nonce: '<?php echo wp_create_nonce('listeo_import_ibge'); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('<?php _e('Reparar Relacionamentos Agora', 'listeo-location-taxonomies'); ?>');

                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('<?php _e('Reparar Relacionamentos Agora', 'listeo-location-taxonomies'); ?>');
                        $result.html('<div class="notice notice-error inline"><p><?php _e('Erro ao reparar relacionamentos.', 'listeo-location-taxonomies'); ?></p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Import estados from IBGE
     */
    public function ajax_import_ibge_estados() {
        check_ajax_referer('listeo_import_ibge', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissão negada.', 'listeo-location-taxonomies')));
        }

        // Fetch estados from IBGE API
        $response = wp_remote_get('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome');

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('Erro ao conectar com API do IBGE.', 'listeo-location-taxonomies')));
        }

        $body = wp_remote_retrieve_body($response);
        $estados = json_decode($body, true);

        if (empty($estados)) {
            wp_send_json_error(array('message' => __('Nenhum estado retornado pela API.', 'listeo-location-taxonomies')));
        }

        $imported = 0;
        $skipped = 0;

        foreach ($estados as $estado) {
            $slug = 'estado-' . strtolower($estado['sigla']);
            $name = $estado['nome'];

            // Check if already exists
            $existing = term_exists($slug, 'estado');

            if (!$existing) {
                $result = wp_insert_term($name, 'estado', array(
                    'slug' => $slug
                ));

                if (!is_wp_error($result)) {
                    $imported++;
                }
            } else {
                $skipped++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('Importação concluída! %d estados importados, %d já existiam.', 'listeo-location-taxonomies'),
                $imported,
                $skipped
            )
        ));
    }

    /**
     * AJAX: Import cidades from IBGE
     */
    public function ajax_import_ibge_cidades() {
        check_ajax_referer('listeo_import_ibge', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissão negada.', 'listeo-location-taxonomies')));
        }

        $uf = isset($_POST['uf']) ? sanitize_text_field($_POST['uf']) : '';
        $estado_term_id = isset($_POST['estado_term_id']) ? absint($_POST['estado_term_id']) : 0;

        if (empty($uf) || !$estado_term_id) {
            wp_send_json_error(array('message' => __('Dados inválidos.', 'listeo-location-taxonomies')));
        }

        // Fetch cidades from IBGE API
        $response = wp_remote_get('https://servicodados.ibge.gov.br/api/v1/localidades/estados/' . $uf . '/municipios?orderBy=nome');

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('Erro ao conectar com API do IBGE.', 'listeo-location-taxonomies')));
        }

        $body = wp_remote_retrieve_body($response);
        $cidades = json_decode($body, true);

        if (empty($cidades)) {
            wp_send_json_error(array('message' => __('Nenhuma cidade retornada pela API.', 'listeo-location-taxonomies')));
        }

        $imported = 0;
        $skipped = 0;

        foreach ($cidades as $cidade) {
            $name = $cidade['nome'];
            $slug = sanitize_title($name) . '-' . strtolower($uf);

            // Check if already exists
            $existing = term_exists($slug, 'cidade');

            if (!$existing) {
                $result = wp_insert_term($name, 'cidade', array(
                    'slug' => $slug
                ));

                if (!is_wp_error($result)) {
                    // Add parent estado relationship
                    update_term_meta($result['term_id'], 'parent_estado', $estado_term_id);
                    $imported++;
                }
            } else {
                // Update parent_estado even if cidade already exists
                $term_id = is_array($existing) ? $existing['term_id'] : $existing;
                update_term_meta($term_id, 'parent_estado', $estado_term_id);
                $skipped++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('Importação concluída! %d cidades importadas, %d já existiam.', 'listeo-location-taxonomies'),
                $imported,
                $skipped
            )
        ));
    }

    /**
     * AJAX: Delete all estados
     */
    public function ajax_delete_all_estados() {
        check_ajax_referer('listeo_import_ibge', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissão negada.', 'listeo-location-taxonomies')));
        }

        $estados = get_terms(array(
            'taxonomy' => 'estado',
            'hide_empty' => false,
            'fields' => 'ids'
        ));

        if (is_wp_error($estados) || empty($estados)) {
            wp_send_json_error(array('message' => __('Nenhum estado encontrado para remover.', 'listeo-location-taxonomies')));
        }

        $deleted = 0;
        foreach ($estados as $term_id) {
            $result = wp_delete_term($term_id, 'estado');
            if (!is_wp_error($result)) {
                $deleted++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('%d estados removidos com sucesso!', 'listeo-location-taxonomies'), $deleted)
        ));
    }

    /**
     * AJAX: Delete all cidades
     */
    public function ajax_delete_all_cidades() {
        check_ajax_referer('listeo_import_ibge', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissão negada.', 'listeo-location-taxonomies')));
        }

        $cidades = get_terms(array(
            'taxonomy' => 'cidade',
            'hide_empty' => false,
            'fields' => 'ids'
        ));

        if (is_wp_error($cidades) || empty($cidades)) {
            wp_send_json_error(array('message' => __('Nenhuma cidade encontrada para remover.', 'listeo-location-taxonomies')));
        }

        $deleted = 0;
        foreach ($cidades as $term_id) {
            $result = wp_delete_term($term_id, 'cidade');
            if (!is_wp_error($result)) {
                $deleted++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('%d cidades removidas com sucesso!', 'listeo-location-taxonomies'), $deleted)
        ));
    }

    /**
     * AJAX: Delete all bairros
     */
    public function ajax_delete_all_bairros() {
        check_ajax_referer('listeo_import_ibge', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissão negada.', 'listeo-location-taxonomies')));
        }

        $bairros = get_terms(array(
            'taxonomy' => 'bairro',
            'hide_empty' => false,
            'fields' => 'ids'
        ));

        if (is_wp_error($bairros) || empty($bairros)) {
            wp_send_json_error(array('message' => __('Nenhum bairro encontrado para remover.', 'listeo-location-taxonomies')));
        }

        $deleted = 0;
        foreach ($bairros as $term_id) {
            $result = wp_delete_term($term_id, 'bairro');
            if (!is_wp_error($result)) {
                $deleted++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('%d bairros removidos com sucesso!', 'listeo-location-taxonomies'), $deleted)
        ));
    }

    /**
     * AJAX: Populate Tipo de Negócio taxonomy
     */
    public function ajax_populate_tipo_negocio() {
        check_ajax_referer('listeo_import_ibge', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissão negada.', 'listeo-location-taxonomies')));
        }

        $tipos_negocio = array(
            'Venda',
            'Aluguel'
        );

        $added = 0;
        $skipped = 0;

        foreach ($tipos_negocio as $tipo) {
            $slug = sanitize_title($tipo);

            // Check if already exists
            $existing = term_exists($slug, 'tipo_de_negocio');

            if (!$existing) {
                $result = wp_insert_term($tipo, 'tipo_de_negocio', array(
                    'slug' => $slug
                ));

                if (!is_wp_error($result)) {
                    $added++;
                } else {
                    error_log('Erro ao adicionar tipo de negócio: ' . $result->get_error_message());
                }
            } else {
                $skipped++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('%d tipos de negócio adicionados, %d já existentes.', 'listeo-location-taxonomies'),
                $added,
                $skipped
            )
        ));
    }

    /**
     * AJAX: Populate Tipo de Imóvel taxonomy
     */
    public function ajax_populate_tipo_imovel() {
        check_ajax_referer('listeo_import_ibge', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissão negada.', 'listeo-location-taxonomies')));
        }

        $tipos_imovel = array(
            'Casa Comercial',
            'Casas & Sobrados',
            'Apartamentos',
            'Casa em Condomínio',
            'Cobertura',
            'Fazendas',
            'Flat',
            'Galpão/Depósito',
            'Garagens',
            'Kitnets',
            'Lofts',
            'Ponto Comercial',
            'Sala Comercial',
            'Sítios & Chácaras',
            'Terrenos'
        );

        $added = 0;
        $skipped = 0;

        foreach ($tipos_imovel as $tipo) {
            $slug = sanitize_title($tipo);

            // Check if already exists
            $existing = term_exists($slug, 'tipo_de_imovel');

            if (!$existing) {
                $result = wp_insert_term($tipo, 'tipo_de_imovel', array(
                    'slug' => $slug
                ));

                if (!is_wp_error($result)) {
                    $added++;
                } else {
                    error_log('Erro ao adicionar tipo de imóvel: ' . $result->get_error_message());
                }
            } else {
                $skipped++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('%d tipos de imóvel adicionados, %d já existentes.', 'listeo-location-taxonomies'),
                $added,
                $skipped
            )
        ));
    }

    /**
     * AJAX: Repair relationships between cidades and estados
     */
    public function ajax_repair_relationships() {
        check_ajax_referer('listeo_import_ibge', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissão negada.', 'listeo-location-taxonomies')));
        }

        // Get all cidades
        $cidades = get_terms(array(
            'taxonomy' => 'cidade',
            'hide_empty' => false,
            'fields' => 'all'
        ));

        if (is_wp_error($cidades) || empty($cidades)) {
            wp_send_json_error(array('message' => __('Nenhuma cidade encontrada.', 'listeo-location-taxonomies')));
        }

        // Get all estados indexed by UF
        $estados = get_terms(array(
            'taxonomy' => 'estado',
            'hide_empty' => false
        ));

        $estados_by_uf = array();
        foreach ($estados as $estado) {
            // Extract UF from slug: estado-sc -> sc
            if (preg_match('/estado-([a-z]{2})$/', $estado->slug, $matches)) {
                $uf = strtolower($matches[1]);
                $estados_by_uf[$uf] = $estado->term_id;
            }
        }

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($cidades as $cidade) {
            // Extract UF from cidade slug: florianopolis-sc -> sc
            if (preg_match('/-([a-z]{2})$/', $cidade->slug, $matches)) {
                $uf = strtolower($matches[1]);

                if (isset($estados_by_uf[$uf])) {
                    $estado_id = $estados_by_uf[$uf];
                    $current_parent = get_term_meta($cidade->term_id, 'parent_estado', true);

                    if ($current_parent != $estado_id) {
                        update_term_meta($cidade->term_id, 'parent_estado', $estado_id);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $errors++;
                    error_log("Repair: Estado não encontrado para UF: {$uf} (cidade: {$cidade->name})");
                }
            } else {
                $errors++;
                error_log("Repair: UF não encontrada no slug da cidade: {$cidade->slug}");
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('%d cidades atualizadas, %d já estavam corretas, %d erros.', 'listeo-location-taxonomies'),
                $updated,
                $skipped,
                $errors
            )
        ));
    }
}

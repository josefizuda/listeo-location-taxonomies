# Guia de Implementação - Listeo Location Taxonomies

## 🎯 Guia Passo a Passo

### Passo 1: Ativar o Plugin

1. Acesse o painel do WordPress
2. Vá em **Plugins** > **Plugins Instalados**
3. Encontre **Listeo - Taxonomias de Localização**
4. Clique em **Ativar**

### Passo 2: Criar Estados

1. No menu lateral, vá em **Listings** > **Estados**
2. Clique em **Adicionar Novo Estado**
3. Exemplos de estados para adicionar:

```
Nome: São Paulo
Slug: sao-paulo

Nome: Rio de Janeiro
Slug: rio-de-janeiro

Nome: Minas Gerais
Slug: minas-gerais

Nome: Bahia
Slug: bahia
```

### Passo 3: Criar Cidades

1. Vá em **Listings** > **Cidades**
2. Ao adicionar uma cidade, **selecione o estado** no campo "Estado"
3. Exemplos:

```
PARA SÃO PAULO:
Nome: São Paulo       | Estado: São Paulo
Nome: Campinas        | Estado: São Paulo
Nome: Santos          | Estado: São Paulo
Nome: Ribeirão Preto  | Estado: São Paulo

PARA RIO DE JANEIRO:
Nome: Rio de Janeiro  | Estado: Rio de Janeiro
Nome: Niterói         | Estado: Rio de Janeiro
Nome: Petrópolis      | Estado: Rio de Janeiro
```

### Passo 4: Criar Bairros

1. Vá em **Listings** > **Bairros**
2. Ao adicionar um bairro, **selecione a cidade** no campo "Cidade"
3. Exemplos:

```
PARA SÃO PAULO (Cidade):
Nome: Vila Mariana      | Cidade: São Paulo
Nome: Moema             | Cidade: São Paulo
Nome: Pinheiros         | Cidade: São Paulo
Nome: Jardins           | Cidade: São Paulo
Nome: Itaim Bibi        | Cidade: São Paulo

PARA CAMPINAS:
Nome: Cambuí            | Cidade: Campinas
Nome: Taquaral          | Cidade: Campinas
```

### Passo 5: Adicionar aos Listings

1. Edite ou crie um listing
2. Na lateral direita, você verá as caixas:
   - **Estados**
   - **Cidades**
   - **Bairros**
3. Selecione a localização apropriada para cada listing

### Passo 6: Integrar ao Search Form

#### Opção A: Usando Child Theme (Recomendado)

1. Crie um child theme se ainda não tiver
2. Copie o arquivo do search form para o child theme
3. Adicione o shortcode:

**Exemplo em `listeo-child/template-split-map-sidebar.php`:**

```php
<?php
/**
 * Template Name: Listing With Map - Split Page with Sidebar
 */

get_header('split');
?>

<!-- Search Section -->
<section class="search-container">
    <div class="search-fields">
        <!-- Search form padrão do Listeo -->
        <?php echo do_shortcode('[listeo_search_form source="half"]'); ?>

        <!-- ADICIONE AQUI: Campos de localização -->
        <div class="location-filters-section">
            <h4>Filtrar por Localização</h4>
            <?php echo do_shortcode('[listeo_location_search]'); ?>
        </div>
    </div>
</section>

<!-- Resto do template -->
<?php get_footer(); ?>
```

#### Opção B: Usando Widget

1. Vá em **Aparência** > **Widgets**
2. Encontre a área de widget da sidebar de busca
3. Adicione um widget **HTML Customizado**
4. Cole o shortcode:

```html
<div class="widget-location-filters">
    <h4>Localização</h4>
    [listeo_location_search]
</div>
```

#### Opção C: Editando o Header (Busca Global)

**Arquivo: `listeo-child/header.php`**

```php
<div class="main-search-container">
    <!-- Busca padrão -->
    <?php echo do_shortcode('[listeo_search_form action="' . get_post_type_archive_link('listing') . '" source="header"]'); ?>

    <!-- Adicione filtros de localização -->
    <div class="row location-filters-header">
        <div class="col-md-12">
            <?php echo do_shortcode('[listeo_location_search show_labels="false"]'); ?>
        </div>
    </div>
</div>
```

### Passo 7: Testar Filtros

1. Vá para a página de listagens
2. Selecione um **Estado**
3. Veja as **Cidades** daquele estado carregarem automaticamente
4. Selecione uma **Cidade**
5. Veja os **Bairros** daquela cidade carregarem
6. Clique em **Buscar** ou os resultados serão filtrados automaticamente

## 🎨 Customizações Comuns

### 1. Adicionar Campos na Sidebar de Filtros

**Arquivo: `listeo-child/sidebar-listeo.php`** (criar se não existir)

```php
<aside class="sidebar">
    <!-- Filtros padrão do Listeo -->

    <!-- ADICIONE: Filtros de localização -->
    <div class="widget location-widget">
        <h3 class="widget-title">Localização</h3>
        <div class="widget-content">
            <?php echo do_shortcode('[listeo_estado_field]'); ?>
            <?php echo do_shortcode('[listeo_cidade_field]'); ?>
            <?php echo do_shortcode('[listeo_bairro_field]'); ?>
        </div>
    </div>
</aside>
```

### 2. Exibir Localização no Card do Listing

**Arquivo: `listeo-child/template-parts/content-listing.php`**

```php
<div class="listing-card">
    <h3><?php the_title(); ?></h3>

    <!-- ADICIONE: Breadcrumb de localização -->
    <div class="listing-location">
        <i class="fa fa-map-marker"></i>
        <?php echo Listeo_Location_Query_Integration::get_location_breadcrumb(get_the_ID()); ?>
    </div>

    <!-- Resto do conteúdo -->
</div>
```

### 3. Criar Página de Resultados por Estado

**Criar template: `listeo-child/taxonomy-estado.php`**

```php
<?php
/**
 * Taxonomy Template: Estado
 */

get_header();

$term = get_queried_object();
?>

<div class="container">
    <h1>Imóveis em <?php echo $term->name; ?></h1>

    <!-- Filtros refinados -->
    <div class="refined-filters">
        <?php echo do_shortcode('[listeo_cidade_field]'); ?>
        <?php echo do_shortcode('[listeo_bairro_field]'); ?>
    </div>

    <!-- Resultados -->
    <?php if (have_posts()) : ?>
        <div class="listings-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('template-parts/content', 'listing'); ?>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <p>Nenhum imóvel encontrado.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
```

### 4. Adicionar Contador de Listings por Localização

```php
// Adicionar ao functions.php do child theme

function get_listings_count_by_location($taxonomy, $term_slug) {
    $term = get_term_by('slug', $term_slug, $taxonomy);

    if ($term) {
        return $term->count;
    }

    return 0;
}

// Uso:
// <?php echo get_listings_count_by_location('estado', 'sao-paulo'); ?> imóveis
```

### 5. Widget Customizado de Localizações Populares

**Adicionar ao `functions.php`:**

```php
function display_popular_locations() {
    $estados = get_terms(array(
        'taxonomy' => 'estado',
        'orderby' => 'count',
        'order' => 'DESC',
        'number' => 5,
        'hide_empty' => true
    ));

    if (!empty($estados) && !is_wp_error($estados)) {
        echo '<ul class="popular-locations">';
        foreach ($estados as $estado) {
            $url = get_term_link($estado);
            echo '<li>';
            echo '<a href="' . esc_url($url) . '">';
            echo esc_html($estado->name) . ' (' . $estado->count . ')';
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
    }
}

// Uso no template:
// <?php display_popular_locations(); ?>
```

## 🔍 Queries Personalizadas

### Buscar Imóveis de Luxo em Bairros Específicos

```php
$args = array(
    'post_type' => 'listing',
    'posts_per_page' => 10,
    'tax_query' => array(
        'relation' => 'AND',
        array(
            'taxonomy' => 'estado',
            'field' => 'slug',
            'terms' => 'sao-paulo'
        ),
        array(
            'taxonomy' => 'bairro',
            'field' => 'slug',
            'terms' => array('jardins', 'moema', 'itaim-bibi'),
            'operator' => 'IN'
        )
    ),
    'meta_query' => array(
        array(
            'key' => '_price',
            'value' => 1000000,
            'type' => 'NUMERIC',
            'compare' => '>='
        )
    )
);

$luxury_listings = new WP_Query($args);
```

### Listar Todas as Cidades de um Estado

```php
function get_cities_by_state($estado_slug) {
    $estado = get_term_by('slug', $estado_slug, 'estado');

    if (!$estado) {
        return array();
    }

    $cidades = get_terms(array(
        'taxonomy' => 'cidade',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key' => 'parent_estado',
                'value' => $estado->term_id,
                'compare' => '='
            )
        )
    ));

    return $cidades;
}

// Uso:
$sp_cities = get_cities_by_state('sao-paulo');
foreach ($sp_cities as $city) {
    echo $city->name . '<br>';
}
```

## 📱 Responsividade

Os campos são 100% responsivos. Em mobile, os campos ficam em coluna:

```css
/* Adicionar ao seu CSS se necessário */
@media (max-width: 768px) {
    .listeo-location-search-wrapper .col-md-4 {
        width: 100%;
        margin-bottom: 15px;
    }
}
```

## ⚡ Performance

### Cachear Queries de Localização

```php
function get_cached_locations($taxonomy) {
    $cache_key = 'locations_' . $taxonomy;
    $locations = get_transient($cache_key);

    if (false === $locations) {
        $locations = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false
        ));

        // Cache por 1 hora
        set_transient($cache_key, $locations, HOUR_IN_SECONDS);
    }

    return $locations;
}
```

## 🐛 Debug

### Verificar se Taxonomias Foram Registradas

```php
// Adicionar ao template temporariamente
$taxonomies = get_taxonomies(array(), 'objects');
foreach ($taxonomies as $taxonomy) {
    if (strpos($taxonomy->name, 'estado') !== false ||
        strpos($taxonomy->name, 'cidade') !== false ||
        strpos($taxonomy->name, 'bairro') !== false) {
        echo $taxonomy->name . '<br>';
    }
}
```

### Verificar Query Vars

```php
// Adicionar ao template
echo '<pre>';
var_dump($wp_query->query_vars);
echo '</pre>';
```

## 📞 Suporte

Se encontrar problemas:

1. Verifique os requisitos do sistema
2. Desative outros plugins temporariamente
3. Teste com tema padrão do WordPress
4. Verifique o console do navegador para erros JS
5. Ative WP_DEBUG para ver erros PHP

---

**Pronto! Seu sistema de localização está configurado!** 🎉

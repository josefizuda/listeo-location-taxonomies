# Listeo Location Taxonomies

**Version:** 2.0.0
**Author:** Fastwp
**Requires:** WordPress 5.0+
**Tested up to:** WordPress 6.4
**License:** GPL v2 or later

[Leia em Português](README.pt-BR.md)

## 📋 Description

WordPress plugin for managing hierarchical location taxonomies for the Listeo theme. Adds complete support for Brazilian States, Cities, and Neighborhoods with automatic IBGE API data import, plus property classification taxonomies (Business Type and Property Type).

## ✨ Key Features

### Location Taxonomies
- **Estado (State)** - Brazilian states (26 states + Federal District)
- **Cidade (City)** - Brazilian municipalities (5,570+ cities)
- **Bairro (Neighborhood)** - Neighborhoods/districts by city

### Classification Taxonomies
- **Tipo de Negócio (Business Type)** - Sale, Rent
- **Tipo de Imóvel (Property Type)** - 15 property types (Commercial House, Apartments, Land, etc.)

### Functionality
- ✅ Automatic import via IBGE API
- ✅ Hierarchical relationships (State → City → Neighborhood)
- ✅ Cascading filters (AJAX)
- ✅ Multiple search form layouts
- ✅ Native Listeo integration
- ✅ Automatic relationship repair tool
- ✅ Complete administrative interface
- ✅ Support for hide_empty (only shows terms with listings)

## 🚀 Installation

1. Upload the `listeo-location-taxonomies` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings → Location Taxonomies**
4. Import IBGE data (see section below)

## 📥 IBGE Data Import

### Step 1: Import States
1. Go to **Settings → Location Taxonomies**
2. Click **"Import States from IBGE"**
3. Wait for import confirmation (27 states)

### Step 2: Import Cities by State
1. Select a state from the dropdown
2. Click **"Import Cities"**
3. Repeat for each desired state
4. Cities are imported with automatic relationship to the state

### Step 3: Populate Property Taxonomies
1. Click **"Populate Business Types"** (Sale, Rent)
2. Click **"Populate Property Types"** (15 property types)

### Step 4: Repair Relationships (if needed)
If cities don't appear when selecting a state:
1. Scroll to the **"Repair Relationships"** section
2. Click **"Repair Relationships Now"**
3. Wait for the process to complete

## 🎨 Available Shortcodes

### Complete Forms

#### Horizontal Tabs Form
```php
[listeo_search_form_tabs]
```
Displays a form with 3 tabs (All, Sale, Rent) in horizontal layout.

**Fields per tab:**
- State (cascade)
- City (cascade)
- Neighborhood (cascade)
- Property Type
- Search Button

#### Vertical Tabs Form
```php
[listeo_search_form_tabs_vertical]
```
Displays a form with 3 tabs in vertical layout (sidebar navigation).

#### Simple Form with Submit
```php
[listeo_location_search_form]
```
Basic form with all fields and search button.

**Optional attributes:**
```php
[listeo_location_search_form
    action_url="/listings/"
    button_text="Search Now"
    placeholder_estado="Select State"
    placeholder_cidade="Select City"
    placeholder_bairro="Select Neighborhood"]
```

### Individual Fields

#### State Field
```php
[listeo_estado_field placeholder="State"]
```

#### City Field
```php
[listeo_cidade_field placeholder="City"]
```

#### Neighborhood Field
```php
[listeo_bairro_field placeholder="Neighborhood"]
```

#### Business Type Field
```php
[listeo_tipo_de_negocio_field placeholder="Business Type"]
```

#### Property Type Field
```php
[listeo_tipo_de_imovel_field placeholder="Property Type"]
```

## 🔧 Listeo Integration

### Adding Fields to Theme Search Form

In your child theme (`listeo-child/functions.php`):

```php
// Add fields before submit button
add_action('listeo_before_search_form_submit', 'add_location_fields');
function add_location_fields() {
    echo '<div class="col-md-2">';
    echo '<h5>Business Type</h5>';
    echo do_shortcode('[listeo_tipo_de_negocio_field]');
    echo '</div>';

    echo '<div class="col-md-3">';
    echo '<h5>Property Type</h5>';
    echo do_shortcode('[listeo_tipo_de_imovel_field]');
    echo '</div>';

    echo '<div class="col-md-2">';
    echo '<h5>State</h5>';
    echo do_shortcode('[listeo_estado_field]');
    echo '</div>';

    echo '<div class="col-md-2">';
    echo '<h5>City</h5>';
    echo do_shortcode('[listeo_cidade_field]');
    echo '</div>';

    echo '<div class="col-md-3">';
    echo '<h5>Neighborhood</h5>';
    echo do_shortcode('[listeo_bairro_field]');
    echo '</div>';
}

// Add filters to search query
add_filter('listeo_core_search_query_args', 'add_location_filters');
function add_location_filters($args) {
    $tax_query = isset($args['tax_query']) ? $args['tax_query'] : array('relation' => 'AND');

    // Filter by Business Type
    if (!empty($_GET['tax-tipo_de_negocio'])) {
        $tax_query[] = array(
            'taxonomy' => 'tipo_de_negocio',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['tax-tipo_de_negocio']),
        );
    }

    // Filter by Property Type
    if (!empty($_GET['tax-tipo_de_imovel'])) {
        $tax_query[] = array(
            'taxonomy' => 'tipo_de_imovel',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['tax-tipo_de_imovel']),
        );
    }

    // Filter by State
    if (!empty($_GET['tax-estado'])) {
        $tax_query[] = array(
            'taxonomy' => 'estado',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['tax-estado']),
        );
    }

    // Filter by City
    if (!empty($_GET['tax-cidade'])) {
        $tax_query[] = array(
            'taxonomy' => 'cidade',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['tax-cidade']),
        );
    }

    // Filter by Neighborhood
    if (!empty($_GET['tax-bairro'])) {
        $tax_query[] = array(
            'taxonomy' => 'bairro',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['tax-bairro']),
        );
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    return $args;
}
```

## 🔗 Relationship Structure

```
Estado (estado-sc)
  └── Cidade (florianopolis-sc)
        ├── Meta: parent_estado = State ID
        └── Bairro (centro-florianopolis-sc)
              └── Meta: parent_cidade = City ID

Listing (Post Type)
  ├── Taxonomy: estado
  ├── Taxonomy: cidade
  ├── Taxonomy: bairro
  ├── Taxonomy: tipo_de_negocio
  └── Taxonomy: tipo_de_imovel
```

## 🎯 AJAX and Cascading

The plugin uses AJAX to dynamically load:
- Cities when selecting a State
- Neighborhoods when selecting a City

**AJAX Endpoints:**
- `get_cidades_by_estado` - Returns cities from a state
- `get_bairros_by_cidade` - Returns neighborhoods from a city

**Global JavaScript:**
```javascript
// Globally available object
listeoLocationData = {
    ajaxurl: 'https://yoursite.com/wp-admin/admin-ajax.php',
    nonce: 'xxxxx',
    strings: {
        select_state: 'Select a State',
        select_city: 'Select a City',
        select_neighborhood: 'Select a Neighborhood',
        loading: 'Loading...'
    }
}
```

## 🛠️ Administrative Tools

### Settings Page
**Location:** Settings → Location Taxonomies

**Available features:**
1. **IBGE Import**
   - Import States
   - Import Cities by State

2. **Bulk Management**
   - Delete all States
   - Delete all Cities
   - Delete all Neighborhoods

3. **Populate Taxonomies**
   - Populate Business Types (2 terms)
   - Populate Property Types (15 terms)

4. **Maintenance**
   - Repair State → City Relationships

### Manual Term Editing

You can manually edit terms through the WordPress admin:
- **States:** Listings → States
- **Cities:** Listings → Cities (parent State selection field)
- **Neighborhoods:** Listings → Neighborhoods (parent City selection field)
- **Business Types:** Listings → Business Types
- **Property Types:** Listings → Property Types

## 📊 Default Property Types

1. Commercial House (Casa Comercial)
2. Houses & Townhouses (Casas & Sobrados)
3. Apartments (Apartamentos)
4. Condominium House (Casa em Condomínio)
5. Penthouse (Cobertura)
6. Farms (Fazendas)
7. Flat
8. Warehouse/Storage (Galpão/Depósito)
9. Garages (Garagens)
10. Studio (Kitnets)
11. Lofts
12. Commercial Point (Ponto Comercial)
13. Commercial Room (Sala Comercial)
14. Ranches & Country Houses (Sítios & Chácaras)
15. Land (Terrenos)

## 🐛 Debug and Logs

The plugin includes debug logs that can be viewed if `WP_DEBUG` is enabled:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

**Generated logs:**
- State imports
- City imports
- AJAX requests (get_cidades_by_estado, get_bairros_by_cidade)
- Relationship repairs
- Browser console (JavaScript)

## 🔍 Troubleshooting

### Cities don't appear when selecting State

**Cause:** `parent_estado` relationship not configured

**Solution:**
1. Go to Settings → Location Taxonomies
2. Scroll to "Repair Relationships"
3. Click "Repair Relationships Now"
4. Wait for success message

### Form doesn't submit to correct URL

**Check:** The `action_url` shortcode attribute

```php
[listeo_search_form_tabs action_url="/listings/"]
```

### Bootstrap Select doesn't work

**Cause:** jQuery not loaded or version conflict

**Solution:** Verify jQuery is loaded before the plugin:
```php
wp_enqueue_script('jquery');
```

### AJAX returns 403 error

**Cause:** Invalid or expired nonce

**Solution:** Clear browser cache and reload the page

## 📁 File Structure

```
listeo-location-taxonomies/
├── assets/
│   ├── css/
│   │   └── admin-styles.css
│   └── js/
│       └── location-filters.js
├── includes/
│   ├── class-admin.php          # Administrative interface
│   ├── class-search-fields.php  # Shortcodes and AJAX
│   └── class-taxonomies.php     # Taxonomy registration
├── listeo-location-taxonomies.php  # Main file
├── README.md                    # This file (English)
├── README.pt-BR.md             # Portuguese version
└── IMPLEMENTACAO.md            # Implementation documentation
```

## 🤝 Contributing

To contribute to plugin development:
1. Fork the project
2. Create a branch for your feature (`git checkout -b feature/MyFeature`)
3. Commit your changes (`git commit -m 'Add MyFeature'`)
4. Push to the branch (`git push origin feature/MyFeature`)
5. Open a Pull Request

## 📄 License

This plugin is licensed under GPL v2 or later.

## 📞 Support

For questions and support, contact [Fastwp](mailto:contact@fastwp.com)

---

**Developed with ❤️ for the Listeo theme**

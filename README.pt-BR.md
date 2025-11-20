# Listeo Location Taxonomies

**Versão:** 2.0.0
**Autor:** Fastwp
**Requer:** WordPress 5.0+
**Testado até:** WordPress 6.4
**Licença:** GPL v2 ou posterior

## 📋 Descrição

Plugin WordPress para gerenciamento de taxonomias de localização hierárquicas para o tema Listeo. Adiciona suporte completo para Estados, Cidades e Bairros brasileiros com importação automática de dados do IBGE, além de taxonomias para classificação de imóveis (Tipo de Negócio e Tipo de Imóvel).

## ✨ Recursos Principais

### Taxonomias de Localização
- **Estado** - Estados brasileiros (26 estados + DF)
- **Cidade** - Municípios brasileiros (5.570+ cidades)
- **Bairro** - Bairros/distritos por cidade

### Taxonomias de Classificação
- **Tipo de Negócio** - Venda, Aluguel
- **Tipo de Imóvel** - 15 tipos de propriedades (Casa Comercial, Apartamentos, Terrenos, etc.)

### Funcionalidades
- ✅ Importação automática via API do IBGE
- ✅ Relacionamento hierárquico (Estado → Cidade → Bairro)
- ✅ Filtros em cascata (AJAX)
- ✅ Múltiplos formatos de formulários de busca
- ✅ Integração nativa com Listeo
- ✅ Ferramenta de reparo automático de relacionamentos
- ✅ Interface administrativa completa
- ✅ Suporte a hide_empty (só mostra termos com listings)

## 🚀 Instalação

1. Faça upload da pasta `listeo-location-taxonomies` para `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Vá em **Configurações → Localização de Taxonomias**
4. Importe os dados do IBGE (veja seção abaixo)

## 📥 Importação de Dados IBGE

### Passo 1: Importar Estados
1. Vá em **Configurações → Localização de Taxonomias**
2. Clique em **"Importar Estados do IBGE"**
3. Aguarde a confirmação de importação (27 estados)

### Passo 2: Importar Cidades por Estado
1. Selecione um estado no dropdown
2. Clique em **"Importar Cidades"**
3. Repita para cada estado desejado
4. Cidades são importadas com relacionamento automático ao estado

### Passo 3: Popular Taxonomias de Imóveis
1. Clique em **"Popular Tipos de Negócio"** (Venda, Aluguel)
2. Clique em **"Popular Tipos de Imóvel"** (15 tipos de propriedades)

### Passo 4: Reparar Relacionamentos (se necessário)
Se as cidades não aparecem ao selecionar um estado:
1. Role até a seção **"Reparar Relacionamentos"**
2. Clique em **"Reparar Relacionamentos Agora"**
3. Aguarde a conclusão do processo

## 🎨 Shortcodes Disponíveis

### Formulários Completos

#### Formulário com Abas Horizontais
```php
[listeo_search_form_tabs]
```
Exibe formulário com 3 abas (Tudo, Venda, Aluguel) em layout horizontal.

**Campos por aba:**
- Estado (cascata)
- Cidade (cascata)
- Bairro (cascata)
- Tipo de Imóvel
- Botão Buscar

#### Formulário com Abas Verticais
```php
[listeo_search_form_tabs_vertical]
```
Exibe formulário com 3 abas em layout vertical (navegação lateral).

#### Formulário Simples com Submit
```php
[listeo_location_search_form]
```
Formulário básico com todos os campos e botão de busca.

**Atributos opcionais:**
```php
[listeo_location_search_form
    action_url="/listings/"
    button_text="Buscar Agora"
    placeholder_estado="Selecione o Estado"
    placeholder_cidade="Selecione a Cidade"
    placeholder_bairro="Selecione o Bairro"]
```

### Campos Individuais

#### Campo Estado
```php
[listeo_estado_field placeholder="Estado"]
```

#### Campo Cidade
```php
[listeo_cidade_field placeholder="Cidade"]
```

#### Campo Bairro
```php
[listeo_bairro_field placeholder="Bairro"]
```

#### Campo Tipo de Negócio
```php
[listeo_tipo_de_negocio_field placeholder="Tipo de Negócio"]
```

#### Campo Tipo de Imóvel
```php
[listeo_tipo_de_imovel_field placeholder="Tipo de Imóvel"]
```

## 🔧 Integração com Listeo

### Adicionando Campos ao Formulário de Busca do Tema

No seu tema child (`listeo-child/functions.php`):

```php
// Adicionar campos antes do botão de submit
add_action('listeo_before_search_form_submit', 'add_location_fields');
function add_location_fields() {
    echo '<div class="col-md-2">';
    echo '<h5>Tipo de Negócio</h5>';
    echo do_shortcode('[listeo_tipo_de_negocio_field]');
    echo '</div>';

    echo '<div class="col-md-3">';
    echo '<h5>Tipo de Imóvel</h5>';
    echo do_shortcode('[listeo_tipo_de_imovel_field]');
    echo '</div>';

    echo '<div class="col-md-2">';
    echo '<h5>Estado</h5>';
    echo do_shortcode('[listeo_estado_field]');
    echo '</div>';

    echo '<div class="col-md-2">';
    echo '<h5>Cidade</h5>';
    echo do_shortcode('[listeo_cidade_field]');
    echo '</div>';

    echo '<div class="col-md-3">';
    echo '<h5>Bairro</h5>';
    echo do_shortcode('[listeo_bairro_field]');
    echo '</div>';
}

// Adicionar filtros à query de busca
add_filter('listeo_core_search_query_args', 'add_location_filters');
function add_location_filters($args) {
    $tax_query = isset($args['tax_query']) ? $args['tax_query'] : array('relation' => 'AND');

    // Filtro por Tipo de Negócio
    if (!empty($_GET['tax-tipo_de_negocio'])) {
        $tax_query[] = array(
            'taxonomy' => 'tipo_de_negocio',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['tax-tipo_de_negocio']),
        );
    }

    // Filtro por Tipo de Imóvel
    if (!empty($_GET['tax-tipo_de_imovel'])) {
        $tax_query[] = array(
            'taxonomy' => 'tipo_de_imovel',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['tax-tipo_de_imovel']),
        );
    }

    // Filtro por Estado
    if (!empty($_GET['tax-estado'])) {
        $tax_query[] = array(
            'taxonomy' => 'estado',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['tax-estado']),
        );
    }

    // Filtro por Cidade
    if (!empty($_GET['tax-cidade'])) {
        $tax_query[] = array(
            'taxonomy' => 'cidade',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['tax-cidade']),
        );
    }

    // Filtro por Bairro
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

## 🔗 Estrutura de Relacionamentos

```
Estado (estado-sc)
  └── Cidade (florianopolis-sc)
        ├── Meta: parent_estado = ID do Estado
        └── Bairro (centro-florianopolis-sc)
              └── Meta: parent_cidade = ID da Cidade

Listing (Post Type)
  ├── Taxonomia: estado
  ├── Taxonomia: cidade
  ├── Taxonomia: bairro
  ├── Taxonomia: tipo_de_negocio
  └── Taxonomia: tipo_de_imovel
```

## 🎯 AJAX e Cascata

O plugin usa AJAX para carregar dinamicamente:
- Cidades ao selecionar um Estado
- Bairros ao selecionar uma Cidade

**Endpoints AJAX:**
- `get_cidades_by_estado` - Retorna cidades de um estado
- `get_bairros_by_cidade` - Retorna bairros de uma cidade

**JavaScript Global:**
```javascript
// Objeto disponível globalmente
listeoLocationData = {
    ajaxurl: 'https://seusite.com/wp-admin/admin-ajax.php',
    nonce: 'xxxxx',
    strings: {
        select_state: 'Selecione um Estado',
        select_city: 'Selecione uma Cidade',
        select_neighborhood: 'Selecione um Bairro',
        loading: 'Carregando...'
    }
}
```

## 🛠️ Ferramentas Administrativas

### Página de Configurações
**Local:** Configurações → Localização de Taxonomias

**Funcionalidades disponíveis:**
1. **Importação IBGE**
   - Importar Estados
   - Importar Cidades por Estado

2. **Gerenciamento em Massa**
   - Deletar todos os Estados
   - Deletar todas as Cidades
   - Deletar todos os Bairros

3. **Popular Taxonomias**
   - Popular Tipos de Negócio (2 termos)
   - Popular Tipos de Imóvel (15 termos)

4. **Manutenção**
   - Reparar Relacionamentos Estado → Cidade

### Edição Manual de Termos

Você pode editar manualmente os termos através do WordPress admin:
- **Estados:** Listings → Estados
- **Cidades:** Listings → Cidades (campo de seleção de Estado pai)
- **Bairros:** Listings → Bairros (campo de seleção de Cidade pai)
- **Tipo de Negócio:** Listings → Tipos de Negócio
- **Tipo de Imóvel:** Listings → Tipos de Imóvel

## 📊 Tipos de Imóvel Padrão

1. Casa Comercial
2. Casas & Sobrados
3. Apartamentos
4. Casa em Condomínio
5. Cobertura
6. Fazendas
7. Flat
8. Galpão/Depósito
9. Garagens
10. Kitnets
11. Lofts
12. Ponto Comercial
13. Sala Comercial
14. Sítios & Chácaras
15. Terrenos

## 🐛 Debug e Logs

O plugin inclui logs de debug que podem ser visualizados se `WP_DEBUG` estiver ativado:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

**Logs gerados:**
- Importação de Estados
- Importação de Cidades
- AJAX requests (get_cidades_by_estado, get_bairros_by_cidade)
- Reparo de relacionamentos
- Console do navegador (JavaScript)

## 🔍 Solução de Problemas

### Cidades não aparecem ao selecionar Estado

**Causa:** Relacionamento `parent_estado` não configurado

**Solução:**
1. Vá em Configurações → Localização de Taxonomias
2. Role até "Reparar Relacionamentos"
3. Clique em "Reparar Relacionamentos Agora"
4. Aguarde a mensagem de sucesso

### Formulário não envia para URL correta

**Verificar:** O atributo `action_url` do shortcode

```php
[listeo_search_form_tabs action_url="/listings/"]
```

### Bootstrap Select não funciona

**Causa:** jQuery não carregado ou conflito de versão

**Solução:** Verificar que jQuery está carregado antes do plugin:
```php
wp_enqueue_script('jquery');
```

### AJAX retorna erro 403

**Causa:** Nonce inválido ou expirado

**Solução:** Limpar cache do navegador e recarregar a página

## 📁 Estrutura de Arquivos

```
listeo-location-taxonomies/
├── assets/
│   ├── css/
│   │   └── admin-styles.css
│   └── js/
│       └── location-filters.js
├── includes/
│   ├── class-admin.php          # Interface administrativa
│   ├── class-search-fields.php  # Shortcodes e AJAX
│   └── class-taxonomies.php     # Registro de taxonomias
├── listeo-location-taxonomies.php  # Arquivo principal
├── README.md                    # Este arquivo (English)
├── README.pt-BR.md             # Este arquivo (Português)
└── IMPLEMENTACAO.md            # Documentação de implementação
```

## 🤝 Contribuindo

Para contribuir com o desenvolvimento do plugin:
1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

## 📄 Licença

Este plugin é licenciado sob GPL v2 ou posterior.

## 📞 Suporte

Para questões e suporte, entre em contato com [Fastwp](mailto:contato@fastwp.com.br)

---

**Desenvolvido com ❤️ para o tema Listeo**

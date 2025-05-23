<?php
/*
Plugin Name: Sufficit Json Exporter
Plugin URI:  https://github.com/sufficit/sufficit-wordpress/tree/main/sufficit-json-exporter
Description: Sufficit JSON Exporter - Exporta posts para JSON com hash de versao (X-Content-Hash) baseado em titulo, conteudo e permalink. Aciona webhooks em alteracoes.
Version:     1.3.6 // Reintroduzindo lock com Transients para evitar multiplos disparos.
Author:      Hugo Castro de Deco
Author URI:  https://github.com/sufficit
License:     GPL2
License URI: https://www.gnu.nu/licenses/gpl-2.0.html
Text Domain: sufficit-json-exporter
Domain Path: /languages/
*/

// Segurança: Garante que o arquivo do plugin não seja acessado diretamente.
// Se ABSPATH não estiver definido, significa que o WordPress não está carregado,
// impedindo a execução direta do script.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Termina a execução do script para prevenir acesso não autorizado.
}

/**
 * NOTA IMPORTANTE SOBRE CODIFICAÇÃO E CARACTERES ESPECIAIS NO CÓDIGO:
 * Ao escrever strings diretamente no código PHP ou HTML (especialmente para labels, descrições, etc.),
 * é uma boa prática evitar caracteres acentuados ou especiais (como 'ç', 'ã', 'õ', 'á', 'é').
 * Isso previne potenciais problemas de codificação de caracteres que podem surgir
 * em diferentes ambientes de servidor ou configurações de banco de dados, resultando em caracteres "quebrados".
 *
 * Para textos que precisam ser traduzidos ou que contêm caracteres especiais, a abordagem recomendada é:
 * 1.  **Internacionalização (i18n):** Usar funções de internacionalização do WordPress (como `__()` ou `_e()`)
 * em conjunto com um `Text Domain`. Isso permite que as strings sejam traduzidas para outros idiomas
 * e o WordPress cuida da codificação correta.
 * 2.  **Entidades HTML:** Para conteúdo dinâmico ou saídas específicas, usar `html_entity_decode()`
 * ou `esc_html()` com a codificação correta pode ser necessário, como feito na lógica de exportação.
 *
 * Neste plugin, embora alguns textos de interface ainda usem caracteres especiais diretamente para simplificar,
 * a maior parte da lógica de conteúdo (títulos, conteúdos de posts) já lida com decodificação.
 * O foco principal é garantir que o *arquivo PHP* esteja salvo em **UTF-8 SEM BOM** para a compatibilidade global.
 */

// --- CONFIGURAÇÕES GERAIS E GARANTIA DE CODIFICAÇÃO UTF-8 ---

/**
 * Tenta forçar a codificação UTF-8 para funções de string multi-byte e para o ambiente PHP em geral.
 * Esta é uma medida proativa. A codificação real pode depender da configuração do servidor,
 * mas ajuda a mitigar problemas. O mais crucial é que o arquivo PHP esteja salvo em UTF-8 SEM BOM.
 */
if ( function_exists( 'mb_internal_encoding' ) ) {
    mb_internal_encoding( 'UTF-8' ); // Define a codificação interna para funções multi-byte (mb_string).
}
if ( function_exists( 'mb_regex_encoding' ) ) {
    mb_regex_encoding( 'UTF-8' ); // Define a codificação para expressões regulares multi-byte.
}
ini_set( 'default_charset', 'UTF-8' ); // Tenta definir a codificação padrão para PHP.

/**
 * Carrega o textdomain para internacionalização (traduções do plugin).
 * Isso permite que todas as strings visíveis na interface do plugin sejam traduzidas.
 * O `dirname( plugin_basename( __FILE__ ) ) . '/languages'` define o caminho para a pasta de traduções.
 */
function sufficit_json_exporter_load_textdomain() {
    load_plugin_textdomain( 'sufficit-json-exporter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'sufficit_json_exporter_load_textdomain' );

/**
 * Constantes para os nomes das opções salvas no banco de dados do WordPress.
 * Usar constantes evita erros de digitação e torna o código mais legível e manutenível.
 * Isso também centraliza os nomes das chaves de opção.
 */
define( 'SUFF_JSON_EXPORTER_TOKEN_OPTION', 'suff_json_exporter_auth_token' );             // Chave para armazenar o token de autenticação.
define( 'SUFF_JSON_EXPORTER_CHARSET_OPTION', 'suff_json_exporter_output_charset' );       // Chave para armazenar a codificação de saída JSON.
define( 'SUFF_JSON_EXPORTER_LAST_HASH_OPTION', 'suff_json_exporter_last_content_hash' );   // Chave para armazenar o último hash do conteúdo.

/**
 * Constantes para as opções de configuração do webhook.
 */
define( 'SUFF_JSON_EXPORTER_WEBHOOK_URL_OPTION', 'suff_json_exporter_webhook_url' );       // Chave para armazenar a URL do webhook.
define( 'SUFF_JSON_EXPORTER_WEBHOOK_ENABLED_OPTION', 'suff_json_exporter_webhook_enabled' ); // Chave para armazenar o status de habilitação do webhook.


// --- CONFIGURAÇÃO DO ENDPOINT REST API ---

/**
 * Função para registrar um endpoint REST personalizado para a exportação de posts.
 * Este endpoint será acessível publicamente, mas protegido por um token de autenticação.
 */
function sufficit_json_exporter_registrar_endpoint() {
    register_rest_route(
        'sufficit-json-exporter/v1', // Namespace: Identifica unicamente a API do plugin (ex: wp-json/sufficit-json-exporter/v1).
        '/posts/',               // Rota: Define o caminho específico para o endpoint (ex: wp-json/sufficit-json-exporter/v1/posts).
        array(
            'methods'             => 'GET', // Métodos HTTP aceitos (apenas GET para exportação de dados).
            'callback'            => 'sufficit_json_exporter_gerar_posts', // Função PHP que será executada quando o endpoint for acessado.
            'permission_callback' => 'sufficit_json_exporter_autenticar_requisitante', // Função para verificar a permissão/autenticação antes de executar o callback.
            'args'                => array(), // Argumentos esperados na URL (o token será tratado como um parâmetro GET, não um argumento formal da rota).
        )
    );
}
// Hook 'rest_api_init' é o momento correto para registrar endpoints REST.
add_action( 'rest_api_init', 'sufficit_json_exporter_registrar_endpoint' );

/**
 * Função de callback para autenticar a requisição ao endpoint REST via token GET.
 * Garante que apenas requisitantes com o token correto possam acessar os dados exportados.
 *
 * @param WP_REST_Request $request O objeto da requisição REST atual, contém os parâmetros da requisição.
 * @return bool|WP_Error Retorna 'true' se o token fornecido for válido, ou um objeto WP_Error se for inválido ou não configurado.
 */
function sufficit_json_exporter_autenticar_requisitante( WP_REST_Request $request ) {
    $token_param = $request->get_param( 'token' ); // Obtém o parâmetro 'token' da URL da requisição.
    $saved_token = get_option( SUFF_JSON_EXPORTER_TOKEN_OPTION ); // Obtém o token de autenticação salvo nas configurações do plugin.

    // 1. Verifica se um token foi sequer configurado nas opções do plugin.
    if ( empty( $saved_token ) ) {
        error_log( 'DEBUG: suff_json_exporter: Tentativa de acesso ao endpoint sem token configurado.' );
        return new WP_Error( 'suff_json_exporter_no_token_set', 'Token de autenticacao nao configurado no plugin.', array( 'status' => 401 ) );
    }

    // 2. Compara o token fornecido na requisição com o token salvo.
    if ( $token_param === $saved_token ) {
        error_log( 'DEBUG: suff_json_exporter: Token de autenticacao valido. Acesso permitido.' );
        return true; // Autenticação bem-sucedida.
    }

    // 3. Se os tokens não corresponderem, retorna um erro de autenticação.
    error_log( 'DEBUG: suff_json_exporter: Token de autenticacao invalido: ' . ( ! empty( $token_param ) ? $token_param : 'NULO/VAZIO' ) );
    return new WP_Error( 'suff_json_exporter_invalid_token', 'Token de autenticacao invalido.', array( 'status' => 401 ) );
}

/**
 * Callback principal para o endpoint REST que gera a exportação dos posts em JSON.
 * Consulta o banco de dados do WordPress, seleciona os posts publicados e formata seus dados
 * em um array JSON. Também calcula e inclui um hash do conteúdo no cabeçalho HTTP (X-Content-Hash)
 * para permitir que os consumidores da API verifiquem se o conteúdo mudou sem baixar todo o JSON.
 * O hash é calculado de forma a ser consistente, focando apenas em título, conteúdo puro e permalink.
 *
 * @return WP_REST_Response Os dados dos posts em formato JSON, com cabeçalho X-Content-Hash.
 */
function sufficit_json_exporter_gerar_posts() {
    error_log( 'DEBUG: suff_json_exporter: Gerando exportacao JSON para o endpoint REST.' );

    // Define os argumentos para a consulta de posts.
    $args = array(
        'posts_per_page' => -1,          // `-1` garante que todos os posts correspondentes sejam retornados (sem paginação).
        'post_type'      => 'post',     // Limita a consulta apenas aos posts do tipo 'post' (padrão do WordPress).
        'post_status'    => 'publish',  // Retorna apenas posts que estão no status 'publicado'.
        // Ordenação crucial para a CONSISTÊNCIA do hash:
        // A ordem dos posts no JSON afeta o hash final. Ordenar por 'modified' (descendente)
        // e depois por 'ID' (ascendente) garante uma ordem estável e previsível,
        // mesmo que posts sejam modificados no mesmo segundo.
        'orderby'        => array(
            'modified' => 'DESC', // Posts mais recentemente modificados primeiro.
            'ID'       => 'ASC',  // Em caso de mesma data de modificação, ordena por ID.
        ),
    );

    $posts = get_posts( $args ); // Executa a consulta de posts com os argumentos definidos.

    $posts_data = array(); // Array para armazenar os dados COMPLETOS de cada post para a resposta JSON.
    $hash_data_array = array(); // Array para armazenar APENAS os dados relevantes para o cálculo do hash.

    // Obtém a codificação de saída JSON configurada pelo usuário nas opções do plugin (padrão: UTF-8).
    $output_charset = get_option( SUFF_JSON_EXPORTER_CHARSET_OPTION, 'UTF-8' );

    // Itera sobre cada post retornado da consulta para formatar os dados.
    foreach ( $posts as $post ) {
        // Obtém o título do post.
        $post_title = get_the_title( $post->ID );
        // Obtém o conteúdo e o resumo do post diretamente do objeto $post para consistência.
        $post_content = $post->post_content;
        $post_excerpt = $post->post_excerpt;
        // Obtém o permalink do post.
        $post_permalink = get_permalink( $post->ID );

        // Processamento para o ARRAY DE DADOS COMPLETO ($posts_data):
        // `html_entity_decode()`: Converte entidades HTML (ex: &amp;) de volta para caracteres reais (ex: &).
        // `ENT_QUOTES | ENT_HTML5`: Lida com aspas e usa regras de HTML5.
        // `$output_charset`: Garante que a decodificação use o charset correto.
        $processed_title_full = html_entity_decode( $post_title, ENT_QUOTES | ENT_HTML5, $output_charset );
        // `apply_filters('the_content', ...)`: Aplica todos os filtros padrão do WordPress ao conteúdo,
        // o que geralmente significa que o HTML será processado (parágrafos, shortcodes, etc.).
        $processed_content_full = apply_filters( 'the_content', $post_content );
        $processed_excerpt_full = apply_filters( 'the_excerpt', $post_excerpt );

        // Adiciona os dados COMPLETOS do post ao array principal para a resposta JSON.
        $posts_data[] = array(
            'ID'            => $post->ID,
            'title'         => $processed_title_full,
            'content'       => $processed_content_full,
            'excerpt'       => $processed_excerpt_full,
            'date_published'=> $post->post_date,       // Data de publicação.
            'date_modified' => $post->post_modified,    // Data da última modificação.
            'slug'          => $post->post_name,       // Slug do post.
            'permalink'     => $post_permalink,         // URL permanente do post.
            'author_id'     => $post->post_author,
            'author_name'   => get_the_author_meta( 'display_name', $post->post_author ), // Nome do autor.
            'thumbnail_url' => get_the_post_thumbnail_url( $post->ID, 'full' ), // URL da imagem destacada.
            'categories'    => wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) ), // Nomes das categorias.
            'tags'          => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ),       // Nomes das tags.
        );

        // Processamento para o ARRAY DE DADOS DO HASH ($hash_data_array):
        // É CRÍTICO que os dados para o hash sejam o mais consistentes possível,
        // evitando variações que não indicam uma mudança de conteúdo real.
        $processed_title_hash = html_entity_decode( $post_title, ENT_QUOTES | ENT_HTML5, $output_charset );
        // `wp_strip_all_tags()`: Remove *todas* as tags HTML do conteúdo. Isso é essencial para o hash,
        // pois filtros de conteúdo (como 'the_content') podem adicionar/remover tags HTML invisíveis ou
        // inconsistentes que alterariam o hash sem uma mudança real de texto.
        $processed_content_hash = wp_strip_all_tags( $post_content );
        $processed_permalink_hash = get_permalink( $post->ID );

        // Adiciona APENAS os dados relevantes e consistentes para o cálculo do hash.
        // A ordem das chaves neste array também é importante para a consistência do hash final.
        $hash_data_array[] = array(
            'title'     => $processed_title_hash,
            'content'   => $processed_content_hash,
            'permalink' => $processed_permalink_hash,
        );
    }

    // Converte o array `$hash_data_array` (apenas dados relevantes para o hash) em uma string JSON.
    // `JSON_UNESCAPED_UNICODE`: Garante que caracteres Unicode (como acentos) não sejam escapados (\uXXXX).
    // `JSON_UNESCAPED_SLASHES`: Garante que barras (/) não sejam escapadas (\/).
    // Ambos são cruciais para a consistência do hash, já que a string JSON gerada será sempre a mesma para o mesmo conteúdo.
    $json_for_hash = wp_json_encode( $hash_data_array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

    // Calcula o hash SHA256 da string JSON. SHA256 é um algoritmo de hash criptográfico.
    $content_hash = hash( 'sha256', $json_for_hash );

    // Cria o objeto `WP_REST_Response` com os dados COMPLETOS dos posts ($posts_data) e o status HTTP 200 (OK).
    // O array `$posts_data` será automaticamente convertido em JSON pela API REST.
    $response = new WP_REST_Response( $posts_data, 200 );

    // Define o cabeçalho HTTP 'Content-Type' explicitamente para indicar que a resposta é JSON e seu charset.
    $response->header( 'Content-Type', 'application/json; charset=' . $output_charset );

    // Adiciona o hash do conteúdo como um cabeçalho HTTP personalizado 'X-Content-Hash'.
    // Isso permite que o cliente verifique a integridade ou a mudança do conteúdo sem baixar todo o corpo da resposta.
    $response->header( 'X-Content-Hash', $content_hash );

    // Salva o último hash gerado no banco de dados. Este é o hash que será comparado
    // nas próximas vezes que um post for salvo para determinar se o webhook deve ser disparado.
    update_option( SUFF_JSON_EXPORTER_LAST_HASH_OPTION, $content_hash );
    error_log( 'DEBUG: suff_json_exporter: Hash do conteudo atualizado no DB apos geracao do JSON: ' . $content_hash );

    return $response; // Retorna a resposta REST.
}

// --- FUNÇÕES PARA A PÁGINA DE CONFIGURAÇÕES DO PLUGIN NO ADMIN ---

/**
 * Adiciona a página de configurações do plugin ao menu "Configurações" do WordPress Admin.
 */
function sufficit_json_exporter_adicionar_pagina_admin() {
    add_options_page(
        'Sufficit JSON Exporter Configuracoes', // Título da página (aparece na aba do navegador).
        'JSON Exporter',                     // Título no menu lateral do WordPress.
        'manage_options',                    // Capacidade mínima necessária para acessar a página (usuários com permissão de 'manage_options').
        'sufficit-json-exporter',            // Slug único da página (usado na URL e como identificador).
        'sufficit_json_exporter_pagina_config' // Função de callback que renderiza o conteúdo HTML da página.
    );
}
// Hook 'admin_menu' é o momento correto para adicionar páginas ao menu administrativo.
add_action( 'admin_menu', 'sufficit_json_exporter_adicionar_pagina_admin' );

/**
 * Adiciona um link direto para a página de "Configurações" na lista de plugins instalados.
 * Isso melhora a usabilidade, permitindo acesso rápido às configurações diretamente da tela de Plugins.
 *
 * @param array $links Array de links de ação para o plugin (ex: Ativar, Desativar, Editar).
 * @return array O array de links modificado com o link "Configurações" adicionado.
 */
function sufficit_json_exporter_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=sufficit-json-exporter">' . 'Configuracoes' . '</a>';
    // `array_unshift()` adiciona o novo link no início do array de links existentes.
    array_unshift( $links, $settings_link );
    return $links;
}
// Obtém o nome base do arquivo do plugin (ex: meu-plugin/meu-plugin.php) para o filtro correto.
// Atenção: Esta linha foi ajustada para refletir o nome do arquivo 'sufficit-json-exporter.php'.
$plugin = plugin_basename( __FILE__ );
// Hook 'plugin_action_links_{$plugin_file}' permite adicionar links personalizados na lista de plugins.
add_filter( "plugin_action_links_$plugin", 'sufficit_json_exporter_add_settings_link' );


/**
 * Registra as configurações do plugin na API de Settings do WordPress.
 * Embora o HTML dos campos seja impresso DIRETAMENTE na função `pagina_config` (uma solução
 * para problemas de renderização observados), o registro com `register_setting()` ainda
 * é ESSENCIAL para que o WordPress processe, saneie e salve os valores dos campos
 * no banco de dados quando o formulário é submetido.
 */
function sufficit_json_exporter_registrar_settings() {
    // 1. Registra a opção do token de autenticação.
    register_setting(
        'suff_json_exporter_settings_group', // Nome do grupo de configurações (usado com `settings_fields()`).
        SUFF_JSON_EXPORTER_TOKEN_OPTION,     // Nome da opção no banco de dados (`wp_options` table).
        array(
            'type'              => 'string',            // Tipo de dado esperado (string).
            'sanitize_callback' => 'sanitize_text_field', // Função para limpar e validar o input (remove tags, etc.).
            'default'           => '',                  // Valor padrão se a opção não estiver definida.
            'show_in_rest'      => false,               // Não expõe esta opção via REST API.
        )
    );

    // 2. Registra a opção de codificação de saída JSON.
    register_setting(
        'suff_json_exporter_settings_group',
        SUFF_JSON_EXPORTER_CHARSET_OPTION,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'UTF-8',
            'show_in_rest'      => false,
        )
    );

    // 3. Registra a opção para o último hash de conteúdo gerado.
    // Esta opção é atualizada automaticamente pelo plugin, não pelo usuário na interface.
    register_setting(
        'suff_json_exporter_settings_group',
        SUFF_JSON_EXPORTER_LAST_HASH_OPTION,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
            'show_in_rest'      => false,
        )
    );

    // 4. Registra a opção da URL do webhook.
    register_setting(
        'suff_json_exporter_settings_group',
        SUFF_JSON_EXPORTER_WEBHOOK_URL_OPTION,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw', // Garante que a URL seja segura e válida.
            'default'           => '',
            'show_in_rest'      => false,
        )
    );

    // 5. Registra a opção de habilitação/desabilitação do webhook (checkbox).
    register_setting(
        'suff_json_exporter_settings_group',
        SUFF_JSON_EXPORTER_WEBHOOK_ENABLED_OPTION,
        array(
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean', // Garante que o valor seja um booleano (true/false).
            'default'           => false,
            'show_in_rest'      => false,
        )
    );

    /**
     * NOTA SOBRE `add_settings_section()`:
     * As funções `add_settings_section()` e `add_settings_field()` são normalmente usadas
     * para ESTRUTURAR e RENDERIZAR o HTML da página de configurações. No entanto,
     * neste plugin, devido a um problema de renderização anterior em alguns ambientes WP,
     * o HTML dos campos é gerado DIRETAMENTE na função `sufficit_json_exporter_pagina_config()`.
     *
     * Mantemos o `add_settings_section()` aqui apenas para o registro lógico
     * com a API de Settings do WordPress, o que é necessário para `settings_fields()`
     * funcionar corretamente e para o processamento de submissão do formulário.
     */
    add_settings_section(
        'suff_json_exporter_general_section', // ID único da seção.
        'Configuracoes de Autenticacao e Codificacao', // Título visível da seção.
        null, // Não há função de callback para descrição da seção, pois o HTML é direto.
        'sufficit-json-exporter' // Slug da página onde esta seção aparece.
    );
}
// Hook 'admin_init' é o momento correto para registrar as configurações.
add_action( 'admin_init', 'sufficit_json_exporter_registrar_settings' );


/**
 * Função de callback principal que renderiza o conteúdo HTML da página de configurações do plugin.
 *
 * ABORDAGEM DE RENDERIZAÇÃO DIRETA:
 * Como mencionado anteriormente, esta função imprime o HTML dos campos do formulário diretamente.
 * Isso foi uma solução para contornar problemas onde os campos não eram exibidos corretamente
 * usando o método tradicional de `add_settings_field()` em certos ambientes WordPress.
 * A funcionalidade de salvar os dados ainda depende do `register_setting()` (definido acima)
 * e do `settings_fields()` e `submit_button()` (chamados abaixo), que são funções padrão do WordPress.
 */
function sufficit_json_exporter_pagina_config() {
    // Garante que o cabeçalho HTTP 'Content-Type' seja enviado como UTF-8,
    // prevenindo problemas de codificação de caracteres na própria página de administração.
    if ( ! headers_sent() ) {
        header( 'Content-Type: text/html; charset=UTF-8' );
    }

    // Obtém os valores atuais das opções salvas no banco de dados do WordPress.
    $token = get_option( SUFF_JSON_EXPORTER_TOKEN_OPTION );
    $current_charset = get_option( SUFF_JSON_EXPORTER_CHARSET_OPTION, 'UTF-8' );
    $webhook_url = get_option( SUFF_JSON_EXPORTER_WEBHOOK_URL_OPTION );
    $webhook_enabled = get_option( SUFF_JSON_EXPORTER_WEBHOOK_ENABLED_OPTION );
    $last_saved_hash_display = get_option( SUFF_JSON_EXPORTER_LAST_HASH_OPTION, 'N/A' ); // Exibe 'N/A' se nenhum hash foi salvo ainda.

    // Constrói a URL base do endpoint REST do WordPress.
    $base_endpoint_url = get_rest_url() . 'sufficit-json-exporter/v1/posts/';
    // Constrói um exemplo de URL completa com o token atual para exibição na interface.
    // `esc_url()`: Limpa a URL para uso seguro em atributos HTML.
    $full_url_example = esc_url( $base_endpoint_url . '?token=' . $token );

    // Array de opções de codificação de caracteres para o campo <select> na interface.
    $charsets = array(
        'UTF-8'         => 'UTF-8 (Recomendado para JSON)',
        'ISO-8859-1'    => 'ISO-8859-1 (Latin-1 - Padrao da sua instalacao do WP se nao for UTF-8)',
        'Windows-1252'  => 'Windows-1252 (ANSI - Comum em sistemas Windows legados)',
    );
    // Adiciona a codificação padrão do próprio WordPress (se for diferente de UTF-8) à lista de opções,
    // para que o usuário possa escolher a codificação que seu site usa.
    $wp_charset = get_bloginfo( 'charset' );
    if ( ! array_key_exists( $wp_charset, $charsets ) && $wp_charset !== 'UTF-8' ) {
        $charsets[$wp_charset] = sprintf( 'Padrao do WordPress (%s)', $wp_charset );
    }
    // Ordena as opções de charset para facilitar a seleção, colocando UTF-8 e o charset atual no topo.
    uksort($charsets, function($a, $b) use ($current_charset) {
        if ($a === 'UTF-8') return -1; // Coloca UTF-8 como a primeira opção.
        if ($b === 'UTF-8') return 1;
        if ($a === $current_charset) return -1; // Coloca o charset atualmente selecionado em seguida.
        if ($b === $current_charset) return 1;
        return 0; // Para os demais, mantém a ordem natural (alfabética).
    });

    ?>
    <div class="wrap">
        <h1>Configuracoes do Sufficit JSON Exporter</h1>
        <form method="post" action="options.php">
            <?php
            // `settings_fields()`: ESSENCIAL. Imprime os campos ocultos de segurança (nonces, etc.)
            // e o nome do grupo de configurações (`suff_json_exporter_settings_group`).
            // Isso é necessário para que o formulário seja processado corretamente pelo WordPress.
            settings_fields( 'suff_json_exporter_settings_group' );
            ?>

            <h2>Configuracoes de Autenticacao e Codificacao</h2>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="suff-json-exporter-token-field">Token de Autenticacao</label></th>
                        <td>
                            <input type="text" id="suff-json-exporter-token-field" name="<?php echo esc_attr( SUFF_JSON_EXPORTER_TOKEN_OPTION ); ?>" value="<?php echo esc_attr( $token ); ?>" class="regular-text" placeholder="Gere um token seguro aqui" />
                            <p class="description">Este token sera usado para autenticar as requisicoes ao endpoint de exportacao. Mantenha-o seguro!</p>
                            <p class="description">URL do Endpoint: <code id="suff-json-exporter-endpoint-url"><?php echo esc_html( $full_url_example ); ?></code> <button type="button" class="button button-secondary" id="suff-json-exporter-copy-url">Copiar URL</button> <span id="suff-json-exporter-copy-message" style="display:none; margin-left: 10px; padding: 5px 10px; border-radius: 4px; background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; font-size: 0.9em; white-space: nowrap;">Copiado! Lembre-se de SALVAR as alteracoes antes de testar.</span></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="suff-json-exporter-charset-field">Codificacao de Saida JSON</label></th>
                        <td>
                            <select name="<?php echo esc_attr( SUFF_JSON_EXPORTER_CHARSET_OPTION ); ?>" id="suff-json-exporter-charset-field">
                                <?php foreach ( $charsets as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_charset, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Selecione a codificacao de caracteres que o plugin usara para decodificar entidades HTML no JSON exportado e para garantir que o texto seja exibido corretamente no painel do WordPress.</p>
                            <p class="description">O UTF-8 e o mais recomendado para JSON. Se os caracteres estiverem quebrados no JSON, tente ISO-8859-1 ou o padrao do WordPress.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Webhook de Alteracao de Post</th>
                        <td>
                            <input type="url" id="suff-json-exporter-webhook-url-field" name="<?php echo esc_attr( SUFF_JSON_EXPORTER_WEBHOOK_URL_OPTION ); ?>" value="<?php echo esc_attr( $webhook_url ); ?>" class="regular-text" placeholder="https://seu-webhook.com/url" />
                            <p class="description">Insira a URL do webhook que sera acionado em cada alteracao (adicao, edicao, remocao) de post. Uma requisicao POST sera enviada com os detalhes da alteracao.</p>
                            <label for="suff-json-exporter-webhook-enabled-field">
                                <input type="checkbox" id="suff-json-exporter-webhook-enabled-field" name="<?php echo esc_attr( SUFF_JSON_EXPORTER_WEBHOOK_ENABLED_OPTION ); ?>" value="1" <?php checked( true, $webhook_enabled ); ?> />
                                Habilitar Webhook
                            </label>
                            <p class="description">Ative para enviar notificacoes para a URL acima.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Ultimo Hash de Conteudo Exportado</th>
                        <td>
                            <code id="suff-json-exporter-last-hash"><?php echo esc_html( $last_saved_hash_display ); ?></code>
                            <p class="description">Este e o hash do conteudo completo dos posts (titulo, conteudo, permalink) da ultima exportacao. O webhook e acionado apenas quando este hash muda.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php
            // `submit_button()`: Gera o botão de submissão padrão do WordPress ("Salvar alterações").
            submit_button( 'Salvar alteracoes' );
            ?>
        </form>
    </div>
    <?php
}

/**
 * Garante que a página de plugins do WordPress também use o charset UTF-8 nos cabeçalhos HTTP.
 * Isso é uma medida de compatibilidade para ambientes que podem não estar configurados para UTF-8 por padrão,
 * evitando problemas de caracteres quebrados na lista de plugins.
 */
add_action('admin_head-plugins.php', 'sufficit_json_exporter_set_plugins_page_charset');
function sufficit_json_exporter_set_plugins_page_charset() {
    if ( ! headers_sent() ) { // Verifica se os cabeçalhos HTTP já foram enviados.
        header( 'Content-Type: text/html; charset=UTF-8' );
    }
}

/**
 * Enfileira o script JavaScript personalizado para a página de configurações do plugin.
 * O script é carregado apenas na página do plugin para otimização de performance,
 * evitando que seja carregado em todas as páginas do admin.
 *
 * @param string $hook_suffix O slug da página de administração atual.
 */
function sufficit_json_exporter_enqueue_admin_scripts( $hook_suffix ) {
    // Verifica se estamos na página de configurações do nosso plugin.
    if ( 'settings_page_sufficit-json-exporter' === $hook_suffix ) {
        wp_enqueue_script(
            'suff-json-exporter-admin-script',      // Handle (nome único) para o script.
            plugin_dir_url( __FILE__ ) . 'admin-script.js', // URL completa para o arquivo JS.
            array( 'jquery' ),                      // Dependências (garante que jQuery seja carregado antes).
            '1.0',                                  // Versão do script (para cache busting, atualize quando o JS mudar).
            true                                    // Carrega o script no footer (melhor para performance).
        );

        // `wp_localize_script()`: Permite passar dados do PHP para o JavaScript.
        // Isso é útil para informações dinâmicas como URLs ou opções do plugin.
        wp_localize_script(
            'suff-json-exporter-admin-script', // Handle do script para o qual os dados serão localizados.
            'suffExporterData',                // Nome do objeto JS global que conterá os dados (e.g., `suffExporterData.baseUrl`).
            array(
                'baseUrl' => get_rest_url() . 'sufficit-json-exporter/v1/posts/', // Passa a URL base do endpoint REST para o JS.
            )
        );
    }
}
// Hook 'admin_enqueue_scripts' é o momento correto para enfileirar scripts e estilos no admin.
add_action( 'admin_enqueue_scripts', 'sufficit_json_exporter_enqueue_admin_scripts' );


// --- FUNÇÃO PARA ENVIAR WEBHOOK ---

/**
 * Envia uma requisição POST para a URL do webhook configurada.
 * Utiliza `wp_remote_post()` para garantir que a requisição seja feita de forma segura,
 * assíncrona (não bloqueando a execução do WordPress) e compatível com as configurações do servidor.
 *
 * @param array $payload Dados a serem enviados no corpo da requisição POST (em formato JSON).
 * @return void
 */
function sufficit_json_exporter_send_webhook( $payload ) {
    $webhook_url = get_option( SUFF_JSON_EXPORTER_WEBHOOK_URL_OPTION );     // Obtém a URL do webhook das opções.
    $webhook_enabled = get_option( SUFF_JSON_EXPORTER_WEBHOOK_ENABLED_OPTION ); // Verifica se o webhook está habilitado.

    // Verifica se o webhook está habilitado E se a URL não está vazia.
    if ( $webhook_enabled && ! empty( $webhook_url ) ) {
        error_log( 'DEBUG: suff_json_exporter: Tentando enviar webhook para: ' . $webhook_url );
        $args = array(
            'body'        => json_encode( $payload ), // Converte o array de dados do payload para uma string JSON.
            'headers'     => array(
                'Content-Type' => 'application/json; charset=utf-8', // Define o cabeçalho Content-Type para JSON.
            ),
            'method'      => 'POST', // Tipo da requisição HTTP.
            'timeout'     => 10,     // Tempo limite em segundos para a requisição. Evita que a requisição demore demais.
            'blocking'    => false,  // Define como `false` para que a requisição seja assíncrona.
                                     // Isso significa que o WordPress não espera a resposta do webhook,
                                     // evitando que o processo de salvamento do post seja bloqueado.
            'data_format' => 'body', // Indica que o corpo da requisição é formatado como 'body'.
        );

        // Envia a requisição POST para a URL do webhook.
        // O resultado da requisição (sucesso ou falha) não é processado aqui devido ao `blocking` ser `false`.
        wp_remote_post( $webhook_url, $args );
        error_log( 'DEBUG: suff_json_exporter: Webhook enviado com sucesso (assincrono).' );
    } else {
        error_log( 'DEBUG: suff_json_exporter: Webhook desabilitado ou URL vazia. Nao enviando webhook.' );
    }
}

// --- HOOKS PARA ACIONAR O WEBHOOK EM EVENTOS DE POST ---

/**
 * Aciona o webhook quando um post é salvo (novo ou atualizado).
 * Utiliza o hook 'wp_after_insert_post' (disponível a partir do WP 5.6) por ser mais robusto,
 * pois é executado *depois* que o post e suas revisões foram completamente salvos no banco de dados.
 *
 * Inclui:
 * - Lógica de "lock" baseada em transients para evitar múltiplos disparos do webhook na mesma operação de salvamento.
 * - Verificações para ignorar revisões, autosaves e tipos/status de posts não monitorados.
 * - Recálculo e comparação de hash para disparar o webhook APENAS se o conteúdo relevante (título, conteúdo, permalink
 * de TODOS os posts publicados) tiver realmente mudado.
 *
 * @param int     $post_ID     O ID do post que foi salvo.
 * @param WP_Post $post        O objeto `WP_Post` do post salvo.
 * @param bool    $update      Verdadeiro se o post foi atualizado, falso se foi um novo post.
 * @param WP_Post $post_before Objeto `WP_Post` do post antes da inserção/atualização (disponível no WP 5.6+).
 */
function sufficit_json_exporter_on_post_save( $post_ID, $post, $update, $post_before ) {
    error_log( 'DEBUG: suff_json_exporter: sufficit_json_exporter_on_post_save acionada para Post ID: ' . $post_ID . ' (Update: ' . ($update ? 'true' : 'false') . ') via wp_after_insert_post.' );

    /**
     * Lógica de "Lock" (bloqueio) usando Transients:
     * Este é um mecanismo CRÍTICO para evitar múltiplos disparos de webhook na mesma operação de "salvar".
     * O WordPress frequentemente dispara `save_post` (e `wp_after_insert_post`) várias vezes
     * em um curto período (para posts, revisões, autosaves, etc.).
     *
     * O transient atua como um "rate limiter":
     * - Um transient com um nome único (`suff_webhook_lock_{post_ID}`) é usado.
     * - Ele tem um tempo de expiração curto (`$lock_duration`, e.g., 10 segundos).
     * - Se a função for chamada novamente para o mesmo `post_ID` enquanto o transient ainda estiver ativo,
     * ela simplesmente retorna, evitando o disparo duplicado do webhook.
     * - Se o transient não existir (primeira chamada ou transient expirou), ele é definido,
     * e a lógica de hash/webhook é executada.
     */
    $transient_key = 'suff_webhook_lock_' . $post_ID; // Chave do transient (única por Post ID).
    $lock_duration = 10; // Duração do bloqueio em segundos.

    // Verifica se o transient de bloqueio já está ativo.
    // `get_transient()` retorna o valor do transient se existir, ou `false` se não existir ou tiver expirado.
    if ( get_transient( $transient_key ) ) {
        error_log( 'DEBUG: suff_json_exporter: Webhook lock ativo para Post ID: ' . $post_ID . '. Ignorando disparo duplicado.' );
        return; // Sai da função se o bloqueio estiver ativo.
    }

    // Se o bloqueio não está ativo, define um novo transient de bloqueio.
    // `set_transient()` armazena o transient no banco de dados (ou cache de objetos).
    set_transient( $transient_key, true, $lock_duration );
    error_log( 'DEBUG: suff_json_exporter: Lock de webhook definido para Post ID: ' . $post_ID . ' por ' . $lock_duration . ' segundos.' );


    // Verificações iniciais para ignorar eventos não relevantes para a exportação de conteúdo.
    // 1. Ignora revisões e auto-saves:
    //    `wp_is_post_revision()`: Retorna verdadeiro se o post_ID for uma revisão.
    //    `wp_is_post_autosave()`: Retorna verdadeiro se o post_ID for um autosave.
    //    Estes tipos de posts não representam uma mudança de conteúdo "final" que exigiria um webhook.
    if ( wp_is_post_revision( $post_ID ) || wp_is_post_autosave( $post_ID ) ) {
        error_log( 'DEBUG: suff_json_exporter: Ignorando revisao ou autosave para Post ID: ' . $post_ID );
        return;
    }

    // 2. Verifica o tipo de post:
    //    Limita o disparo do webhook apenas para o tipo de post 'post' (posts de blog padrão).
    //    Pode ser expandido para outros tipos de posts personalizados se necessário.
    if ( 'post' !== $post->post_type ) {
        error_log( 'DEBUG: suff_json_exporter: Ignorando tipo de post nao monitorado: ' . $post->post_type );
        return;
    }

    // 3. Verifica o status do post:
    //    O webhook deve ser disparado apenas para posts que estão ou serão publicados.
    //    'publish': Post publicado.
    //    'future': Post agendado para publicação.
    //    Ignora rascunhos, posts na lixeira, etc., que não afetam o "conteúdo ao vivo".
    $supported_post_statuses = array( 'publish', 'future' );
    if ( ! in_array( $post->post_status, $supported_post_statuses, true ) ) {
        error_log( 'DEBUG: suff_json_exporter: Ignorando post com status nao suportado: ' . $post->post_status );
        return;
    }


    // --- Recálculo do Hash do Conteúdo Completo ---
    // Esta seção consulta TODOS os posts publicados para calcular um novo hash global.
    // Este hash representa o estado atual do conjunto de dados exportável.
    $args_for_hash = array(
        'posts_per_page' => -1,          // Obter todos os posts.
        'post_type'      => 'post',     // Apenas posts padrão.
        'post_status'    => 'publish',  // Apenas posts publicados.
        // A ordenação é crucial para a consistência do hash. Deve ser a mesma que no endpoint REST.
        'orderby'        => array(
            'modified' => 'DESC',
            'ID'       => 'ASC',
        ),
    );
    $posts_for_hash_calculation = get_posts( $args_for_hash );

    $hash_data_array_on_save = array();
    $output_charset = get_option( SUFF_JSON_EXPORTER_CHARSET_OPTION, 'UTF-8' ); // Obtém o charset de saída.

    foreach ( $posts_for_hash_calculation as $p ) {
        // Processa o título para o hash: decodifica entidades HTML.
        $processed_title = html_entity_decode( get_the_title( $p->ID ), ENT_QUOTES | ENT_HTML5, $output_charset );
        // Processa o conteúdo para o hash: remove TODAS as tags HTML.
        // Isso garante que pequenas mudanças de formatação HTML não alterem o hash.
        $processed_content = wp_strip_all_tags( $p->post_content );
        // Obtém o permalink para o hash.
        $processed_permalink = get_permalink( $p->ID );

        // Adiciona os dados ao array de hash. A ordem das chaves é importante aqui.
        $hash_data_array_on_save[] = array(
            'title'     => $processed_title,
            'content'   => $processed_content,
            'permalink' => $processed_permalink,
        );
    }

    // Converte o array de dados do hash para JSON.
    // As flags `JSON_UNESCAPED_UNICODE` e `JSON_UNESCAPED_SLASHES` são vitais para a consistência do hash.
    $json_for_hash_on_save = wp_json_encode( $hash_data_array_on_save, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    // Calcula o hash SHA256 da string JSON.
    $current_calculated_hash = hash( 'sha256', $json_for_hash_on_save );

    // --- Compara com o Último Hash Salvo no Banco de Dados ---
    $last_saved_hash = get_option( SUFF_JSON_EXPORTER_LAST_HASH_OPTION );

    error_log( 'DEBUG: suff_json_exporter: Current Calculated Hash: ' . $current_calculated_hash );
    error_log( 'DEBUG: suff_json_exporter: Last Saved Hash (do DB): ' . $last_saved_hash );

    // Se o hash atual for igual ao último hash salvo, significa que o conteúdo relevante não mudou.
    if ( $current_calculated_hash === $last_saved_hash ) {
        error_log( 'DEBUG: suff_json_exporter: Hash IGUAL. Nao disparando webhook para Post ID: ' . $post_ID );
        return; // Não dispara o webhook e sai da função.
    }

    // Se o hash for diferente, significa que houve uma mudança significativa no conteúdo.
    // ATUALIZA O HASH SALVO no banco de dados com o novo hash calculado.
    // Esta atualização garante que a próxima comparação (se o post for salvo novamente sem mudanças)
    // usará o hash mais recente.
    update_option( SUFF_JSON_EXPORTER_LAST_HASH_OPTION, $current_calculated_hash );
    error_log( 'DEBUG: suff_json_exporter: Hash DIFERENTE. Hash no DB atualizado para: ' . $current_calculated_hash );


    // --- Se o hash mudou, procede para disparar o Webhook ---
    error_log( 'DEBUG: suff_json_exporter: Hash DIFERENTE. Disparando webhook para Post ID: ' . $post_ID );

    // Determina o tipo de evento (atualizado ou criado) para incluir no payload do webhook.
    $event_type = $update ? 'post_updated' : 'post_created';

    // Obtém o token salvo para construir a URL de exportação completa autenticada.
    $saved_token = get_option( SUFF_JSON_EXPORTER_TOKEN_OPTION );
    $base_export_url = get_rest_url() . 'sufficit-json-exporter/v1/posts/';
    $authenticated_export_url = '';
    if ( ! empty( $saved_token ) ) {
        $authenticated_export_url = add_query_arg( 'token', $saved_token, $base_export_url );
    }

    // Obtém os dados completos do post alterado para incluir no payload do webhook.
    // Estes dados são mais detalhados do que os usados para o cálculo do hash.
    $post_data_full = array(
        'ID'            => $post->ID,
        'title'         => html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES | ENT_HTML5, $output_charset ),
        // Aqui, `apply_filters('the_content', ...)` é usado para incluir o HTML formatado no webhook payload,
        // o que pode ser útil para o consumidor do webhook.
        'content'       => apply_filters( 'the_content', $post->post_content ),
        'excerpt'       => apply_filters( 'the_excerpt', $post->post_excerpt ),
        'date_published'=> $post->post_date,
        'date_modified' => $post->post_modified,
        'slug'          => $post->post_name,
        'permalink'     => get_permalink( $post->ID ),
        'author_id'     => $post->post_author,
        'author_name'   => get_the_author_meta( 'display_name', $post->post_author ),
        'thumbnail_url' => get_the_post_thumbnail_url( $post->ID, 'full' ),
        'categories'    => wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) ),
        'tags'          => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ),
    );

    // Prepara o payload (carga útil) do webhook que será enviado via POST.
    $payload = array(
        'event'               => $event_type,               // Tipo de evento (post_updated ou post_created).
        'post_id'             => $post_ID,                 // ID do post afetado.
        'post_title_simple'   => get_the_title( $post_ID ), // Título simples do post.
        'permalink'           => get_permalink( $post_ID ),   // Permalink do post.
        'timestamp'           => current_time( 'mysql' ),     // Timestamp do evento no formato MySQL.
        'full_export_url'     => $authenticated_export_url, // URL para o cliente baixar todos os posts (com token).
        'changed_post_data'   => $post_data_full,           // Dados completos do post que foi alterado.
    );

    // Envia o webhook.
    sufficit_json_exporter_send_webhook( $payload );
}

// Remove o hook `save_post` (caso estivesse ativo de versões anteriores)
// para evitar conflitos ou disparos indesejados.
remove_action( 'save_post', 'sufficit_json_exporter_on_post_save', 99, 3 );
// Adiciona a função ao hook `wp_after_insert_post`.
// Prioridade 10 (padrão) e 4 argumentos (`$post_ID`, `$post`, `$update`, `$post_before`).
add_action( 'wp_after_insert_post', 'sufficit_json_exporter_on_post_save', 10, 4 );


/**
 * Aciona o webhook quando um post é excluído permanentemente.
 * A exclusão de um post *sempre* implica em uma mudança no conjunto de dados de posts publicados,
 * então o webhook deve ser disparado e o hash global atualizado.
 *
 * @param int $post_ID O ID do post que está sendo excluído.
 */
function sufficit_json_exporter_on_post_delete( $post_ID ) {
    // Obtém o objeto post antes que ele seja permanentemente excluído.
    $post = get_post( $post_ID );

    // Verifica se o post existe e se é um tipo de post que queremos monitorar.
    // Isso evita processar exclusões de posts que não são 'post' ou que já foram removidos.
    if ( ! $post || 'post' !== $post->post_type ) {
        error_log( 'DEBUG: suff_json_exporter: Ignorando exclusao de post nao monitorado ou inexistente para Post ID: ' . $post_ID );
        return;
    }

    error_log( 'DEBUG: suff_json_exporter: sufficit_json_exporter_on_post_delete acionada para Post ID: ' . $post_ID );


    // --- Recálculo do Hash do Conteúdo Completo APÓS a exclusão ---
    // É crucial recalcular o hash *sem* o post que acabou de ser excluído.
    $args_after_delete = array(
        'posts_per_page' => -1,          // Obter todos os posts.
        'post_type'      => 'post',     // Apenas posts padrão.
        'post_status'    => 'publish',  // Apenas posts publicados.
        'orderby'        => array(       // Ordenação consistente.
            'modified' => 'DESC',
            'ID'       => 'ASC',
        ),
        'post__not_in'   => array($post_ID) // EXCLUI o post que está sendo deletado do cálculo do hash.
    );
    $posts_after_delete_for_hash = get_posts( $args_after_delete );

    $hash_data_array_after_delete = array();
    $output_charset = get_option( SUFF_JSON_EXPORTER_CHARSET_OPTION, 'UTF-8' );

    foreach ( $posts_after_delete_for_hash as $p ) {
        // Processamento para o hash (título decodificado, conteúdo sem tags, permalink).
        $processed_title = html_entity_decode( get_the_title( $p->ID ), ENT_QUOTES | ENT_HTML5, $output_charset );
        $processed_content = wp_strip_all_tags( $p->post_content );
        $processed_permalink = get_permalink( $p->ID );

        $hash_data_array_after_delete[] = array(
            'title'     => $processed_title,
            'content'   => $processed_content,
            'permalink' => $processed_permalink,
        );
    }
    // Converte o array de hash para JSON e calcula o hash SHA256.
    $json_for_hash_after_delete = wp_json_encode( $hash_data_array_after_delete, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    $current_calculated_hash_after_delete = hash( 'sha256', $json_for_hash_after_delete );

    // Atualiza o hash salvo no DB com o novo hash que reflete a exclusão do post.
    update_option( SUFF_JSON_EXPORTER_LAST_HASH_OPTION, $current_calculated_hash_after_delete );
    error_log( 'DEBUG: suff_json_exporter: Hash no DB atualizado apos exclusao para: ' . $current_calculated_hash_after_delete );


    // --- Dispara o Webhook para o Evento de Exclusão ---
    $saved_token = get_option( SUFF_JSON_EXPORTER_TOKEN_OPTION );
    $base_export_url = get_rest_url() . 'sufficit-json-exporter/v1/posts/';
    $authenticated_export_url = '';
    if ( ! empty( $saved_token ) ) {
        $authenticated_export_url = add_query_arg( 'token', $saved_token, $base_export_url );
    }

    // Prepara o payload do webhook para o evento de remoção.
    // Inclui informações básicas do post que foi excluído e a URL para a exportação completa.
    $payload = array(
        'event'               => 'post_deleted',          // Tipo de evento: post excluído.
        'post_id'             => $post_ID,                // ID do post que foi excluído.
        'post_title_simple'   => $post->post_title,       // Título do post (disponível do objeto $post antes da exclusão).
        'timestamp'           => current_time( 'mysql' ), // Timestamp do evento.
        'full_export_url'     => $authenticated_export_url, // URL para o cliente obter o novo conjunto de posts.
        // `changed_post_data` não é incluído aqui, pois o post não existe mais no DB de posts 'publish'.
    );

    // Envia o webhook.
    sufficit_json_exporter_send_webhook( $payload );
    error_log( 'DEBUG: suff_json_exporter: Webhook disparado para exclusao do Post ID: ' . $post_ID );
}
// Hook 'before_delete_post' é acionado antes que um post seja excluído permanentemente do banco de dados.
add_action( 'before_delete_post', 'sufficit_json_exporter_on_post_delete' );
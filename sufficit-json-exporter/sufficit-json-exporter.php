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

// Seguran�a: Garante que o arquivo do plugin n�o seja acessado diretamente.
// Se ABSPATH n�o estiver definido, significa que o WordPress n�o est� carregado,
// impedindo a execu��o direta do script.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Termina a execu��o do script para prevenir acesso n�o autorizado.
}

/**
 * NOTA IMPORTANTE SOBRE CODIFICA��O E CARACTERES ESPECIAIS NO C�DIGO:
 * Ao escrever strings diretamente no c�digo PHP ou HTML (especialmente para labels, descri��es, etc.),
 * � uma boa pr�tica evitar caracteres acentuados ou especiais (como '�', '�', '�', '�', '�').
 * Isso previne potenciais problemas de codifica��o de caracteres que podem surgir
 * em diferentes ambientes de servidor ou configura��es de banco de dados, resultando em caracteres "quebrados".
 *
 * Para textos que precisam ser traduzidos ou que cont�m caracteres especiais, a abordagem recomendada �:
 * 1.  **Internacionaliza��o (i18n):** Usar fun��es de internacionaliza��o do WordPress (como `__()` ou `_e()`)
 * em conjunto com um `Text Domain`. Isso permite que as strings sejam traduzidas para outros idiomas
 * e o WordPress cuida da codifica��o correta.
 * 2.  **Entidades HTML:** Para conte�do din�mico ou sa�das espec�ficas, usar `html_entity_decode()`
 * ou `esc_html()` com a codifica��o correta pode ser necess�rio, como feito na l�gica de exporta��o.
 *
 * Neste plugin, embora alguns textos de interface ainda usem caracteres especiais diretamente para simplificar,
 * a maior parte da l�gica de conte�do (t�tulos, conte�dos de posts) j� lida com decodifica��o.
 * O foco principal � garantir que o *arquivo PHP* esteja salvo em **UTF-8 SEM BOM** para a compatibilidade global.
 */

// --- CONFIGURA��ES GERAIS E GARANTIA DE CODIFICA��O UTF-8 ---

/**
 * Tenta for�ar a codifica��o UTF-8 para fun��es de string multi-byte e para o ambiente PHP em geral.
 * Esta � uma medida proativa. A codifica��o real pode depender da configura��o do servidor,
 * mas ajuda a mitigar problemas. O mais crucial � que o arquivo PHP esteja salvo em UTF-8 SEM BOM.
 */
if ( function_exists( 'mb_internal_encoding' ) ) {
    mb_internal_encoding( 'UTF-8' ); // Define a codifica��o interna para fun��es multi-byte (mb_string).
}
if ( function_exists( 'mb_regex_encoding' ) ) {
    mb_regex_encoding( 'UTF-8' ); // Define a codifica��o para express�es regulares multi-byte.
}
ini_set( 'default_charset', 'UTF-8' ); // Tenta definir a codifica��o padr�o para PHP.

/**
 * Carrega o textdomain para internacionaliza��o (tradu��es do plugin).
 * Isso permite que todas as strings vis�veis na interface do plugin sejam traduzidas.
 * O `dirname( plugin_basename( __FILE__ ) ) . '/languages'` define o caminho para a pasta de tradu��es.
 */
function sufficit_json_exporter_load_textdomain() {
    load_plugin_textdomain( 'sufficit-json-exporter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'sufficit_json_exporter_load_textdomain' );

/**
 * Constantes para os nomes das op��es salvas no banco de dados do WordPress.
 * Usar constantes evita erros de digita��o e torna o c�digo mais leg�vel e manuten�vel.
 * Isso tamb�m centraliza os nomes das chaves de op��o.
 */
define( 'SUFF_JSON_EXPORTER_TOKEN_OPTION', 'suff_json_exporter_auth_token' );             // Chave para armazenar o token de autentica��o.
define( 'SUFF_JSON_EXPORTER_CHARSET_OPTION', 'suff_json_exporter_output_charset' );       // Chave para armazenar a codifica��o de sa�da JSON.
define( 'SUFF_JSON_EXPORTER_LAST_HASH_OPTION', 'suff_json_exporter_last_content_hash' );   // Chave para armazenar o �ltimo hash do conte�do.

/**
 * Constantes para as op��es de configura��o do webhook.
 */
define( 'SUFF_JSON_EXPORTER_WEBHOOK_URL_OPTION', 'suff_json_exporter_webhook_url' );       // Chave para armazenar a URL do webhook.
define( 'SUFF_JSON_EXPORTER_WEBHOOK_ENABLED_OPTION', 'suff_json_exporter_webhook_enabled' ); // Chave para armazenar o status de habilita��o do webhook.


// --- CONFIGURA��O DO ENDPOINT REST API ---

/**
 * Fun��o para registrar um endpoint REST personalizado para a exporta��o de posts.
 * Este endpoint ser� acess�vel publicamente, mas protegido por um token de autentica��o.
 */
function sufficit_json_exporter_registrar_endpoint() {
    register_rest_route(
        'sufficit-json-exporter/v1', // Namespace: Identifica unicamente a API do plugin (ex: wp-json/sufficit-json-exporter/v1).
        '/posts/',               // Rota: Define o caminho espec�fico para o endpoint (ex: wp-json/sufficit-json-exporter/v1/posts).
        array(
            'methods'             => 'GET', // M�todos HTTP aceitos (apenas GET para exporta��o de dados).
            'callback'            => 'sufficit_json_exporter_gerar_posts', // Fun��o PHP que ser� executada quando o endpoint for acessado.
            'permission_callback' => 'sufficit_json_exporter_autenticar_requisitante', // Fun��o para verificar a permiss�o/autentica��o antes de executar o callback.
            'args'                => array(), // Argumentos esperados na URL (o token ser� tratado como um par�metro GET, n�o um argumento formal da rota).
        )
    );
}
// Hook 'rest_api_init' � o momento correto para registrar endpoints REST.
add_action( 'rest_api_init', 'sufficit_json_exporter_registrar_endpoint' );

/**
 * Fun��o de callback para autenticar a requisi��o ao endpoint REST via token GET.
 * Garante que apenas requisitantes com o token correto possam acessar os dados exportados.
 *
 * @param WP_REST_Request $request O objeto da requisi��o REST atual, cont�m os par�metros da requisi��o.
 * @return bool|WP_Error Retorna 'true' se o token fornecido for v�lido, ou um objeto WP_Error se for inv�lido ou n�o configurado.
 */
function sufficit_json_exporter_autenticar_requisitante( WP_REST_Request $request ) {
    $token_param = $request->get_param( 'token' ); // Obt�m o par�metro 'token' da URL da requisi��o.
    $saved_token = get_option( SUFF_JSON_EXPORTER_TOKEN_OPTION ); // Obt�m o token de autentica��o salvo nas configura��es do plugin.

    // 1. Verifica se um token foi sequer configurado nas op��es do plugin.
    if ( empty( $saved_token ) ) {
        error_log( 'DEBUG: suff_json_exporter: Tentativa de acesso ao endpoint sem token configurado.' );
        return new WP_Error( 'suff_json_exporter_no_token_set', 'Token de autenticacao nao configurado no plugin.', array( 'status' => 401 ) );
    }

    // 2. Compara o token fornecido na requisi��o com o token salvo.
    if ( $token_param === $saved_token ) {
        error_log( 'DEBUG: suff_json_exporter: Token de autenticacao valido. Acesso permitido.' );
        return true; // Autentica��o bem-sucedida.
    }

    // 3. Se os tokens n�o corresponderem, retorna um erro de autentica��o.
    error_log( 'DEBUG: suff_json_exporter: Token de autenticacao invalido: ' . ( ! empty( $token_param ) ? $token_param : 'NULO/VAZIO' ) );
    return new WP_Error( 'suff_json_exporter_invalid_token', 'Token de autenticacao invalido.', array( 'status' => 401 ) );
}

/**
 * Callback principal para o endpoint REST que gera a exporta��o dos posts em JSON.
 * Consulta o banco de dados do WordPress, seleciona os posts publicados e formata seus dados
 * em um array JSON. Tamb�m calcula e inclui um hash do conte�do no cabe�alho HTTP (X-Content-Hash)
 * para permitir que os consumidores da API verifiquem se o conte�do mudou sem baixar todo o JSON.
 * O hash � calculado de forma a ser consistente, focando apenas em t�tulo, conte�do puro e permalink.
 *
 * @return WP_REST_Response Os dados dos posts em formato JSON, com cabe�alho X-Content-Hash.
 */
function sufficit_json_exporter_gerar_posts() {
    error_log( 'DEBUG: suff_json_exporter: Gerando exportacao JSON para o endpoint REST.' );

    // Define os argumentos para a consulta de posts.
    $args = array(
        'posts_per_page' => -1,          // `-1` garante que todos os posts correspondentes sejam retornados (sem pagina��o).
        'post_type'      => 'post',     // Limita a consulta apenas aos posts do tipo 'post' (padr�o do WordPress).
        'post_status'    => 'publish',  // Retorna apenas posts que est�o no status 'publicado'.
        // Ordena��o crucial para a CONSIST�NCIA do hash:
        // A ordem dos posts no JSON afeta o hash final. Ordenar por 'modified' (descendente)
        // e depois por 'ID' (ascendente) garante uma ordem est�vel e previs�vel,
        // mesmo que posts sejam modificados no mesmo segundo.
        'orderby'        => array(
            'modified' => 'DESC', // Posts mais recentemente modificados primeiro.
            'ID'       => 'ASC',  // Em caso de mesma data de modifica��o, ordena por ID.
        ),
    );

    $posts = get_posts( $args ); // Executa a consulta de posts com os argumentos definidos.

    $posts_data = array(); // Array para armazenar os dados COMPLETOS de cada post para a resposta JSON.
    $hash_data_array = array(); // Array para armazenar APENAS os dados relevantes para o c�lculo do hash.

    // Obt�m a codifica��o de sa�da JSON configurada pelo usu�rio nas op��es do plugin (padr�o: UTF-8).
    $output_charset = get_option( SUFF_JSON_EXPORTER_CHARSET_OPTION, 'UTF-8' );

    // Itera sobre cada post retornado da consulta para formatar os dados.
    foreach ( $posts as $post ) {
        // Obt�m o t�tulo do post.
        $post_title = get_the_title( $post->ID );
        // Obt�m o conte�do e o resumo do post diretamente do objeto $post para consist�ncia.
        $post_content = $post->post_content;
        $post_excerpt = $post->post_excerpt;
        // Obt�m o permalink do post.
        $post_permalink = get_permalink( $post->ID );

        // Processamento para o ARRAY DE DADOS COMPLETO ($posts_data):
        // `html_entity_decode()`: Converte entidades HTML (ex: &amp;) de volta para caracteres reais (ex: &).
        // `ENT_QUOTES | ENT_HTML5`: Lida com aspas e usa regras de HTML5.
        // `$output_charset`: Garante que a decodifica��o use o charset correto.
        $processed_title_full = html_entity_decode( $post_title, ENT_QUOTES | ENT_HTML5, $output_charset );
        // `apply_filters('the_content', ...)`: Aplica todos os filtros padr�o do WordPress ao conte�do,
        // o que geralmente significa que o HTML ser� processado (par�grafos, shortcodes, etc.).
        $processed_content_full = apply_filters( 'the_content', $post_content );
        $processed_excerpt_full = apply_filters( 'the_excerpt', $post_excerpt );

        // Adiciona os dados COMPLETOS do post ao array principal para a resposta JSON.
        $posts_data[] = array(
            'ID'            => $post->ID,
            'title'         => $processed_title_full,
            'content'       => $processed_content_full,
            'excerpt'       => $processed_excerpt_full,
            'date_published'=> $post->post_date,       // Data de publica��o.
            'date_modified' => $post->post_modified,    // Data da �ltima modifica��o.
            'slug'          => $post->post_name,       // Slug do post.
            'permalink'     => $post_permalink,         // URL permanente do post.
            'author_id'     => $post->post_author,
            'author_name'   => get_the_author_meta( 'display_name', $post->post_author ), // Nome do autor.
            'thumbnail_url' => get_the_post_thumbnail_url( $post->ID, 'full' ), // URL da imagem destacada.
            'categories'    => wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) ), // Nomes das categorias.
            'tags'          => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ),       // Nomes das tags.
        );

        // Processamento para o ARRAY DE DADOS DO HASH ($hash_data_array):
        // � CR�TICO que os dados para o hash sejam o mais consistentes poss�vel,
        // evitando varia��es que n�o indicam uma mudan�a de conte�do real.
        $processed_title_hash = html_entity_decode( $post_title, ENT_QUOTES | ENT_HTML5, $output_charset );
        // `wp_strip_all_tags()`: Remove *todas* as tags HTML do conte�do. Isso � essencial para o hash,
        // pois filtros de conte�do (como 'the_content') podem adicionar/remover tags HTML invis�veis ou
        // inconsistentes que alterariam o hash sem uma mudan�a real de texto.
        $processed_content_hash = wp_strip_all_tags( $post_content );
        $processed_permalink_hash = get_permalink( $post->ID );

        // Adiciona APENAS os dados relevantes e consistentes para o c�lculo do hash.
        // A ordem das chaves neste array tamb�m � importante para a consist�ncia do hash final.
        $hash_data_array[] = array(
            'title'     => $processed_title_hash,
            'content'   => $processed_content_hash,
            'permalink' => $processed_permalink_hash,
        );
    }

    // Converte o array `$hash_data_array` (apenas dados relevantes para o hash) em uma string JSON.
    // `JSON_UNESCAPED_UNICODE`: Garante que caracteres Unicode (como acentos) n�o sejam escapados (\uXXXX).
    // `JSON_UNESCAPED_SLASHES`: Garante que barras (/) n�o sejam escapadas (\/).
    // Ambos s�o cruciais para a consist�ncia do hash, j� que a string JSON gerada ser� sempre a mesma para o mesmo conte�do.
    $json_for_hash = wp_json_encode( $hash_data_array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

    // Calcula o hash SHA256 da string JSON. SHA256 � um algoritmo de hash criptogr�fico.
    $content_hash = hash( 'sha256', $json_for_hash );

    // Cria o objeto `WP_REST_Response` com os dados COMPLETOS dos posts ($posts_data) e o status HTTP 200 (OK).
    // O array `$posts_data` ser� automaticamente convertido em JSON pela API REST.
    $response = new WP_REST_Response( $posts_data, 200 );

    // Define o cabe�alho HTTP 'Content-Type' explicitamente para indicar que a resposta � JSON e seu charset.
    $response->header( 'Content-Type', 'application/json; charset=' . $output_charset );

    // Adiciona o hash do conte�do como um cabe�alho HTTP personalizado 'X-Content-Hash'.
    // Isso permite que o cliente verifique a integridade ou a mudan�a do conte�do sem baixar todo o corpo da resposta.
    $response->header( 'X-Content-Hash', $content_hash );

    // Salva o �ltimo hash gerado no banco de dados. Este � o hash que ser� comparado
    // nas pr�ximas vezes que um post for salvo para determinar se o webhook deve ser disparado.
    update_option( SUFF_JSON_EXPORTER_LAST_HASH_OPTION, $content_hash );
    error_log( 'DEBUG: suff_json_exporter: Hash do conteudo atualizado no DB apos geracao do JSON: ' . $content_hash );

    return $response; // Retorna a resposta REST.
}

// --- FUN��ES PARA A P�GINA DE CONFIGURA��ES DO PLUGIN NO ADMIN ---

/**
 * Adiciona a p�gina de configura��es do plugin ao menu "Configura��es" do WordPress Admin.
 */
function sufficit_json_exporter_adicionar_pagina_admin() {
    add_options_page(
        'Sufficit JSON Exporter Configuracoes', // T�tulo da p�gina (aparece na aba do navegador).
        'JSON Exporter',                     // T�tulo no menu lateral do WordPress.
        'manage_options',                    // Capacidade m�nima necess�ria para acessar a p�gina (usu�rios com permiss�o de 'manage_options').
        'sufficit-json-exporter',            // Slug �nico da p�gina (usado na URL e como identificador).
        'sufficit_json_exporter_pagina_config' // Fun��o de callback que renderiza o conte�do HTML da p�gina.
    );
}
// Hook 'admin_menu' � o momento correto para adicionar p�ginas ao menu administrativo.
add_action( 'admin_menu', 'sufficit_json_exporter_adicionar_pagina_admin' );

/**
 * Adiciona um link direto para a p�gina de "Configura��es" na lista de plugins instalados.
 * Isso melhora a usabilidade, permitindo acesso r�pido �s configura��es diretamente da tela de Plugins.
 *
 * @param array $links Array de links de a��o para o plugin (ex: Ativar, Desativar, Editar).
 * @return array O array de links modificado com o link "Configura��es" adicionado.
 */
function sufficit_json_exporter_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=sufficit-json-exporter">' . 'Configuracoes' . '</a>';
    // `array_unshift()` adiciona o novo link no in�cio do array de links existentes.
    array_unshift( $links, $settings_link );
    return $links;
}
// Obt�m o nome base do arquivo do plugin (ex: meu-plugin/meu-plugin.php) para o filtro correto.
// Aten��o: Esta linha foi ajustada para refletir o nome do arquivo 'sufficit-json-exporter.php'.
$plugin = plugin_basename( __FILE__ );
// Hook 'plugin_action_links_{$plugin_file}' permite adicionar links personalizados na lista de plugins.
add_filter( "plugin_action_links_$plugin", 'sufficit_json_exporter_add_settings_link' );


/**
 * Registra as configura��es do plugin na API de Settings do WordPress.
 * Embora o HTML dos campos seja impresso DIRETAMENTE na fun��o `pagina_config` (uma solu��o
 * para problemas de renderiza��o observados), o registro com `register_setting()` ainda
 * � ESSENCIAL para que o WordPress processe, saneie e salve os valores dos campos
 * no banco de dados quando o formul�rio � submetido.
 */
function sufficit_json_exporter_registrar_settings() {
    // 1. Registra a op��o do token de autentica��o.
    register_setting(
        'suff_json_exporter_settings_group', // Nome do grupo de configura��es (usado com `settings_fields()`).
        SUFF_JSON_EXPORTER_TOKEN_OPTION,     // Nome da op��o no banco de dados (`wp_options` table).
        array(
            'type'              => 'string',            // Tipo de dado esperado (string).
            'sanitize_callback' => 'sanitize_text_field', // Fun��o para limpar e validar o input (remove tags, etc.).
            'default'           => '',                  // Valor padr�o se a op��o n�o estiver definida.
            'show_in_rest'      => false,               // N�o exp�e esta op��o via REST API.
        )
    );

    // 2. Registra a op��o de codifica��o de sa�da JSON.
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

    // 3. Registra a op��o para o �ltimo hash de conte�do gerado.
    // Esta op��o � atualizada automaticamente pelo plugin, n�o pelo usu�rio na interface.
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

    // 4. Registra a op��o da URL do webhook.
    register_setting(
        'suff_json_exporter_settings_group',
        SUFF_JSON_EXPORTER_WEBHOOK_URL_OPTION,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw', // Garante que a URL seja segura e v�lida.
            'default'           => '',
            'show_in_rest'      => false,
        )
    );

    // 5. Registra a op��o de habilita��o/desabilita��o do webhook (checkbox).
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
     * As fun��es `add_settings_section()` e `add_settings_field()` s�o normalmente usadas
     * para ESTRUTURAR e RENDERIZAR o HTML da p�gina de configura��es. No entanto,
     * neste plugin, devido a um problema de renderiza��o anterior em alguns ambientes WP,
     * o HTML dos campos � gerado DIRETAMENTE na fun��o `sufficit_json_exporter_pagina_config()`.
     *
     * Mantemos o `add_settings_section()` aqui apenas para o registro l�gico
     * com a API de Settings do WordPress, o que � necess�rio para `settings_fields()`
     * funcionar corretamente e para o processamento de submiss�o do formul�rio.
     */
    add_settings_section(
        'suff_json_exporter_general_section', // ID �nico da se��o.
        'Configuracoes de Autenticacao e Codificacao', // T�tulo vis�vel da se��o.
        null, // N�o h� fun��o de callback para descri��o da se��o, pois o HTML � direto.
        'sufficit-json-exporter' // Slug da p�gina onde esta se��o aparece.
    );
}
// Hook 'admin_init' � o momento correto para registrar as configura��es.
add_action( 'admin_init', 'sufficit_json_exporter_registrar_settings' );


/**
 * Fun��o de callback principal que renderiza o conte�do HTML da p�gina de configura��es do plugin.
 *
 * ABORDAGEM DE RENDERIZA��O DIRETA:
 * Como mencionado anteriormente, esta fun��o imprime o HTML dos campos do formul�rio diretamente.
 * Isso foi uma solu��o para contornar problemas onde os campos n�o eram exibidos corretamente
 * usando o m�todo tradicional de `add_settings_field()` em certos ambientes WordPress.
 * A funcionalidade de salvar os dados ainda depende do `register_setting()` (definido acima)
 * e do `settings_fields()` e `submit_button()` (chamados abaixo), que s�o fun��es padr�o do WordPress.
 */
function sufficit_json_exporter_pagina_config() {
    // Garante que o cabe�alho HTTP 'Content-Type' seja enviado como UTF-8,
    // prevenindo problemas de codifica��o de caracteres na pr�pria p�gina de administra��o.
    if ( ! headers_sent() ) {
        header( 'Content-Type: text/html; charset=UTF-8' );
    }

    // Obt�m os valores atuais das op��es salvas no banco de dados do WordPress.
    $token = get_option( SUFF_JSON_EXPORTER_TOKEN_OPTION );
    $current_charset = get_option( SUFF_JSON_EXPORTER_CHARSET_OPTION, 'UTF-8' );
    $webhook_url = get_option( SUFF_JSON_EXPORTER_WEBHOOK_URL_OPTION );
    $webhook_enabled = get_option( SUFF_JSON_EXPORTER_WEBHOOK_ENABLED_OPTION );
    $last_saved_hash_display = get_option( SUFF_JSON_EXPORTER_LAST_HASH_OPTION, 'N/A' ); // Exibe 'N/A' se nenhum hash foi salvo ainda.

    // Constr�i a URL base do endpoint REST do WordPress.
    $base_endpoint_url = get_rest_url() . 'sufficit-json-exporter/v1/posts/';
    // Constr�i um exemplo de URL completa com o token atual para exibi��o na interface.
    // `esc_url()`: Limpa a URL para uso seguro em atributos HTML.
    $full_url_example = esc_url( $base_endpoint_url . '?token=' . $token );

    // Array de op��es de codifica��o de caracteres para o campo <select> na interface.
    $charsets = array(
        'UTF-8'         => 'UTF-8 (Recomendado para JSON)',
        'ISO-8859-1'    => 'ISO-8859-1 (Latin-1 - Padrao da sua instalacao do WP se nao for UTF-8)',
        'Windows-1252'  => 'Windows-1252 (ANSI - Comum em sistemas Windows legados)',
    );
    // Adiciona a codifica��o padr�o do pr�prio WordPress (se for diferente de UTF-8) � lista de op��es,
    // para que o usu�rio possa escolher a codifica��o que seu site usa.
    $wp_charset = get_bloginfo( 'charset' );
    if ( ! array_key_exists( $wp_charset, $charsets ) && $wp_charset !== 'UTF-8' ) {
        $charsets[$wp_charset] = sprintf( 'Padrao do WordPress (%s)', $wp_charset );
    }
    // Ordena as op��es de charset para facilitar a sele��o, colocando UTF-8 e o charset atual no topo.
    uksort($charsets, function($a, $b) use ($current_charset) {
        if ($a === 'UTF-8') return -1; // Coloca UTF-8 como a primeira op��o.
        if ($b === 'UTF-8') return 1;
        if ($a === $current_charset) return -1; // Coloca o charset atualmente selecionado em seguida.
        if ($b === $current_charset) return 1;
        return 0; // Para os demais, mant�m a ordem natural (alfab�tica).
    });

    ?>
    <div class="wrap">
        <h1>Configuracoes do Sufficit JSON Exporter</h1>
        <form method="post" action="options.php">
            <?php
            // `settings_fields()`: ESSENCIAL. Imprime os campos ocultos de seguran�a (nonces, etc.)
            // e o nome do grupo de configura��es (`suff_json_exporter_settings_group`).
            // Isso � necess�rio para que o formul�rio seja processado corretamente pelo WordPress.
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
            // `submit_button()`: Gera o bot�o de submiss�o padr�o do WordPress ("Salvar altera��es").
            submit_button( 'Salvar alteracoes' );
            ?>
        </form>
    </div>
    <?php
}

/**
 * Garante que a p�gina de plugins do WordPress tamb�m use o charset UTF-8 nos cabe�alhos HTTP.
 * Isso � uma medida de compatibilidade para ambientes que podem n�o estar configurados para UTF-8 por padr�o,
 * evitando problemas de caracteres quebrados na lista de plugins.
 */
add_action('admin_head-plugins.php', 'sufficit_json_exporter_set_plugins_page_charset');
function sufficit_json_exporter_set_plugins_page_charset() {
    if ( ! headers_sent() ) { // Verifica se os cabe�alhos HTTP j� foram enviados.
        header( 'Content-Type: text/html; charset=UTF-8' );
    }
}

/**
 * Enfileira o script JavaScript personalizado para a p�gina de configura��es do plugin.
 * O script � carregado apenas na p�gina do plugin para otimiza��o de performance,
 * evitando que seja carregado em todas as p�ginas do admin.
 *
 * @param string $hook_suffix O slug da p�gina de administra��o atual.
 */
function sufficit_json_exporter_enqueue_admin_scripts( $hook_suffix ) {
    // Verifica se estamos na p�gina de configura��es do nosso plugin.
    if ( 'settings_page_sufficit-json-exporter' === $hook_suffix ) {
        wp_enqueue_script(
            'suff-json-exporter-admin-script',      // Handle (nome �nico) para o script.
            plugin_dir_url( __FILE__ ) . 'admin-script.js', // URL completa para o arquivo JS.
            array( 'jquery' ),                      // Depend�ncias (garante que jQuery seja carregado antes).
            '1.0',                                  // Vers�o do script (para cache busting, atualize quando o JS mudar).
            true                                    // Carrega o script no footer (melhor para performance).
        );

        // `wp_localize_script()`: Permite passar dados do PHP para o JavaScript.
        // Isso � �til para informa��es din�micas como URLs ou op��es do plugin.
        wp_localize_script(
            'suff-json-exporter-admin-script', // Handle do script para o qual os dados ser�o localizados.
            'suffExporterData',                // Nome do objeto JS global que conter� os dados (e.g., `suffExporterData.baseUrl`).
            array(
                'baseUrl' => get_rest_url() . 'sufficit-json-exporter/v1/posts/', // Passa a URL base do endpoint REST para o JS.
            )
        );
    }
}
// Hook 'admin_enqueue_scripts' � o momento correto para enfileirar scripts e estilos no admin.
add_action( 'admin_enqueue_scripts', 'sufficit_json_exporter_enqueue_admin_scripts' );


// --- FUN��O PARA ENVIAR WEBHOOK ---

/**
 * Envia uma requisi��o POST para a URL do webhook configurada.
 * Utiliza `wp_remote_post()` para garantir que a requisi��o seja feita de forma segura,
 * ass�ncrona (n�o bloqueando a execu��o do WordPress) e compat�vel com as configura��es do servidor.
 *
 * @param array $payload Dados a serem enviados no corpo da requisi��o POST (em formato JSON).
 * @return void
 */
function sufficit_json_exporter_send_webhook( $payload ) {
    $webhook_url = get_option( SUFF_JSON_EXPORTER_WEBHOOK_URL_OPTION );     // Obt�m a URL do webhook das op��es.
    $webhook_enabled = get_option( SUFF_JSON_EXPORTER_WEBHOOK_ENABLED_OPTION ); // Verifica se o webhook est� habilitado.

    // Verifica se o webhook est� habilitado E se a URL n�o est� vazia.
    if ( $webhook_enabled && ! empty( $webhook_url ) ) {
        error_log( 'DEBUG: suff_json_exporter: Tentando enviar webhook para: ' . $webhook_url );
        $args = array(
            'body'        => json_encode( $payload ), // Converte o array de dados do payload para uma string JSON.
            'headers'     => array(
                'Content-Type' => 'application/json; charset=utf-8', // Define o cabe�alho Content-Type para JSON.
            ),
            'method'      => 'POST', // Tipo da requisi��o HTTP.
            'timeout'     => 10,     // Tempo limite em segundos para a requisi��o. Evita que a requisi��o demore demais.
            'blocking'    => false,  // Define como `false` para que a requisi��o seja ass�ncrona.
                                     // Isso significa que o WordPress n�o espera a resposta do webhook,
                                     // evitando que o processo de salvamento do post seja bloqueado.
            'data_format' => 'body', // Indica que o corpo da requisi��o � formatado como 'body'.
        );

        // Envia a requisi��o POST para a URL do webhook.
        // O resultado da requisi��o (sucesso ou falha) n�o � processado aqui devido ao `blocking` ser `false`.
        wp_remote_post( $webhook_url, $args );
        error_log( 'DEBUG: suff_json_exporter: Webhook enviado com sucesso (assincrono).' );
    } else {
        error_log( 'DEBUG: suff_json_exporter: Webhook desabilitado ou URL vazia. Nao enviando webhook.' );
    }
}

// --- HOOKS PARA ACIONAR O WEBHOOK EM EVENTOS DE POST ---

/**
 * Aciona o webhook quando um post � salvo (novo ou atualizado).
 * Utiliza o hook 'wp_after_insert_post' (dispon�vel a partir do WP 5.6) por ser mais robusto,
 * pois � executado *depois* que o post e suas revis�es foram completamente salvos no banco de dados.
 *
 * Inclui:
 * - L�gica de "lock" baseada em transients para evitar m�ltiplos disparos do webhook na mesma opera��o de salvamento.
 * - Verifica��es para ignorar revis�es, autosaves e tipos/status de posts n�o monitorados.
 * - Rec�lculo e compara��o de hash para disparar o webhook APENAS se o conte�do relevante (t�tulo, conte�do, permalink
 * de TODOS os posts publicados) tiver realmente mudado.
 *
 * @param int     $post_ID     O ID do post que foi salvo.
 * @param WP_Post $post        O objeto `WP_Post` do post salvo.
 * @param bool    $update      Verdadeiro se o post foi atualizado, falso se foi um novo post.
 * @param WP_Post $post_before Objeto `WP_Post` do post antes da inser��o/atualiza��o (dispon�vel no WP 5.6+).
 */
function sufficit_json_exporter_on_post_save( $post_ID, $post, $update, $post_before ) {
    error_log( 'DEBUG: suff_json_exporter: sufficit_json_exporter_on_post_save acionada para Post ID: ' . $post_ID . ' (Update: ' . ($update ? 'true' : 'false') . ') via wp_after_insert_post.' );

    /**
     * L�gica de "Lock" (bloqueio) usando Transients:
     * Este � um mecanismo CR�TICO para evitar m�ltiplos disparos de webhook na mesma opera��o de "salvar".
     * O WordPress frequentemente dispara `save_post` (e `wp_after_insert_post`) v�rias vezes
     * em um curto per�odo (para posts, revis�es, autosaves, etc.).
     *
     * O transient atua como um "rate limiter":
     * - Um transient com um nome �nico (`suff_webhook_lock_{post_ID}`) � usado.
     * - Ele tem um tempo de expira��o curto (`$lock_duration`, e.g., 10 segundos).
     * - Se a fun��o for chamada novamente para o mesmo `post_ID` enquanto o transient ainda estiver ativo,
     * ela simplesmente retorna, evitando o disparo duplicado do webhook.
     * - Se o transient n�o existir (primeira chamada ou transient expirou), ele � definido,
     * e a l�gica de hash/webhook � executada.
     */
    $transient_key = 'suff_webhook_lock_' . $post_ID; // Chave do transient (�nica por Post ID).
    $lock_duration = 10; // Dura��o do bloqueio em segundos.

    // Verifica se o transient de bloqueio j� est� ativo.
    // `get_transient()` retorna o valor do transient se existir, ou `false` se n�o existir ou tiver expirado.
    if ( get_transient( $transient_key ) ) {
        error_log( 'DEBUG: suff_json_exporter: Webhook lock ativo para Post ID: ' . $post_ID . '. Ignorando disparo duplicado.' );
        return; // Sai da fun��o se o bloqueio estiver ativo.
    }

    // Se o bloqueio n�o est� ativo, define um novo transient de bloqueio.
    // `set_transient()` armazena o transient no banco de dados (ou cache de objetos).
    set_transient( $transient_key, true, $lock_duration );
    error_log( 'DEBUG: suff_json_exporter: Lock de webhook definido para Post ID: ' . $post_ID . ' por ' . $lock_duration . ' segundos.' );


    // Verifica��es iniciais para ignorar eventos n�o relevantes para a exporta��o de conte�do.
    // 1. Ignora revis�es e auto-saves:
    //    `wp_is_post_revision()`: Retorna verdadeiro se o post_ID for uma revis�o.
    //    `wp_is_post_autosave()`: Retorna verdadeiro se o post_ID for um autosave.
    //    Estes tipos de posts n�o representam uma mudan�a de conte�do "final" que exigiria um webhook.
    if ( wp_is_post_revision( $post_ID ) || wp_is_post_autosave( $post_ID ) ) {
        error_log( 'DEBUG: suff_json_exporter: Ignorando revisao ou autosave para Post ID: ' . $post_ID );
        return;
    }

    // 2. Verifica o tipo de post:
    //    Limita o disparo do webhook apenas para o tipo de post 'post' (posts de blog padr�o).
    //    Pode ser expandido para outros tipos de posts personalizados se necess�rio.
    if ( 'post' !== $post->post_type ) {
        error_log( 'DEBUG: suff_json_exporter: Ignorando tipo de post nao monitorado: ' . $post->post_type );
        return;
    }

    // 3. Verifica o status do post:
    //    O webhook deve ser disparado apenas para posts que est�o ou ser�o publicados.
    //    'publish': Post publicado.
    //    'future': Post agendado para publica��o.
    //    Ignora rascunhos, posts na lixeira, etc., que n�o afetam o "conte�do ao vivo".
    $supported_post_statuses = array( 'publish', 'future' );
    if ( ! in_array( $post->post_status, $supported_post_statuses, true ) ) {
        error_log( 'DEBUG: suff_json_exporter: Ignorando post com status nao suportado: ' . $post->post_status );
        return;
    }


    // --- Rec�lculo do Hash do Conte�do Completo ---
    // Esta se��o consulta TODOS os posts publicados para calcular um novo hash global.
    // Este hash representa o estado atual do conjunto de dados export�vel.
    $args_for_hash = array(
        'posts_per_page' => -1,          // Obter todos os posts.
        'post_type'      => 'post',     // Apenas posts padr�o.
        'post_status'    => 'publish',  // Apenas posts publicados.
        // A ordena��o � crucial para a consist�ncia do hash. Deve ser a mesma que no endpoint REST.
        'orderby'        => array(
            'modified' => 'DESC',
            'ID'       => 'ASC',
        ),
    );
    $posts_for_hash_calculation = get_posts( $args_for_hash );

    $hash_data_array_on_save = array();
    $output_charset = get_option( SUFF_JSON_EXPORTER_CHARSET_OPTION, 'UTF-8' ); // Obt�m o charset de sa�da.

    foreach ( $posts_for_hash_calculation as $p ) {
        // Processa o t�tulo para o hash: decodifica entidades HTML.
        $processed_title = html_entity_decode( get_the_title( $p->ID ), ENT_QUOTES | ENT_HTML5, $output_charset );
        // Processa o conte�do para o hash: remove TODAS as tags HTML.
        // Isso garante que pequenas mudan�as de formata��o HTML n�o alterem o hash.
        $processed_content = wp_strip_all_tags( $p->post_content );
        // Obt�m o permalink para o hash.
        $processed_permalink = get_permalink( $p->ID );

        // Adiciona os dados ao array de hash. A ordem das chaves � importante aqui.
        $hash_data_array_on_save[] = array(
            'title'     => $processed_title,
            'content'   => $processed_content,
            'permalink' => $processed_permalink,
        );
    }

    // Converte o array de dados do hash para JSON.
    // As flags `JSON_UNESCAPED_UNICODE` e `JSON_UNESCAPED_SLASHES` s�o vitais para a consist�ncia do hash.
    $json_for_hash_on_save = wp_json_encode( $hash_data_array_on_save, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    // Calcula o hash SHA256 da string JSON.
    $current_calculated_hash = hash( 'sha256', $json_for_hash_on_save );

    // --- Compara com o �ltimo Hash Salvo no Banco de Dados ---
    $last_saved_hash = get_option( SUFF_JSON_EXPORTER_LAST_HASH_OPTION );

    error_log( 'DEBUG: suff_json_exporter: Current Calculated Hash: ' . $current_calculated_hash );
    error_log( 'DEBUG: suff_json_exporter: Last Saved Hash (do DB): ' . $last_saved_hash );

    // Se o hash atual for igual ao �ltimo hash salvo, significa que o conte�do relevante n�o mudou.
    if ( $current_calculated_hash === $last_saved_hash ) {
        error_log( 'DEBUG: suff_json_exporter: Hash IGUAL. Nao disparando webhook para Post ID: ' . $post_ID );
        return; // N�o dispara o webhook e sai da fun��o.
    }

    // Se o hash for diferente, significa que houve uma mudan�a significativa no conte�do.
    // ATUALIZA O HASH SALVO no banco de dados com o novo hash calculado.
    // Esta atualiza��o garante que a pr�xima compara��o (se o post for salvo novamente sem mudan�as)
    // usar� o hash mais recente.
    update_option( SUFF_JSON_EXPORTER_LAST_HASH_OPTION, $current_calculated_hash );
    error_log( 'DEBUG: suff_json_exporter: Hash DIFERENTE. Hash no DB atualizado para: ' . $current_calculated_hash );


    // --- Se o hash mudou, procede para disparar o Webhook ---
    error_log( 'DEBUG: suff_json_exporter: Hash DIFERENTE. Disparando webhook para Post ID: ' . $post_ID );

    // Determina o tipo de evento (atualizado ou criado) para incluir no payload do webhook.
    $event_type = $update ? 'post_updated' : 'post_created';

    // Obt�m o token salvo para construir a URL de exporta��o completa autenticada.
    $saved_token = get_option( SUFF_JSON_EXPORTER_TOKEN_OPTION );
    $base_export_url = get_rest_url() . 'sufficit-json-exporter/v1/posts/';
    $authenticated_export_url = '';
    if ( ! empty( $saved_token ) ) {
        $authenticated_export_url = add_query_arg( 'token', $saved_token, $base_export_url );
    }

    // Obt�m os dados completos do post alterado para incluir no payload do webhook.
    // Estes dados s�o mais detalhados do que os usados para o c�lculo do hash.
    $post_data_full = array(
        'ID'            => $post->ID,
        'title'         => html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES | ENT_HTML5, $output_charset ),
        // Aqui, `apply_filters('the_content', ...)` � usado para incluir o HTML formatado no webhook payload,
        // o que pode ser �til para o consumidor do webhook.
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

    // Prepara o payload (carga �til) do webhook que ser� enviado via POST.
    $payload = array(
        'event'               => $event_type,               // Tipo de evento (post_updated ou post_created).
        'post_id'             => $post_ID,                 // ID do post afetado.
        'post_title_simple'   => get_the_title( $post_ID ), // T�tulo simples do post.
        'permalink'           => get_permalink( $post_ID ),   // Permalink do post.
        'timestamp'           => current_time( 'mysql' ),     // Timestamp do evento no formato MySQL.
        'full_export_url'     => $authenticated_export_url, // URL para o cliente baixar todos os posts (com token).
        'changed_post_data'   => $post_data_full,           // Dados completos do post que foi alterado.
    );

    // Envia o webhook.
    sufficit_json_exporter_send_webhook( $payload );
}

// Remove o hook `save_post` (caso estivesse ativo de vers�es anteriores)
// para evitar conflitos ou disparos indesejados.
remove_action( 'save_post', 'sufficit_json_exporter_on_post_save', 99, 3 );
// Adiciona a fun��o ao hook `wp_after_insert_post`.
// Prioridade 10 (padr�o) e 4 argumentos (`$post_ID`, `$post`, `$update`, `$post_before`).
add_action( 'wp_after_insert_post', 'sufficit_json_exporter_on_post_save', 10, 4 );


/**
 * Aciona o webhook quando um post � exclu�do permanentemente.
 * A exclus�o de um post *sempre* implica em uma mudan�a no conjunto de dados de posts publicados,
 * ent�o o webhook deve ser disparado e o hash global atualizado.
 *
 * @param int $post_ID O ID do post que est� sendo exclu�do.
 */
function sufficit_json_exporter_on_post_delete( $post_ID ) {
    // Obt�m o objeto post antes que ele seja permanentemente exclu�do.
    $post = get_post( $post_ID );

    // Verifica se o post existe e se � um tipo de post que queremos monitorar.
    // Isso evita processar exclus�es de posts que n�o s�o 'post' ou que j� foram removidos.
    if ( ! $post || 'post' !== $post->post_type ) {
        error_log( 'DEBUG: suff_json_exporter: Ignorando exclusao de post nao monitorado ou inexistente para Post ID: ' . $post_ID );
        return;
    }

    error_log( 'DEBUG: suff_json_exporter: sufficit_json_exporter_on_post_delete acionada para Post ID: ' . $post_ID );


    // --- Rec�lculo do Hash do Conte�do Completo AP�S a exclus�o ---
    // � crucial recalcular o hash *sem* o post que acabou de ser exclu�do.
    $args_after_delete = array(
        'posts_per_page' => -1,          // Obter todos os posts.
        'post_type'      => 'post',     // Apenas posts padr�o.
        'post_status'    => 'publish',  // Apenas posts publicados.
        'orderby'        => array(       // Ordena��o consistente.
            'modified' => 'DESC',
            'ID'       => 'ASC',
        ),
        'post__not_in'   => array($post_ID) // EXCLUI o post que est� sendo deletado do c�lculo do hash.
    );
    $posts_after_delete_for_hash = get_posts( $args_after_delete );

    $hash_data_array_after_delete = array();
    $output_charset = get_option( SUFF_JSON_EXPORTER_CHARSET_OPTION, 'UTF-8' );

    foreach ( $posts_after_delete_for_hash as $p ) {
        // Processamento para o hash (t�tulo decodificado, conte�do sem tags, permalink).
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

    // Atualiza o hash salvo no DB com o novo hash que reflete a exclus�o do post.
    update_option( SUFF_JSON_EXPORTER_LAST_HASH_OPTION, $current_calculated_hash_after_delete );
    error_log( 'DEBUG: suff_json_exporter: Hash no DB atualizado apos exclusao para: ' . $current_calculated_hash_after_delete );


    // --- Dispara o Webhook para o Evento de Exclus�o ---
    $saved_token = get_option( SUFF_JSON_EXPORTER_TOKEN_OPTION );
    $base_export_url = get_rest_url() . 'sufficit-json-exporter/v1/posts/';
    $authenticated_export_url = '';
    if ( ! empty( $saved_token ) ) {
        $authenticated_export_url = add_query_arg( 'token', $saved_token, $base_export_url );
    }

    // Prepara o payload do webhook para o evento de remo��o.
    // Inclui informa��es b�sicas do post que foi exclu�do e a URL para a exporta��o completa.
    $payload = array(
        'event'               => 'post_deleted',          // Tipo de evento: post exclu�do.
        'post_id'             => $post_ID,                // ID do post que foi exclu�do.
        'post_title_simple'   => $post->post_title,       // T�tulo do post (dispon�vel do objeto $post antes da exclus�o).
        'timestamp'           => current_time( 'mysql' ), // Timestamp do evento.
        'full_export_url'     => $authenticated_export_url, // URL para o cliente obter o novo conjunto de posts.
        // `changed_post_data` n�o � inclu�do aqui, pois o post n�o existe mais no DB de posts 'publish'.
    );

    // Envia o webhook.
    sufficit_json_exporter_send_webhook( $payload );
    error_log( 'DEBUG: suff_json_exporter: Webhook disparado para exclusao do Post ID: ' . $post_ID );
}
// Hook 'before_delete_post' � acionado antes que um post seja exclu�do permanentemente do banco de dados.
add_action( 'before_delete_post', 'sufficit_json_exporter_on_post_delete' );
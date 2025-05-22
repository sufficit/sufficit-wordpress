<?php
/*
Plugin Name: Sufficit Json Exporter
Plugin URI:  https://github.com/sufficit/sufficit-wordpress/sufficit-json-exporter
Description: Um plugin simples para exportar todos os posts para um arquivo JSON atraves de um endpoint REST.
Version:     1.17 // Versao com comentarios detalhados
Author:      Hugo Castro de Deco
Author URI:  https://github.com/sufficit
License:     GPL2
License URI: https://www.gnu.nu/licenses/gpl-2.0.html
Text Domain: sufficit-json-exporter
Domain Path: /languages/
*/

// Evita acesso direto ao arquivo do plugin para aumentar a seguranca.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sai se o arquivo for acessado diretamente.
}

/**
 * [NOTA IMPORTANTE PARA O PROGRAMA PARCEIRO DE PROGRAMACAO]:
 * Ao gerar ou modificar textos e strings em PHP ou HTML, evite o uso de acentuacao
 * ou caracteres especiais (e.g., 'ç', 'ã', 'õ') diretamente no codigo para prevenir
 * problemas de codificacao em ambientes diversos. Use entidades HTML quando
 * estritamente necessario ou confie no Text Domain para traducao.
 * Manter o codigo limpo de caracteres complexos em hardcode ajuda na compatibilidade.
 */

// --- CONFIGURACOES E GARANTIA DE CODIFICACAO UTF-8 ---

// Tenta forcar a codificacao UTF-8 para funcoes multi-byte e para o ambiente PHP em geral.
// Isso e uma medida proativa para tentar garantir consistencia, mas o arquivo PHP em si
// DEVE ser salvo em UTF-8 SEM BOM para evitar problemas de caracteres.
if ( function_exists( 'mb_internal_encoding' ) ) {
    mb_internal_encoding( 'UTF-8' );
}
if ( function_exists( 'mb_regex_encoding' ) ) {
    mb_regex_encoding( 'UTF-8' );
}
ini_set( 'default_charset', 'UTF-8' );

// Carrega o textdomain para internacionalizacao (traducoes do plugin).
// Permite que o plugin seja traduzido para diferentes idiomas.
function sufficit_json_exporter_load_textdomain() {
    load_plugin_textdomain( 'sufficit-json-exporter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'sufficit_json_exporter_load_textdomain' );

/**
 * Constantes para os nomes das opcoes salvas no banco de dados do WordPress.
 * Isso ajuda a evitar erros de digitacao e torna o codigo mais legivel e manutenivel.
 */
define( 'SUFF_JSON_EXPORTER_TOKEN_OPTION', 'suff_json_exporter_auth_token' );
define( 'SUFF_JSON_EXPORTER_CHARSET_OPTION', 'suff_json_exporter_output_charset' );

// --- CONFIGURACAO DO ENDPOINT REST API ---

/**
 * Funcao para registrar um endpoint REST personalizado para a exportacao de posts.
 * Este endpoint sera acessivel publicamente, mas protegido por um token de autenticacao.
 */
function sufficit_json_exporter_registrar_endpoint() {
    register_rest_route(
        'sufficit-json-exporter/v1', // Namespace e versao da API (ex: wp-json/sufficit-json-exporter/v1).
        '/posts/',               // Rota do endpoint (ex: wp-json/sufficit-json-exporter/v1/posts).
        array(
            'methods'             => 'GET', // Define que o endpoint aceitara apenas requisicoes GET.
            'callback'            => 'sufficit_json_exporter_gerar_posts', // Funcao que sera executada quando o endpoint for acessado.
            'permission_callback' => 'sufficit_json_exporter_autenticar_requisitante', // Funcao para verificar a permissao/autenticacao.
            'args'                => array(), // Argumentos esperados (nenhum especifico para esta rota, o token sera tratado como um parametro GET).
        )
    );
}
add_action( 'rest_api_init', 'sufficit_json_exporter_registrar_endpoint' );

/**
 * Funcao de callback para autenticar a requisicao ao endpoint REST via token GET.
 * Garante que apenas requisicoes com o token correto possam acessar os dados.
 *
 * @param WP_REST_Request $request O objeto da requisicao REST atual, contem os parametros.
 * @return bool|WP_Error Retorna 'true' se o token for valido, ou um objeto WP_Error se invalido/nao configurado.
 */
function sufficit_json_exporter_autenticar_requisitante( WP_REST_Request $request ) {
    $token_param = $request->get_param( 'token' ); // Obtem o parametro 'token' da URL.
    $saved_token = get_option( SUFF_JSON_EXPORTER_TOKEN_OPTION ); // Obtem o token salvo nas configuracoes do plugin.

    // Verifica se um token foi configurado nas opcoes do plugin.
    if ( empty( $saved_token ) ) {
        return new WP_Error( 'suff_json_exporter_no_token_set', 'Token de autenticacao nao configurado no plugin.', array( 'status' => 401 ) );
    }

    // Compara o token fornecido na requisicao com o token salvo.
    if ( $token_param === $saved_token ) {
        return true; // Autenticacao bem-sucedida.
    }

    // Se os tokens nao corresponderem, retorna um erro.
    return new WP_Error( 'suff_json_exporter_invalid_token', 'Token de autenticacao invalido.', array( 'status' => 401 ) );
}

/**
 * Callback principal para o endpoint REST que gera a exportacao dos posts em JSON.
 * Consulta o banco de dados do WordPress e formata os dados dos posts em um array JSON.
 *
 * @return WP_REST_Response Os dados dos posts em formato JSON.
 */
function sufficit_json_exporter_gerar_posts() {
    // Define os argumentos para a consulta de posts.
    $args = array(
        'posts_per_page' => -1,          // Retorna todos os posts.
        'post_type'      => 'post',     // Apenas posts do tipo 'post'.
        'post_status'    => 'publish',  // Apenas posts publicados.
    );

    $posts = get_posts( $args ); // Executa a consulta de posts.

    $posts_data = array(); // Array para armazenar os dados formatados de cada post.

    // Obtem a codificacao de saida JSON configurada pelo usuario (padrao: UTF-8).
    $output_charset = get_option( SUFF_JSON_EXPORTER_CHARSET_OPTION, 'UTF-8' );

    // Itera sobre cada post retornado para formatar os dados.
    foreach ( $posts as $post ) {
        // Obtem os dados brutos do post.
        $post_title = get_the_title( $post->ID );
        $post_content = $post->post_content;
        $post_excerpt = $post->post_excerpt;

        // Processa o titulo para decodificar entidades HTML usando o charset selecionado.
        // Isso e crucial para garantir que caracteres especiais (e.g., &eacute; se tornem é)
        // sejam exibidos corretamente no JSON de acordo com a codificacao escolhida.
        $processed_title = html_entity_decode( $post_title, ENT_QUOTES | ENT_HTML5, $output_charset );
        // Aplica filtros padrao do WordPress ao conteudo e resumo para processamento de shortcodes, etc.
        $processed_content = apply_filters( 'the_content', $post_content );
        $processed_excerpt = apply_filters( 'the_excerpt', $post_excerpt );

        // Adiciona os dados do post formatados ao array de dados de posts.
        $posts_data[] = array(
            'ID'            => $post->ID,
            'title'         => $processed_title,
            'content'       => $processed_content,
            'excerpt'       => $processed_excerpt,
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
    }

    // Retorna os dados como uma resposta RESTful com status 200 (OK).
    return new WP_REST_Response( $posts_data, 200 );
}

// --- FUNCOES PARA A PAGINA DE CONFIGURACOES DO PLUGIN ---

/**
 * Adiciona a pagina de configuracoes do plugin ao menu "Configuracoes" do WordPress Admin.
 */
function sufficit_json_exporter_adicionar_pagina_admin() {
    add_options_page(
        'Sufficit JSON Exporter Configuracoes', // Titulo da pagina.
        'JSON Exporter',                     // Titulo no menu.
        'manage_options',                    // Capacidade minima necessaria para acessar a pagina.
        'sufficit-json-exporter',            // Slug unico da pagina.
        'sufficit_json_exporter_pagina_config' // Funcao de callback que renderiza o conteudo da pagina.
    );
}
add_action( 'admin_menu', 'sufficit_json_exporter_adicionar_pagina_admin' );

/**
 * Adiciona um link direto para a pagina de "Configuracoes" na lista de plugins instalados.
 * Isso melhora a usabilidade, permitindo acesso rapido as configuracoes.
 *
 * @param array $links Array de links de acao para o plugin.
 * @return array O array de links modificado.
 */
function sufficit_json_exporter_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=sufficit-json-exporter">' . 'Configuracoes' . '</a>';
    array_unshift( $links, $settings_link ); // Adiciona o link no inicio do array.
    return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'sufficit_json_exporter_add_settings_link' );


/**
 * Registra as configuracoes do plugin na API de Settings do WordPress.
 * Embora o HTML seja impresso diretamente na pagina_config (solucao para um problema de renderizacao anterior),
 * o registro com register_setting() ainda e essencial para que o WordPress
 * processe e salve os valores dos campos no banco de dados.
 */
function sufficit_json_exporter_registrar_settings() {
    // Registra a opcao do token de autenticacao.
    register_setting(
        'suff_json_exporter_settings_group', // Grupo de configuracoes (usado em settings_fields()).
        SUFF_JSON_EXPORTER_TOKEN_OPTION,     // Nome da opcao no banco de dados.
        array(
            'type'              => 'string',            // Tipo de dado esperado.
            'sanitize_callback' => 'sanitize_text_field', // Funcao para limpar e validar o input.
            'default'           => '',                  // Valor padrao.
            'show_in_rest'      => false,               // Nao expor via REST API.
        )
    );

    // Registra a opcao de codificacao de saida JSON.
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

    // As sections e fields tradicionalmente usadas com a Settings API para RENDERIZAR o HTML
    // NAO serao usadas para essa finalidade nesta versao do plugin.
    // Elas sao mantidas aqui apenas para o registro logico das opcoes com o WordPress.
    add_settings_section(
        'suff_json_exporter_general_section', // ID unico da secao.
        'Configuracoes de Autenticacao e Codificacao', // Titulo da secao.
        null, // No callback function for section description (como o HTML e direto, nao ha funcao para isso).
        'sufficit-json-exporter' // Slug da pagina onde esta secao aparece.
    );
}
add_action( 'admin_init', 'sufficit_json_exporter_registrar_settings' );


/**
 * Funcao de callback principal para renderizar o conteudo da pagina de configuracoes do plugin.
 *
 * NOTA DE DEBUGGING / SOLUCAO PARA PROBLEMA DE RENDERIZACAO:
 * Originalmente, este plugin usava a Settings API completa (add_settings_field) para renderizar os campos.
 * No entanto, devido a um comportamento inesperado em alguns ambientes WordPress (possivelmente devido a
 * interacoes com outros plugins ou configuracoes especificas), os campos nao estavam sendo exibidos.
 *
 * A SOLUCAO implementada foi imprimir o HTML dos campos (input e select) DIRETAMENTE
 * dentro desta funcao. Isso contorna o problema de renderizacao da Settings API,
 * garantindo que os campos aparecam. O registro com register_setting() ainda e necessario
 * para que o WordPress salve os valores dos campos ao submeter o formulario.
 */
function sufficit_json_exporter_pagina_config() {
    // Define o cabecalho HTTP para garantir que a pagina seja servida como UTF-8.
    if ( ! headers_sent() ) {
        header( 'Content-Type: text/html; charset=UTF-8' );
    }

    // Obtem os valores atuais das opcoes salvas no banco de dados.
    $token = get_option( SUFF_JSON_EXPORTER_TOKEN_OPTION );
    $current_charset = get_option( SUFF_JSON_EXPORTER_CHARSET_OPTION, 'UTF-8' );
    // Constroi a URL base do endpoint REST do WordPress.
    $base_endpoint_url = get_rest_url() . 'sufficit-json-exporter/v1/posts/';
    // Constroi um exemplo de URL completa com o token atual, para exibicao.
    $full_url_example = esc_url( $base_endpoint_url . '?token=' . $token );

    // Array de opcoes de codificacao para o campo <select>.
    $charsets = array(
        'UTF-8'         => 'UTF-8 (Recomendado para JSON)',
        'ISO-8859-1'    => 'ISO-8859-1 (Latin-1 - Padrao da sua instalacao do WP se nao for UTF-8)',
        'Windows-1252'  => 'Windows-1252 (ANSI - Comum em sistemas Windows legados)',
    );
    // Adiciona o charset padrao do WordPress se for diferente de UTF-8 e nao estiver na lista.
    $wp_charset = get_bloginfo( 'charset' );
    if ( ! array_key_exists( $wp_charset, $charsets ) && $wp_charset !== 'UTF-8' ) {
        $charsets[$wp_charset] = sprintf( 'Padrao do WordPress (%s)', $wp_charset );
    }
    // Ordena as opcoes de charset, colocando UTF-8 e o charset atual no topo para facil acesso.
    uksort($charsets, function($a, $b) use ($current_charset) {
        if ($a === 'UTF-8') return -1; // UTF-8 primeiro
        if ($b === 'UTF-8') return 1;
        if ($a === $current_charset) return -1; // Charset atual em seguida
        if ($b === $current_charset) return 1;
        return 0; // Ordem alfabetica para os demais
    });

    ?>
    <div class="wrap">
        <h1>Configuracoes do Sufficit JSON Exporter</h1>
        <form method="post" action="options.php">
            <?php
            // Funcao essencial do WordPress que imprime os campos hidden de seguranca (nonce, etc.)
            // e o nome do grupo de configuracoes. Necessario para que o formulario seja salvo.
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
                </tbody>
            </table>

            <?php
            // Botao padrao de submissao do WordPress.
            submit_button( 'Salvar alteracoes' );
            ?>
        </form>
    </div>
    <?php
}

/**
 * Garante que a pagina de plugins do WordPress tambem use o charset UTF-8 nos cabecalhos HTTP.
 * Isso ajuda a prevenir problemas de codificacao em ambientes que nao estao configurados para UTF-8 por padrao.
 */
add_action('admin_head-plugins.php', 'sufficit_json_exporter_set_plugins_page_charset');
function sufficit_json_exporter_set_plugins_page_charset() {
    if ( ! headers_sent() ) {
        header( 'Content-Type: text/html; charset=UTF-8' );
    }
}

/**
 * Enfileira o script JavaScript personalizado para a pagina de configuracoes do plugin.
 * O script eh carregado apenas na pagina do plugin para otimizacao de performance.
 */
function sufficit_json_exporter_enqueue_admin_scripts( $hook_suffix ) {
    // Verifica se estamos na pagina de configuracoes do nosso plugin.
    if ( 'settings_page_sufficit-json-exporter' === $hook_suffix ) {
        wp_enqueue_script(
            'suff-json-exporter-admin-script',      // Handle unico para o script.
            plugin_dir_url( __FILE__ ) . 'admin-script.js', // URL completa para o arquivo JS.
            array( 'jquery' ),                      // Dependencias (garante que jQuery seja carregado antes).
            '1.0',                                  // Versao do script (para cache busting).
            true                                    // Carrega o script no footer.
        );

        // Passa dados do PHP para o JavaScript.
        // Aqui, a URL base do endpoint REST e passada para que o JavaScript possa construir a URL completa.
        wp_localize_script(
            'suff-json-exporter-admin-script', // Handle do script para o qual os dados serao localizados.
            'suffExporterData',                // Nome do objeto JS que contera os dados (e.g., suffExporterData.baseUrl).
            array(
                'baseUrl' => get_rest_url() . 'sufficit-json-exporter/v1/posts/',
            )
        );
    }
}
add_action( 'admin_enqueue_scripts', 'sufficit_json_exporter_enqueue_admin_scripts' );
**Nome do Plugin:** Sufficit JSON Exporter
**Vers�o Atual:** 1.3.6

**Observa��o para o Modelo (Gemini):**
Este prompt deve ser atualizado e expandido *automaticamente* por voc� sempre que novas funcionalidades forem adicionadas ao plugin e confirmadas como implementadas com sucesso pelo usu�rio. Mantenha-o sempre como a descri��o mais completa e atualizada dos requisitos do plugin.

**Objetivo Principal:**
Desenvolver um plugin WordPress que exporta posts (e seus dados relevantes) para um endpoint REST personalizado em formato JSON, com um mecanismo de controle de vers�o (hash de conte�do) e disparo de webhook para notificar sistemas externos sobre altera��es.

**Estrutura de Arquivos Necess�ria:**
1.  `sufficit-json-exporter.php` (Arquivo principal do plugin)
2.  `admin-script.js` (Script JavaScript para a p�gina de administra��o)
3.  `languages/` (Diret�rio para arquivos de tradu��o .mo/.po, embora n�o sejam o foco principal, devem ser previstos)

**Funcionalidades Detalhadas:**

**1. Endpoint REST API para Exporta��o de Posts:**
    * **Namespace:** `sufficit-json-exporter/v1`
    * **Rota:** `/posts/` (resultando em `/wp-json/sufficit-json-exporter/v1/posts/`)
    * **M�todo:** `GET`
    * **Autentica��o:**
        * Requer um `token` como par�metro GET (ex: `?token=SEUTOKEN`).
        * O token deve ser configur�vel na p�gina de administra��o do plugin e salvo no banco de dados.
        * Se o token for inv�lido ou n�o configurado, a API deve retornar um erro HTTP 401.
    * **Conte�do da Resposta JSON:**
        * Um array de objetos JSON, onde cada objeto representa um post.
        * Incluir os seguintes campos para cada post publicado (tipo 'post'):
            * `ID`
            * `title` (t�tulo decodificado de entidades HTML)
            * `content` (conte�do com filtros `the_content` aplicados para incluir HTML formatado)
            * `excerpt` (resumo com filtros `the_excerpt` aplicados)
            * `date_published`
            * `date_modified`
            * `slug`
            * `permalink`
            * `author_id`
            * `author_name` (display name do autor)
            * `thumbnail_url` (URL da imagem destacada em tamanho 'full', se existir)
            * `categories` (array de nomes das categorias)
            * `tags` (array de nomes das tags)
        * **Ordena��o:** Posts devem ser ordenados por `modified` (descendente) e, em seguida, por `ID` (ascendente) para garantir consist�ncia na exporta��o e no c�lculo do hash.
    * **Cabe�alho HTTP `X-Content-Hash`:**
        * A resposta REST DEVE incluir um cabe�alho HTTP `X-Content-Hash`.
        * Este hash (SHA256) deve ser calculado com base em uma string JSON que representa *apenas* o `title` (decodificado), `content` (strip_all_tags) e `permalink` de *todos os posts publicados*.
        * O c�lculo do hash deve ser consistente: usar `wp_json_encode` com `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`.
        * Este hash deve ser salvo no banco de dados (`suff_json_exporter_last_content_hash`) a cada vez que o endpoint � acessado.

**2. P�gina de Configura��es no WordPress Admin:**
    * **Localiza��o:** "Configura��es" -> "JSON Exporter"
    * **Campos Configur�veis:**
        * **Token de Autentica��o:** Campo de texto para inserir o token.
        * **Codifica��o de Sa�da JSON:** Campo `<select>` com op��es como UTF-8, ISO-8859-1, Windows-1252. O padr�o deve ser UTF-8. O charset padr�o do WordPress tamb�m deve ser uma op��o se for diferente de UTF-8.
        * **URL do Webhook de Altera��o de Post:** Campo de URL para o webhook.
        * **Habilitar Webhook:** Checkbox para ativar/desativar o disparo de webhooks.
    * **Informa��es Exibidas:**
        * URL completa de exemplo do endpoint REST (com o token atual).
        * O "�ltimo Hash de Conte�do Exportado" salvo no banco de dados.
    * **Usabilidade:** Um link "Configura��es" deve ser adicionado � lista de plugins na tela de "Plugins".

**3. Disparo de Webhooks em Altera��es de Post:**
    * **Gatilhos:**
        * `wp_after_insert_post` (para cria��o e atualiza��o de posts)
        * `before_delete_post` (para exclus�o de posts)
    * **Condi��es para Disparo (para `wp_after_insert_post`):**
        * N�O deve ser uma revis�o de post (`wp_is_post_revision`).
        * N�O deve ser um autosave de post (`wp_is_post_autosave`).
        * O tipo de post deve ser `post` (padr�o do WordPress).
        * O status do post deve ser `publish` ou `future`.
        * **Mecanismo de "Lock" (Rate Limiting):**
            * Usar a Transients API (`get_transient` e `set_transient`) para implementar um bloqueio tempor�rio (e.g., 10 segundos) por `post_ID`.
            * Isso evita m�ltiplos disparos de webhook para a mesma opera��o de "salvar" (que pode acionar o hook v�rias vezes).
            * Se o lock estiver ativo para um `post_ID`, a execu��o da fun��o deve ser ignorada.
            * O transient deve ser usado para garantir que o webhook para um `post_ID` s� seja disparado uma vez dentro do per�odo de bloqueio, mesmo que o `wp_after_insert_post` seja chamado v�rias vezes em uma �nica requisi��o.
        * **Verifica��o de Hash:**
            * Ap�s todas as verifica��es acima (e se o lock n�o estiver ativo), o plugin deve recalcular o hash de *todo o conte�do export�vel* (conforme definido na se��o do endpoint REST).
            * Comparar este `current_calculated_hash` com o `last_saved_hash` (salvo no banco de dados pelo endpoint REST ou por uma chamada anterior deste hook).
            * O webhook S� deve ser disparado se `current_calculated_hash` for DIFERENTE de `last_saved_hash`.
            * Ap�s o disparo (se houve mudan�a), o `last_saved_hash` no banco de dados DEVE ser atualizado com o `current_calculated_hash`.
    * **Condi��es para Disparo (para `before_delete_post`):**
        * O tipo de post deve ser `post`.
        * Recalcular e atualizar o hash global (excluindo o post deletado) no banco de dados.
        * Sempre disparar o webhook, pois a exclus�o � uma mudan�a definitiva no conjunto de dados.
    * **Conte�do do Webhook Payload (Requisi��o POST):**
        * Formato JSON.
        * `event` (string: `post_created`, `post_updated` ou `post_deleted`)
        * `post_id` (ID do post afetado)
        * `post_title_simple` (t�tulo simples do post afetado)
        * `permalink` (permalink do post afetado)
        * `timestamp` (hora exata do evento no formato MySQL)
        * `full_export_url` (URL autenticada para baixar todos os posts, para refer�ncia)
        * `changed_post_data` (OBJETO JSON contendo os dados completos do post que foi alterado/criado, similar � sa�da do endpoint REST para um �nico post; para `post_deleted`, este campo n�o � necess�rio ou pode ser nulo).
    * **Comportamento do Envio:**
        * Deve ser ass�ncrono (`'blocking' => false` em `wp_remote_post`) para n�o atrasar o processo de salvamento do WordPress.
        * Implementar registro de erros (via `error_log`) para depura��o.

**4. Compatibilidade e Boas Pr�ticas:**
    * **Codifica��o de Arquivo:** O arquivo PHP principal (`sufficit-json-exporter.php`) DEVE ser salvo em `UTF-8 SEM BOM`.
    * **Coment�rios:** C�digo amplamente comentado para clareza sobre a l�gica, decis�es e prop�sitos de cada se��o/fun��o.
    * **Seguran�a:** Preven��o de acesso direto ao arquivo do plugin (`if ( ! defined( 'ABSPATH' ) ) { exit; }`).
    * **Sanitiza��o/Valida��o:** Uso de fun��es WordPress como `sanitize_text_field`, `esc_url_raw`, `rest_sanitize_boolean` para inputs de usu�rio.
    * **Internacionaliza��o:** Preparado para tradu��o (uso de `load_plugin_textdomain` e `__()`/`_e()` - embora n�o implementado com as strings de UI para simplificar o prompt, a estrutura est� l�).
    * **Debugging:** Uso extensivo de `error_log()` para rastrear a execu��o da l�gica de hash, transients e webhooks.

**Instru��es de Implementa��o/Teste:**
1.  **Requisito de WP:** WordPress 5.6 ou superior (para `wp_after_insert_post`).
2.  **Upload:** Colocar o arquivo `sufficit-json-exporter.php` e `admin-script.js` na pasta de um plugin.
3.  **Ativar:** Ativar o plugin no painel do WordPress.
4.  **Configurar:** Acessar "Configura��es" -> "JSON Exporter" e configurar o Token e a URL do Webhook. Salvar.
5.  **Inicializar Hash:** Acessar manualmente a URL do endpoint REST com o token (ex: `seusite.com/wp-json/sufficit-json-exporter/v1/posts/?token=SEUTOKEN`) para que o hash inicial seja salvo.
6.  **Limpeza de Cache:** Limpar TODOS os caches (plugins de cache, CDN, servidor, navegador) antes dos testes.
7.  **Testar Cen�rios:**
    * Editar um post existente e **mudar o conte�do** (verificar disparo �nico do webhook).
    * Editar um post existente e **n�o mudar o conte�do** (verificar que o webhook N�O dispara).
    * Criar um novo post (verificar disparo �nico do webhook).
    * Excluir um post (verificar disparo �nico do webhook de exclus�o).
8.  **Monitorar:** Acompanhar o `debug.log` do WordPress para verificar a sa�da das mensagens de depura��o, especialmente as relacionadas aos hashes e ao lock de transients.
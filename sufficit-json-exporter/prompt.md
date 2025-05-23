**Nome do Plugin:** Sufficit JSON Exporter
**Versão Atual:** 1.3.6

**Observação para o Modelo (Gemini):**
Este prompt deve ser atualizado e expandido *automaticamente* por você sempre que novas funcionalidades forem adicionadas ao plugin e confirmadas como implementadas com sucesso pelo usuário. Mantenha-o sempre como a descrição mais completa e atualizada dos requisitos do plugin.

**Objetivo Principal:**
Desenvolver um plugin WordPress que exporta posts (e seus dados relevantes) para um endpoint REST personalizado em formato JSON, com um mecanismo de controle de versão (hash de conteúdo) e disparo de webhook para notificar sistemas externos sobre alterações.

**Estrutura de Arquivos Necessária:**
1.  `sufficit-json-exporter.php` (Arquivo principal do plugin)
2.  `admin-script.js` (Script JavaScript para a página de administração)
3.  `languages/` (Diretório para arquivos de tradução .mo/.po, embora não sejam o foco principal, devem ser previstos)

**Funcionalidades Detalhadas:**

**1. Endpoint REST API para Exportação de Posts:**
    * **Namespace:** `sufficit-json-exporter/v1`
    * **Rota:** `/posts/` (resultando em `/wp-json/sufficit-json-exporter/v1/posts/`)
    * **Método:** `GET`
    * **Autenticação:**
        * Requer um `token` como parâmetro GET (ex: `?token=SEUTOKEN`).
        * O token deve ser configurável na página de administração do plugin e salvo no banco de dados.
        * Se o token for inválido ou não configurado, a API deve retornar um erro HTTP 401.
    * **Conteúdo da Resposta JSON:**
        * Um array de objetos JSON, onde cada objeto representa um post.
        * Incluir os seguintes campos para cada post publicado (tipo 'post'):
            * `ID`
            * `title` (título decodificado de entidades HTML)
            * `content` (conteúdo com filtros `the_content` aplicados para incluir HTML formatado)
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
        * **Ordenação:** Posts devem ser ordenados por `modified` (descendente) e, em seguida, por `ID` (ascendente) para garantir consistência na exportação e no cálculo do hash.
    * **Cabeçalho HTTP `X-Content-Hash`:**
        * A resposta REST DEVE incluir um cabeçalho HTTP `X-Content-Hash`.
        * Este hash (SHA256) deve ser calculado com base em uma string JSON que representa *apenas* o `title` (decodificado), `content` (strip_all_tags) e `permalink` de *todos os posts publicados*.
        * O cálculo do hash deve ser consistente: usar `wp_json_encode` com `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`.
        * Este hash deve ser salvo no banco de dados (`suff_json_exporter_last_content_hash`) a cada vez que o endpoint é acessado.

**2. Página de Configurações no WordPress Admin:**
    * **Localização:** "Configurações" -> "JSON Exporter"
    * **Campos Configuráveis:**
        * **Token de Autenticação:** Campo de texto para inserir o token.
        * **Codificação de Saída JSON:** Campo `<select>` com opções como UTF-8, ISO-8859-1, Windows-1252. O padrão deve ser UTF-8. O charset padrão do WordPress também deve ser uma opção se for diferente de UTF-8.
        * **URL do Webhook de Alteração de Post:** Campo de URL para o webhook.
        * **Habilitar Webhook:** Checkbox para ativar/desativar o disparo de webhooks.
    * **Informações Exibidas:**
        * URL completa de exemplo do endpoint REST (com o token atual).
        * O "Último Hash de Conteúdo Exportado" salvo no banco de dados.
    * **Usabilidade:** Um link "Configurações" deve ser adicionado à lista de plugins na tela de "Plugins".

**3. Disparo de Webhooks em Alterações de Post:**
    * **Gatilhos:**
        * `wp_after_insert_post` (para criação e atualização de posts)
        * `before_delete_post` (para exclusão de posts)
    * **Condições para Disparo (para `wp_after_insert_post`):**
        * NÃO deve ser uma revisão de post (`wp_is_post_revision`).
        * NÃO deve ser um autosave de post (`wp_is_post_autosave`).
        * O tipo de post deve ser `post` (padrão do WordPress).
        * O status do post deve ser `publish` ou `future`.
        * **Mecanismo de "Lock" (Rate Limiting):**
            * Usar a Transients API (`get_transient` e `set_transient`) para implementar um bloqueio temporário (e.g., 10 segundos) por `post_ID`.
            * Isso evita múltiplos disparos de webhook para a mesma operação de "salvar" (que pode acionar o hook várias vezes).
            * Se o lock estiver ativo para um `post_ID`, a execução da função deve ser ignorada.
            * O transient deve ser usado para garantir que o webhook para um `post_ID` só seja disparado uma vez dentro do período de bloqueio, mesmo que o `wp_after_insert_post` seja chamado várias vezes em uma única requisição.
        * **Verificação de Hash:**
            * Após todas as verificações acima (e se o lock não estiver ativo), o plugin deve recalcular o hash de *todo o conteúdo exportável* (conforme definido na seção do endpoint REST).
            * Comparar este `current_calculated_hash` com o `last_saved_hash` (salvo no banco de dados pelo endpoint REST ou por uma chamada anterior deste hook).
            * O webhook SÓ deve ser disparado se `current_calculated_hash` for DIFERENTE de `last_saved_hash`.
            * Após o disparo (se houve mudança), o `last_saved_hash` no banco de dados DEVE ser atualizado com o `current_calculated_hash`.
    * **Condições para Disparo (para `before_delete_post`):**
        * O tipo de post deve ser `post`.
        * Recalcular e atualizar o hash global (excluindo o post deletado) no banco de dados.
        * Sempre disparar o webhook, pois a exclusão é uma mudança definitiva no conjunto de dados.
    * **Conteúdo do Webhook Payload (Requisição POST):**
        * Formato JSON.
        * `event` (string: `post_created`, `post_updated` ou `post_deleted`)
        * `post_id` (ID do post afetado)
        * `post_title_simple` (título simples do post afetado)
        * `permalink` (permalink do post afetado)
        * `timestamp` (hora exata do evento no formato MySQL)
        * `full_export_url` (URL autenticada para baixar todos os posts, para referência)
        * `changed_post_data` (OBJETO JSON contendo os dados completos do post que foi alterado/criado, similar à saída do endpoint REST para um único post; para `post_deleted`, este campo não é necessário ou pode ser nulo).
    * **Comportamento do Envio:**
        * Deve ser assíncrono (`'blocking' => false` em `wp_remote_post`) para não atrasar o processo de salvamento do WordPress.
        * Implementar registro de erros (via `error_log`) para depuração.

**4. Compatibilidade e Boas Práticas:**
    * **Codificação de Arquivo:** O arquivo PHP principal (`sufficit-json-exporter.php`) DEVE ser salvo em `UTF-8 SEM BOM`.
    * **Comentários:** Código amplamente comentado para clareza sobre a lógica, decisões e propósitos de cada seção/função.
    * **Segurança:** Prevenção de acesso direto ao arquivo do plugin (`if ( ! defined( 'ABSPATH' ) ) { exit; }`).
    * **Sanitização/Validação:** Uso de funções WordPress como `sanitize_text_field`, `esc_url_raw`, `rest_sanitize_boolean` para inputs de usuário.
    * **Internacionalização:** Preparado para tradução (uso de `load_plugin_textdomain` e `__()`/`_e()` - embora não implementado com as strings de UI para simplificar o prompt, a estrutura está lá).
    * **Debugging:** Uso extensivo de `error_log()` para rastrear a execução da lógica de hash, transients e webhooks.

**Instruções de Implementação/Teste:**
1.  **Requisito de WP:** WordPress 5.6 ou superior (para `wp_after_insert_post`).
2.  **Upload:** Colocar o arquivo `sufficit-json-exporter.php` e `admin-script.js` na pasta de um plugin.
3.  **Ativar:** Ativar o plugin no painel do WordPress.
4.  **Configurar:** Acessar "Configurações" -> "JSON Exporter" e configurar o Token e a URL do Webhook. Salvar.
5.  **Inicializar Hash:** Acessar manualmente a URL do endpoint REST com o token (ex: `seusite.com/wp-json/sufficit-json-exporter/v1/posts/?token=SEUTOKEN`) para que o hash inicial seja salvo.
6.  **Limpeza de Cache:** Limpar TODOS os caches (plugins de cache, CDN, servidor, navegador) antes dos testes.
7.  **Testar Cenários:**
    * Editar um post existente e **mudar o conteúdo** (verificar disparo único do webhook).
    * Editar um post existente e **não mudar o conteúdo** (verificar que o webhook NÃO dispara).
    * Criar um novo post (verificar disparo único do webhook).
    * Excluir um post (verificar disparo único do webhook de exclusão).
8.  **Monitorar:** Acompanhar o `debug.log` do WordPress para verificar a saída das mensagens de depuração, especialmente as relacionadas aos hashes e ao lock de transients.
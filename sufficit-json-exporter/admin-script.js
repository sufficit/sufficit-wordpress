jQuery(document).ready(function($) {
    var $tokenField = $('#suff-json-exporter-token-field');
    var $endpointUrl = $('#suff-json-exporter-endpoint-url');
    var $copyButton = $('#suff-json-exporter-copy-url'); // Novo elemento
    var $copyMessage = $('#suff-json-exporter-copy-message'); // Novo elemento

    // Verifica se suffExporterData esta disponivel
    if (typeof suffExporterData !== 'undefined' && suffExporterData.baseUrl) {
        var baseUrl = suffExporterData.baseUrl;
    } else {
        // Fallback caso baseUrl nao esteja definido
        var baseUrl = 'NAO_DEFINIDO_VERIFICAR_WP_LOCALIZE_SCRIPT/';
    }

    /**
     * Funcao para atualizar a URL do endpoint exibida na pagina.
     */
    function updateEndpointUrl() {
        var token = $tokenField.val();
        var fullUrl = baseUrl + '?token=' + encodeURIComponent(token);
        if ($endpointUrl.length > 0) {
            $endpointUrl.text(fullUrl);
        }
    }

    // Vincula a funcao updateEndpointUrl ao evento 'input' do campo do token.
    if ($tokenField.length > 0) {
        $tokenField.on('input', updateEndpointUrl);
    }

    // Chama a funcao uma vez ao carregar a pagina para exibir a URL correta com o token ja salvo.
    updateEndpointUrl();

    // Lógica para o botão de copiar
    if ($copyButton.length > 0) { // Verifica se o botão existe no HTML
        $copyButton.on('click', function() {
            var urlToCopy = $endpointUrl.text();

            var $tempInput = $('<input>');
            $('body').append($tempInput);
            $tempInput.val(urlToCopy).select();

            try {
                document.execCommand('copy');
                if ($copyMessage.length > 0) {
                    $copyMessage.fadeIn().delay(2000).fadeOut(); // Exibe "Copiado!" por 2 segundos
                }
            } catch (err) {
                console.error('Falha ao copiar o texto: ', err);
                // Pode adicionar um alert ou outra mensagem de erro aqui, se desejar
            } finally {
                $tempInput.remove(); // Remove o input temporário
            }
        });
    }
});
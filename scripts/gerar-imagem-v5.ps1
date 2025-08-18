param(
    [Parameter(Mandatory=$true)]
    [string]$Prompt
)

try {
    # Carrega configurações
    $config = Get-Content -Raw -Path "c:\Desenvolvimento\wordpress\config-openai.json" | ConvertFrom-Json
    
    # Normaliza o prompt para remover caracteres especiais
    $normalizedPrompt = $Prompt.Normalize([Text.NormalizationForm]::FormD)
    $normalizedPrompt = [Text.RegularExpressions.Regex]::Replace($normalizedPrompt, "[^a-zA-Z0-9\s.,]", "")
    
    # Define a URL da API e os cabeçalhos
    $apiUrl = "https://api.openai.com/v1/images/generations"
    $headers = @{
        "Authorization" = "Bearer $($config.token)"
        "OpenAI-Organization" = $config.organization
        "Content-Type" = "application/json"
    }
    
    # Define o corpo da requisição
    $body = @{
        model = "dall-e-3"
        prompt = $normalizedPrompt
        n = 1
        size = "1024x1024"
    } | ConvertTo-Json
    
    Write-Host "Enviando prompt para a API DALL-E: '$normalizedPrompt'"
    
    # Faz a requisição usando Invoke-WebRequest
    $response = Invoke-WebRequest -Uri $apiUrl -Headers $headers -Method Post -Body $body -UseBasicParsing
    
    # Converte a resposta em objeto
    $result = $response.Content | ConvertFrom-Json
    
    # Extrai e exibe a URL da imagem
    $imageUrl = $result.data[0].url
    Write-Host "Imagem gerada com sucesso!"
    Write-Host "URL: $imageUrl"
    
    # Define o caminho de saída
    $outputPath = "c:\Desenvolvimento\wordpress\postagens\imagens\telefone-voip-2025.png"
    
    # Baixa a imagem
    & .\baixar-imagem.ps1 -ImageUrl $imageUrl -OutputPath $outputPath
    
} catch {
    Write-Error "Ocorreu um erro: $_"
    if ($_.ErrorDetails.Message) {
        Write-Error "Detalhes do erro da API: $($_.ErrorDetails.Message)"
    }
}

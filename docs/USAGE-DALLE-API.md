# Guia de Uso - API DALL-E OpenAI

## 🎨 Como Gerar Imagens usando a API DALL-E

### Configuração

As credenciais da OpenAI devem estar no arquivo `config/config-openai.json`:

```json
{
    "organization": "org-xxxxxxxxxxxxxxxxxxxx",
    "token": "sk-xxxxxxxxxxxxxxxxxxxx"
}
```

### Script Python para Gerar Imagens

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json
import requests
from pathlib import Path

def gerar_imagem_dalle(prompt, tamanho="1024x1024"):
    """
    Gera imagem usando DALL-E 3
    
    Args:
        prompt (str): Descrição da imagem
        tamanho (str): Tamanho da imagem (1024x1024, 1152x866, etc.)
    
    Returns:
        str: URL da imagem gerada
    """
    try:
        # Carrega configuração
        config_path = Path("./config/config-openai.json")
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        # URL da API
        api_url = "https://api.openai.com/v1/images/generations"
        
        # Headers
        headers = {
            "Authorization": f"Bearer {config['token']}",
            "OpenAI-Organization": config['organization'],
            "Content-Type": "application/json"
        }
        
        # Dados da requisição
        data = {
            "model": "dall-e-3",
            "prompt": prompt,
            "n": 1,
            "size": tamanho
        }
        
        print(f"🎨 Gerando imagem: '{prompt}'")
        
        # Requisição
        response = requests.post(api_url, headers=headers, json=data)
        
        if response.status_code == 200:
            result = response.json()
            image_url = result['data'][0]['url']
            print(f"✅ Imagem gerada com sucesso!")
            print(f"🔗 URL: {image_url}")
            return image_url
        else:
            print(f"❌ Erro: {response.status_code}")
            print(response.text)
            return None
            
    except Exception as e:
        print(f"❌ Erro: {e}")
        return None

if __name__ == "__main__":
    # Exemplo de uso
    url = gerar_imagem_dalle("Um telefone VoIP moderno em escritório corporativo")
    if url:
        print(f"Imagem disponível em: {url}")
```

### Script PowerShell Completo

```powershell
param(
    [Parameter(Mandatory=$true)]
    [string]$Prompt,
    [string]$Size = "1024x1024"
)

try {
    # Carrega configurações
    $config = Get-Content -Raw -Path "c:\Desenvolvimento\wordpress\config\config-openai.json" | ConvertFrom-Json
    
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
        size = $Size
    }
    
    Write-Host "🎨 Enviando prompt para a API DALL-E: '$normalizedPrompt'"
    
    # Faz a requisição usando Invoke-WebRequest
    $response = Invoke-WebRequest -Uri $apiUrl -Headers $headers -Method Post -Body ($body | ConvertTo-Json) -UseBasicParsing
    
    # Converte a resposta em objeto
    $result = $response.Content | ConvertFrom-Json
    
    # Extrai e exibe a URL da imagem
    $imageUrl = $result.data[0].url
    Write-Host "✅ Imagem gerada com sucesso!"
    Write-Host "🔗 URL: $imageUrl"
    
} catch {
    Write-Error "❌ Ocorreu um erro: $_"
    if ($_.ErrorDetails.Message) {
        Write-Error "Detalhes do erro da API: $($_.ErrorDetails.Message)"
    }
}
```

### Uso do Script

```powershell
# Gerar imagem padrão
.\gerar-imagem.ps1 -Prompt "Descrição da imagem que você quer gerar"

# Gerar imagem com tamanho específico
.\gerar-imagem.ps1 -Prompt "Telefone VoIP corporativo" -Size "1152x866"
```

## 📚 Informações da API

- **Endpoint**: `https://api.openai.com/v1/images/generations`
- **Modelo**: dall-e-3
- **Tamanhos suportados**: 1024x1024, 1152x864, 1792x1024
- **Rate limits**: Respeitar limites da API
- **Formato de saída**: PNG/WebP
- **Qualidade**: Alta resolução automática

## 🏗️ Estrutura de Arquivos

- Imagens são salvas em `imagens/`
- Use nomes descritivos para os arquivos
- Padrão: `post-{id}-{descricao}.png`
- Capas: `post-{id}-featured.png` (1152x866)
